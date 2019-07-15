const request = require('request-promise')

const submitCaptcha = (captchaParams, api) => {
    return new Promise((resolve, reject) => {
        captchaParams.soft_id = '2496'
        request.post(api.in, {
            json: true,
            body: (typeof captchaParams === 'object') ? captchaParams : null
        })
            .then((res) => {
                if (res.status === 1) {
                    resolve(res.request)
                } else {
                    reject(res.request)
                }
            })
            .catch((error) => {
                reject({ error })
            })
    })
}

const getAnswer = (id, api) => {
    return new Promise((resolve, reject) => {
        const polling = setInterval(() => {
            request.get({
                uri: `${api.res}?key=${api.key}&json=1&action=get&id=${id}`,
                json: true
            })
                .then((res) => {
                    if (res.status === 1) {
                        clearInterval(polling)
                        resolve(res.request)
                    } else if (res.request !== 'CAPCHA_NOT_READY') {
                        clearInterval(polling)
                        reject(res.request)
                    }
                })
                .catch((error) => {
                    clearInterval(polling)
                    reject(error)
                })
        }, api.pollingInterval)
    })
}

const solveCaptcha = async (params, api) => {
    try {
        let id = await submitCaptcha(params, api)
        let answer = await getAnswer(id, api)
        return answer
    } catch (e) {
        return e
    }
}

module.exports = {
    submitCaptcha,
    getAnswer,
    solveCaptcha
}