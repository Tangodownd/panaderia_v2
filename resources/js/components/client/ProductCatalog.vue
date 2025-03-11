<template>
    <div class="product-catalog">
      <!-- Filtros y búsqueda -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-brown text-white"><i class="fas fa-search"></i></span>
            <input 
              type="search" 
              class="form-control border-brown" 
              placeholder="Buscar productos..." 
              v-model="searchQuery"
            >
          </div>
        </div>
        <div class="col-md-3">
          <select class="form-select border-brown" v-model="selectedCategory">
            <option value="">Todas las categorías</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
        <div class="col-md-3">
          <select class="form-select border-brown" v-model="sortBy">
            <option value="titulo">Nombre (A-Z)</option>
            <option value="titulo-desc">Nombre (Z-A)</option>
            <option value="precio-asc">Precio (menor a mayor)</option>
            <option value="precio-desc">Precio (mayor a menor)</option>
            <option value="valoracion">Mejor valorados</option>
          </select>
        </div>
        <div class="col-md-2">
          <select class="form-select border-brown" v-model="availabilityFilter">
            <option value="">Todos</option>
            <option value="In Stock">En stock</option>
            <option value="Low Stock">Bajo stock</option>
          </select>
        </div>
      </div>
  
      <!-- Productos Grid -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-brown" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>
      
      <div v-else-if="filteredProducts.length === 0" class="text-center py-5">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h3 class="text-muted">No se encontraron productos</h3>
        <p>Intenta con otra búsqueda o categoría</p>
      </div>
      
      <div v-else class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <div v-for="product in filteredProducts" :key="product.id" class="col">
          <div class="card h-100 border-0 shadow-sm product-card">
            <div class="position-relative">
              <img 
                :src="getProductImage(product)" 
                class="card-img-top" 
                :alt="product.titulo"
                style="height: 200px; object-fit: cover;"
                @error="handleImageError"
              >
              <div v-if="product.descuento > 0" class="position-absolute top-0 end-0 bg-danger text-white m-2 px-2 py-1 rounded">
                -{{ product.descuento }}%
              </div>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title text-brown">{{ product.titulo }}</h5>
                <div class="badge bg-light-brown text-white">
                  {{ product.category ? product.category.name : 'Sin categoría' }}
                </div>
              </div>
              <p class="card-text small text-muted mb-2">{{ truncateText(product.contenido, 80) }}</p>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                  <span class="text-brown fw-bold">
                    {{ formatPrice(calculateDiscountedPrice(product)) }}
                  </span>
                  <span v-if="product.descuento > 0" class="text-muted text-decoration-line-through ms-2 small">
                    {{ formatPrice(product.precio) }}
                  </span>
                </div>
                <div class="text-warning">
                  {{ '★'.repeat(Math.round(product.valoracion || 0)) }}{{ '☆'.repeat(5 - Math.round(product.valoracion || 0)) }}
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span :class="getAvailabilityClass(product.availabilityStatus)">
                  {{ getAvailabilityText(product.availabilityStatus) }}
                </span>
                <button 
                  @click="addToCart(product)" 
                  class="btn btn-sm btn-brown"
                  :disabled="product.availabilityStatus === 'Out of Stock'"
                >
                  <i class="fas fa-cart-plus me-1"></i> Añadir
                </button>
              </div>
            </div>
            <div class="card-footer bg-transparent border-top-0">
              <button @click="showProductDetails(product)" class="btn btn-link text-brown p-0">
                Ver detalles
              </button>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Product Detail Modal -->
      <div class="modal fade" id="productDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content bg-beige">
            <div class="modal-header bg-brown text-white">
              <h5 class="modal-title">{{ selectedProduct.titulo }}</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-6">
                  <img 
                    :src="getProductImage(selectedProduct)" 
                    class="img-fluid rounded mb-3" 
                    :alt="selectedProduct.titulo"
                    @error="handleImageError"
                  >
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                      <span class="fs-4 text-brown fw-bold">
                        {{ formatPrice(calculateDiscountedPrice(selectedProduct)) }}
                      </span>
                      <span v-if="selectedProduct.descuento > 0" class="text-muted text-decoration-line-through ms-2">
                        {{ formatPrice(selectedProduct.precio) }}
                      </span>
                    </div>
                    <div class="text-warning fs-5">
                      {{ '★'.repeat(Math.round(selectedProduct.valoracion || 0)) }}{{ '☆'.repeat(5 - Math.round(selectedProduct.valoracion || 0)) }}
                    </div>
                  </div>
                  <div class="d-flex align-items-center mb-3">
                    <div class="input-group input-group-sm me-3" style="width: 120px;">
                      <button class="btn btn-outline-brown" type="button" @click="decrementQuantity">-</button>
                      <input type="number" class="form-control text-center" v-model="quantity" min="1">
                      <button class="btn btn-outline-brown" type="button" @click="incrementQuantity">+</button>
                    </div>
                    <button 
                      @click="addToCartWithQuantity(selectedProduct, quantity)" 
                      class="btn btn-brown"
                      :disabled="selectedProduct.availabilityStatus === 'Out of Stock'"
                    >
                      <i class="fas fa-cart-plus me-1"></i> Añadir al carrito
                    </button>
                  </div>
                  <div :class="getAvailabilityClass(selectedProduct.availabilityStatus, 'mb-3')">
                    {{ getAvailabilityText(selectedProduct.availabilityStatus) }}
                  </div>
                </div>
                <div class="col-md-6">
                  <h5 class="text-brown">Descripción</h5>
                  <p>{{ selectedProduct.contenido }}</p>
                  
                  <div class="mb-3">
                    <h6 class="text-brown">Detalles del producto</h6>
                    <ul class="list-group list-group-flush bg-transparent">
                      <li class="list-group-item bg-transparent px-0 py-1 border-brown">
                        <strong>Categoría:</strong> {{ selectedProduct.category ? selectedProduct.category.name : 'Sin categoría' }}
                      </li>
                      <li class="list-group-item bg-transparent px-0 py-1 border-brown">
                        <strong>Marca:</strong> {{ selectedProduct.brand || 'No especificada' }}
                      </li>
                      <li class="list-group-item bg-transparent px-0 py-1 border-brown">
                        <strong>Peso:</strong> {{ selectedProduct.weight ? `${selectedProduct.weight} kg` : 'No especificado' }}
                      </li>
                      <li v-if="selectedProduct.dimensions" class="list-group-item bg-transparent px-0 py-1 border-brown">
                        <strong>Dimensiones:</strong> {{ `${selectedProduct.dimensions?.width || 0} × ${selectedProduct.dimensions?.height || 0} × ${selectedProduct.dimensions?.depth || 0} cm` }}
                      </li>
                      <li class="list-group-item bg-transparent px-0 py-1 border-brown">
                        <strong>SKU:</strong> {{ selectedProduct.sku || 'No especificado' }}
                      </li>
                      <li v-if="selectedProduct.etiquetas && selectedProduct.etiquetas.length > 0" class="list-group-item bg-transparent px-0 py-1 border-brown">
                        <strong>Etiquetas:</strong> 
                        <span v-for="(tag, index) in selectedProduct.etiquetas" :key="index" class="badge bg-light-brown text-white me-1">
                          {{ tag }}
                        </span>
                      </li>
                    </ul>
                  </div>
                  
                  <div v-if="selectedProduct.shippingInformation" class="mb-3">
                    <h6 class="text-brown">Información de envío</h6>
                    <p class="small">{{ selectedProduct.shippingInformation }}</p>
                  </div>
                  
                  <div v-if="selectedProduct.returnPolicy" class="mb-3">
                    <h6 class="text-brown">Política de devolución</h6>
                    <p class="small">{{ selectedProduct.returnPolicy }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, computed, onMounted, watch } from 'vue';
  import axios from 'axios';
  
  export default {
    props: {
      initialCategory: {
        type: [String, Number],
        default: ''
      }
    },
    
    emits: ['add-to-cart'],
    
    setup(props, { emit }) {
      const products = ref([]);
      const categories = ref([]);
      const loading = ref(true);
      const searchQuery = ref('');
      const selectedCategory = ref(props.initialCategory);
      const sortBy = ref('titulo');
      const availabilityFilter = ref('');
      const selectedProduct = ref({});
      const quantity = ref(1);
  
      // Cargar productos y categorías
      const fetchProducts = async () => {
        try {
          loading.value = true;
          const response = await axios.get('/api/blog');
          products.value = response.data;
          
          // Asegurarse de que todos los productos tengan valores por defecto
          products.value = products.value.map(product => ({
            ...product,
            availabilityStatus: product.availabilityStatus || 'In Stock',
            valoracion: product.valoracion || 0,
            descuento: product.descuento || 0
          }));
        } catch (error) {
          console.error('Error al cargar productos:', error);
          products.value = [];
        } finally {
          loading.value = false;
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
  
      // Filtrado y ordenación de productos
      const filteredProducts = computed(() => {
        let result = products.value.filter(product => {
          const matchesSearch = product.titulo.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                               (product.contenido && product.contenido.toLowerCase().includes(searchQuery.value.toLowerCase()));
          const matchesCategory = selectedCategory.value ? product.category_id == selectedCategory.value : true;
          const matchesAvailability = availabilityFilter.value ? product.availabilityStatus === availabilityFilter.value : true;
          
          return matchesSearch && matchesCategory && matchesAvailability;
        });
  
        // Ordenar productos
        if (sortBy.value === 'titulo') {
          result.sort((a, b) => a.titulo.localeCompare(b.titulo));
        } else if (sortBy.value === 'titulo-desc') {
          result.sort((a, b) => b.titulo.localeCompare(a.titulo));
        } else if (sortBy.value === 'precio-asc') {
          result.sort((a, b) => a.precio - b.precio);
        } else if (sortBy.value === 'precio-desc') {
          result.sort((a, b) => b.precio - a.precio);
        } else if (sortBy.value === 'valoracion') {
          result.sort((a, b) => b.valoracion - a.valoracion);
        }
  
        return result;
      });
  
      // Funciones de utilidad
      const truncateText = (text, length) => {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
      };
  
      const formatPrice = (price) => {
        return `$${parseFloat(price).toFixed(2)}`;
      };
  
      const calculateDiscountedPrice = (product) => {
        if (!product || !product.precio) return 0;
        const discount = product.descuento || 0;
        return product.precio * (1 - discount / 100);
      };
  
      const getProductImage = (product) => {
        if (!product || !product.thumbnail) {
          return 'https://via.placeholder.com/300x200?text=Producto';
        }
        
        if (product.thumbnail.startsWith('http')) {
          return product.thumbnail;
        }
        
        return `${window.location.origin}/storage/${product.thumbnail}`;
      };
  
      const handleImageError = (event) => {
        event.target.src = 'https://via.placeholder.com/300x200?text=Imagen+no+disponible';
      };
  
      const getAvailabilityClass = (status, additionalClass = '') => {
        let className = additionalClass + ' badge ';
        
        switch (status) {
          case 'In Stock':
            className += 'bg-success';
            break;
          case 'Low Stock':
            className += 'bg-warning text-dark';
            break;
          case 'Out of Stock':
            className += 'bg-danger';
            break;
          default:
            className += 'bg-secondary';
        }
        
        return className;
      };
  
      const getAvailabilityText = (status) => {
        switch (status) {
          case 'In Stock':
            return 'En stock';
          case 'Low Stock':
            return 'Pocas unidades';
          case 'Out of Stock':
            return 'Agotado';
          default:
            return 'Disponibilidad desconocida';
        }
      };
  
      // Funciones del carrito
      const addToCart = (product) => {
        emit('add-to-cart', { product, quantity: 1 });
        
        // Mostrar notificación
        showNotification('Producto añadido al carrito');
      };
  
      const addToCartWithQuantity = (product, qty) => {
        emit('add-to-cart', { product, quantity: parseInt(qty) });
        
        // Cerrar modal y mostrar notificación
        const modal = document.getElementById('productDetailModal');
        if (modal && typeof bootstrap !== 'undefined') {
          const modalInstance = bootstrap.Modal.getInstance(modal);
          if (modalInstance) modalInstance.hide();
        }
        
        // Mostrar notificación
        showNotification('Producto añadido al carrito');
      };
  
      const incrementQuantity = () => {
        quantity.value = parseInt(quantity.value) + 1;
      };
  
      const decrementQuantity = () => {
        if (quantity.value > 1) {
          quantity.value = parseInt(quantity.value) - 1;
        }
      };
  
      // Funciones de UI
      const showProductDetails = (product) => {
        selectedProduct.value = product;
        quantity.value = 1;
        
        const modal = document.getElementById('productDetailModal');
        if (modal && typeof bootstrap !== 'undefined') {
          const modalInstance = new bootstrap.Modal(modal);
          modalInstance.show();
        }
      };
  
      const showNotification = (message) => {
        // Crear un elemento de notificación
        const notification = document.createElement('div');
        notification.className = 'toast align-items-center text-white bg-success border-0 position-fixed bottom-0 end-0 m-3';
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'assertive');
        notification.setAttribute('aria-atomic', 'true');
        
        notification.innerHTML = `
          <div class="d-flex">
            <div class="toast-body">
              ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        `;
        
        document.body.appendChild(notification);
        
        // Mostrar la notificación
        if (typeof bootstrap !== 'undefined') {
          const toast = new bootstrap.Toast(notification, { delay: 3000 });
          toast.show();
        }
        
        // Eliminar la notificación después de 3 segundos en caso de que bootstrap no esté disponible
        setTimeout(() => {
          if (document.body.contains(notification)) {
            document.body.removeChild(notification);
          }
        }, 3000);
      };
  
      // Observar cambios en la categoría inicial
      watch(() => props.initialCategory, (newCategory) => {
        selectedCategory.value = newCategory;
      });
  
      // Ciclo de vida
      onMounted(() => {
        fetchProducts();
        fetchCategories();
        
        // Asegurarse de que bootstrap esté disponible
        if (typeof bootstrap === 'undefined') {
          const script = document.createElement('script');
          script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js';
          script.onload = () => {
            console.log('Bootstrap cargado dinámicamente');
          };
          document.head.appendChild(script);
        }
      });
  
      return {
        products,
        categories,
        loading,
        searchQuery,
        selectedCategory,
        sortBy,
        availabilityFilter,
        filteredProducts,
        selectedProduct,
        quantity,
        
        // Métodos
        truncateText,
        formatPrice,
        calculateDiscountedPrice,
        getProductImage,
        handleImageError,
        getAvailabilityClass,
        getAvailabilityText,
        addToCart,
        addToCartWithQuantity,
        showProductDetails,
        incrementQuantity,
        decrementQuantity
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
  .bg-light-brown {
    background-color: #A67C52;
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
  .product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
  }
  </style>
  
  