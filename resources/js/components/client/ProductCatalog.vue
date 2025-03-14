<template>
  <div class="product-catalog">
  <div class="row">
    <div class="col-md-3 mb-4">
      <div class="card bg-beige border-brown">
        <div class="card-header bg-brown text-white">
          <h5 class="mb-0">Categorías</h5>
        </div>
        <div class="card-body">
          <div class="list-group">
            <button 
              class="list-group-item list-group-item-action bg-cream"
              :class="{ 'active bg-brown text-white': selectedCategory === null }"
              @click="selectCategory(null)"
            >
              Todas las categorías
            </button>
            <button 
              v-for="category in categories" 
              :key="category.id"
              class="list-group-item list-group-item-action bg-cream"
              :class="{ 'active bg-brown text-white': selectedCategory === category.id }"
              @click="selectCategory(category.id)"
            >
              {{ category.name }}
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-9">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-brown">{{ categoryTitle }}</h2>
        <div class="input-group" style="max-width: 300px;">
          <input 
            type="text" 
            class="form-control border-brown" 
            placeholder="Buscar productos..." 
            v-model="searchQuery"
          >
          <button class="btn btn-brown" type="button">
            <i class="fas fa-search"></i>
          </button>
        </div>
      </div>
      
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-brown" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>
      
      <div v-else-if="filteredProducts.length === 0" class="text-center py-5">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">No se encontraron productos</h4>
        <p>Intenta con otra búsqueda o categoría</p>
      </div>
      
      <div v-else class="row">
        <div v-for="product in filteredProducts" :key="product.id" class="col-md-4 mb-4">
          <div class="card h-100 bg-beige border-brown product-card">
            <div class="position-relative">
              <img 
                :src="getProductImage(product)" 
                class="card-img-top" 
                :alt="product.name"
                style="height: 200px; object-fit: cover;"
                @error="handleImageError"
              >
              <div v-if="product.discount > 0" class="position-absolute top-0 end-0 bg-danger text-white p-2">
                {{ product.discount }}% OFF
              </div>
            </div>
            <div class="card-body d-flex flex-column">
              <h5 class="card-title text-brown">{{ product.name }}</h5>
              <p class="card-text text-muted small">{{ truncateDescription(product.description) }}</p>
              <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div>
                    <span v-if="product.discount > 0" class="text-decoration-line-through text-muted me-2">
                      {{ formatPrice(product.price) }}
                    </span>
                    <span class="fw-bold text-brown">
                      {{ formatDiscountedPrice(product) }}
                    </span>
                  </div>
                  <div class="text-warning">
                    <i v-for="n in Math.floor(product.rating || 0)" :key="n" class="fas fa-star"></i>
                    <i v-if="product.rating % 1 >= 0.5" class="fas fa-star-half-alt"></i>
                    <i v-for="n in (5 - Math.ceil(product.rating || 0))" :key="`empty-${n}`" class="far fa-star"></i>
                  </div>
                </div>
                <div class="d-grid">
                  <button @click="addToCart(product)" class="btn btn-brown">
                    <i class="fas fa-cart-plus me-2"></i>Añadir al carrito
                  </button>
                </div>
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
  import { ToastService } from '../../services/toast-service';
  
  export default {
  setup() {
  const products = ref([]);
  const categories = ref([]);
  const loading = ref(true);
  const selectedCategory = ref(null);
  const searchQuery = ref('');
  
  const categoryTitle = computed(() => {
    if (selectedCategory.value === null) {
      return 'Todos los Productos';
    } else {
      const category = categories.value.find(c => c.id === selectedCategory.value);
      return category ? category.name : 'Productos';
    }
  });
  
  const filteredProducts = computed(() => {
    let result = products.value;
    
    // Filtrar por categoría
    if (selectedCategory.value !== null) {
      result = result.filter(product => product.category_id === selectedCategory.value);
    }
    
    // Filtrar por búsqueda
    if (searchQuery.value.trim() !== '') {
      const query = searchQuery.value.toLowerCase();
      result = result.filter(product => 
        product.name.toLowerCase().includes(query) || 
        (product.description && product.description.toLowerCase().includes(query))
      );
    }
    
    return result;
  });
  
  const fetchProducts = async () => {
    loading.value = true;
    try {
      const response = await axios.get('/api/products');
      products.value = response.data;
    } catch (error) {
      console.error('Error al cargar productos:', error);
      ToastService.error('Error al cargar productos');
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
      ToastService.error('Error al cargar categorías');
    }
  };
  
  const selectCategory = (categoryId) => {
    selectedCategory.value = categoryId;
  };
  
  const truncateDescription = (description, length = 100) => {
    if (!description) return '';
    return description.length > length 
      ? description.substring(0, length) + '...' 
      : description;
  };
  
  const formatPrice = (price) => {
    return `$${parseFloat(price).toFixed(2)}`;
  };
  
  const formatDiscountedPrice = (product) => {
    if (!product.discount || product.discount <= 0) {
      return formatPrice(product.price);
    }
    
    const discountedPrice = product.price * (1 - product.discount / 100);
    return formatPrice(discountedPrice);
  };
  
  const getProductImage = (product) => {
    // Si no hay producto o no tiene imagen, devolver imagen por defecto
    if (!product || !product.image) {
      return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22286%22%20height%3D%22180%22%20viewBox%3D%220%200%20286%20180%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%22286%22%20height%3D%22180%22%20fill%3D%22%23E9ECEF%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2299.5%22%20y%3D%2296.3%22%3EImage%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
    }
  
    // Intentar obtener la ruta de la imagen
    let imagePath = product.image;
    
    // Si la ruta ya es una URL completa, devolverla directamente
    if (imagePath.startsWith('http')) {
      return imagePath;
    }
    
    // Probar diferentes combinaciones de rutas
    const possiblePaths = [
      // Ruta directa desde storage
      `${window.location.origin}/storage/${imagePath}`,
      // Ruta sin storage (por si ya incluye 'storage' en la ruta)
      `${window.location.origin}/${imagePath}`,
      // Ruta relativa
      `/storage/${imagePath}`,
      // Ruta directa
      `/${imagePath}`,
      // Ruta con public
      `${window.location.origin}/public/storage/${imagePath}`,
      // Ruta con app/public
      `${window.location.origin}/app/public/storage/${imagePath}`,
    ];
    
    // Devolver la primera ruta (la más probable)
    return possiblePaths[0];
  };
  
  const handleImageError = (event) => {
    console.error("Error al cargar imagen:", event.target.src);
    event.target.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22286%22%20height%3D%22180%22%20viewBox%3D%220%200%20286%20180%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23721c24%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%22286%22%20height%3D%22180%22%20fill%3D%22%23f8d7da%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2299.5%22%20y%3D%2296.3%22%3EError%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
  };
  
  const addToCart = async (product) => {
    try {
      await axios.post('/api/cart/add', {
        product_id: product.id,
        quantity: 1
      });
      
      ToastService.success(`${product.name} añadido al carrito`);
    } catch (error) {
      console.error('Error al añadir producto al carrito:', error);
      ToastService.error('Error al añadir producto al carrito');
    }
  };
  
  const showNotification = (message, type = 'success') => {
    ToastService.show(message, type);
  };
  
  onMounted(() => {
    fetchProducts();
    fetchCategories();
  });
  
  return {
    products,
    categories,
    loading,
    selectedCategory,
    searchQuery,
    categoryTitle,
    filteredProducts,
    selectCategory,
    truncateDescription,
    formatPrice,
    formatDiscountedPrice,
    getProductImage,
    handleImageError,
    addToCart,
    showNotification
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
  .product-card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.1);
  }
  </style>

