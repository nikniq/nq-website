@extends('layouts.app')
@section('title', 'ゲーム開発者の方へ')
@section('description', 'script タグひとつで、ブラウザゲームに NikniQ のサインイン、スコア、ランキングを追加できます。')
@php($slug = request('game', $games->first()?->slug ?? 'your-game'))
@php($game = \App\Models\Game::where('slug', $slug)->first())
@php($clientId = auth()->user()?->is_admin && $game ? $game->client_id : 'CLIENT_ID')
@section('content')
<article class="prose">
    <h1>あなたのゲームに NikniQ を追加する</h1>
    <p class="lead">script タグひとつで、あなたのゲームに NikniQ のサインイン、スコアの保存、プレイヤーの順位、共通のランキングが加わります。プレイヤーの成績は、マイページ、プレイヤーページ、殿堂入りに表示されます。</p>

    <h2>NikniQ の使い方を選ぶ</h2>
    <p>NikniQ サインインは任意です。各ゲームは自分のアカウントを管理し続け、ポータルにつながらなくても動きます。ゲームが受け取るのはトークンとプレイヤーの公開プロフィールだけで、ユーザーデータベースには触れません。</p>
    <table>
        <thead><tr><th>方式</th><th>仕組み</th><th>プレイヤーにとって</th></tr></thead>
        <tbody>
            <tr><td><strong>独立</strong></td><td>独自アカウントのみ。<code>nikniq.js</code> を読み込みません。</td><td>ゲームごとのログイン。スコアはゲーム内に残り、ポータルはランキングを表示しません。</td></tr>
            <tr><td><strong>任意の連携</strong></td><td>独自ログインに「NikniQ でサインイン」ボタンを追加。<code>autoSignIn: false</code> を指定。</td><td>プレイヤーが選べます。連携するとポータルのスコア・順位・お気に入りが使えます。</td></tr>
            <tr><td><strong>ハイブリッド</strong></td><td>独自ログインを残しつつ、まず NikniQ を試します（既定）。</td><td>nikniq.com にログイン済みならそのまま入れ、それ以外は独自ログインが表示されます。</td></tr>
            <tr><td><strong>NikniQ のみ</strong></td><td>NikniQ だけをログインに使います。</td><td>すべての NikniQ ゲームで 1 つのアカウント。</td></tr>
        </tbody>
    </table>

    <h2>1. ゲームを登録する</h2>
    <p>管理者が<a href="{{ route('admin.games.index') }}">管理 → ゲーム</a>でゲームを追加します。ブラウザだけで動くゲームなら <em>Public client</em> にチェックを入れ、プレイヤーが戻ってくるページの正確な URL（例：<code>https://{{ $slug }}.nikniq.com/</code>）をリダイレクト URI として登録します。ゲームのクライアント ID は、そのゲームの管理ページに表示されます。</p>

    <h2>2. スクリプトを追加する</h2>
    @if(auth()->user()?->is_admin)
        <form method="get" class="row" style="margin-bottom:.6rem">
            <label class="inline">コードを表示するゲーム
                <select name="game" onchange="this.form.submit()">
                    @foreach($games as $g)<option value="{{ $g->slug }}" @selected($g->slug === $slug)>{{ $g->name }}</option>@endforeach
                </select>
            </label>
            <noscript><button class="btn sm" type="submit">表示</button></noscript>
        </form>
    @endif
<pre><code>&lt;script src="{{ url('/js/nikniq.js') }}"&gt;&lt;/script&gt;
&lt;script&gt;
  const nq = NikniQ.init({ clientId: '{{ $clientId }}', game: '{{ $slug }}' });

  nq.onChange(player =&gt; {
    signInButton.hidden = !!player;
    playerName.textContent = player ? player.name : '';
  });
  nq.ready.catch(err =&gt; console.warn('NikniQ sign-in failed:', err.message));

  signInButton.onclick = () =&gt; nq.signIn();   // NikniQ に移動して戻ってきます
&lt;/script&gt;</code></pre>

    <h2>3. ゲームの出来事を送る</h2>
<pre><code>nq.report({ event: 'level_complete', level: '3', score: 820 });
nq.report({ event: 'game_over', score: 1240, won: false, duration_seconds: 87 })
  .then(res =&gt; res &amp;&amp; showRank(res.rank));   // 更新された成績と順位</code></pre>
    <ul>
        <li><code>game_over</code>、<code>finish</code>、<code>complete</code>、<code>round_end</code>、<code>session_end</code> は1回のプレイとして数えられ、すぐに送信されます。その他のイベントはまとめて、数秒以内またはページを閉じたときに送信されます。</li>
        <li>フィールド：<code>event</code>（必須。英字、数字、<code>_ . -</code>）、<code>score</code>、<code>level</code>、<code>duration_seconds</code>、<code>won</code>、<code>metadata</code>（キー50個までのオブジェクト）。</li>
        <li>誰もサインインしていないときは何も送信されないので、ゲストでもゲームは同じように動きます。</li>
    </ul>

    <h2>独自ログインを残す（ハイブリッド）</h2>
    <p><code>nq.ready</code> を待ちます。プレイヤーが返れば nikniq.com にログイン済みです。そうでなければ独自ログインに切り替えます。ポータルに接続できない場合は <code>ready</code> が失敗しますが、ゲームはそのまま続けられます。</p>
<pre><code>const nq = NikniQ.init({ clientId: '{{ $clientId }}', game: '{{ $slug }}' });
nq.ready
  .then(player =&gt; {
    if (player) useNikniqPlayer(player);   // NikniQ でサインイン済み：自分のアカウントに対応付ける
    else showOwnLogin();                    // nikniq.com に未ログイン
  })
  .catch(() =&gt; showOwnLogin());             // ポータルに接続できない：そのまま続行</code></pre>

    <h2>標準の OAuth 2.0 と OpenID Connect</h2>
    <p>独自サーバーを持つサイトや、OAuth / OpenID Connect ライブラリを使うフレームワークは、SDK の代わりに NikniQ を標準の ID プロバイダーとして使えます。ライブラリにディスカバリー文書を指定すると、エンドポイント（認可、トークン、UserInfo、失効、JWKS）が自動で設定されます。認可コードフロー（PKCE S256）、スコープ openid・profile・email、RS256 署名の ID トークン、<code>prompt=none</code> に対応しています。アカウントの紐付けにはメールではなく <code>sub</code> を使ってください。</p>
<pre><code>{{ url('/.well-known/openid-configuration') }}</code></pre>

    <h2>その他の機能</h2>
    <table>
        <thead><tr><th>呼び出し</th><th>内容</th></tr></thead>
        <tbody>
            <tr><td><code>nq.player</code></td><td>サインイン中のプレイヤー（<code>id</code>、<code>name</code>、<code>avatar</code>）、または <code>null</code></td></tr>
            <tr><td><code>nq.stats</code>, <code>nq.rank</code></td><td>このゲームでのプレイヤーの合計成績とランキング順位</td></tr>
            <tr><td><code>nq.leaderboard(10)</code></td><td>上位のスコア（サインイン不要）</td></tr>
            <tr><td><code>nq.setFavourite(true)</code></td><td>プレイヤーのお気に入りにゲームを追加、または削除</td></tr>
            <tr><td><code>autoSignIn</code></td><td><code>false</code> にすると、最初の読み込み時の自動サインイン確認を行いません</td></tr>
            <tr><td><code>nq.signOut()</code></td><td>このゲームのトークンを無効にする</td></tr>
        </tbody>
    </table>
    <p class="muted">SDK は標準的な OAuth の認可コードフローと PKCE を使うため、ゲームにシークレットを含める必要はありません。自前のサーバーを持つゲームは、API を直接使うこともできます。詳しくはプロジェクトの README をご覧ください。</p>
</article>
@endsection
