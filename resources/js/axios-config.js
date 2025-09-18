// resources/js/axios-config.js
import axios from "axios"

axios.defaults.baseURL = window.location.origin
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest"
axios.defaults.headers.common["Accept"] = "application/json"

// CSRF desde <meta name="csrf-token">
const csrf = document.head.querySelector('meta[name="csrf-token"]')
if (csrf) {
  axios.defaults.headers.common["X-CSRF-TOKEN"] = csrf.content
}

// Cookies si usas sesión basada en cookies
axios.defaults.withCredentials = true

// Token JWT si existe
const token = localStorage.getItem("auth_token")
if (token) {
  axios.defaults.headers.common["Authorization"] = `Bearer ${token}`
}

export default axios
