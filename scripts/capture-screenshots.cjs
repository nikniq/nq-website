#!/usr/bin/env node
/*
 * Capture gameplay screenshots of each game into public_html/images/games/{slug}/.
 * Game::screenshotUrls() picks those files up automatically.
 *
 *   NODE_PATH=/path/to/node_modules node scripts/capture-screenshots.cjs [slug ...] [--count=3] [--force]
 *
 * Needs Playwright (npm i playwright && npx playwright install chromium). Reads
 * slugs and URLs from `php artisan tinker`-free JSON: /api/games on APP_URL, or
 * --base=https://nikniq.com (default).
 */
const fs = require('fs'), path = require('path');
const { chromium } = require('playwright');
const args = process.argv.slice(2);
const opt = (k, d) => { const a = args.find(x => x.startsWith('--' + k + '=')); return a ? a.split('=').slice(1).join('=') : d; };
const count = +opt('count', 3), force = args.includes('--force'), base = opt('base', 'https://nikniq.com');
const only = args.filter(a => !a.startsWith('--'));
const root = path.join(__dirname, '..', 'public_html', 'images', 'games');

(async () => {
  const res = await fetch(base + '/api/games?per_page=200');
  const body = await res.json(); const list = body.games || body.data || [];
  const games = list.filter(g => g.url && (!only.length || only.includes(g.slug)));
  const browser = await chromium.launch();
  for (const g of games) {
    const dir = path.join(root, g.slug);
    if (!force && fs.existsSync(dir) && fs.readdirSync(dir).some(f => /^shot-\d+\.jpg$/.test(f))) { console.log('skip', g.slug); continue; }
    fs.mkdirSync(dir, { recursive: true });
    const page = await browser.newPage({ viewport: { width: 1280, height: 720 } });
    // Keep games on their own page: block the NikniQ sign-in round trip.
    await page.route(u => /^https?:\/\/(www\.)?nikniq\.com\/sso\//.test(u.href), r => r.abort());
    try {
      await page.goto(g.url, { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
      await page.waitForTimeout(+opt('wait', 2500));
      await page.mouse.click(640, 360).catch(() => {});
      for (let n = 1; n <= count; n++) {
        // Nudge the game along: start keys, then some movement between shots.
        for (const k of ['Enter', 'Space', 'ArrowRight', 'ArrowUp', 'ArrowLeft', 'ArrowDown']) { await page.keyboard.press(k).catch(() => {}); await page.waitForTimeout(250); }
        await page.waitForTimeout(n === 1 ? 800 : 2500);
        await page.screenshot({ path: path.join(dir, `shot-${n}.jpg`), type: 'jpeg', quality: 78 });
      }
      console.log('ok  ', g.slug);
    } catch (e) { console.log('fail', g.slug, e.message); }
    await page.close();
  }
  await browser.close();
})();
