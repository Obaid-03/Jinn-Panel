// Second sign-in step: switch between the authenticator code and a recovery code.
(function () {
    'use strict';
    var code = document.querySelector('[data-tf="code"]');
    var rec = document.querySelector('[data-tf="recovery"]');
    var sw = document.querySelector('[data-tf-switch]');
    if (!code || !rec || !sw) return;
    sw.addEventListener('click', function () {
        var toRecovery = !code.classList.contains('js-hide');
        code.classList.toggle('js-hide', toRecovery);
        rec.classList.toggle('js-hide', !toRecovery);
        code.querySelector('input:not([type=hidden])').required = !toRecovery;
        rec.querySelector('input:not([type=hidden])').required = toRecovery;
        sw.textContent = toRecovery ? 'Use the authenticator app' : 'Use a recovery code';
        (toRecovery ? rec : code).querySelector('input:not([type=hidden])').focus();
    });
})();
