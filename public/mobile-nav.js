/*
 * Fixes layered on the pre-built public app (its source isn't in this repo):
 *  - Mobile menu: the app hides its nav links below 900px without offering a
 *    menu button. Adds a hamburger button that opens the existing links as a
 *    panel (styled in mobile.css), so the app's own router links keep working.
 *  - Scroll to top on page change: the app keeps the old scroll position, so a
 *    footer link opened the next page near the bottom.
 *  - Tags the app's plain "Loading…" placeholder so mobile.css can replace it
 *    with a quiet, delayed spinner.
 * Re-applies itself whenever the app re-renders.
 */
(function () {
  var pushState = history.pushState;
  history.pushState = function () {
    var before = location.pathname;
    var result = pushState.apply(this, arguments);
    if (location.pathname !== before && !location.hash) {
      // React restores the scroll position around the focused element (the
      // tapped link) after it swaps the page, dragging the new page down to
      // where the old link was. Nothing focused, nothing to restore.
      if (document.activeElement && document.activeElement !== document.body) document.activeElement.blur();
      // The app sets html { scroll-behavior: smooth }, which would turn this
      // into a slow glide that the incoming page's content pushes back down.
      // The browser applies that when it performs the scroll (a frame later),
      // so ask for 'instant' and keep the override until it has happened.
      var root = document.documentElement;
      root.style.scrollBehavior = 'auto';
      try {
        window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
      } catch (e) {
        window.scrollTo(0, 0);
      }
      setTimeout(function () { root.style.scrollBehavior = ''; }, 100);
    }
    return result;
  };

  function closeMenu(nav) {
    nav.classList.remove('mnav-open');
    var btn = nav.querySelector('.mnav-toggle');
    if (btn) {
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'Open menu');
    }
  }

  function tagLoading() {
    var els = document.querySelectorAll('#root .wrap');
    for (var i = 0; i < els.length; i++) {
      if (els[i].textContent.trim() === 'Loading\u2026') els[i].classList.add('page-loading');
    }
  }

  function setup() {
    tagLoading();
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
