/**
 * @file
 * Copies the callout's shell commands to the clipboard.
 */
((Drupal, once) => {
  Drupal.behaviors.fdDemoCopy = {
    attach(context) {
      once('fd-demo-copy', '[data-fd-demo-copy]', context).forEach((button) => {
        const label = button.querySelector('span');
        const original = label.textContent;
        button.addEventListener('click', async () => {
          const code = button.parentElement.querySelector('code');
          try {
            await navigator.clipboard.writeText(code.textContent);
          } catch (e) {
            // No clipboard (plain http, old browser): select the text instead.
            const range = document.createRange();
            range.selectNodeContents(code);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            return;
          }
          label.textContent = button.dataset.copiedLabel;
          button.classList.add('is-copied');
          setTimeout(() => {
            label.textContent = original;
            button.classList.remove('is-copied');
          }, 1600);
        });
      });
    },
  };
})(Drupal, once);
