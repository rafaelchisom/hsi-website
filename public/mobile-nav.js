/*
 * Mobile menu for the pre-built public app, which hides its nav links below
 * 900px without offering a menu button. Adds a hamburger button to the nav
 * bar that opens the existing links as a panel (styled in mobile.css), so
 * the app's own router links keep working. Re-attaches itself if the app
 * re-renders the nav.
 */
(function () {
  function closeMenu(nav) {
    nav.classList.remove('mnav-open');
    var btn = nav.querySelector('.mnav-toggle');
    if (btn) {
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'Open menu');
    }
  }

  function setup() {
    var nav = document.querySelector('nav');
    var inner = nav && nav.querySelector('.nav-inner');
    var links = inner && inner.querySelector('.nav-links');
    if (!links || inner.querySelector('.mnav-toggle')) return;

    if (!links.id) links.id = 'site-nav-links';
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'mnav-toggle';
    btn.setAttribute('aria-controls', links.id);
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-label', 'Open menu');
    btn.innerHTML = '<span></span><span></span><span></span>';
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = !nav.classList.contains('mnav-open');
      nav.classList.toggle('mnav-open', open);
      btn.setAttribute('aria-expanded', String(open));
      btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });
    inner.appendChild(btn);

    // Choosing a page closes the menu (the app navigates without a reload).
    links.addEventListener('click', function (e) {
      if (e.target.closest('a')) closeMenu(nav);
    });
  }

  document.addEventListener('click', function (e) {
    var nav = document.querySelector('nav.mnav-open');
    if (nav && !nav.contains(e.target)) closeMenu(nav);
  });
  document.addEventListener('keydown', function (e) {
    var nav = document.querySelector('nav.mnav-open');
    if (nav && e.key === 'Escape') {
      closeMenu(nav);
      var btn = nav.querySelector('.mnav-toggle');
      if (btn) btn.focus();
    }
  });
  window.addEventListener('popstate', function () {
    var nav = document.querySelector('nav.mnav-open');
    if (nav) closeMenu(nav);
  });
  window.matchMedia('(max-width: 900px)').addEventListener('change', function (e) {
    var nav = document.querySelector('nav.mnav-open');
    if (nav && !e.matches) closeMenu(nav);
  });

  var queued = false;
  new MutationObserver(function () {
    if (queued) return;
    queued = true;
    requestAnimationFrame(function () { queued = false; setup(); });
  }).observe(document.documentElement, { childList: true, subtree: true });
  setup();
})();
