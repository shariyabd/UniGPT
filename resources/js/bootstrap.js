import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Realtime client. Self-initialises only when broadcasting keys are present;
// otherwise the messenger falls back to polling (see resources/js/echo.js).
import './echo';
