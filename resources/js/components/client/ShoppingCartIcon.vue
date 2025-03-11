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
          <div v-if="cart.items.length === 0" class="text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Tu carrito está vacío</h5>
            <p>Agrega algunos productos deliciosos</p>
            <button class="btn btn-brown" data-bs-dismiss="offcanvas">Continuar comprando</button>
          </div>
          
          <div v-else>
            <div class="list-group mb-3">
              <div v-for="(item, index) in cart.items" :key="index" class="list-group-item bg-beige border-brown">
                <div class="d-flex">
                  <div class="flex-shrink-0">
                    <img 
                      :src="getProductImage(item.product)" 
                      class="img-thumbnail" 
                      :alt="item.product.titulo"
                      style="width: 60px; height: 60px; object-fit: cover;"
                      @error="handleImageError"
                    >
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <div class="d-flex justify-content-between align-items-center">
                      <h6 class="mb-0 text-brown">{{ item.product.titulo }}</h6>
                      <button @click="removeFromCart(index)" class="btn btn-sm text-danger">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                      <div class="input-group input-group-sm" style="width: 100px;">
                        <button class="btn btn-outline-brown" type="button" @click="decrementCartItem(index)">-</button>
                        <input type="number" class="form-control text-center" v-model="item.quantity" min="1" @change="updateCartItem(index, item.quantity)">
                        <button class="btn btn-outline-brown" type="button" @click="incrementCartItem(index)">+</button>
                      </div>
                      <span class="text-brown fw-bold">{{ formatPrice(calculateItemTotal(item)) }}</span>
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
                  <span>{{ formatPrice(calculateSubtotal()) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span>Descuento</span>
                  <span>-{{ formatPrice(calculateDiscount()) }}</span>
                </div>
                <div class="d-flex justify-content-between fw-bold">
                  <span>Total</span>
                  <span>{{ formatPrice(calculateTotal()) }}</span>
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
  
  export default {
    emits: ['checkout'],
    
    setup(props, { emit }) {
      const cart = ref({
        items: [],
        total: 0
      });
  
      const cartItemCount = computed(() => {
        return cart.value.items.reduce((total, item) => total + parseInt(item.quantity), 0);
      });
  
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
  
      const formatPrice = (price) => {
        return `$${parseFloat(price).toFixed(2)}`;
      };
  
      const calculateDiscountedPrice = (product) => {
        if (!product || !product.precio) return 0;
        const discount = product.descuento || 0;
        return product.precio * (1 - discount / 100);
      };
  
      const calculateItemTotal = (item) => {
        return calculateDiscountedPrice(item.product) * item.quantity;
      };
  
      const calculateSubtotal = () => {
        return cart.value.items.reduce((total, item) => {
          return total + (item.product.precio * item.quantity);
        }, 0);
      };
  
      const calculateDiscount = () => {
        return cart.value.items.reduce((total, item) => {
          const discount = item.product.descuento || 0;
          return total + ((item.product.precio * discount / 100) * item.quantity);
        }, 0);
      };
  
      const calculateTotal = () => {
        return cart.value.items.reduce((total, item) => {
          return total + calculateItemTotal(item);
        }, 0);
      };
  
      const removeFromCart = (index) => {
        cart.value.items.splice(index, 1);
        saveCartToLocalStorage();
      };
  
      const updateCartItem = (index, quantity) => {
        cart.value.items[index].quantity = parseInt(quantity);
        if (cart.value.items[index].quantity < 1) {
          cart.value.items[index].quantity = 1;
        }
        saveCartToLocalStorage();
      };
  
      const incrementCartItem = (index) => {
        cart.value.items[index].quantity = parseInt(cart.value.items[index].quantity) + 1;
        saveCartToLocalStorage();
      };
  
      const decrementCartItem = (index) => {
        if (cart.value.items[index].quantity > 1) {
          cart.value.items[index].quantity = parseInt(cart.value.items[index].quantity) - 1;
          saveCartToLocalStorage();
        }
      };
  
      const saveCartToLocalStorage = () => {
        localStorage.setItem('panaderia-cart', JSON.stringify(cart.value));
      };
  
      const loadCartFromLocalStorage = () => {
        const savedCart = localStorage.getItem('panaderia-cart');
        if (savedCart) {
          try {
            cart.value = JSON.parse(savedCart);
          } catch (e) {
            console.error('Error al cargar el carrito desde localStorage:', e);
            cart.value = { items: [], total: 0 };
          }
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
  
      // Observar cambios en el carrito para actualizar el localStorage
      watch(() => cart.value, () => {
        saveCartToLocalStorage();
      }, { deep: true });
  
      onMounted(() => {
        loadCartFromLocalStorage();
      });
  
      return {
        cart,
        cartItemCount,
        getProductImage,
        handleImageError,
        formatPrice,
        calculateItemTotal,
        calculateSubtotal,
        calculateDiscount,
        calculateTotal,
        removeFromCart,
        updateCartItem,
        incrementCartItem,
        decrementCartItem,
        openCart,
        proceedToCheckout
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
  
  