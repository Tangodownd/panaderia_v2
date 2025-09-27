// resources/js/client-app.js
import { createApp } from "vue"
import ClientApp from "./components/client/ClientApp.vue"
import router from "./routes-client"

// Axios unificado
import axios from "./axios-config"
import VueAxios from "vue-axios"
import ChatWidget from "./components/client/ChatWidget.vue"

const app = createApp(ClientApp)
app.use(router)
app.use(VueAxios, axios)
app.component("chat-widget", ChatWidget)
app.mount("#client-app")
