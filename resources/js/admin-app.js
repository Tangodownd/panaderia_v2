// resources/js/admin-app.js
import { createApp } from "vue"
import AdminApp from "./components/admin/AdminApp.vue"
import router from "./routes-admin"
import auth from "./services/auth"

// Usa la misma instancia de Axios en toda la app
import axios from "./axios-config"

// 1) Inicializa el estado de autenticación (recupera token/usuario si aplica)
auth.initializeAuth?.()

// 2) Aplica el token actual (si existe) al header Authorization de Axios
const token = auth.getToken?.()
if (token) {
  axios.defaults.headers.common["Authorization"] = `Bearer ${token}`
} else {
  delete axios.defaults.headers.common["Authorization"]
}

// 3) (Opcional pero recomendado) Interceptor de REQUEST para adjuntar siempre el token
axios.interceptors.request.use(
  (config) => {
    const t = auth.getToken?.()
    if (t) {
      config.headers = config.headers || {}
      config.headers["Authorization"] = `Bearer ${t}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// 4) Interceptor de RESPONSE 401 → cierra sesión con el servicio y redirige a login
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401) {
      // Centraliza el cierre de sesión en el servicio:
      // - limpia token/usuario
      // - quita Authorization de axios
      // - cualquier side-effect (cookies, storage seguro, etc.)
      auth.logout?.()

      // Asegura limpiar el header por si acaso
      delete axios.defaults.headers.common["Authorization"]

      // Redirige al login del admin
      router.push({ name: "adminLogin" })
    }
    return Promise.reject(error)
  }
)

const app = createApp(AdminApp)
app.use(router)
app.mount("#admin-app")
