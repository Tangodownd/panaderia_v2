<template>
    <div class="admin-users">
      <div class="row">
        <div class="col-12 mb-4">
          <h1 class="text-brown">Gestión de Administradores</h1>
          <p class="text-muted">Administra los usuarios con acceso al panel de administración</p>
        </div>
      </div>
  
      <div class="row">
        <div class="col-md-6">
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-brown text-white">
              <h5 class="mb-0">Administradores Actuales</h5>
            </div>
            <div class="card-body">
              <div v-if="loading.admins" class="text-center py-3">
                <div class="spinner-border text-brown" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
              </div>
              <div v-else-if="admins.length === 0" class="text-center py-3">
                <p class="text-muted mb-0">No hay administradores registrados</p>
              </div>
              <ul v-else class="list-group list-group-flush">
                <li v-for="admin in admins" :key="admin.id" class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-0 text-brown">{{ admin.name }}</h6>
                    <small class="text-muted">{{ admin.email }}</small>
                  </div>
                  <button 
                    @click="confirmDelete(admin)" 
                    class="btn btn-sm btn-outline-danger"
                    :disabled="admin.id === currentUserId"
                    :title="admin.id === currentUserId ? 'No puedes eliminar tu propia cuenta' : 'Eliminar administrador'"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>
  
        <div class="col-md-6">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-brown text-white">
              <h5 class="mb-0">Registrar Nuevo Administrador</h5>
            </div>
            <div class="card-body">
              <div v-if="successMessage" class="alert alert-success" role="alert">
                {{ successMessage }}
              </div>
              <div v-if="error" class="alert alert-danger" role="alert">
                {{ error }}
              </div>
              <form @submit.prevent="registerAdmin">
                <div class="mb-3">
                  <label for="name" class="form-label text-brown">Nombre</label>
                  <input 
                    type="text" 
                    class="form-control border-brown" 
                    id="name" 
                    v-model="form.name" 
                    required
                  >
                  <div v-if="errors.name" class="text-danger mt-1">{{ errors.name[0] }}</div>
                </div>
                <div class="mb-3">
                  <label for="email" class="form-label text-brown">Correo Electrónico</label>
                  <input 
                    type="email" 
                    class="form-control border-brown" 
                    id="email" 
                    v-model="form.email" 
                    required
                  >
                  <div v-if="errors.email" class="text-danger mt-1">{{ errors.email[0] }}</div>
                </div>
                <div class="mb-3">
                  <label for="password" class="form-label text-brown">Contraseña</label>
                  <input 
                    type="password" 
                    class="form-control border-brown" 
                    id="password" 
                    v-model="form.password" 
                    required
                  >
                  <div v-if="errors.password" class="text-danger mt-1">{{ errors.password[0] }}</div>
                </div>
                <div class="mb-4">
                  <label for="password_confirmation" class="form-label text-brown">Confirmar Contraseña</label>
                  <input 
                    type="password" 
                    class="form-control border-brown" 
                    id="password_confirmation" 
                    v-model="form.password_confirmation" 
                    required
                  >
                </div>
                <div class="d-grid">
                  <button 
                    type="submit" 
                    class="btn btn-brown"
                    :disabled="loading.form"
                  >
                    <span v-if="loading.form" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ loading.form ? 'Registrando...' : 'Registrar Administrador' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Modal de confirmación para eliminar -->
      <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-danger text-white">
              <h5 class="modal-title">Confirmar Eliminación</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p>¿Estás seguro de que deseas eliminar al administrador <strong>{{ selectedAdmin.name }}</strong>?</p>
              <p class="mb-0 text-danger">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button 
                type="button" 
                class="btn btn-danger" 
                @click="deleteAdmin"
                :disabled="loading.delete"
              >
                <span v-if="loading.delete" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                {{ loading.delete ? 'Eliminando...' : 'Eliminar' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, reactive, onMounted } from 'vue';
  import axios from 'axios';
  
  export default {
    setup() {
      const admins = ref([]);
      const currentUserId = ref(null);
      const selectedAdmin = ref({});
      const successMessage = ref('');
      const error = ref('');
      const errors = ref({});
      
      const form = reactive({
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      });
      
      const loading = reactive({
        admins: true,
        form: false,
        delete: false
      });
  
      const fetchAdmins = async () => {
        loading.admins = true;
        try {
          const response = await axios.get('/api/admin/users');
          admins.value = response.data;
        } catch (err) {
          console.error('Error al cargar administradores:', err);
          error.value = 'Error al cargar la lista de administradores';
        } finally {
          loading.admins = false;
        }
      };
  
      const registerAdmin = async () => {
        loading.form = true;
        error.value = '';
        errors.value = {};
        successMessage.value = '';
        
        try {
          const response = await axios.post('/api/admin/users', form);
          successMessage.value = response.data.message;
          
          // Limpiar formulario
          form.name = '';
          form.email = '';
          form.password = '';
          form.password_confirmation = '';
          
          // Recargar lista de administradores
          fetchAdmins();
        } catch (err) {
          console.error('Error al registrar administrador:', err);
          
          if (err.response && err.response.data) {
            if (err.response.data.message) {
              error.value = err.response.data.message;
            }
            
            if (err.response.data.errors) {
              errors.value = err.response.data.errors;
            }
          } else {
            error.value = 'Error al registrar el administrador';
          }
        } finally {
          loading.form = false;
        }
      };
  
      const confirmDelete = (admin) => {
        selectedAdmin.value = admin;
        
        // Mostrar modal de confirmación
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
      };
  
      const deleteAdmin = async () => {
        loading.delete = true;
        
        try {
          const response = await axios.delete(`/api/admin/users/${selectedAdmin.value.id}`);
          
          // Cerrar modal
          const modalElement = document.getElementById('deleteModal');
          const modal = bootstrap.Modal.getInstance(modalElement);
          modal.hide();
          
          // Mostrar mensaje de éxito
          successMessage.value = response.data.message;
          
          // Recargar lista de administradores
          fetchAdmins();
        } catch (err) {
          console.error('Error al eliminar administrador:', err);
          
          if (err.response && err.response.data && err.response.data.message) {
            error.value = err.response.data.message;
          } else {
            error.value = 'Error al eliminar el administrador';
          }
          
          // Cerrar modal
          const modalElement = document.getElementById('deleteModal');
          const modal = bootstrap.Modal.getInstance(modalElement);
          modal.hide();
        } finally {
          loading.delete = false;
        }
      };
  
      const getCurrentUser = () => {
        const userData = localStorage.getItem('user');
        if (userData) {
          try {
            const user = JSON.parse(userData);
            currentUserId.value = user.id;
          } catch (e) {
            console.error('Error parsing user data:', e);
          }
        }
      };
  
      onMounted(() => {
        fetchAdmins();
        getCurrentUser();
      });
  
      return {
        admins,
        currentUserId,
        selectedAdmin,
        form,
        loading,
        error,
        errors,
        successMessage,
        registerAdmin,
        confirmDelete,
        deleteAdmin
      };
    }
  };
  </script>
  
  <style scoped>
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
  .bg-brown {
    background-color: #8B4513;
  }
  </style>
  
  