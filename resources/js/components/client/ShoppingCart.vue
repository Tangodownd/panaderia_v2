<template>
  <div class="shopping-cart-container">
    <!-- Botón del carrito -->
    <button 
      class="btn btn-cart btn-brown position-relative" 
      type="button" 
      @click="isCartOpen = true"
    >
      <i class="fas fa-shopping-cart me-1"></i>
      <span>Carrito</span>
      <span v-if="cartItemCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
        {{ cartItemCount }}
      </span>
    </button>
    
    <!-- Modal personalizado del carrito -->
    <div v-if="isCartOpen" class="custom-modal-backdrop" @click="isCartOpen = false">
      <div class="custom-modal-content bg-beige" @click.stop>
        <div class="custom-modal-header bg-brown text-white">
          <h5 class="modal-title">Carrito de Compras</h5>
          <button type="button" class="btn-close btn-close-white" @click="isCartOpen = false" aria-label="Close"></button>
        </div>
        <div class="custom-modal-body">
          <div v-if="loading" class="text-center py-4">
            <div class="spinner-border text-brown" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando carrito...</p>
          </div>
          
          <div v-else-if="cart.items.length === 0" class="text-center py-4">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Tu carrito está vacío</h5>
            <p>Agrega algunos productos deliciosos</p>
            <button class="btn btn-brown" @click="isCartOpen = false">Continuar comprando</button>
          </div>
          
          <div v-else>
            <div class="list-group mb-4">
              <div v-for="item in cart.items" :key="item.id" class="list-group-item bg-cream border-brown">
                <div class="d-flex">
                  <div class="flex-shrink-0">
                    <img 
                      :src="getProductImage(item.product)" 
                      class="img-thumbnail" 
                      :alt="item.product.name"
                      style="width: 80px; height: 80px; object-fit: cover;"
                      @error="handleImageError"
                    >
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <div class="d-flex justify-content-between align-items-center">
                      <h6 class="mb-0 text-brown">{{ item.product.name }}</h6>
                      <button @click="removeFromCart(item)" class="btn btn-sm text-danger">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                      <div class="input-group input-group-sm" style="width: 120px;">
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
            
            <div class="card bg-cream mb-4">
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
              <button @click="customProceedToCheckout" class="btn btn-brown">Proceder al pago</button>
              <button class="btn btn-outline-brown" @click="isCartOpen = false">Continuar comprando</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Checkout Form (se muestra cuando se hace clic en Proceder al pago) -->
    <checkout-form 
      v-if="showCheckoutForm" 
      :cart="cart" 
      @order-completed="handleOrderCompleted"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { ToastService } from '../../services/toast-service';
import CheckoutForm from './CheckoutForm.vue';

export default {
  components: {
    CheckoutForm
  },
  
  setup() {
    const cart = ref({
      id: null,
      session_id: null,
      total: 0,
      items: []
    });
    const loading = ref(false);
    const showCheckoutForm = ref(false);
    const isCartOpen = ref(false);
    
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
        console.log('Obteniendo carrito desde API...');
        const response = await axios.get('/api/cart');
        console.log('Respuesta del carrito:', response.data);
        
        if (response.data && response.data.cart) {
          cart.value = response.data.cart;
          cart.value.items = response.data.items || [];
          console.log('Carrito actualizado:', cart.value);
        } else {
          console.error('Respuesta de API inesperada:', response.data);
          initializeEmptyCart();
        }
      } catch (error) {
        console.error('Error al cargar el carrito:', error);
        initializeEmptyCart();
      } finally {
        loading.value = false;
      }
    };
    
    const initializeEmptyCart = () => {
      cart.value = {
        id: null,
        session_id: null,
        total: 0,
        items: []
      };
    };
    
    // Añadir producto al carrito
    const addToCart = async (product, quantity = 1) => {
      loading.value = true;
      try {
        console.log('Añadiendo producto al carrito:', product.id, quantity);
        const response = await axios.post('/api/cart/add', {
          product_id: product.id,
          quantity: quantity
        });
        
        console.log('Respuesta de añadir al carrito:', response.data);
        
        if (response.data && response.data.cart) {
          cart.value = response.data.cart;
          cart.value.items = response.data.items || [];
          console.log('Carrito actualizado después de añadir:', cart.value);
        }
        
        ToastService.success('Producto añadido al carrito');
      } catch (error) {
        console.error('Error al añadir producto al carrito:', error);
        ToastService.error('Error al añadir producto al carrito');
      } finally {
        loading.value = false;
      }
    };
    
    // Actualizar cantidad de un item en el carrito
    const updateCartItem = async (item) => {
      loading.value = true;
      try {
        // Asegurarse de que la cantidad sea al menos 1
        if (item.quantity < 1) {
          item.quantity = 1;
        }
        
        const response = await axios.post('/api/cart/update', {
          product_id: item.product_id,
          quantity: item.quantity
        });
        
        if (response.data && response.data.cart) {
          cart.value = response.data.cart;
          cart.value.items = response.data.items || [];
        }
      } catch (error) {
        console.error('Error al actualizar item del carrito:', error);
        ToastService.error('Error al actualizar item del carrito');
        // Recargar el carrito para asegurar consistencia
        fetchCart();
      } finally {
        loading.value = false;
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
    const removeFromCart = async (item) => {
      loading.value = true;
      try {
        const response = await axios.post('/api/cart/remove', {
          product_id: item.product_id
        });
        
        if (response.data && response.data.cart) {
          cart.value = response.data.cart;
          cart.value.items = response.data.items || [];
        }
        
        ToastService.success('Producto eliminado del carrito');
      } catch (error) {
        console.error('Error al eliminar producto del carrito:', error);
        ToastService.error('Error al eliminar producto del carrito');
        // Recargar el carrito para asegurar consistencia
        fetchCart();
      } finally {
        loading.value = false;
      }
    };
    
    const customProceedToCheckout = () => {
      // Cerrar el modal personalizado
      isCartOpen.value = false;
      
      // Mostrar el formulario de checkout
      showCheckoutForm.value = true;
      
      // Emitir evento
      const event = new CustomEvent('checkout', { detail: { cart: cart.value } });
      document.dispatchEvent(event);
    };
    
    const handleOrderCompleted = async () => {
      try {
        // Usar el nuevo endpoint que marca el carrito como completado
        const response = await axios.post('/api/cart/mark-completed');
        
        if (response.data && response.data.cart) {
          cart.value = response.data.cart;
          cart.value.items = [];
        } else {
          initializeEmptyCart();
        }
        
        // Emitir evento para actualizar otros componentes
        document.dispatchEvent(new Event('cart-updated'));
        
        showCheckoutForm.value = false;
      } catch (error) {
        console.error('Error al resetear el carrito:', error);
        initializeEmptyCart();
        
        // Emitir evento para actualizar otros componentes
        document.dispatchEvent(new Event('cart-updated'));
        
        showCheckoutForm.value = false;
      }
    };
    
    onMounted(() => {
      fetchCart();
      
      // Escuchar eventos de actualización del carrito
      document.addEventListener('cart-updated', () => {
        fetchCart();
      });
      
      // Escuchar evento para abrir el carrito desde otros componentes
      document.addEventListener('open-cart', () => {
        isCartOpen.value = true;
      });
    });
    
    return {
      cart,
      loading,
      cartItemCount,
      showCheckoutForm,
      isCartOpen,
      getProductImage,
      handleImageError,
      formatPrice,
      addToCart,
      updateCartItem,
      incrementCartItem,
      decrementCartItem,
      removeFromCart,
      customProceedToCheckout,
      handleOrderCompleted,
      fetchCart
    };
  },
  
  // Exponer métodos para que puedan ser llamados desde fuera
  methods: {
    addToCart(product, quantity) {
      this.addToCart(product, quantity);
    },
    fetchCart() {
      this.fetchCart();
    }
  }
};
</script>

<style scoped>
.shopping-cart-container {
  display: inline-block;
}

.btn-cart {
  padding: 0.5rem 1rem;
  font-size: 1rem;
  text-align: center;
}

.custom-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1050;
}

.custom-modal-content {
  width: 100%;
  max-width: 800px;
  border-radius: 5px;
  overflow: hidden;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
}

.custom-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
}

.custom-modal-body {
  padding: 1rem;
  max-height: 80vh;
  overflow-y: auto;
}

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