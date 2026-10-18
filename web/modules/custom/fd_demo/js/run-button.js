/**
 * @file
 * "Run" buttons: a click plays a short run animation, then follows the link.
 *
 * The button fills like a node executing, turns into a tick, then navigates
 * (or, for an in-page link, scrolls). Modified and middle clicks, and visitors
 * who prefer reduced motion, get the plain link at once.
 */
((Drupal, once) => {
  // Match --ds-fd-demo-run-fill and --ds-fd-demo-run-tick (fd_demo.tokens.yml).
  const RUN_MS = 520;
  const DONE_MS = 220;
  const TICK = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="fd-demo-btn__tick"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>';

  const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function reset(button) {
    button.classList.remove('is-running', 'is-done');
    button.removeAttribute('aria-busy');
    button.querySelectorAll('.fd-demo-btn__ripple, .fd-demo-btn__tick').forEach((el) => el.remove());
  }

  function follow(button) {
    const href = button.getAttribute('href') || '';
    if (href.startsWith('#')) {
      const target = document.querySelector(href);
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.history.pushState(null, '', href);
      }
      window.setTimeout(() => reset(button), 600);
      return;
    }
    window.location.assign(button.href);
  }

  function ripple(button, event) {
    const rect = button.getBoundingClientRect();
    const dot = document.createElement('span');
    dot.className = 'fd-demo-btn__ripple';
    // Keyboard activation reports 0,0: start from the centre instead.
    const x = event.clientX ? event.clientX - rect.left : rect.width / 2;
    const y = event.clientY ? event.clientY - rect.top : rect.height / 2;
    dot.style.setProperty('--x', `${x}px`);
    dot.style.setProperty('--y', `${y}px`);
    button.append(dot);
  }

  Drupal.behaviors.fdDemoRunButton = {
    attach(context) {
      once('fd-demo-run', '.fd-demo-btn--run', context).forEach((button) => {
        button.addEventListener('click', (event) => {
          if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
          }
          if (button.classList.contains('is-running')) {
            event.preventDefault();
            return;
          }
          if (reducedMotion()) {
            return;
          }
          event.preventDefault();
          ripple(button, event);
          button.classList.add('is-running');
          button.setAttribute('aria-busy', 'true');
          window.setTimeout(() => {
            button.classList.add('is-done');
            button.insertAdjacentHTML('afterbegin', TICK);
            window.setTimeout(() => follow(button), DONE_MS);
          }, RUN_MS);
        });
      });
    },
  };

  // Coming back with the Back button restores the page from the cache, button
  // state included: put every button back to rest.
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
      document.querySelectorAll('.fd-demo-btn--run').forEach(reset);
    }
  });
})(Drupal, once);
