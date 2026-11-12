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

    <h2>その他の機能</h2>
    <table>
        <thead><tr><th>呼び出し</th><th>内容</th></tr></thead>
        <tbody>
            <tr><td><code>nq.player</code></td><td>サインイン中のプレイヤー（<code>id</code>、<code>name</code>、<code>avatar</code>）、または <code>null</code></td></tr>
            <tr><td><code>nq.stats</code>, <code>nq.rank</code></td><td>このゲームでのプレイヤーの合計成績とランキング順位</td></tr>
            <tr><td><code>nq.leaderboard(10)</code></td><td>上位のスコア（サインイン不要）</td></tr>
            <tr><td><code>nq.setFavourite(true)</code></td><td>プレイヤーのお気に入りにゲームを追加、または削除</td></tr>
            <tr><td><code>nq.signOut()</code></td><td>このゲームのトークンを無効にする</td></tr>
        </tbody>
    </table>
    <p class="muted">SDK は標準的な OAuth の認可コードフローと PKCE を使うため、ゲームにシークレットを含める必要はありません。自前のサーバーを持つゲームは、API を直接使うこともできます。詳しくはプロジェクトの README をご覧ください。</p>
</article>
@endsection
