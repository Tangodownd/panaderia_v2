import auth from "../services/auth"

export default {
  guest(to, from, next) {
    if (!auth.isAuthenticated()) {
      next()
    } else {
      next({ name: "adminDashboard" })
    }
  },

  auth(to, from, next) {
    if (auth.isAuthenticated()) {
      next()
    } else {
      next({ name: "adminLogin" })
    }
  },

  admin(to, from, next) {
    if (auth.isAuthenticated() && auth.isAdmin()) {
      next()
    } else {
      next({ name: "adminLogin" })
    }
  },
}

