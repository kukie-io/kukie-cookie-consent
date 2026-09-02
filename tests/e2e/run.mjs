/**
 * Headless end-to-end check of the admin pages: real admin.css + admin.js
 * against the REAL Laravel plugin-API payloads captured in fixtures/
 * (GET /settings and GET /status for an entitled and a locked plan, taken
 * from the Laravel test suite on 2026-09-01). admin-ajax.php is answered
 * from those fixtures via page.route(); nothing touches the network.
 *
 *   node tests/e2e/run.mjs            (KUKIE_PHP / KUKIE_PLAYWRIGHT override the binaries)
 *
 * Scenarios: entitled (unlocked form populated), locked (upsell, inert
 * fieldset, still-on note), noblock (a pre-1.8.0 service answer: error
 * notice, spinner hidden), dashboard card per scenario, Settings page
 * option rows (native radio clipped, figure beside the text).
 */
// Playwright comes from the Laravel repo's node_modules (a production dep
// there); point KUKIE_PLAYWRIGHT at another install if needed.
const pwPath = process.env.KUKIE_PLAYWRIGHT || new URL('../../../../kukie/node_modules/playwright/index.mjs', import.meta.url).pathname;
const { chromium } = await import(pwPath);
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const HERE = path.dirname(new URL(import.meta.url).pathname);
const PLUGIN = path.resolve(HERE, '..', '..');
const OUT = path.join(HERE, 'out');
const E2E = OUT; // rendered pages + screenshots
const json = (n) => JSON.parse(fs.readFileSync(path.join(HERE, 'fixtures', n), 'utf8'));
const settings = { entitled: json('settings-entitled.json'), locked: json('settings-locked.json') };
const status = { entitled: json('status-entitled.json'), locked: json('status-locked.json') };
// Pre-1.8.0 API answer: same payload without the block.
const noBlock = { ...settings.entitled }; delete noBlock.accessibility_widget;
const noBlockStatus = { ...status.entitled }; delete noBlockStatus.accessibility_widget;

// Render the three pages first (PHP + the WordPress stubs).
for (const tpl of ['admin-accessibility.php', 'admin-dashboard.php', 'admin-settings.php']) {
  execFileSync(process.env.KUKIE_PHP || 'php', [path.join(HERE, 'render.php'), tpl], { stdio: 'inherit' });
}
for (const tab of ['design', 'behaviour', 'iframes', 'language', 'gcm', 'uet', 'regions']) {
  execFileSync(process.env.KUKIE_PHP || 'php', [path.join(HERE, 'render.php'), 'admin-banner.php', 'kukie-banner', tab], { stdio: 'inherit' });
}

const results = [];
const check = (name, ok, extra = '') => { results.push(`${ok ? 'PASS' : 'FAIL'} ${name}${extra ? ' - ' + extra : ''}`); };

async function open(browser, file, scenario) {
  const page = await browser.newPage({ viewport: { width: 1400, height: 1000 } });
  await page.addInitScript(() => {
    window.kukieAdmin = { ajaxUrl: 'http://localhost:8765/wp-admin/admin-ajax.php', nonce: 'n', dashboardUrl: 'https://app.kukie.io', billingUrl: 'https://app.kukie.io/billing', embedUrl: 'https://cdn.kukie.io/s/site-key-1/c.js', siteId: 7, isConnected: '1', a11yPageUrl: '#', rocketDismissNonce: 'r', i18n: {} };
  });
  const errors = [];
  page.on('pageerror', (e) => errors.push(String(e)));
  page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
  await page.route('http://localhost:8765/**', async (route) => {
    const url = new URL(route.request().url());
    if (url.pathname.startsWith('/plugin/')) {
      const p = path.join(PLUGIN, url.pathname.replace('/plugin/', ''));
      const ct = p.endsWith('.css') ? 'text/css' : p.endsWith('.js') ? 'application/javascript' : 'text/plain';
      return route.fulfill({ status: 200, contentType: ct, body: fs.readFileSync(p) });
    }
    if (url.pathname.endsWith('/admin-ajax.php')) {
      const body = route.request().postData() || '';
      const action = /name="action"\r?\n\r?\n([^\r\n]+)/.exec(body)?.[1] || new URLSearchParams(body).get('action');
      let data;
      if (action === 'kukie_get_settings') {
        const s = scenario === 'noblock' ? noBlock : settings[scenario];
        data = { ...s, script_position: 'head', force_language: 'auto' };
      } else if (action === 'kukie_get_status') {
        data = scenario === 'noblock' ? noBlockStatus : status[scenario];
      } else {
        data = { message: 'ok', config_version: 8 };
      }
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, data }) });
    }
    if (url.pathname.endsWith('.html')) {
      return route.fulfill({ status: 200, contentType: 'text/html', body: fs.readFileSync(path.join(E2E, path.basename(url.pathname))) });
    }
    return route.fulfill({ status: 404, body: '' });
  });
  await page.goto(`http://localhost:8765/${file}`);
  await page.waitForTimeout(800);
  return { page, errors };
}

const browser = await chromium.launch();

// 1. Accessibility page, entitled plan
{
  const { page, errors } = await open(browser, 'admin-accessibility.html', 'entitled');
  const st = await page.evaluate(() => ({
    loadingVisible: getComputedStyle(document.getElementById('kukie-a11y-loading')).display !== 'none',
    errorVisible: getComputedStyle(document.getElementById('kukie-a11y-error')).display !== 'none',
    contentVisible: getComputedStyle(document.getElementById('kukie-a11y-content')).display !== 'none',
    lockedVisible: getComputedStyle(document.getElementById('kukie-a11y-locked')).display !== 'none',
    introVisible: getComputedStyle(document.getElementById('kukie-a11y-intro')).display !== 'none',
    fieldsDisabled: document.getElementById('kukie-a11y-fields').disabled,
    enabled: document.getElementById('kukie-a11y-enabled').checked,
    position: document.querySelector('input[name="kukie_a11y_position"]:checked')?.value,
    size: document.getElementById('kukie-a11y-size').value,
    modules: document.querySelectorAll('input[name="kukie_a11y_modules[]"]').length,
    ttsChecked: document.querySelector('input[name="kukie_a11y_modules[]"][value="tts"]')?.checked,
    langs: document.querySelectorAll('input[name="kukie_a11y_languages[]"]').length,
    customLangs: document.getElementById('kukie-a11y-langs-custom').checked,
    defaultLang: document.getElementById('kukie-a11y-default-language').value,
    defaultOptions: Array.from(document.getElementById('kukie-a11y-default-language').options).map(o => o.value),
    saveDisabled: document.getElementById('kukie-a11y-save').disabled,
  }));
  check('a11y entitled: spinner hidden', !st.loadingVisible);
  check('a11y entitled: no error, content shown, unlocked', !st.errorVisible && st.contentVisible && !st.lockedVisible && st.introVisible && !st.fieldsDisabled && !st.saveDisabled, JSON.stringify(st));
  check('a11y entitled: values populated', st.enabled && st.position === 'bottom-left' && st.size === '52' && st.modules === 20 && st.ttsChecked === false && st.langs === 71 && st.customLangs && st.defaultLang === 'bg' && JSON.stringify([...st.defaultOptions].sort()) === JSON.stringify(['', 'bg', 'de', 'en']), JSON.stringify(st));
  check('a11y entitled: no JS errors', errors.length === 0, errors.join(' | '));
  const ext = await page.evaluate(() => { const e = document.querySelector('.kukie-cta-banner a .kukie-ext'); const r = e.getBoundingClientRect(); const cs = getComputedStyle(e); return { h: r.height, fs: cs.fontSize }; });
  check('a11y entitled: inline new-tab marker in the CTA banner is text-sized (13px)', ext.fs === '13px' && ext.h <= 14, JSON.stringify(ext));
  await page.screenshot({ path: path.join(E2E, 'shot-a11y-entitled.png'), fullPage: true });
  const cta = await page.$('.kukie-cta-banner');
  if (cta) await cta.screenshot({ path: path.join(E2E, 'shot-cta-banner.png') });
  await page.close();
}
// 2. Accessibility page, locked plan
{
  const { page, errors } = await open(browser, 'admin-accessibility.html', 'locked');
  const st = await page.evaluate(() => ({
    lockedVisible: getComputedStyle(document.getElementById('kukie-a11y-locked')).display !== 'none',
    lockedText: document.getElementById('kukie-a11y-locked-text').textContent,
    stillOn: getComputedStyle(document.getElementById('kukie-a11y-still-on')).display !== 'none',
    fieldsDisabled: document.getElementById('kukie-a11y-fields').disabled,
    saveDisabled: document.getElementById('kukie-a11y-save').disabled,
    upgradeHref: document.getElementById('kukie-a11y-upgrade').href,
    loadingVisible: getComputedStyle(document.getElementById('kukie-a11y-loading')).display !== 'none',
  }));
  check('a11y locked: upsell shown, fields inert, still-on note', st.lockedVisible && st.fieldsDisabled && st.saveDisabled && st.stillOn && !st.loadingVisible && / plan and above/.test(st.lockedText), JSON.stringify(st));
  check('a11y locked: no JS errors', errors.length === 0, errors.join(' | '));
  await page.screenshot({ path: path.join(E2E, 'shot-a11y-locked.png'), fullPage: true });
  await page.close();
}
// 3. Accessibility page, API without the block (pre-deploy service)
{
  const { page } = await open(browser, 'admin-accessibility.html', 'noblock');
  const st = await page.evaluate(() => ({
    loadingVisible: getComputedStyle(document.getElementById('kukie-a11y-loading')).display !== 'none',
    errorVisible: getComputedStyle(document.getElementById('kukie-a11y-error')).display !== 'none',
    errorText: document.getElementById('kukie-a11y-error').textContent.trim(),
    contentVisible: getComputedStyle(document.getElementById('kukie-a11y-content')).display !== 'none',
  }));
  check('a11y no-block: error shown, spinner hidden, form withheld', !st.loadingVisible && st.errorVisible && !st.contentVisible, JSON.stringify(st));
  await page.close();
}
// 4. Dashboard, entitled + enabled -> Active; locked -> Not in plan; no block -> "-"
for (const [scenario, expected] of [['entitled', 'Active'], ['locked', 'Not in plan'], ['noblock', '-']]) {
  const { page, errors } = await open(browser, 'admin-dashboard.html', scenario);
  const txt = await page.evaluate(() => document.getElementById('kukie-stat-a11y').textContent.trim());
  check(`dashboard ${scenario}: a11y card = "${expected}"`, txt === expected, `got "${txt}" errors=${errors.join('|')}`);
  if (scenario === 'entitled') {
    const dash = await page.evaluate(() => ({
      order: Array.from(document.querySelectorAll('#kukie-overview-cards .kukie-stat-label')).map(e => Array.from(e.childNodes).filter(n => n.nodeType === 3).map(n => n.textContent).join('').trim()),
      linkCards: Array.from(document.querySelectorAll('#kukie-overview-cards a.kukie-stat-card--link')).map(a => ({ label: a.querySelector('.kukie-stat-label').textContent.trim(), newTab: a.target === '_blank', marker: !!a.querySelector('.kukie-stat-card-go'), sr: !!a.querySelector('.screen-reader-text') })),
      externalWithoutSr: Array.from(document.querySelectorAll('a[target="_blank"]')).filter(a => !a.querySelector('.screen-reader-text')).length,
    }));
    check('dashboard: card order', JSON.stringify(dash.order) === JSON.stringify(['Cookie Banner Status', 'Accessibility widget', 'Verification', 'Consents Today', 'Plan']), JSON.stringify(dash.order));
    check('dashboard: link cards marked (a11y same-tab arrow, plan new-tab icon + sr text)', dash.linkCards.length === 2 && dash.linkCards.every(c => c.marker) && dash.linkCards[1].newTab && dash.linkCards[1].sr && !dash.linkCards[0].newTab, JSON.stringify(dash.linkCards));
    check('dashboard: every new-tab link carries screen-reader text', dash.externalWithoutSr === 0, String(dash.externalWithoutSr));
    const geo = await page.evaluate(() => {
      const plan = document.querySelector('#kukie-stat-plan').getBoundingClientRect();
      const go = document.querySelector('#kukie-stat-plan').closest('.kukie-stat-card').querySelector('.kukie-stat-card-go').getBoundingClientRect();
      const overlap = !(plan.right <= go.left || plan.left >= go.right || plan.bottom <= go.top || plan.top >= go.bottom);
      const ext = document.querySelector('.kukie-cta-banner .kukie-ext, .kukie-help-text .kukie-ext');
      const extSize = ext ? ext.getBoundingClientRect().height : 0;
      return { overlap, extSize, planText: document.querySelector('#kukie-stat-plan').textContent.trim() };
    });
    check('dashboard: long plan name never overlaps the card marker', !geo.overlap && geo.planText === 'Pixadoro Custom Plan', JSON.stringify(geo));
    await page.screenshot({ path: path.join(E2E, 'shot-dashboard.png'), fullPage: true });
    const cards = await page.$('#kukie-overview-cards');
    if (cards) await cards.screenshot({ path: path.join(E2E, 'shot-cards.png') });
  }
  await page.close();
}
// 5. Settings page renders; screenshot the script position picker
{
  const { page, errors } = await open(browser, 'admin-settings.html', 'entitled');
  const st = await page.evaluate(() => ({
    loadingVisible: getComputedStyle(document.getElementById('kukie-settings-loading')).display !== 'none',
    contentVisible: getComputedStyle(document.getElementById('kukie-settings-content')).display !== 'none',
    checked: document.querySelector('input[name="script_position"]:checked')?.value,
    radioVisible: (() => { const r = document.querySelector('input[name="script_position"]'); const cs = getComputedStyle(r); return cs.opacity !== '0' || parseInt(cs.width) > 2; })(),
  }));
  check('settings: loaded, spinner hidden, native radio clipped', !st.loadingVisible && st.contentVisible && st.checked === 'head' && !st.radioVisible, JSON.stringify(st) + ' ' + errors.join('|'));
  const geo = await page.evaluate(() => { const f = document.querySelector('.kukie-option-figure').getBoundingClientRect(); const t = document.querySelector('.kukie-option-title').getBoundingClientRect(); const o = document.querySelector('.kukie-option').getBoundingClientRect(); return { figureLeftOfTitle: f.right <= t.left, sameRow: Math.abs((f.top + f.height / 2) - (o.top + o.height / 2)) < 4, rowHeight: o.height }; });
  check('settings: option row is horizontal (figure beside text)', geo.figureLeftOfTitle && geo.sameRow, JSON.stringify(geo));
  await page.screenshot({ path: path.join(E2E, 'shot-settings.png'), fullPage: true });
  const card = await page.$('.kukie-option-list');
  if (card) await card.screenshot({ path: path.join(E2E, 'shot-script-position.png') });
  await page.close();
}
// 6. Settings page: language card gone, connection card refreshed from /status
{
  const { page, errors } = await open(browser, 'admin-settings.html', 'entitled');
  const st = await page.evaluate(() => ({
    langGone: !document.getElementById('kukie-force-language') && !document.getElementById('kukie-languages-grid'),
    plan: document.getElementById('kukie-conn-plan').textContent.trim(),
    org: document.getElementById('kukie-conn-org').textContent.trim(),
    domain: document.getElementById('kukie-conn-domain').textContent.trim(),
  }));
  check('settings: language fields moved off the page', st.langGone, JSON.stringify(st));
  check('settings: connection card shows the live plan/org/domain from /status', st.plan === 'Pixadoro Custom Plan' && st.org.length > 0 && st.domain === 'obshti-uslovia.com', JSON.stringify(st) + ' ' + errors.join('|'));
  await page.close();
}
// 7. Consent banner tabs
{
  const { page, errors } = await open(browser, 'admin-banner-behaviour.html', 'entitled');
  const st = await page.evaluate(() => ({
    tabs: Array.from(document.querySelectorAll('.nav-tab')).map(a => a.textContent.trim()),
    formVisible: getComputedStyle(document.getElementById('kukie-behaviour-form')).display !== 'none',
    branding: document.getElementById('kukie-show-branding').checked,
    brandingDisabled: document.getElementById('kukie-show-branding').disabled,
    badgeVisible: getComputedStyle(document.getElementById('kukie-branding-locked')).display !== 'none',
    dnt: document.getElementById('kukie-respect-dnt').checked,
    gpc: document.getElementById('kukie-respect-gpc').checked,
    overlay: document.getElementById('kukie-show-overlay').checked,
    pages: document.getElementById('kukie-disabled-pages').value,
  }));
  check('behaviour tab: seven tabs in order', JSON.stringify(st.tabs) === JSON.stringify(['Design', 'Behaviour', 'iFrame blocking', 'Language', 'Google Consent Mode v2', 'Microsoft UET', 'Regions']), JSON.stringify(st.tabs));
  check('behaviour tab (branding removable): toggles populated, branding editable', st.formVisible && !st.branding && !st.brandingDisabled && !st.badgeVisible && st.dnt && !st.gpc && st.overlay && st.pages === '/checkout/*\n/account', JSON.stringify(st) + ' ' + errors.join('|'));
  await page.screenshot({ path: path.join(E2E, 'shot-behaviour.png'), fullPage: true });
  await page.close();
}
{
  const { page } = await open(browser, 'admin-banner-behaviour.html', 'locked');
  const st = await page.evaluate(() => ({
    branding: document.getElementById('kukie-show-branding').checked,
    brandingDisabled: document.getElementById('kukie-show-branding').disabled,
    badgeVisible: getComputedStyle(document.getElementById('kukie-branding-locked')).display !== 'none',
  }));
  check('behaviour tab (branding required): toggle locked ON with the plan badge', st.branding && st.brandingDisabled && st.badgeVisible, JSON.stringify(st));
  await page.close();
}
{
  const { page, errors } = await open(browser, 'admin-banner-iframes.html', 'entitled');
  const st = await page.evaluate(() => ({
    enabled: document.getElementById('kukie-iframe-enabled').checked,
    wrapVisible: getComputedStyle(document.getElementById('kukie-iframe-services-wrap')).display !== 'none',
    services: document.querySelectorAll('input[name="blocked_iframe_services[]"]').length,
    checked: Array.from(document.querySelectorAll('input[name="blocked_iframe_services[]"]:checked')).map(cb => cb.value),
  }));
  check('iframes tab: registry rendered, blocked subset ticked', st.enabled && st.wrapVisible && st.services === 12 && JSON.stringify(st.checked) === JSON.stringify(['youtube', 'google-maps']), JSON.stringify(st) + ' ' + errors.join('|'));
  await page.screenshot({ path: path.join(E2E, 'shot-iframes.png'), fullPage: true });
  await page.close();
}
{
  const { page, errors } = await open(browser, 'admin-banner-language.html', 'entitled');
  const st = await page.evaluate(() => ({
    formVisible: getComputedStyle(document.getElementById('kukie-language-form')).display !== 'none',
    force: document.getElementById('kukie-force-language').value,
    auto: document.getElementById('kukie-auto-translate').checked,
    optionsVisible: getComputedStyle(document.getElementById('kukie-language-options')).display !== 'none',
    grid: document.querySelectorAll('input[name="enabled_languages[]"]').length,
    ticked: Array.from(document.querySelectorAll('input[name="enabled_languages[]"]:checked')).map(cb => cb.value).sort(),
  }));
  check('language tab: populated from the API + local override', st.formVisible && st.force === 'auto' && st.auto && st.optionsVisible && st.grid === 3 && JSON.stringify(st.ticked) === JSON.stringify(['bg', 'en']), JSON.stringify(st) + ' ' + errors.join('|'));
  await page.screenshot({ path: path.join(E2E, 'shot-language.png'), fullPage: true });
  await page.close();
}
{
  const { page } = await open(browser, 'admin-banner-regions.html', 'entitled');
  const st = await page.evaluate(() => {
    const a = document.querySelector('.kukie-cta-card a.kukie-btn-primary');
    return { href: a?.href, newTab: a?.target === '_blank', sr: !!a?.querySelector('.screen-reader-text'), noForm: !document.querySelector('form') };
  });
  check('regions tab: only a CTA to the Kukie.io regions editor', st.noForm && st.newTab && st.sr && /\/sites\/7\/banner\?tab=regions$/.test(st.href), JSON.stringify(st));
  await page.close();
}
{
  const { page } = await open(browser, 'admin-banner-gcm.html', 'entitled');
  const st = await page.evaluate(() => ({ autoBlockGone: !document.getElementById('kukie-auto-block'), gcm: document.getElementById('kukie-gcm-enabled') !== null }));
  check('gcm tab: script blocking moved to Behaviour', st.autoBlockGone && st.gcm, JSON.stringify(st));
  await page.close();
}

await browser.close();
console.log(results.join('\n'));
if (results.some((r) => r.startsWith('FAIL'))) process.exit(1);
