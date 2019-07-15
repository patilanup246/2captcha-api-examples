const { solveCaptcha } = require('./captchaapi')
const puppeteer = require('puppeteer');

const service = process.env.SERVICE || '2captcha.com'
const api = {
    in: 'https://' + service + '/in.php',
    res: 'https://' + service + '/res.php',
    key: process.env.APIKEY || 'YOUR_API_KEY',
    pollingInterval: 5000
}

const targetUrl = 'https://www.google.com/recaptcha/api2/demo'

var browserParams = {
    headless: false,
    args: [
        '--no-sandbox',
        '--disable-setuid-sandbox'
    ]
    //	executablePath:'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' //let's use real chrome instead of Chromium
}

let main = async () => {
    const browser = await puppeteer.launch(browserParams);
    const page = await browser.newPage();
    await page.setViewport({ width: 1200, height: 1000 });
    await page.setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36');

    // open the page
    await page.goto(targetUrl)
    await page.waitFor(3000)

    // find recaptcha parameters
    const recapParams = await page.evaluate(() => {
        return new Promise((resolve, reject) => {
            var cs = []
            var rs = {}
            for (var id in ___grecaptcha_cfg.clients) {
                cs.push(id)
            }
            cs.forEach(cid => {
                for (var p in ___grecaptcha_cfg.clients[cid]) {
                    var path = "___grecaptcha_cfg.clients[" + cid + "]." + p
                    var pp = eval(path)
                    if (typeof pp === 'object') {
                        for (var s in pp) {
                            var subpath = "___grecaptcha_cfg.clients[" + cid + "]." + p + "." + s
                            var sp = eval(subpath)
                            if (sp && typeof sp === 'object' && sp.hasOwnProperty('sitekey') && sp.hasOwnProperty('size')) {
                                rs.sitekey = eval(subpath + '.sitekey')
                                if (eval(subpath + '.callback') == null) {
                                    rs.callback = null
                                }
                                else {
                                    rs.callback = eval(subpath + '.callback')
                                }
                                resolve(rs)
                            }
                        }
                    }

                }
            })
        })
    })

    const params = {
        key: api.key,
        method: 'userrecaptcha',
        googlekey: recapParams.sitekey,
        pageurl: await page.url(),
        json: 1,
    }

    // solve the captcha with API
    const token = await solveCaptcha(params, api)

    // place the token into response field
    await page.evaluate((token) => {
        document.querySelector('#g-recaptcha-response').value = token
    }, token)

    // execute the callback
    if (recapParams.callback) {
        await page.evaluate((args) => {
            eval(args.cb + '(\'' + args.token + '\')')
        }, {
                cb: recapParams.callback,
                token
            })
    }

    // click submit button
    await page.focus('#recaptcha-demo-submit')
    await page.click('#recaptcha-demo-submit')

    // await page.close()
    // await browser.close()
}

main()