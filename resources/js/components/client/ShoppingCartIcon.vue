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
      if (!product || (!product.thumbnail && !product.image)) {
        // Use a data URI instead of an external service
        return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%2260%22%20height%3D%2260%22%20fill%3D%22%23E9ECEF%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2213%22%20y%3D%2236%22%3EProd%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
  }
  
  // Check for image property first, then thumbnail
  const imagePath = product.image || product.thumbnail;
  
  // Check if the image is already a full URL
  if (imagePath.startsWith('http')) {
    return imagePath;
  }
  
  // If it's not a full URL, construct it based on the current origin
  return `${window.location.origin}/storage/${imagePath}`;
};

    const handleImageError = (event) => {
      // Use a data URI for the error image as well
      event.target.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23721c24%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%2260%22%20height%3D%2260%22%20fill%3D%22%23f8d7da%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2210%22%20y%3D%2236%22%3EError%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
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

