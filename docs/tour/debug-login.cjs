const puppeteer = require('puppeteer-core');
(async () => {
  const b = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true,
    args: ['--no-proxy-server','--proxy-bypass-list=*','--hide-scrollbars'], defaultViewport: {width:1440,height:900} });
  const p = await b.newPage();
  p.on('console', m => console.log('CONSOLE', m.type(), m.text()));
  p.on('requestfailed', r => console.log('FAILED', r.url(), r.failure()?.errorText));
  try {
    const resp = await p.goto('http://127.0.0.1:8000/login', { waitUntil: 'domcontentloaded', timeout: 20000 });
    console.log('STATUS', resp && resp.status(), 'URL', p.url(), 'TITLE', await p.title());
    await p.type('#password', 'password');
    await p.click('button[type=submit]');
    await new Promise(r => setTimeout(r, 4000));
    console.log('AFTER URL', p.url());
    console.log('TEXT', (await p.evaluate(() => document.body.innerText)).slice(0, 300).replace(/\n/g, ' | '));
  } catch (e) { console.error('ERR', e.message); }
  await b.close();
})();
