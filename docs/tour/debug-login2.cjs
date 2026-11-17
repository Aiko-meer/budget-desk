const puppeteer = require('puppeteer-core');
(async () => {
  const b = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true,
    args: ['--no-proxy-server','--proxy-bypass-list=*','--hide-scrollbars','--disable-gpu'], defaultViewport: {width:1920,height:1080,deviceScaleFactor:2} });
  const p = await b.newPage();
  p.on('requestfailed', r => console.log('FAILED', r.url(), r.failure()?.errorText));
  await p.goto('http://127.0.0.1:8000/login', { waitUntil: 'domcontentloaded' });
  await p.waitForSelector('#email');
  await p.click('#email', { clickCount: 3 });
  await p.type('#email', 'me@budgetdesk.local');
  await p.click('#password', { clickCount: 3 });
  await p.type('#password', 'password');
  console.log('email value:', await p.$eval('#email', el => el.value));
  await p.click('button[type=submit]');
  await new Promise(r => setTimeout(r, 5000));
  console.log('URL', p.url());
  console.log('TEXT', (await p.evaluate(() => document.body.innerText)).slice(0,200).replace(/\n/g,' | '));
  await b.close();
})();
