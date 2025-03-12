<template>
    <div class="admin-categories">
    <div class="row">
      <div class="col-12 mb-4">
        <h1 class="text-brown">Gestión de Categorías</h1>
        <p class="text-muted">Administra las categorías de productos de la panadería</p>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-brown text-white">
            <h5 class="mb-0">Categorías</h5>
          </div>
          <div class="card-body">
            <div v-if="loading.categories" class="text-center py-3">
              <div class="spinner-border text-brown" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
            </div>
            <div v-else-if="categories.length === 0" class="text-center py-3">
              <p class="text-muted mb-0">No hay categorías registradas</p>
            </div>
            <div v-else class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>

                    <th>Productos</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="category in categories" :key="category.id">
                    <td>{{ category.id }}</td>
                    <td>{{ category.name }}</td>

                    <td>{{ category.products_count || 0 }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <button @click="editCategory(category)" class="btn btn-sm btn-outline-brown">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button @click="confirmDelete(category)" class="btn btn-sm btn-outline-danger" :disabled="category.products_count > 0">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    
      <div class="col-md-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-brown text-white">
            <h5 class="mb-0">{{ isEditing ? 'Editar Categoría' : 'Nueva Categoría' }}</h5>
          </div>
          <div class="card-body">
            <div v-if="successMessage" class="alert alert-success" role="alert">
              {{ successMessage }}
            </div>
            <div v-if="error" class="alert alert-danger" role="alert">
              {{ error }}
            </div>
            <form @submit.prevent="saveCategory">
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

              <div class="d-grid gap-2">
                <button 
                  type="submit" 
                  class="btn btn-brown"
                  :disabled="loading.form"
                >
                  <span v-if="loading.form" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  {{ isEditing ? 'Actualizar Categoría' : 'Crear Categoría' }}
                </button>
                <button 
                  v-if="isEditing" 
                  type="button" 
                  class="btn btn-outline-secondary"
                  @click="cancelEdit"
                >
                  Cancelar
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
            <p>¿Estás seguro de que deseas eliminar la categoría <strong>{{ selectedCategory.name }}</strong>?</p>
            <p class="mb-0 text-danger">Esta acción no se puede deshacer.</p>
            <div v-if="selectedCategory.products_count > 0" class="alert alert-warning mt-3">
              <i class="fas fa-exclamation-triangle me-2"></i>
              No se puede eliminar esta categoría porque tiene {{ selectedCategory.products_count }} productos asociados.
              Primero debes cambiar la categoría de estos productos o eliminarlos.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button 
              type="button" 
              class="btn btn-danger" 
              @click="deleteCategory"
              :disabled="loading.delete || selectedCategory.products_count > 0"
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
      const categories = ref([]);
      const selectedCategory = ref({});
      const successMessage = ref('');
      const error = ref('');
      const errors = ref({});
      const isEditing = ref(false);
      
      const form = reactive({
        id: null,
        name: '',
        description: ''
      });
      
      const loading = reactive({
        categories: true,
        form: false,
        delete: false
      });
    
      const fetchCategories = async () => {
        loading.categories = true;
        try {
          const response = await axios.get('/api/categories');
          categories.value = response.data;
        } catch (err) {
          console.error('Error al cargar categorías:', err);
          error.value = 'Error al cargar la lista de categorías';
        } finally {
          loading.categories = false;
        }
      };
    
      const saveCategory = async () => {
        loading.form = true;
        error.value = '';
        errors.value = {};
        successMessage.value = '';
        
        try {
          let response;
          
          if (isEditing.value) {
            response = await axios.put(`/api/categories/${form.id}`, form);
            successMessage.value = 'Categoría actualizada con éxito';
          } else {
            response = await axios.post('/api/categories', form);
            successMessage.value = 'Categoría creada con éxito';
          }
          
          // Limpiar formulario
          resetForm();
          
          // Recargar lista de categorías
          fetchCategories();
        } catch (err) {
          console.error('Error al guardar categoría:', err);
          
          if (err.response && err.response.data) {
            if (err.response.data.message) {
              error.value = err.response.data.message;
            }
            
            if (err.response.data.errors) {
              errors.value = err.response.data.errors;
            }
          } else {
            error.value = 'Error al guardar la categoría';
          }
        } finally {
          loading.form = false;
        }
      };
    
      const editCategory = (category) => {
        form.id = category.id;
        form.name = category.name;
        form.description = category.description || '';
        isEditing.value = true;
      };
    
      const cancelEdit = () => {
        resetForm();
      };
    
      const resetForm = () => {
        form.id = null;
        form.name = '';
        form.description = '';
        isEditing.value = false;
      };
    
      const confirmDelete = (category) => {
        selectedCategory.value = category;
        
        // Mostrar modal de confirmación
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
      };
    
      const deleteCategory = async () => {
        loading.delete = true;
        error.value = '';
        
        try {
          const response = await axios.delete(`/api/categories/${selectedCategory.value.id}`);
          
          // Cerrar modal
          const modalElement = document.getElementById('deleteModal');
          const modal = bootstrap.Modal.getInstance(modalElement);
          if (modal) modal.hide();
          
          // Mostrar mensaje de éxito
          successMessage.value = response.data.message || 'Categoría eliminada con éxito';
          
          // Recargar lista de categorías
          fetchCategories();
        } catch (err) {
          console.error('Error al eliminar categoría:', err);
          
          // Cerrar modal
          const modalElement = document.getElementById('deleteModal');
          const modal = bootstrap.Modal.getInstance(modalElement);
          if (modal) modal.hide();
          
          if (err.response && err.response.data && err.response.data.message) {
            error.value = err.response.data.message;
          } else {
            error.value = 'Error al eliminar la categoría';
          }
        } finally {
          loading.delete = false;
        }
      };
    
      onMounted(() => {
        fetchCategories();
      });
    
      return {
        categories,
        selectedCategory,
        form,
        loading,
        error,
        errors,
        successMessage,
        isEditing,
        saveCategory,
        editCategory,
        cancelEdit,
        confirmDelete,
        deleteCategory
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
    .btn-outline-brown {
    color: #8B4513;
    border-color: #8B4513;
    }
    .btn-outline-brown:hover {
    background-color: #8B4513;
    color: #FFF8E7;
    }
    .bg-brown {
    background-color: #8B4513;
    }
    </style>
    
    