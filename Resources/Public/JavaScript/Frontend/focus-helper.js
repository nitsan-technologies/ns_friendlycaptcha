/**
 * Ensures Friendly Captcha "focus" start mode works with TYPO3 forms.
 * The widget listens for focusin on the parent <form>; this adds a fallback via closest('form').
 */
(function () {
  const bindFocusMode = () => {
    document.querySelectorAll('.frc-captcha[data-start="focus"]').forEach((captchaElement) => {
      const form = captchaElement.closest('form');
      if (!form || form.dataset.frcFocusHelper) {
        return;
      }
      form.dataset.frcFocusHelper = '1';

      const startCaptcha = () => {
        const widget = captchaElement.friendlyChallengeWidget;
        if (widget && !widget.hasBeenStarted) {
          widget.start();
        }
      };

      form.addEventListener('focusin', startCaptcha, { once: true, passive: true });
      form.addEventListener(
        'click',
        (event) => {
          if (event.target.closest('input, textarea, select, button, [tabindex]')) {
            startCaptcha();
          }
        },
        { once: true, passive: true }
      );
    });
  };

  const run = () => {
    bindFocusMode();
    window.setTimeout(bindFocusMode, 500);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
