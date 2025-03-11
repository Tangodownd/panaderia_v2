<template>
  <div class="container-fluid py-4 bg-beige text-brown">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card bg-cream text-brown border-brown">
          <div class="card-header bg-brown text-cream">
            <h4 class="text-white">Añadir Nuevo Producto</h4>
          </div>
          <div class="card-body">
            <form @submit.prevent="crearBlog" enctype="multipart/form-data">
              <div class="mb-3">
                <label for="titulo" class="form-label text-brown">Nombre del Producto</label>
                <input type="text" v-model="blog.titulo" class="form-control bg-beige text-brown border-brown" id="titulo" required>
              </div>
              <div class="mb-3">
                <label for="contenido" class="form-label text-brown">Descripción del Producto</label>
                <textarea v-model="blog.contenido" class="form-control bg-beige text-brown border-brown" id="contenido" rows="3" required></textarea>
              </div>
              <div class="row mb-3">
              <div class="col-md-2">
                <label for="categoria" class="form-label text-brown">Categoría</label>
                <select v-model="blog.category_id" class="form-control bg-beige text-brown border-brown" id="categoria" required>
                  <option value="" disabled>Seleccione una categoría</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                </select>
              </div>             
                <div class="col-md-2">
                <label for="precio" class="form-label text-brown">Precio</label>
                <input type="number" v-model.number="blog.precio" class="form-control bg-beige text-brown border-brown" id="precio" step="0.01" min="0" required>
              </div>
              <div class="col-md-2">
                <label for="descuento" class="form-label text-brown">Descuento (%)</label>
                <input type="number" v-model.number="blog.descuento" class="form-control bg-beige text-brown border-brown" id="descuento" min="0" max="100" required>
              </div>
              <div class="col-md-1">
                <label for="valoracion" class="form-label text-brown">Valoración</label>
                <input type="number" v-model.number="blog.valoracion" class="form-control bg-beige text-brown border-brown" id="valoracion" min="0" max="5" step="0.1" required>
              </div>
              <div class="col-md-1">
                <label for="stock" class="form-label text-brown">Stock</label>
                <input type="number" v-model.number="blog.stock" class="form-control bg-beige text-brown border-brown" id="stock" min="0" required>
              </div>
              </div>
              <div class="mb-3">
                <label for="etiquetas" class="form-label text-brown">Etiquetas (separadas por comas)</label>
                <input type="text" v-model="etiquetasString" class="form-control bg-beige text-brown border-brown" id="etiquetas">
              </div>
              <div class="row mb-3">
                <div class="col-md-4">
                <label for="brand" class="form-label text-brown">Marca</label>
                <input type="text" v-model="blog.brand" class="form-control bg-beige text-brown border-brown" id="brand">
              </div>
              <div class="col-md-4">
                <label for="sku" class="form-label text-brown">SKU</label>
                <input type="text" v-model="blog.sku" class="form-control bg-beige text-brown border-brown" id="sku">
              </div>
              <div class="col-md-4">
                <label for="weight" class="form-label text-brown">Peso (kg)</label>
                <input type="number" v-model.number="blog.weight" class="form-control bg-beige text-brown border-brown" id="weight" step="0.01" min="0">
              </div>
              </div>
              <div class="mb-3">
                <label class="form-label text-brown">Dimensiones (cm)</label>
                <div class="row">
                  <div class="col">
                    <input type="number" v-model.number="blog.dimensions.width" class="form-control bg-beige text-brown border-brown" placeholder="Ancho" step="0.01" min="0">
                  </div>
                  <div class="col">
                    <input type="number" v-model.number="blog.dimensions.height" class="form-control bg-beige text-brown border-brown" placeholder="Alto" step="0.01" min="0">
                  </div>
                  <div class="col">
                    <input type="number" v-model.number="blog.dimensions.depth" class="form-control bg-beige text-brown border-brown" placeholder="Profundidad" step="0.01" min="0">
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label for="warrantyInformation" class="form-label text-brown">Información de Garantía</label>
                <input type="text" v-model="blog.warrantyInformation" class="form-control bg-beige text-brown border-brown" id="warrantyInformation">
              </div>
              <div class="mb-3">
                <label for="shippingInformation" class="form-label text-brown">Información de Envío</label>
                <input type="text" v-model="blog.shippingInformation" class="form-control bg-beige text-brown border-brown" id="shippingInformation">
              </div>
              <div class="mb-3">
                <label for="availabilityStatus" class="form-label text-brown">Estado de Disponibilidad</label>
                <select v-model="blog.availabilityStatus" class="form-select bg-beige text-brown border-brown" id="availabilityStatus">
                  <option value="In Stock">En Stock</option>
                  <option value="Low Stock">Bajo Stock</option>
                  <option value="Out of Stock">Agotado</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="returnPolicy" class="form-label text-brown">Política de Devolución</label>
                <input type="text" v-model="blog.returnPolicy" class="form-control bg-beige text-brown border-brown" id="returnPolicy">
              </div>
              <div class="mb-3">
                <label for="minimumOrderQuantity" class="form-label text-brown">Cantidad Mínima de Pedido</label>
                <input type="number" v-model.number="blog.minimumOrderQuantity" class="form-control bg-beige text-brown border-brown" id="minimumOrderQuantity" min="1">
              </div>
              <div class="mb-3">
                <label for="thumbnail" class="form-label text-brown">Imagen del Producto</label>
                <input type="file" @change="handleFileUpload" class="form-control bg-beige text-brown border-brown" id="thumbnail" accept="image/*">
              </div>
              <div class="mb-3">
                <label for="newCategory" class="form-label text-brown">Nueva Categoría (opcional)</label>
                <div class="input-group">
                  <input type="text" v-model="newCategory" class="form-control bg-beige text-brown border-brown" id="newCategory" placeholder="Nombre de la nueva categoría">
                  <button type="button" @click="addCategory" class="btn btn-outline-brown">Agregar Categoría</button>
                </div>
              </div>
              <button type="submit" class="btn btn-brown">Guardar Producto</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  setup() {
    const router = useRouter();
    const blog = reactive({
      titulo: '',
      contenido: '',
      category_id: '',
      precio: 0,
      descuento: 0,
      valoracion: 0,
      stock: 0,
      etiquetas: [],
      brand: '',
      sku: '',
      weight: null,
      dimensions: { width: null, height: null, depth: null },
      warrantyInformation: '',
      shippingInformation: '',
      availabilityStatus: 'In Stock',
      returnPolicy: '',
      minimumOrderQuantity: 1,
      thumbnail: null
    });
    const categories = ref([]);
    const newCategory = ref('');
    const etiquetasString = computed({
      get: () => blog.etiquetas.join(', '),
      set: (val) => {
        blog.etiquetas = val.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
      }
    });

    const fetchCategories = async () => {
      try {
        const response = await axios.get('/api/categories');
        categories.value = response.data;
      } catch (error) {
        console.error('Error al cargar las categorías:', error);
      }
    };

    const addCategory = async () => {
      if (newCategory.value) {
        try {
          const response = await axios.post('/api/categories', { name: newCategory.value });
          categories.value.push(response.data);
          blog.category_id = response.data.id;
          newCategory.value = '';
        } catch (error) {
          console.error('Error al añadir la categoría:', error);
        }
      }
    };

    const handleFileUpload = (event) => {
      blog.thumbnail = event.target.files[0];
    };

    const crearBlog = async () => {
      try {
        const formData = new FormData();
        for (const key in blog) {
          if (key === 'dimensions' || key === 'etiquetas') {
            formData.append(key, JSON.stringify(blog[key]));
          } else if (key === 'thumbnail' && blog[key] instanceof File) {
            formData.append(key, blog[key]);
          } else {
            formData.append(key, blog[key]);
          }
        }

        const response = await axios.post('/api/blog', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        console.log('Respuesta del servidor:', response.data);
        router.push({ name: 'mostrarBlogs' });
      } catch (error) {
        console.error('Error al crear el producto:', error);
        if (error.response) {
          console.error('Detalles del error:', error.response.data);
        }
      }
    };

    fetchCategories();

    return {
      blog,
      categories,
      newCategory,
      etiquetasString,
      addCategory,
      crearBlog,
      handleFileUpload
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
  background-color: transparent;
}
.btn-outline-brown:hover {
  background-color: #8B4513;
  color: #FFF8E7;
}
.bg-brown {
  background-color: #8B4513;
}
</style>