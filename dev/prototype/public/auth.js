// Sign-in pages: show/hide the password, warn when caps lock is on.
(function () {
    'use strict';
    document.querySelectorAll('[data-reveal]').forEach(function (b) {
        var input = b.parentNode.querySelector('input');
        b.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            b.setAttribute('aria-pressed', show ? 'true' : 'false');
            b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            input.focus();
        });
    });
    document.querySelectorAll('[data-password]').forEach(function (input) {
        var warn = input.closest('.field').querySelector('[data-caps]');
        function check(e) { if (warn && e.getModifierState) warn.hidden = !e.getModifierState('CapsLock'); }
        input.addEventListener('keydown', check);
        input.addEventListener('keyup', check);
        input.addEventListener('blur', function () { if (warn) warn.hidden = true; });
    });
})();
