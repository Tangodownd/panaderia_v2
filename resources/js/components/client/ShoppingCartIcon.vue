<template>
  <div class="shopping-cart-icon">
  <button @click="openCart" class="btn btn-brown position-relative">
    <i class="fas fa-shopping-cart me-2"></i>Carrito
    <span v-if="cartItemCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
      {{ cartItemCount }}
    </span>
  </button>
  
  <!-- Shopping Cart Offcanvas -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="shoppingCartOffcanvas">
    <div class="offcanvas-header bg-brown text-white">
      <h5 class="offcanvas-title">Carrito de Compras</h5>
      <button type="button" class="btn-close text-reset btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-brown" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>
      
      <div v-else-if="cart.items.length === 0" class="text-center py-5">
        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Tu carrito está vacío</h5>
        <p>Agrega algunos productos deliciosos</p>
        <button class="btn btn-brown" data-bs-dismiss="offcanvas">Continuar comprando</button>
      </div>
      
      <div v-else>
        <div class="list-group mb-3">
          <div v-for="item in cart.items" :key="item.id" class="list-group-item bg-beige border-brown">
            <div class="d-flex">
              <div class="flex-shrink-0">
                <img 
                  :src="getProductImage(item.product)" 
                  class="img-thumbnail" 
                  :alt="item.product.name"
                  style="width: 60px; height: 60px; object-fit: cover;"
                  @error="handleImageError"
                >
              </div>
              <div class="flex-grow-1 ms-3">
                <div class="d-flex justify-content-between align-items-center">
                  <h6 class="mb-0 text-brown">{{ item.product.name }}</h6>
                  <button @click="removeFromCart(item.id)" class="btn btn-sm text-danger">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <div class="input-group input-group-sm" style="width: 100px;">
                    <button class="btn btn-outline-brown" type="button" @click="decrementCartItem(item)">-</button>
                    <input type="number" class="form-control text-center" v-model="item.quantity" min="1" @change="updateCartItem(item)">
                    <button class="btn btn-outline-brown" type="button" @click="incrementCartItem(item)">+</button>
                  </div>
                  <span class="text-brown fw-bold">{{ formatPrice(item.price * item.quantity) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card bg-cream mb-3">
          <div class="card-body">
            <h6 class="card-title text-brown">Resumen del pedido</h6>
            <div class="d-flex justify-content-between mb-2">
              <span>Subtotal</span>
              <span>{{ formatPrice(cart.total) }}</span>
            </div>
            <div class="d-flex justify-content-between fw-bold">
              <span>Total</span>
              <span>{{ formatPrice(cart.total) }}</span>
            </div>
          </div>
        </div>
        
        <div class="d-grid gap-2">
          <button @click="proceedToCheckout" class="btn btn-brown">
            Proceder al pago
          </button>
          <button class="btn btn-outline-brown" data-bs-dismiss="offcanvas">
            Continuar comprando
          </button>
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
  emits: ['checkout'],
  
  setup(props, { emit }) {
  const cart = ref({
    id: null,
    total: 0,
    items: []
  });
  const loading = ref(true);
  
  const cartItemCount = computed(() => {
    return cart.value.items.reduce((total, item) => total + parseInt(item.quantity), 0);
  });
  
  const getProductImage = (product) => {
    // Si no hay producto o no tiene imagen, devolver imagen por defecto
    if (!product || (!product.image && !product.thumbnail)) {
      return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%2260%22%20height%3D%2260%22%20fill%3D%22%23E9ECEF%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2213%22%20y%3D%2236%22%3EProd%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
    }
  
    // Intentar obtener la ruta de la imagen
    let imagePath = '';
    
    // Si product es un objeto con image o thumbnail
    if (typeof product === 'object') {
      imagePath = product.image || product.thumbnail || '';
    } 
    // Si product es directamente la ruta de la imagen
    else if (typeof product === 'string') {
      imagePath = product;
    }
    
    // Si la ruta está vacía, devolver imagen por defecto
    if (!imagePath) {
      return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%2260%22%20height%3D%2260%22%20fill%3D%22%23E9ECEF%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2213%22%20y%3D%2236%22%3EProd%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
    }
  
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
    // Use a data URI for the error image as well
    event.target.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23721c24%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%2260%22%20height%3D%2260%22%20fill%3D%22%23f8d7da%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2210%22%20y%3D%2236%22%3EError%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
  };
  
  const formatPrice = (price) => {
    return `$${parseFloat(price).toFixed(2)}`;
  };
  
  // Cargar el carrito desde la API
  const fetchCart = async () => {
    loading.value = true;
    try {
      console.log('Fetching cart...');
      const response = await axios.get('/api/cart');
      console.log('Cart response:', response.data);
      
      // Asegurarse de que los datos del carrito son válidos
      if (response.data && response.data.cart) {
        cart.value = response.data.cart;
        cart.value.items = response.data.items || [];
      } else {
        // Si no hay datos válidos, inicializar un carrito vacío
        cart.value = { id: null, total: 0, items: [] };
        console.warn('No valid cart data received');
      }
    } catch (error) {
      console.error('Error al cargar el carrito:', error);
      // En caso de error, inicializar un carrito vacío
      cart.value = { id: null, total: 0, items: [] };
    } finally {
      loading.value = false;
    }
  };
  
  // Añadir producto al carrito
  const addToCart = async (product, quantity = 1) => {
    try {
      const response = await axios.post('/api/cart/add', {
        product_id: product.id,
        quantity: quantity
      });
      
      cart.value = response.data.cart;
      cart.value.items = response.data.items;
      
      ToastService.success('Producto añadido al carrito');
    } catch (error) {
      console.error('Error al añadir producto al carrito:', error);
      ToastService.error('Error al añadir producto al carrito');
    }
  };
  
  // Actualizar cantidad de un item en el carrito
  const updateCartItem = async (item) => {
    try {
      const response = await axios.put(`/api/cart/items/${item.id}`, {
        quantity: item.quantity
      });
      
      cart.value = response.data.cart;
      cart.value.items = response.data.items;
    } catch (error) {
      console.error('Error al actualizar item del carrito:', error);
      ToastService.error('Error al actualizar item del carrito');
    }
  };
  
  // Incrementar cantidad de un item
  const incrementCartItem = async (item) => {
    item.quantity = parseInt(item.quantity) + 1;
    await updateCartItem(item);
  };
  
  // Decrementar cantidad de un item
  const decrementCartItem = async (item) => {
    if (item.quantity > 1) {
      item.quantity = parseInt(item.quantity) - 1;
      await updateCartItem(item);
    }
  };
  
  // Eliminar item del carrito
  const removeFromCart = async (itemId) => {
    try {
      const response = await axios.delete(`/api/cart/items/${itemId}`);
      
      cart.value = response.data.cart;
      cart.value.items = response.data.items;
      
      ToastService.success('Producto eliminado del carrito');
    } catch (error) {
      console.error('Error al eliminar producto del carrito:', error);
      ToastService.error('Error al eliminar producto del carrito');
    }
  };
  
  const openCart = () => {
    // Asegurarse de que bootstrap esté disponible
    if (typeof bootstrap !== 'undefined') {
      const offcanvasElement = document.getElementById('shoppingCartOffcanvas');
      if (offcanvasElement) {
        const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
        offcanvas.show();
      }
    } else {
      console.error('Bootstrap no está disponible');
    }
  };
  
  const proceedToCheckout = () => {
    const offcanvasElement = document.getElementById('shoppingCartOffcanvas');
    if (offcanvasElement && typeof bootstrap !== 'undefined') {
      const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
      if (offcanvas) offcanvas.hide();
    }
    
    emit('checkout', cart.value);
  };
  
  onMounted(() => {
    fetchCart();
  });
  
  return {
    cart,
    loading,
    cartItemCount,
    getProductImage,
    handleImageError,
    formatPrice,
    addToCart,
    updateCartItem,
    incrementCartItem,
    decrementCartItem,
    removeFromCart,
    openCart,
    proceedToCheckout,
    fetchCart // Asegúrate de que el método fetchCart sea público para que pueda ser llamado desde fuera
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