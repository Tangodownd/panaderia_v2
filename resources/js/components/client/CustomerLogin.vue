<template>
  <div class="login-wrap">
    <div class="login-card">
      <div class="brand">
        <i class="fas fa-bread-slice"></i>
      </div>
      <h1 class="title">Iniciar sesión</h1>

      <form @submit.prevent="submit" class="form">
        <div class="form-floating mb-3">
          <input v-model="email" type="email" class="form-control" id="loginEmail" placeholder="email@dominio.com" required />
          <label for="loginEmail">Correo</label>
        </div>

        <div class="form-floating mb-2">
          <input v-model="password" type="password" class="form-control" id="loginPass" placeholder="••••••••" required />
          <label for="loginPass">Contraseña</label>
        </div>

        <div class="form-check mb-3">
          <input v-model="remember" class="form-check-input" type="checkbox" id="remember">
          <label class="form-check-label" for="remember">Recordarme</label>
        </div>

        <button class="btn btn-brown w-100" :disabled="loading">
          {{ loading ? 'Entrando…' : 'Entrar' }}
        </button>

        <p class="small mt-3 text-center">
          ¿No tienes cuenta?
          <router-link :to="{ name: 'customerRegister' }">Regístrate</router-link>
        </p>

        <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from "@/axios-config"
import { eventBus } from "../../services/event-bus"

export default {
  name: "CustomerLogin",
  data: () => ({
    email: "",
    password: "",
    remember: true,
    loading: false,
    error: "",
  }),
  methods: {
    async submit() {
      this.loading = true; this.error = ""
      try {
        const { data } = await axios.post("/api/login", {
          email: this.email, password: this.password, remember: this.remember
        })
        localStorage.setItem("auth_token", data.token)
        localStorage.setItem("user", JSON.stringify(data.user))
        axios.defaults.headers.common["Authorization"] = `Bearer ${data.token}`
        eventBus.emit("auth:changed", true)
        this.$router.push({ name: "home" })
      } catch (e) {
        this.error = e?.response?.data?.message || "Error de autenticación"
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
/* Fondo suave en tu paleta */
.login-wrap{
  min-height: 100vh;
  background: #F5E6D3;           /* bg-beige */
  display:flex; align-items:center; justify-content:center;
  padding: 24px;
}

/* Tarjeta centrada */
.login-card{
  width: 100%;
  max-width: 420px;
  background: #FFF8E7;          /* bg-cream */
  border: 1px solid #E3D6C4;
  border-radius: 16px;
  box-shadow: 0 12px 40px rgba(0,0,0,.12);
  padding: 28px 26px;
  color: #4A3728;
}

/* Marca */
.brand{
  width: 64px; height: 64px;
  border-radius: 16px;
  display:grid; place-items:center;
  color: #FFF8E7;
  background: #8B4513;
  margin: 0 auto 12px;
  font-size: 28px;
  box-shadow: 0 8px 20px rgba(139,69,19,.35);
}

.title{
  text-align:center;
  font-size: 1.35rem;
  font-weight: 700;
  color: #8B4513;
  margin-bottom: 16px;
}

.form .form-control{
  border: 1px solid #E3D6C4;
  background: #fff;
  color: #4A3728;
}
.form .form-control:focus{
  border-color: #8B4513;
  box-shadow: 0 0 0 0.2rem rgba(139,69,19,.15);
}

/* Botón principal */
.btn-brown{
  background-color: #8B4513;
  border-color: #8B4513;
  color: #FFF8E7;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 600;
}
.btn-brown:hover{
  background-color: #6B3E0A;
  border-color: #6B3E0A;
}

/* Links */
a{ color:#7b4b2a; font-weight:600; }
a:hover{ color:#8B4513; }
</style>