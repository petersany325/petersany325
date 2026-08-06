# petersany325

## Cursor Cloud specific instructions

This repo currently contains a single static demo: a Persian (RTL) law-firm
sample site under `sample-site/` (`index.html`, `assets/css/style.css`,
`assets/js/main.js`, `assets/img/*.png`). There is no backend, package manager,
build step, automated tests, or linter.

- Run the site: `cd sample-site && python3 -m http.server 8080`, then open
  `http://localhost:8080`. `python3` is preinstalled; the site has no
  dependencies to install.
- There is nothing to build, lint, or unit-test. The "app" is the served static
  files; verify changes by loading the page and exercising the UI (nav toggle,
  scroll reveals, and the consultation form which shows a confirmation on
  submit).
- The contact form is demo-only (`onsubmit="return false;"` plus a JS handler in
  `main.js`); submissions are not sent anywhere.
- The README notes Laravel/Blade may be added later if a real backend is needed;
  as of now none exists.
