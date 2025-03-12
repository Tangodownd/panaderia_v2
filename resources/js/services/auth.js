import axios from "axios"

const TOKEN_KEY = "auth_token"
const USER_KEY = "user"

export default {
  login(email, password, remember) {
    return axios
      .post("/api/login", {
        email,
        password,
        remember,
      })
      .then((response) => {
        this.setToken(response.data.token)
        this.setUser(response.data.user)
        return response.data.user
      })
  },

  logout() {
    return axios.post("/api/logout").then(() => {
      this.clearAuth()
    })
  },

  setToken(token) {
    localStorage.setItem(TOKEN_KEY, token)
    axios.defaults.headers.common["Authorization"] = `Bearer ${token}`
  },

  setUser(user) {
    localStorage.setItem(USER_KEY, JSON.stringify(user))
  },

  getToken() {
    return localStorage.getItem(TOKEN_KEY)
  },

  getUser() {
    const userStr = localStorage.getItem(USER_KEY)
    if (!userStr) return null
    try {
      return JSON.parse(userStr)
    } catch (e) {
      return null
    }
  },

  clearAuth() {
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
    delete axios.defaults.headers.common["Authorization"]
  },

  isAuthenticated() {
    return !!this.getToken()
  },

  isAdmin() {
    const user = this.getUser()
    return user && user.role === "admin"
  },

  initializeAuth() {
    const token = this.getToken()
    if (token) {
      axios.defaults.headers.common["Authorization"] = `Bearer ${token}`
      return true
    }
    return false
  },
}

