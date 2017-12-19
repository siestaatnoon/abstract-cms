var isRecatchaVerified = false;
var recaptchaId = null;

var recaptchaReset = function() {
    grecaptcha.reset(recaptchaId);
    isRecatchaVerified = false;
};

var recaptchaVerify = function() {
    $('#recaptcha').parent('label').removeClass('error');
    isRecatchaVerified = true;
};

var recaptchaCallback = function() {
    var rcId = 'recaptcha';
    recaptchaId = grecaptcha.render(rcId, {
        'sitekey' : $('#' + rcId).data('sitekey'),
        'callback' : recaptchaVerify,
        'expired-callback' : recaptchaReset
    });
};
