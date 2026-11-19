/*!
 * NikniQ Games SDK: sign players in with their NikniQ account and report scores.
 * No dependencies. Works in any modern browser.
 *
 *   <script src="https://nikniq.com/js/nikniq.js"></script>
 *   const nq = NikniQ.init({ clientId: 'CLIENT_ID', game: 'chopper' });
 *   await nq.ready;                       // finishes a sign-in redirect if there is one
 *                                         // (players already logged in on nikniq.com are signed in
 *                                         //  automatically; pass autoSignIn: false to turn that off)
 *   if (!nq.player) button.onclick = () => nq.signIn();               // NikniQ login page
 *   if (!nq.player) googleBtn.onclick = () => nq.signIn({ provider: 'google' }); // straight to Google
 *   nq.mountSignIn(document.getElementById('signin'));  // or: ready-made NikniQ + Google buttons
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
    this.autoSignIn = opts.autoSignIn !== false;
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
      if (url.get('error') === 'login_required' && pending.silent) { this._emit(); return null; }
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
    // Silent sign-in: once per tab, ask the portal whether the player is already
    // logged in there. Guests come straight back with login_required.
    var ss = store('session');
    if (!this._token && this.autoSignIn && ss && !ss.getItem(this.key + ':silent')) {
      ss.setItem(this.key + ':silent', '1');
      await this.signIn({ silent: true });
      return new Promise(function () {}); // the page is navigating away
    }
    if (this._token) {
      try { var me = await this._api('/api/sso/me'); this.player = me.player; this.stats = me.stats; this.rank = me.rank; this.favourite = me.favourite; }
      catch (e) { if (e.status !== 401) throw e; }
    }
    this._emit(); // always fire once, so the game can draw its signed-in or guest state
    return this.player;
  };

  /** Send the player to NikniQ to sign in; they come back to redirectUri. */
  Client.prototype.signIn = async function (opts) {
    var silent = !!(opts && opts.silent);
    var provider = opts && opts.provider;
    var verifier = randomString(48), state = randomString(16);
    store('session').setItem(this.key + ':pkce', JSON.stringify({ verifier: verifier, state: state, redirectUri: this.redirectUri, silent: silent }));
    var q = { client_id: this.clientId, redirect_uri: this.redirectUri, state: state,
      code_challenge: await challengeFor(verifier), code_challenge_method: 'S256' };
    if (silent) q.prompt = 'none';
    else if (provider && provider !== 'nikniq') q.provider = provider;
    location.href = this.portal + '/sso/authorize?' + new URLSearchParams(q);
  };

  /** Sign-in options the portal offers: resolves to e.g. ['nikniq', 'google']. */
  Client.prototype.providers = function () {
    var self = this;
    if (!this._providers) {
      this._providers = fetch(this.portal + '/.well-known/openid-configuration', { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (d) { return d.nikniq_login_providers || ['nikniq']; })
        .catch(function () { self._providers = null; return ['nikniq']; });
    }
    return this._providers;
  };

  /**
   * Render "Sign in with NikniQ" and (when the portal offers it) "Sign in with Google"
   * buttons into el; shows the player's name and a Sign out button once signed in.
   * Style with .nq-signin, .nq-btn, .nq-nikniq, .nq-google, .nq-player.
   */
  Client.prototype.mountSignIn = function (el, opts) {
    if (!el) return this;
    var self = this, labels = Object.assign({ nikniq: 'Sign in with NikniQ', google: 'Sign in with Google', signOut: 'Sign out' }, opts && opts.labels);
    if (!document.getElementById('nq-signin-css')) {
      var css = document.createElement('style'); css.id = 'nq-signin-css';
      css.textContent = '.nq-signin{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;font:600 15px/1.2 system-ui,sans-serif}'
        + '.nq-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1rem;border-radius:999px;border:1px solid #d0d4dc;background:#fff;color:#1d2433;cursor:pointer;font:inherit}'
        + '.nq-btn:hover{box-shadow:0 0 0 3px rgba(0,150,255,.25)}.nq-nikniq{background:linear-gradient(135deg,#e0218a,#7b2ff7);color:#fff;border:0}'
        + '.nq-btn svg{width:18px;height:18px}.nq-player{display:inline-flex;align-items:center;gap:.5rem}';
      document.head.appendChild(css);
    }
    function btn(cls, html, fn) { var b = document.createElement('button'); b.type = 'button'; b.className = 'nq-btn ' + cls; b.innerHTML = html; b.onclick = fn; return b; }
    var G = '<svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.6l6.7-6.7C35.6 2.4 30.2 0 24 0 14.6 0 6.6 5.4 2.7 13.3l7.8 6C12.4 13.6 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.4c-.5 2.9-2.2 5.3-4.6 7l7.4 5.7c4.3-4 6.9-9.9 6.9-17.2z"/><path fill="#FBBC05" d="M10.5 28.7c-.5-1.4-.8-3-.8-4.7s.3-3.3.8-4.7l-7.8-6C1 16.6 0 20.2 0 24s1 7.4 2.7 10.7l7.8-6z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.4-5.7c-2.1 1.4-4.8 2.3-8.5 2.3-6.3 0-11.6-4.1-13.5-9.8l-7.8 6C6.6 42.6 14.6 48 24 48z"/></svg>';
    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function render() {
      el.innerHTML = ''; el.classList.add('nq-signin');
      if (self.player) {
        var p = document.createElement('span'); p.className = 'nq-player'; p.innerHTML = esc(self.player.name || 'Player');
        el.appendChild(p); el.appendChild(btn('nq-out', esc(labels.signOut), function () { self.signOut(); }));
        return;
      }
      el.appendChild(btn('nq-nikniq', esc(labels.nikniq), function () { self.signIn(); }));
      self.providers().then(function (list) {
        if (!self.player && list.indexOf('google') !== -1 && !el.querySelector('.nq-google')) {
          el.appendChild(btn('nq-google', G + esc(labels.google), function () { self.signIn({ provider: 'google' }); }));
        }
      });
    }
    this.ready.then(render, render); this.onChange(render);
    return this;
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

  global.NikniQ = { init: function (opts) { return new Client(opts); }, version: '1.2.0' };
})(window);
