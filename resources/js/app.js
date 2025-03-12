require("./bootstrap")
import { createApp } from "vue"

import App from "./components/App.vue"

// Import Axios
import VueAxios from "vue-axios"
import axios from "axios"

// Import and configure Vue Router
import router from "./routes"
import $ from "jquery"

// Create and mount the Vue app
const app = createApp(App)
app.use(router)
app.use(VueAxios, axios)
app.mount("#app")

// Inicializar DataTables (se hará desde el HTML con jQuery)
$(document).ready(() => {
  if ($.fn.DataTable && $("#example").length) {
    $("#example").DataTable()
  }
})

