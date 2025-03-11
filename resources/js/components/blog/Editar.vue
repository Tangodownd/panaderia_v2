<template>
  <div class="container-fluid py-4 bg-beige">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card bg-white shadow-lg rounded-lg overflow-hidden">
          <div class="card-header bg-brown text-white">
            <h4 class="mb-0 font-bold">Editar Producto</h4>
          </div>
          <div class="card-body">
            <div v-if="isLoading" class="text-center">
              <div class="spinner-border text-brown" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
            </div>
            <form v-else @submit.prevent="actualizar" enctype="multipart/form-data">
              <div class="mb-3">
                <label for="titulo" class="form-label text-brown">Nombre del Producto</label>
                <input type="text" v-model="blog.titulo" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="titulo" required>
              </div>
              <div class="mb-3">
                <label for="contenido" class="form-label text-brown">Descripción del Producto</label>
                <textarea v-model="blog.contenido" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="contenido" rows="3" required></textarea>
              </div>
              <div class="row mb-3">
                <div class="col-md-2">
                <label for="categoria" class="form-label text-brown">Categoría</label>
                <select v-model="blog.category_id" class="form-select border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="categoria" required>
                  <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                </select>
              </div>
              <div class="col-md-2">
                <label for="precio" class="form-label text-brown">Precio</label>
                <input type="number" v-model="blog.precio" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="precio" step="0.01" min="0" required>
              </div>
              <div class="col-md-2">
                <label for="descuento" class="form-label text-brown">Descuento (%)</label>
                <input type="number" v-model="blog.descuento" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="descuento" min="0" max="100" required>
              </div>
              <div class="col-md-2">
                <label for="valoracion" class="form-label text-brown">Valoración</label>
                <input type="number" v-model="blog.valoracion" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="valoracion" min="0" max="5" step="0.1" required>
              </div>
              <div class="col-md-2">
                <label for="stock" class="form-label text-brown">Stock</label>
                <input type="number" v-model="blog.stock" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="stock" min="0" required>
              </div>
            </div>
              <div class="mb-3">
                <label for="etiquetas" class="form-label text-brown">Etiquetas (separadas por comas)</label>
                <input type="text" v-model="etiquetasString" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="etiquetas">
              </div>
              <div class="row mb-3">
                <div class="col-md-4">
                <label for="brand" class="form-label text-brown">Marca</label>
                <input type="text" v-model="blog.brand" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="brand">
              </div>
              <div class="col-md-4">
                <label for="sku" class="form-label text-brown">SKU</label>
                <input type="text" v-model="blog.sku" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="sku">
              </div>
              <div class="col-md-4">
                <label for="weight" class="form-label text-brown">Peso (kg)</label>
                <input type="number" v-model="blog.weight" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="weight" step="0.01" min="0">
              </div>
            </div>
              <div class="mb-3">
                <label class="form-label text-brown">Dimensiones (cm)</label>
                <div class="row">
                  <div class="col">
                    <input type="number" v-model="blog.dimensions.width" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" placeholder="Ancho" step="0.01" min="0">
                  </div>
                  <div class="col">
                    <input type="number" v-model="blog.dimensions.height" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" placeholder="Alto" step="0.01" min="0">
                  </div>
                  <div class="col">
                    <input type="number" v-model="blog.dimensions.depth" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" placeholder="Profundidad" step="0.01" min="0">
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label for="warrantyInformation" class="form-label text-brown">Información de Garantía</label>
                <input type="text" v-model="blog.warrantyInformation" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="warrantyInformation">
              </div>
              <div class="mb-3">
                <label for="shippingInformation" class="form-label text-brown">Información de Envío</label>
                <input type="text" v-model="blog.shippingInformation" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="shippingInformation">
              </div>
              <div class="mb-3">
                <label for="availabilityStatus" class="form-label text-brown">Estado de Disponibilidad</label>
                <select v-model="blog.availabilityStatus" class="form-select border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="availabilityStatus">
                  <option value="In Stock">En Stock</option>
                  <option value="Low Stock">Bajo Stock</option>
                  <option value="Out of Stock">Agotado</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="returnPolicy" class="form-label text-brown">Política de Devolución</label>
                <input type="text" v-model="blog.returnPolicy" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="returnPolicy">
              </div>
              <div class="mb-3">
                <label for="minimumOrderQuantity" class="form-label text-brown">Cantidad Mínima de Pedido</label>
                <input type="number" v-model="blog.minimumOrderQuantity" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="minimumOrderQuantity" min="1">
              </div>
              <div class="mb-3">
                <label for="thumbnail" class="form-label text-brown">Imagen del Producto</label>
                <input type="file" @change="handleFileUpload" class="form-control border-brown focus:border-brown focus:ring focus:ring-brown focus:ring-opacity-50" id="thumbnail" accept="image/*">
              </div>
              <button type="submit" class="btn bg-brown text-beige hover:bg-light-beige">Guardar Cambios</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

export default {
  setup() {
    const router = useRouter();
    const route = useRoute();
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
    const etiquetasString = computed({
      get: () => blog.etiquetas.join(', '),
      set: (val) => {
        blog.etiquetas = val.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
      }
    });
    const isLoading = ref(true);

    const cargarBlog = async () => {
      isLoading.value = true;
      try {
        const response = await axios.get(`/api/blog/${route.params.id}`);
        Object.assign(blog, response.data);
        blog.dimensions = blog.dimensions || { width: null, height: null, depth: null };
        blog.etiquetas = blog.etiquetas || [];
        blog.minimumOrderQuantity = blog.minimumOrderQuantity || 1;
        blog.availabilityStatus = blog.availabilityStatus || 'In Stock';
      } catch (error) {
        console.error('Error al cargar el blog:', error);
      } finally {
        isLoading.value = false;
      }
    };

    const fetchCategories = async () => {
      try {
        const response = await axios.get('/api/categories');
        categories.value = response.data;
      } catch (error) {
        console.error('Error al cargar las categorías:', error);
      }
    };

    const handleFileUpload = (event) => {
      blog.thumbnail = event.target.files[0];
    };

    const actualizar = async () => {
      try {
        const formData = new FormData();
        for (const key in blog) {
          if (key === 'dimensions' || key === 'etiquetas') {
            formData.append(key, JSON.stringify(blog[key]));
          } else if (key === 'thumbnail' && blog[key] instanceof File) {
            formData.append(key, blog[key]);
          } else if (key !== 'thumbnail') {
            formData.append(key, blog[key]);
          }
        }

        await axios.post(`/api/blog/${blog.id}`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'X-HTTP-Method-Override': 'PUT'
          }
        });
        router.push({ name: 'mostrarBlogs' });
      } catch (error) {
        console.error('Error al actualizar el blog:', error);
      }
    };

    onMounted(() => {
      cargarBlog();
      fetchCategories();
    });

    return {
      blog,
      categories,
      etiquetasString,
      actualizar,
      handleFileUpload,
      isLoading
    };
  }
};
</script>

<style scoped>
#blogsTable {
  width: 100%;
}
.table-responsive {
  overflow-x: auto;
}
.btn-group {
  white-space: nowrap;
}
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
.btn-outline-brown {
  color: #8D6E63;
  border-color: #8D6E63;
}
.btn-outline-brown:hover {
  background-color: #8D6E63;
  color: #F5E6D3;
}
.bg-beige {
  background-color: #F5E6D3;
}
.bg-light-beige {
  background-color: #D7CCC8;
}
.text-beige {
  color: #F5E6D3;
}
:focus-visible {
  outline: 2px solid #8D6E63 !important;
  outline-offset: 2px;
}
</style>
