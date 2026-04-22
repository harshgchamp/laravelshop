// bootstrap.js — HTTP client setup
// Imported once in app.js before Inertia starts.

import axios from 'axios';

// Attach axios to window so third-party scripts or legacy code can reach it.
// Inertia uses its own XHR internally, but axios is available for custom API calls.
window.axios = axios;

// X-Requested-With: XMLHttpRequest tells Laravel this is an AJAX request.
// Laravel uses this header to differentiate JSON/XHR responses from full-page requests
// (e.g., abort(403) returns JSON for XHR but redirects for browser requests).
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
