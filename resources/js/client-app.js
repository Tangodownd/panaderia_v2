require('./bootstrap');
import { createApp } from 'vue';

import ClientApp from './components/client/ClientApp.vue';

// Import Axios
import VueAxios from 'vue-axios';
import axios from 'axios';

// Import and configure Vue Router
import router from './routes-client';

// Create and mount the Vue app
const app = createApp(ClientApp);
app.use(router);
app.use(VueAxios, axios);
app.mount('#client-app');