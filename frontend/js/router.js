/*
 * A small hash-based router for the single-page application.
 *
 * URLs look like  index.html#/houses/norway?ref=home
 * Only the part after "#" changes when the user clicks a link, so the
 * browser never reloads the page. On every change we:
 *   1. find the route that matches the path,
 *   2. load its HTML fragment from views/ (cached after the first load),
 *   3. put the fragment into <main>,
 *   4. run the route's init() function to fill in data and add events.
 *
 * A route looks like:
 *   { path: '/houses/:slug', view: 'house-detail', title: 'Model', init(ctx) {...} }
 * ":slug" is a parameter; its value is passed to init() as ctx.params.slug.
 * init() may return { title } to override the page title, or
 * { notFound: true } to show the "page not found" view instead.
 */

const fragmentCache = new Map();

async function loadFragment(name) {
  if (!fragmentCache.has(name)) {
    const response = await fetch(`views/${name}.html`);
    if (!response.ok) {
      throw new Error(`Could not load view "${name}" (HTTP ${response.status})`);
    }
    fragmentCache.set(name, await response.text());
  }
  return fragmentCache.get(name);
}

function parseHash(hash) {
  const raw = hash.replace(/^#/, '');
  const [pathPart, queryPart = ''] = raw.split('?');
  const segments = pathPart.split('/').filter(Boolean);
  return { segments, path: `/${segments.join('/')}`, query: new URLSearchParams(queryPart) };
}

function matchRoute(routes, segments) {
  for (const route of routes) {
    const routeSegments = route.path.split('/').filter(Boolean);
    if (routeSegments.length !== segments.length) continue;

    const params = {};
    const isMatch = routeSegments.every((part, i) => {
      if (part.startsWith(':')) {
        params[part.slice(1)] = decodeURIComponent(segments[i]);
        return true;
      }
      return part === segments[i];
    });

    if (isMatch) return { route, params };
  }
  return null;
}

export function startRouter({ outlet, routes, notFound, siteName, onNavigate }) {
  let latestNavigation = 0;
  let isFirstRender = true;

  async function showView(route, context) {
    const html = await loadFragment(route.view);
    outlet.innerHTML = html;
    return route.init ? (route.init(context) ?? {}) : {};
  }

  async function render() {
    // Ignore plain anchors such as "#app"; only "#/..." are routes.
    if (location.hash && !location.hash.startsWith('#/')) return;

    const navigationId = ++latestNavigation;
    const { segments, path, query } = parseHash(location.hash);
    const match = matchRoute(routes, segments);
    let route = match ? match.route : notFound;
    const context = { outlet, params: match ? match.params : {}, query };

    let result;
    try {
      await loadFragment(route.view);
      // If the user clicked another link while this view was loading, stop.
      if (navigationId !== latestNavigation) return;
      result = await showView(route, context);
      if (result.notFound) {
        route = notFound;
        result = await showView(route, context);
      }
    } catch (error) {
      console.error(error);
      outlet.innerHTML = `
        <section class="container section message-page">
          <h1>Something went wrong</h1>
          <p>This page could not be loaded. Make sure the site is opened through a local web server.</p>
          <a class="btn btn--primary" href="#/">Back to home</a>
        </section>`;
      result = { title: 'Error' };
    }

    const title = result.title ?? route.title;
    document.title = title ? `${title} | ${siteName}` : siteName;
    onNavigate?.({ path, segments });

    // "?section=compare" scrolls to the element with id="compare"; otherwise start at the top.
    const section = query.get('section');
    const target = section ? document.getElementById(section) : null;
    if (target) {
      target.scrollIntoView();
    } else {
      window.scrollTo(0, 0);
    }
    // Move keyboard/screen-reader focus to the new page heading (not on first load).
    if (!isFirstRender) {
      const heading = outlet.querySelector('h1');
      if (heading) {
        heading.setAttribute('tabindex', '-1');
        heading.focus({ preventScroll: true });
      }
    }
    isFirstRender = false;
  }

  window.addEventListener('hashchange', render);
  render();
}
