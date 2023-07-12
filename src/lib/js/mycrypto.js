/* global CryptoJS */

function nsCrypto(content) {
    var contentString = JSON.stringify(content);
    contentString = contentString.replace(/&#34;/g, '');
    return CryptoJS.AES.encrypt(contentString, _NSC118, { format: CryptoJSAesJson }).toString();
}

function nsDecrypto(content) {
    try {
        return JSON.parse(CryptoJS.AES.decrypt(content, _NSC118, { format: CryptoJSAesJson }).toString(CryptoJS.enc.Utf8));
    } catch (exception) {
        return {};
    }
}

var CryptoJSAesJson = {
    stringify: function (cipherParams) {
        var j = { ct: cipherParams.ciphertext.toString(CryptoJS.enc.Base64) };
        if (cipherParams.iv)
            j.iv = cipherParams.iv.toString();
        if (cipherParams.salt)
            j.s = cipherParams.salt.toString();
        return JSON.stringify(j);
    },
    parse: function (jsonStr) {
        var j = JSON.parse(jsonStr);
        var cipherParams = CryptoJS.lib.CipherParams.create({ ciphertext: CryptoJS.enc.Base64.parse(j.ct) });
        if (j.iv) {
            cipherParams.iv = CryptoJS.enc.Hex.parse(j.iv);
        }
        if (j.s) {
            cipherParams.salt = CryptoJS.enc.Hex.parse(j.s);
        }
        return cipherParams;
    }
};