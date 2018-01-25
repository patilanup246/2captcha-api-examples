// Solving KeyCaptcha from broswer's console (tested in Chrome only)
// To start KeyCaptcha solving open the webpage, then open console, paste the code, hit Enter and call sendCaptcha();
// And don't forget to set your API key:

var apiKey = "YOUR_API_KEY";
 
function removeElements(){
    document.getElementById("div_for_keycaptcha").remove();
    var filename = "http://backs.keycaptcha.com/swfs/cap.js";
    var tags = document.getElementsByTagName('script');
    for (var i = tags.length; i >= 0; i--){
    if (tags[i] && tags[i].getAttribute('src') != null && tags[i].getAttribute('src').indexOf(filename) != -1)
        tags[i].parentNode.removeChild(tags[i]);
    }
 }
 
function httpGet(theUrl){
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.open( "GET", theUrl, false );
    xmlHttp.send( null );
    return xmlHttp.responseText;
}
 
 
function sendCaptcha(){
    var keyCaptchaParams = "https://2captcha.com/in.php?key=" + apiKey + "&s_s_c_user_id=" + s_s_c_user_id + "&s_s_c_session_id=" + s_s_c_session_id + "&s_s_c_web_server_sign=" + s_s_c_web_server_sign + "&s_s_c_web_server_sign2=" + s_s_c_web_server_sign2 + "&method=keycaptcha" + "&pageurl=" + window.location.href + "&header_acao=1&json=1";
    var captchaId = JSON.parse(httpGet(keyCaptchaParams)).request;
    var keyCaptchaResponse = "https://2captcha.com/res.php?key=" + apiKey + "&action=get&header_acao=1&json=1&id=" + captchaId;
   
    var intId = setInterval(function(){
        var captchaAnswer = JSON.parse(httpGet(keyCaptchaResponse));
        console.log(captchaAnswer);
        if ( captchaAnswer.status === 1 ) {
            clearInterval(intId);
            var answer = captchaAnswer.request;
            removeElements();
            document.getElementById(s_s_c_captcha_field_id).value=answer;
//           document.getElementById("postbut").click();
        }
        else if ( captchaAnswer.request === "ERROR_CAPTCHA_UNSOLVABLE" ) {
            clearInterval(intId);
            console.log("Captcha is unsolvable");
        }
    },5000);
   
}
