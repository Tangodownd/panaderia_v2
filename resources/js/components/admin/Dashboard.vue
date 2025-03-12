<template>
    <div class="admin-dashboard">
      <!-- Navbar -->
      <nav class="navbar navbar-expand-lg navbar-dark bg-brown shadow-sm">
        <div class="container-fluid">
          <a class="navbar-brand" href="#">
            <i class="fas fa-bread-slice me-2"></i>
            Panadería El Buen Gusto - Admin
          </a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" aria-controls="navbarAdmin" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              <li class="nav-item">
                <router-link :to="{ name: 'adminDashboard' }" class="nav-link" exact-active-class="active">
                  <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </router-link>
              </li>
              <li class="nav-item">
                <router-link :to="{ name: 'mostrarBlogs' }" class="nav-link" active-class="active">
                  <i class="fas fa-box me-1"></i> Productos
                </router-link>
              </li>
              <li class="nav-item">
                <router-link :to="{ name: 'adminCategories' }" class="nav-link" active-class="active">
                  <i class="fas fa-tags me-1"></i> Categorías
                </router-link>
              </li>
              <!-- Opción de menú de Pedidos eliminada -->
              <li class="nav-item">
                <router-link :to="{ name: 'adminUsers' }" class="nav-link" active-class="active">
                  <i class="fas fa-users-cog me-1"></i> Administradores
                </router-link>
              </li>
            </ul>
            <div class="d-flex">
              <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="fas fa-user-circle me-1"></i> {{ user.name || 'Admin' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                  <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-1"></i> Perfil</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><button class="dropdown-item text-danger" @click="logout"><i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión</button></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </nav>
    
      <!-- Main Content -->
      <div class="container-fluid py-4">
        <router-view></router-view>
      </div>
    </div>
    </template>
    
    <script>
    import { ref, onMounted } from 'vue';
    import { useRouter } from 'vue-router';
    import axios from 'axios';
    
    export default {
    setup() {
      const router = useRouter();
      const user = ref({});
    
      const loadUserData = () => {
        const userData = localStorage.getItem('user');
        if (userData) {
          try {
            user.value = JSON.parse(userData);
          } catch (e) {
            console.error('Error parsing user data:', e);
            user.value = {};
          }
        }
      };
    
      const logout = async () => {
        try {
          await axios.post('/api/logout');
        } catch (error) {
          console.error('Error during logout:', error);
        } finally {
          // Limpiar datos de autenticación
          localStorage.removeItem('auth_token');
          localStorage.removeItem('user');
          delete axios.defaults.headers.common['Authorization'];
          
          // Redirigir al login
          router.push({ name: 'adminLogin' });
        }
      };
    
      onMounted(() => {
        loadUserData();
      });
    
      return {
        user,
        logout
      };
    }
    };
    </script>
    
    <style scoped>
    .admin-dashboard {
    min-height: 100vh;
    background-color: #F5E6D3;
    }
    .bg-brown {
    background-color: #8B4513;
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
    </style>
    
    