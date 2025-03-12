<template>
  <div class="container-fluid py-4 bg-beige text-brown">
  <div class="row mb-4">
    <div class="col-12">
      <div class="card bg-light-beige text-brown border-brown">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="card-title mb-0 text-brown">Productos de Panadería</h2>
            <router-link :to="{ name: 'crearBlog' }" class="btn btn-brown">
              <i class="fas fa-plus-circle me-2"></i>Añadir Producto
            </router-link>
          </div>
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <input type="search" class="form-control bg-beige text-brown border-brown" placeholder="Buscar" v-model="searchQuery" @input="filterTable">
            </div>
            <div class="col-md-4">
              <select class="form-select bg-beige text-brown border-brown" v-model="selectedCategory" @change="filterTable">
                <option value="">Todas las Categorías</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <select class="form-select bg-beige text-brown border-brown" v-model="sortBy" @change="filterTable">
                <option value="">Ordenar por</option>
                <option value="id">ID</option>
                <option value="name">Nombre</option>
                <option value="category">Categoría</option>
                <option value="price">Precio</option>
              </select>
            </div>
          </div>
          <div class="table-responsive">
            <table id="productsTable" class="table table-hover">
              <thead class="bg-brown text-beige">
                <tr>
                  <th>ID</th>
                  <th>Nombre</th>
                  <th>Categoría</th>
                  <th>Precio</th>
                  <th>Stock</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(product, index) in filteredProducts" :key="product.id">
                  <td>{{ product.id }}</td>
                  <td>{{ product.name }}</td>
                  <td>{{ product.category ? product.category.name : 'Sin categoría' }}</td>
                  <td>${{ parseFloat(product.price).toFixed(2) }}</td>
                  <td>{{ product.stock }}</td>
                  <td>
                    <div class="btn-group" role="group">
                      <router-link :to="{ name: 'editarBlog', params: { id: product.id } }" class="btn btn-sm btn-outline-brown">
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <button type="button" class="btn btn-sm btn-outline-danger" @click="borrarProducto(product.id)">
                        <i class="fas fa-trash"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-brown" @click="verDetalles(index)">
                        <i class="fas fa-eye"></i>
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
  </div>
  </div>
  
  <!-- Modal para ver detalles -->
  <div class="modal fade" id="detallesModal" tabindex="-1" aria-labelledby="detallesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-beige text-brown">
      <div class="modal-header bg-brown text-beige">
        <h5 class="modal-title" id="detallesModalLabel">Detalles del Producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <p><strong>ID:</strong> {{ selectedProduct.id }}</p>
            <p><strong>Nombre:</strong> {{ selectedProduct.name }}</p>
            <p><strong>Categoría:</strong> {{ selectedProduct.category ? selectedProduct.category.name : 'Sin categoría' }}</p>
            <p><strong>Descripción:</strong> {{ selectedProduct.description }}</p>
            <p><strong>Precio:</strong> ${{ selectedProduct.price }}</p>
            <p><strong>Descuento:</strong> {{ selectedProduct.discount }}%</p>
            <p><strong>Valoración:</strong> {{ '⭐'.repeat(Math.round(selectedProduct.rating || 0)) }}</p>
            <p><strong>Stock:</strong> {{ selectedProduct.stock }}</p>
          </div>
          <div class="col-md-6">
            <div v-if="selectedProduct.image" class="mb-3">
              <h6>Imagen del Producto</h6>
              <img 
                :src="getProductImage(selectedProduct.image)" 
                class="img-fluid" 
                alt="Imagen del producto" 
                @error="handleImageError"
                v-show="imageLoaded"
                @load="handleImageLoad"
              >
              <div v-if="!imageLoaded && !imageError" class="text-center">
                <div class="spinner-border text-brown" role="status">
                  <span class="visually-hidden">Cargando imagen...</span>
                </div>
              </div>
              <p v-if="imageError" class="text-danger mt-2">
                Error al cargar la imagen. Por favor, inténtelo de nuevo más tarde.
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
  </div>
  </template>
  
  <script>
  import { ref, computed, onMounted, nextTick, watch } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';
  
  export default {
  setup() {
    const router = useRouter();
    const products = ref([]);
    const categories = ref([]);
    const searchQuery = ref('');
    const selectedCategory = ref('');
    const sortBy = ref('');
    const selectedProduct = ref({});
    const imageLoaded = ref(false);
    const imageError = ref(false);
  
    const getProductImage = (image) => {
    if (!image) {
      // Use a data URI instead of an external service
      return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22300%22%20height%3D%22200%22%20viewBox%3D%220%200%20300%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A15pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%22300%22%20height%3D%22200%22%20fill%3D%22%23E9ECEF%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2256.1953%22%20y%3D%22107.2%22%3EProducto%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
    }
  
    // Check if the image is already a full URL
    if (image.startsWith('http')) {
      return image;
    }
  
    // If it's not a full URL, construct it based on the current origin
    return `${window.location.origin}/storage/${image}`;
  };
  
  const handleImageError = (event) => {
    // Use a data URI for the error image as well
    event.target.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22300%22%20height%3D%22200%22%20viewBox%3D%220%200%20300%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23721c24%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A15pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%22300%22%20height%3D%22200%22%20fill%3D%22%23f8d7da%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2261.4687%22%20y%3D%22107.2%22%3EImagen%20no%20disponible%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
    imageLoaded.value = true; // Set to true to hide the loading spinner
    imageError.value = true;
  };
  
    const handleImageLoad = () => {
      imageLoaded.value = true;
      imageError.value = false;
    };
  
    const filteredProducts = computed(() => {
      let result = products.value.filter(product => {
        const matchesCategory = selectedCategory.value ? product.category_id == selectedCategory.value : true;
        const matchesSearch = product.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                            (product.description && product.description.toLowerCase().includes(searchQuery.value.toLowerCase()));
        return matchesCategory && matchesSearch;
      });
  
      if (sortBy.value) {
        result.sort((a, b) => {
          if (sortBy.value === 'id') {
            return a.id - b.id;
          } else if (sortBy.value === 'name') {
            return a.name?.localeCompare(b.name || '');
          } else if (sortBy.value === 'category') {
            return (a.category?.name || '').localeCompare(b.category?.name || '');
          } else if (sortBy.value === 'price') {
            return a.price - b.price;
          }
          return 0;
        });
      }
  
      return result;
    });
  
    // Cambiar la función mostrarProductos para usar /api/products en lugar de /api/blog
    const mostrarProductos = async () => {
      try {
        const response = await axios.get('/api/products');
        products.value = response.data;
        console.log('Productos obtenidos:', products.value); // Para depuración
      } catch (error) {
        console.error('Error al cargar productos:', error);
        products.value = [];
      }
    };
  
    const fetchCategories = async () => {
      try {
        const response = await axios.get('/api/categories');
        categories.value = response.data;
      } catch (error) {
        console.error('Error al cargar categorías:', error);
        categories.value = [];
      }
    };
  
    // Cambiar la función borrarProducto para usar /api/products en lugar de /api/blog
    const borrarProducto = async (id) => {
      if (confirm("¿Confirma eliminar el registro?")) {
        try {
          await axios.delete(`/api/products/${id}`);
          mostrarProductos();
        } catch (error) {
          console.error('Error al eliminar producto:', error);
          alert('Error al eliminar el producto');
        }
      }
    };
  
    const verDetalles = (index) => {
      selectedProduct.value = JSON.parse(JSON.stringify(filteredProducts.value[index]));
      imageLoaded.value = false;
      imageError.value = false;
      
      // Usar Bootstrap 5 para mostrar el modal
      const modalElement = document.getElementById('detallesModal');
      if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
      } else {
        console.error('Modal element not found');
      }
    };
  
    const filterTable = () => {
      // No es necesario hacer nada aquí, ya que estamos usando computed properties
      // para filtrar los productos
    };
  
    onMounted(async () => {
    try {
      await mostrarProductos();
      await fetchCategories();
    } catch (error) {
      console.error('Error during component initialization:', error);
      // Initialize with empty arrays to prevent further errors
      products.value = [];
      categories.value = [];
    }
  });
  
    return {
      products,
      categories,
      searchQuery,
      selectedCategory,
      sortBy,
      selectedProduct,
      imageLoaded,
      imageError,
      filteredProducts,
      getProductImage,
      handleImageError,
      handleImageLoad,
      mostrarProductos,
      borrarProducto,
      verDetalles,
      filterTable
    };
  }
  };
  </script>
  
  <style scoped>
  #productsTable {
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
  
  