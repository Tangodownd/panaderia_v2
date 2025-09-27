<template>
  <div class="container py-5">
    <h2 class="mb-4">Iniciar sesión</h2>
    <form @submit.prevent="submit">
      <div class="mb-3">
        <label class="form-label">Correo</label>
        <input v-model="email" type="email" class="form-control" required />
      </div>
      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input v-model="password" type="password" class="form-control" required />
      </div>
      <div class="form-check mb-3">
        <input v-model="remember" type="checkbox" class="form-check-input" id="remember">
        <label class="form-check-label" for="remember">Recordarme</label>
      </div>
      <button class="btn btn-brown" :disabled="loading">{{ loading ? '...' : 'Entrar' }}</button>
      <p class="mt-3">¿No tienes cuenta? <router-link :to="{name:'customerRegister'}">Regístrate</router-link></p>
      <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
    </form>
  </div>
</template>

<script>
import axios from "@/axios-config"
import { eventBus } from "../../services/event-bus"

export default {
  name: "CustomerLogin",
  data: () => ({ email: "", password: "", remember: true, loading: false, error: "" }),
  methods: {
async submit() {
      this.loading = true
      this.error = ""
      try {
        const { data } = await axios.post("/api/login", {
          email: this.email,
          password: this.password,
          remember: this.remember,
        })

        // Guardar credenciales
        localStorage.setItem("auth_token", data.token)
        localStorage.setItem("user", JSON.stringify(data.user))
        axios.defaults.headers.common["Authorization"] = `Bearer ${data.token}`

        // 🔔 Notificar a ClientApp que cambió el estado de auth
        eventBus.emit("auth:changed", true)

        // Redirigir al home
        this.$router.push({ name: "home" })
      } catch (e) {
        this.error = e?.response?.data?.message || "Error de autenticación"
      } finally {
        this.loading = false
      }
    },
  }
}
</script>
