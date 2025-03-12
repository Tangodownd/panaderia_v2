<template>
  <div class="container-fluid py-4 bg-beige text-brown">
    <div class="row mb-4">
      <div class="col-12">
        <div class="card bg-light-beige text-brown border-brown">
          <div class="card-header bg-brown text-white">
            <h2 class="card-title mb-0">Editar Producto</h2>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center py-5">
              <div class="spinner-border text-brown" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
              <p class="mt-2">Cargando datos del producto...</p>
            </div>
            
            <form v-else @submit.prevent="actualizarProducto" enctype="multipart/form-data">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="name" class="form-label">Nombre del Producto</label>
                    <input type="text" class="form-control border-brown" id="name" v-model="producto.name" required>
                  </div>
                  
                  <div class="mb-3">
                    <label for="category_id" class="form-label">Categoría</label>
                    <select class="form-select border-brown" id="category_id" v-model="producto.category_id" required>
                      <option value="">Seleccionar categoría</option>
                      <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                      </option>
                    </select>
                  </div>
                  
                  <div class="mb-3">
                    <label for="price" class="form-label">Precio</label>
                    <div class="input-group">
                      <span class="input-group-text border-brown">$</span>
                      <input type="number" step="0.01" min="0" class="form-control border-brown" id="price" v-model="producto.price" required>
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <label for="discount" class="form-label">Descuento (%)</label>
                    <div class="input-group">
                      <input type="number" step="0.01" min="0" max="100" class="form-control border-brown" id="discount" v-model="producto.discount">
                      <span class="input-group-text border-brown">%</span>
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <label for="stock" class="form-label">Stock</label>
                    <input type="number" min="0" class="form-control border-brown" id="stock" v-model="producto.stock" required>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control border-brown" id="description" rows="5" v-model="producto.description"></textarea>
                  </div>
                  
                  <div class="mb-3">
                    <label for="image" class="form-label">Imagen del Producto</label>
                    <input type="file" class="form-control border-brown" id="image" @change="handleImageUpload" accept="image/*">
                    <div class="form-text">Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 2MB</div>
                  </div>
                  
                  <div v-if="imagePreview || producto.image" class="mb-3">
                    <label class="form-label">Imagen actual</label>
                    <div class="border p-2 rounded">
                      <img :src="imagePreview || getProductImage(producto.image)" class="img-fluid rounded" alt="Imagen del producto">
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="d-flex justify-content-between mt-4">
                <router-link :to="{ name: 'mostrarBlogs' }" class="btn btn-secondary">
                  <i class="fas fa-arrow-left me-2"></i>Volver
                </router-link>
                <button type="submit" class="btn btn-brown" :disabled="isSubmitting">
                  <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  <i v-else class="fas fa-save me-2"></i>Actualizar Producto
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const categories = ref([]);
const imagePreview = ref(null);
const isSubmitting = ref(false);
const loading = ref(true);

const producto = ref({
  id: null,
  name: '',
  description: '',
  price: 0,
  discount: 0,
  stock: 0,
  category_id: '',
  image: null
});

const getProductImage = (image) => {
  if (!image) return null;

  // Check if the image is already a full URL
  if (image.startsWith('http')) {
    return image;
  }

  // If it's not a full URL, construct it based on the current origin
  return `${window.location.origin}/storage/${image}`;
};

const fetchCategories = async () => {
  try {
    const response = await axios.get('/api/categories');
    categories.value = response.data;
  } catch (error) {
    console.error('Error al cargar categorías:', error);
  }
};

// Cambiar la función fetchProducto para usar /api/products en lugar de /api/blog
const fetchProducto = async () => {
  loading.value = true;
  try {
    const response = await axios.get(`/api/products/${route.params.id}`);
    producto.value = response.data;
  } catch (error) {
    console.error('Error al cargar producto:', error);
    alert('Error al cargar los datos del producto');
    router.push({ name: 'mostrarBlogs' });
  } finally {
    loading.value = false;
  }
};

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (!file) {
    imagePreview.value = null;
    producto.value.newImage = null;
    return;
  }
  
  // Validar tipo y tamaño
  const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
  const maxSize = 2 * 1024 * 1024; // 2MB
  
  if (!validTypes.includes(file.type)) {
    alert('Por favor, seleccione un formato de imagen válido (JPG, PNG, GIF)');
    event.target.value = '';
    imagePreview.value = null;
    producto.value.newImage = null;
    return;
  }
  
  if (file.size > maxSize) {
    alert('La imagen es demasiado grande. El tamaño máximo es 2MB');
    event.target.value = '';
    imagePreview.value = null;
    producto.value.newImage = null;
    return;
  }
  
  // Guardar archivo para envío
  producto.value.newImage = file;
  
  // Crear vista previa
  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

// Cambiar la función actualizarProducto para usar /api/products en lugar de /api/blog
const actualizarProducto = async () => {
  isSubmitting.value = true;
  
  try {
    // Crear FormData para enviar archivos
    const formData = new FormData();
    formData.append('name', producto.value.name);
    formData.append('description', producto.value.description || '');
    formData.append('price', producto.value.price);
    formData.append('discount', producto.value.discount || 0);
    formData.append('stock', producto.value.stock);
    formData.append('category_id', producto.value.category_id);
    formData.append('_method', 'PUT'); // Para simular PUT con FormData
    
    if (producto.value.newImage) {
      formData.append('image', producto.value.newImage);
    }
    
    await axios.post(`/api/products/${producto.value.id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    
    alert('Producto actualizado con éxito');
    router.push({ name: 'mostrarBlogs' });
  } catch (error) {
    console.error('Error al actualizar producto:', error);
    let errorMessage = 'Error al actualizar el producto';
    
    if (error.response && error.response.data && error.response.data.message) {
      errorMessage = error.response.data.message;
    }
    
    alert(errorMessage);
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(() => {
  fetchCategories();
  fetchProducto();
});
</script>

<style scoped>
.bg-brown {
  background-color: #8D6E63;
}
.text-brown {
  color: #5D4037;
}
.border-brown {
  border-color: #8D6E63;
}
.btn-brown {
  background-color: #8D6E63;
  border-color: #8D6E63;
  color: #F5E6D3;
}
.btn-brown:hover {
  background-color: #795548;
  border-color: #795548;
  color: #F5E6D3;
}
.bg-beige {
  background-color: #F5E6D3;
}
.bg-light-beige {
  background-color: #D7CCC8;
}
</style>

