const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');
const scenes = require('./scenes.cjs');

const BASE = process.env.TOUR_BASE || 'http://127.0.0.1:8000';
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const FRAMES = path.join(__dirname, 'frames');
const SLEEP = (ms) => new Promise((r) => setTimeout(r, ms));

function captionHtml(scene, index) {
  const num = String(index + 1).padStart(2, '0');
  return `
    <div style="position:fixed;left:0;right:0;bottom:0;height:250px;z-index:99999;pointer-events:none;
        background:linear-gradient(to top, rgba(2,6,23,.94) 12%, rgba(2,6,23,.72) 46%, rgba(2,6,23,0) 100%);
        display:flex;align-items:flex-end;justify-content:center;padding:0 56px 34px;
        font-family:'Segoe UI',system-ui,sans-serif;">
      <div style="width:100%;max-width:1180px;display:flex;align-items:center;gap:22px;">
        <div style="flex:none;min-width:74px;height:60px;border-radius:16px;display:grid;place-items:center;
            background:rgba(52,211,153,.14);border:1px solid rgba(52,211,153,.45);color:#6ee7b7;
            font-size:20px;font-weight:800;letter-spacing:.04em;">${num}</div>
        <div style="min-width:0;">
          <div style="font-size:30px;font-weight:800;color:#ffffff;letter-spacing:-.02em;line-height:1.1;">${scene.title}</div>
          <div style="margin-top:7px;font-size:17px;color:#94a3b8;font-weight:500;">${scene.subtitle}</div>
        </div>
        <div style="margin-left:auto;flex:none;display:flex;align-items:center;gap:9px;color:#475569;
            font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;">
          <span style="width:7px;height:7px;border-radius:50%;background:#34d399;display:inline-block;"></span> Budget Desk
        </div>
      </div>
    </div>`;
}

async function clickByText(page, text) {
  const clicked = await page.evaluate((label) => {
    const buttons = [...document.querySelectorAll('button')];
    const match = buttons.find((b) => b.textContent.replace(/\s+/g, ' ').trim().startsWith(label));
    if (match) {
      match.click();
      return true;
    }
    return false;
  }, text);
  if (!clicked) throw new Error(`Button starting with "${text}" not found`);
}

(async () => {
  fs.mkdirSync(FRAMES, { recursive: true });

  const browser = await puppeteer.launch({
    executablePath: CHROME,
    headless: true,
    args: [
      '--hide-scrollbars',
      '--disable-gpu',
      '--force-color-profile=srgb',
      '--font-render-hinting=none',
      '--no-proxy-server',
      '--proxy-bypass-list=*',
    ],
    defaultViewport: { width: 1920, height: 1080, deviceScaleFactor: 2 },
  });

  const page = await browser.newPage();
  page.setDefaultTimeout(30000);

  const errors = [];
  page.on('pageerror', (err) => errors.push(String(err)));
  page.on('console', (msg) => {
    if (msg.type() === 'error') errors.push(msg.text());
  });

  // --- Login ---
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
  await page.waitForSelector('#email');
  await page.$eval('#email', (el) => {
    const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    setter.call(el, 'me@budgetdesk.local');
    el.dispatchEvent(new Event('input', { bubbles: true }));
  });
  await page.$eval('#password', (el) => {
    const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    setter.call(el, 'password');
    el.dispatchEvent(new Event('input', { bubbles: true }));
  });
  await Promise.all([
    page.waitForFunction(() => location.pathname === '/', { timeout: 25000 }),
    page.click('button[type=submit]'),
  ]);
  await page.waitForSelector('main', { timeout: 25000 });
  await SLEEP(1500);
  console.log('Logged in as seeded user.');

  for (let i = 0; i < scenes.length; i++) {
    const scene = scenes[i];
    const out = path.join(FRAMES, `${scene.id}.png`);

    if (scene.file) {
      await page.goto('file:///' + path.join(__dirname, scene.file).replace(/\\/g, '/'), {
        waitUntil: 'load',
      });
    } else {
      await page.goto(BASE + scene.path, { waitUntil: 'domcontentloaded' });
      await page.waitForSelector('main', { timeout: 20000 });
      await SLEEP(1600); // entrance animations + charts

      if (scene.action === 'quick-add') {
        await page.click('button[aria-label="Add expense"]');
        await page.waitForSelector('#quick-amount', { visible: true });
        await SLEEP(900);
      } else if (scene.action === 'savings-save') {
        await clickByText(page, 'Save now');
        await page.waitForFunction(
          () => [...document.querySelectorAll('button')].some((b) => b.textContent.includes('Move to savings')),
          { timeout: 15000 }
        );
        await SLEEP(900);
      }
    }

    if (scene.scroll) {
      await page.evaluate((y) => window.scrollTo(0, y), scene.scroll);
      await SLEEP(700);
    }

    if (!scene.noCaption) {
      await page.evaluate((html) => {
        document.getElementById('tour-caption')?.remove();
        const el = document.createElement('div');
        el.id = 'tour-caption';
        el.innerHTML = html;
        document.body.appendChild(el);
      }, captionHtml(scene, i));
      await SLEEP(350);
    }

    await page.screenshot({ path: out, type: 'png' });
    console.log(`Captured ${scene.id} -> ${path.basename(out)}`);
  }

  await browser.close();

  if (errors.length) {
    console.log('--- browser console errors ---');
    console.log([...new Set(errors)].slice(0, 10).join('\n'));
  } else {
    console.log('No browser console errors.');
  }
  console.log('Done.');
})().catch((err) => {
  console.error('CAPTURE FAILED:', err);
  process.exit(1);
});
