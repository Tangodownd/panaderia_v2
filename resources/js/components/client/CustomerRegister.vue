<template>
  <div class="container py-5">
    <h2 class="mb-4">Crear cuenta</h2>
    <form @submit.prevent="submit">
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input v-model="name" class="form-control" required />
      </div>
      <div class="mb-3">
        <label class="form-label">Correo</label>
        <input v-model="email" type="email" class="form-control" required />
      </div>
      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input v-model="password" type="password" class="form-control" required minlength="6" />
      </div>
      <button class="btn btn-brown" :disabled="loading">{{ loading ? '...' : 'Crear cuenta' }}</button>
      <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
    </form>
  </div>
</template>

<script>
import axios from "@/axios-config"
import { eventBus } from "../../services/event-bus"

export default {
  name: "CustomerRegister",
  data: () => ({ name: "", email: "", password: "", loading: false, error: "" }),
  methods: {
    async submit() {
      this.loading = true; this.error = ""
      try {
        const { data } = await axios.post("/api/customer/register", {
          name: this.name, email: this.email, password: this.password
        })
        // Guardar token y usuario, igual que login
        localStorage.setItem("auth_token", data.token)
        localStorage.setItem("user", JSON.stringify(data.user))
        axios.defaults.headers.common["Authorization"] = `Bearer ${data.token}`
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
