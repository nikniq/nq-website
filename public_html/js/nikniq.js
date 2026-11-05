/*!
 * NikniQ Games SDK: sign players in with their NikniQ account and report scores.
 * No dependencies. Works in any modern browser.
 *
 *   <script src="https://nikniq.com/js/nikniq.js"></script>
 *   const nq = NikniQ.init({ clientId: 'CLIENT_ID', game: 'chopper' });
 *   await nq.ready;                       // finishes a sign-in redirect if there is one
 *   if (!nq.player) button.onclick = () => nq.signIn();
 *   nq.report({ event: 'game_over', score: 1240, won: false, duration_seconds: 87 });
 */
(function (global) {
  'use strict';

  var SCRIPT_ORIGIN = (function () {
    try { return new URL(document.currentScript.src).origin; } catch (e) { return 'https://nikniq.com'; }
  })();

  function b64url(bytes) {
    var s = ''; bytes = new Uint8Array(bytes);
    for (var i = 0; i < bytes.length; i++) s += String.fromCharCode(bytes[i]);
    return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }
  function randomString(n) { return b64url(crypto.getRandomValues(new Uint8Array(n))); }
  async function challengeFor(verifier) {
    return b64url(await crypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier)));
  }
  function store(kind) { try { return kind === 'session' ? sessionStorage : localStorage; } catch (e) { return null; } }

  function Client(opts) {
    if (!opts || !opts.clientId || !opts.game) throw new Error('NikniQ.init needs clientId and game');
    this.portal = (opts.portal || SCRIPT_ORIGIN).replace(/\/$/, '');
    this.clientId = opts.clientId;
    this.game = opts.game;
    this.redirectUri = opts.redirectUri || (location.origin + location.pathname);
    this.key = 'nikniq:' + this.game;
    this.player = null;
    this.stats = null;
    this._queue = [];
    this._listeners = [];
    var saved = store('local') && store('local').getItem(this.key);
    this._token = saved ? JSON.parse(saved) : null;
    if (this._token && Date.parse(this._token.expires_at) < Date.now()) this._forget();
    this.ready = this._boot();
    var self = this;
    global.addEventListener('pagehide', function () { self.flush(true); });
  }

  Client.prototype._api = function (path, options, keepalive) {
    options = options || {};
    var headers = { 'Accept': 'application/json' };
    if (options.body) headers['Content-Type'] = 'application/json';
    if (this._token) headers['Authorization'] = 'Bearer ' + this._token.access_token;
    var self = this;
    return fetch(this.portal + path, { method: options.method || 'GET', headers: headers, body: options.body ? JSON.stringify(options.body) : undefined, keepalive: !!keepalive })
      .then(function (r) {
        if (r.status === 401 && self._token) { self._forget(); self._emit(); }
        return r.json().catch(function () { return {}; }).then(function (data) {
          if (!r.ok) { var err = new Error(data.error_description || data.message || ('HTTP ' + r.status)); err.status = r.status; err.data = data; throw err; }
          return data;
        });
      });
  };

  Client.prototype._forget = function () {
    this._token = null; this.player = null; this.stats = null;
    var s = store('local'); if (s) s.removeItem(this.key);
  };

  Client.prototype._emit = function () {
    for (var i = 0; i < this._listeners.length; i++) { try { this._listeners[i](this.player); } catch (e) {} }
  };

  Client.prototype._boot = async function () {
    await Promise.resolve(); // let the caller attach onChange() before the first emit
    var params = new URLSearchParams(location.search);
    var pending = store('session') && JSON.parse(store('session').getItem(this.key + ':pkce') || 'null');
    if (pending && (params.has('code') || params.has('error'))) {
      store('session').removeItem(this.key + ':pkce');
      ['code', 'state', 'error', 'error_description'].forEach(function (k) { params.delete(k); });
      var clean = location.pathname + (params.toString() ? '?' + params : '') + location.hash;
      var url = new URLSearchParams(location.search);
      history.replaceState(null, '', clean);
      if (url.get('error')) throw new Error(url.get('error_description') || url.get('error'));
      if (url.get('state') !== pending.state) throw new Error('Sign-in state mismatch, please try again.');
      var res = await this._api('/api/sso/token', { method: 'POST', body: {
        grant_type: 'authorization_code', client_id: this.clientId, code: url.get('code'),
        redirect_uri: pending.redirectUri, code_verifier: pending.verifier,
      } });
      this._token = { access_token: res.access_token, expires_at: res.expires_at };
      store('local') && store('local').setItem(this.key, JSON.stringify(this._token));
      this.player = res.player; this.stats = res.stats;
      this._emit();
      return this.player;
    }
    if (this._token) {
      try { var me = await this._api('/api/sso/me'); this.player = me.player; this.stats = me.stats; this.rank = me.rank; this.favourite = me.favourite; }
      catch (e) { if (e.status !== 401) throw e; }
    }
    this._emit(); // always fire once, so the game can draw its signed-in or guest state
    return this.player;
  };

  /** Send the player to NikniQ to sign in; they come back to redirectUri. */
  Client.prototype.signIn = async function () {
    var verifier = randomString(48), state = randomString(16);
    store('session').setItem(this.key + ':pkce', JSON.stringify({ verifier: verifier, state: state, redirectUri: this.redirectUri }));
    location.href = this.portal + '/sso/authorize?' + new URLSearchParams({
      client_id: this.clientId, redirect_uri: this.redirectUri, state: state,
      code_challenge: await challengeFor(verifier), code_challenge_method: 'S256',
    });
  };

  Client.prototype.signOut = async function () {
    if (this._token) { try { await this._api('/api/sso/logout', { method: 'POST' }); } catch (e) {} }
    this._forget(); this._emit();
  };

  /** Called with the player (or null) once at start-up, then after sign-in, sign-out, or an expired token. */
  Client.prototype.onChange = function (fn) { this._listeners.push(fn); return this; };

  /**
   * Queue one event ({ event, score, level, duration_seconds, won, metadata }).
   * Play-ending events (game_over, finish, complete, round_end, session_end)
   * are sent at once; others are batched. Ignored when nobody is signed in.
   * Resolves with the updated stats and rank when the event is sent.
   */
  Client.prototype.report = function (evt) {
    if (!this._token) return Promise.resolve(null);
    evt = Object.assign({ occurred_at: new Date().toISOString() }, typeof evt === 'string' ? { event: evt } : evt);
    this._queue.push(evt);
    var ends = ['game_over', 'finish', 'complete', 'round_end', 'session_end'];
    if (ends.indexOf(evt.event) !== -1 || this._queue.length >= 25) return this.flush();
    var self = this; clearTimeout(this._timer);
    this._timer = setTimeout(function () { self.flush(); }, 5000);
    return Promise.resolve(null);
  };

  Client.prototype.flush = function (keepalive) {
    clearTimeout(this._timer);
    if (!this._token || !this._queue.length) return Promise.resolve(null);
    var batch = this._queue.splice(0, 50), self = this;
    return this._api('/api/games/' + encodeURIComponent(this.game) + '/events', { method: 'POST', body: { events: batch } }, keepalive)
      .then(function (res) { self.stats = res.stats; self.rank = res.rank; return res; })
      .catch(function (e) { if (e.status !== 401 && e.status !== 422) self._queue = batch.concat(self._queue); throw e; });
  };

  Client.prototype.setFavourite = function (on) {
    var self = this;
    return this._api('/api/sso/favourite', { method: on ? 'POST' : 'DELETE' }).then(function (r) { self.favourite = r.favourite; return r.favourite; });
  };

  Client.prototype.leaderboard = function (limit) {
    return fetch(this.portal + '/api/games/' + encodeURIComponent(this.game) + '/leaderboard?limit=' + (limit || 10), { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); }).then(function (d) { return d.leaderboard || []; });
  };

  global.NikniQ = { init: function (opts) { return new Client(opts); }, version: '1.0.0' };
})(window);
