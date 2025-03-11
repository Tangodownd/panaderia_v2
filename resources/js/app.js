require('./bootstrap');
import { createApp } from 'vue';

import App from './components/App.vue';

// Import Axios
import VueAxios from 'vue-axios';
import axios from 'axios';

// Import and configure Vue Router
import { createRouter, createWebHistory } from 'vue-router';
import { routes } from './routes';
import $ from 'jquery';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'datatables.net';

const router = createRouter({
    history: createWebHistory(),
    routes: routes
});

// Create and mount the Vue app
const app = createApp(App);
app.use(router);
app.use(VueAxios, axios);
app.mount('#app');
// Inicializar DataTables
$(document).ready(function() {
    $('#example').DataTable();
});