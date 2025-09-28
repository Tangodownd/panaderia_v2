<template>
  <div class="register-wrap">
    <div class="register-card">
      <div class="brand">
        <i class="fas fa-bread-slice"></i>
      </div>
      <h1 class="title">Crear cuenta</h1>

      <form @submit.prevent="submit" class="form" novalidate>
        <div class="form-floating mb-3">
          <input
            v-model.trim="name"
            type="text"
            class="form-control"
            id="regName"
            placeholder="Tu nombre"
            :class="{'is-invalid': touched.name && !validName}"
            required
          />
          <label for="regName">Nombre</label>
          <div class="invalid-feedback">El nombre es obligatorio.</div>
        </div>

        <div class="form-floating mb-3">
          <input
            v-model.trim="email"
            type="email"
            class="form-control"
            id="regEmail"
            placeholder="email@dominio.com"
            :class="{'is-invalid': touched.email && !validEmail}"
            required
          />
          <label for="regEmail">Correo</label>
          <div class="invalid-feedback">Ingresa un correo válido.</div>
        </div>

        <div class="form-floating mb-3">
          <input
            v-model="password"
            type="password"
            class="form-control"
            id="regPass"
            placeholder="••••••••"
            :class="{'is-invalid': touched.password && !validPassword}"
            minlength="6"
            required
          />
          <label for="regPass">Contraseña</label>
          <div class="invalid-feedback">
            La contraseña debe tener al menos 6 caracteres.
          </div>
        </div>

        <div class="form-floating mb-1">
          <input
            v-model="password2"
            type="password"
            class="form-control"
            id="regPass2"
            placeholder="••••••••"
            :class="{'is-invalid': touched.password2 && !passwordsMatch}"
            required
          />
          <label for="regPass2">Confirmar contraseña</label>
          <div class="invalid-feedback">Las contraseñas no coinciden.</div>
        </div>

        <button class="btn btn-brown w-100 mt-2" :disabled="loading || !formOk">
          {{ loading ? 'Creando cuenta…' : 'Registrarme' }}
        </button>

        <p class="small mt-3 text-center">
          ¿Ya tienes cuenta?
          <router-link :to="{ name: 'customerLogin' }">Inicia sesión</router-link>
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
  name: "CustomerRegister",
  data: () => ({
    name: "",
    email: "",
    password: "",
    password2: "",
    loading: false,
    error: "",
    touched: {
      name: false,
      email: false,
      password: false,
      password2: false,
    },
  }),
  computed: {
    validName() {
      return this.name.trim().length > 0
    },
    validEmail() {
      // email simple
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email.trim())
    },
    validPassword() {
      return (this.password || "").length >= 6
    },
    passwordsMatch() {
      return this.password && this.password === this.password2
    },
    formOk() {
      return this.validName && this.validEmail && this.validPassword && this.passwordsMatch
    },
  },
  methods: {
    markAllTouched() {
      this.touched = { name: true, email: true, password: true, password2: true }
    },
    async submit() {
      this.error = ""
      this.markAllTouched()
      if (!this.formOk) return

      this.loading = true
      try {
        const { data } = await axios.post("/api/customer/register", {
          name: this.name,
          email: this.email,
          password: this.password,
        })

        // Guardar token/usuario y setear Authorization
        localStorage.setItem("auth_token", data.token)
        localStorage.setItem("user", JSON.stringify(data.user))
        axios.defaults.headers.common["Authorization"] = `Bearer ${data.token}`

        // Notificar a la app que cambió el estado de autenticación
        eventBus.emit("auth:changed", true)

        // Ir al home privado
        this.$router.push({ name: "home" })
      } catch (e) {
        // Muestra el mensaje del backend si viene, si no uno genérico
        this.error =
          e?.response?.data?.message ||
          e?.response?.data?.errors?.email?.[0] ||
          "No se pudo completar el registro."
      } finally {
        this.loading = false
      }
    },
  },
  watch: {
    name() { this.touched.name = true },
    email() { this.touched.email = true },
    password() { this.touched.password = true },
    password2() { this.touched.password2 = true },
  },
}
</script>

<style scoped>
.register-wrap{
  min-height: 100vh;
  background: #F5E6D3;           /* bg-beige */
  display:flex; align-items:center; justify-content:center;
  padding: 24px;
}

.register-card{
  width: 100%;
  max-width: 460px;
  background: #FFF8E7;          /* bg-cream */
  border: 1px solid #E3D6C4;
  border-radius: 16px;
  box-shadow: 0 12px 40px rgba(0,0,0,.12);
  padding: 28px 26px;
  color: #4A3728;
}

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

a{ color:#7b4b2a; font-weight:600; }
a:hover{ color:#8B4513; }
</style>
