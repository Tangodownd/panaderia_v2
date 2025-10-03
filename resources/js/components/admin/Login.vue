<template>
    <div class="container-fluid py-5 bg-beige min-vh-100 d-flex align-items-center">
      <div class="row justify-content-center w-100">
        <div class="col-md-6 col-lg-4">
          <div class="card border-0 shadow-lg">
            <div class="card-header bg-brown text-white py-3">
              <h4 class="mb-0 text-center">Acceso Administrativo</h4>
            </div>
            <div class="card-body p-4">
              <div v-if="error" class="alert alert-danger" role="alert">
                {{ error }}
              </div>
              <form @submit.prevent="login">
                <div class="mb-3">
                  <label for="email" class="form-label text-brown">Correo Electrónico</label>
                  <div class="input-group">
                    <span class="input-group-text bg-brown text-white">
                      <i class="fas fa-envelope"></i>
                    </span>
                    <input 
                      type="email" 
                      class="form-control border-brown" 
                      id="email" 
                      v-model="email" 
                      placeholder="admin@example.com" 
                      required
                    >
                  </div>
                </div>
                <div class="mb-4">
                  <label for="password" class="form-label text-brown">Contraseña</label>
                  <div class="input-group">
                    <span class="input-group-text bg-brown text-white">
                      <i class="fas fa-lock"></i>
                    </span>
                    <input 
                      :type="showPassword ? 'text' : 'password'" 
                      class="form-control border-brown" 
                      id="password" 
                      v-model="password" 
                      placeholder="••••••••" 
                      required
                    >
                    <button 
                      class="btn btn-outline-brown" 
                      type="button"
                      @click="togglePassword"
                    >
                      <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                  </div>
                </div>
                <div class="mb-3 form-check">
                  <input type="checkbox" class="form-check-input" id="remember" v-model="remember">
                  <label class="form-check-label text-brown" for="remember">Recordarme</label>
                </div>
                <div class="d-grid">
                  <button 
                    type="submit" 
                    class="btn btn-brown py-2"
                    :disabled="loading"
                  >
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ loading ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
                  </button>
                </div>
              </form>
            </div>
            <div class="card-footer bg-light-beige py-3 text-center">
              <a href="/" class="text-brown text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i> Volver a la Tienda
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';
  
  export default {
    setup() {
      const router = useRouter();
      const email = ref('');
      const password = ref('');
      const remember = ref(false);
      const loading = ref(false);
      const error = ref('');
      const showPassword = ref(false);
  
      const login = async () => {
        loading.value = true;
        error.value = '';
        
        try {
          const response = await axios.post('/api/login', {
            email: email.value,
            password: password.value,
            remember: remember.value
          });
          
          // Guardar token en localStorage
          localStorage.setItem('auth_token', response.data.token);
          localStorage.setItem('user', JSON.stringify(response.data.user));
          
          // Configurar axios para incluir el token en futuras solicitudes
          axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
          
          // Redirigir al panel de administración
          // Notificar a otras partes (AdminApp) que cambió autenticación
          try { window.dispatchEvent(new Event('auth-changed')) } catch {}
          router.push({ name: 'adminDashboard' });
        } catch (err) {
          console.error('Error de inicio de sesión:', err);
          if (err.response && err.response.data && err.response.data.message) {
            error.value = err.response.data.message;
          } else {
            error.value = 'Error al iniciar sesión. Por favor, inténtelo de nuevo.';
          }
        } finally {
          loading.value = false;
        }
      };
  
      const togglePassword = () => {
        showPassword.value = !showPassword.value;
      };
  
      return {
        email,
        password,
        remember,
        loading,
        error,
        showPassword,
        login,
        togglePassword
      };
    }
  };
  </script>
  
  <style scoped>
  .bg-beige {
    background-color: #F5E6D3;
  }
  .bg-cream {
    background-color: #FFF8E7;
  }
  .bg-light-beige {
    background-color: #F8F0E5;
  }
  .text-brown {
    color: #8B4513;
  }
  .border-brown {
    border-color: #8B4513;
  }
  .btn-brown {
    background-color: #8B4513;
    border-color: #8B4513;
    color: #FFF8E7;
  }
  .btn-brown:hover {
    background-color: #6B3E0A;
    border-color: #6B3E0A;
    color: #FFF8E7;
  }
  .btn-outline-brown {
    color: #8B4513;
    border-color: #8B4513;
    background-color: transparent;
  }
  .btn-outline-brown:hover {
    background-color: #8B4513;
    color: #FFF8E7;
  }
  .bg-brown {
    background-color: #8B4513;
  }
  .form-check-input:checked {
    background-color: #8B4513;
    border-color: #8B4513;
  }
  </style>
  
  