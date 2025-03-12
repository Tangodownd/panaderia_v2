import { createApp } from "vue"
import AdminApp from "./components/admin/AdminApp.vue"
import router from "./routes-admin"
import axios from "axios"

// Configurar axios
axios.defaults.baseURL = window.location.origin
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest"
axios.defaults.headers.common["Accept"] = "application/json"

// Configurar el token CSRF
const csrfToken = document.head.querySelector('meta[name="csrf-token"]')
if (csrfToken) {
  axios.defaults.headers.common["X-CSRF-TOKEN"] = csrfToken.content
}

// Interceptor para manejar errores de autenticación
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem("auth_token")
      localStorage.removeItem("user")
      delete axios.defaults.headers.common["Authorization"]
      router.push({ name: "adminLogin" })
    }
    return Promise.reject(error)
  },
)

// Inicializar token si existe
const token = localStorage.getItem("auth_token")
if (token) {
  axios.defaults.headers.common["Authorization"] = `Bearer ${token}`
}

// Crear la aplicación Vue
const app = createApp(AdminApp)

// Usar el router
app.use(router)

// Montar la aplicación
app.mount("#admin-app")

// Para debugging
if (process.env.NODE_ENV === "development") {
  console.log("Vue app mounted")
  window.app = app
}

