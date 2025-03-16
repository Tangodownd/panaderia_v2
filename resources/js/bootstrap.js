// Asegurarse de que Axios esté configurado correctamente para enviar cookies y CSRF tokens
window.axios = require("axios")
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest"

// Configurar el token CSRF
const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
  window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content
} else {
  console.error("CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token")
}

// Configurar withCredentials para enviar cookies
window.axios.defaults.withCredentials = true

// Añadir interceptor para depurar las solicitudes
window.axios.interceptors.request.use(
  (config) => {
    console.log("Axios Request:", config)
    return config
  },
  (error) => {
    console.error("Axios Request Error:", error)
    return Promise.reject(error)
  },
)

// Añadir interceptor para depurar las respuestas
window.axios.interceptors.response.use(
  (response) => {
    console.log("Axios Response:", response)
    return response
  },
  (error) => {
    console.error("Axios Response Error:", error)
    return Promise.reject(error)
  },
)

