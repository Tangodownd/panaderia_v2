<template>
  <div class="bg-beige min-vh-100 d-flex flex-column">
    <nav class="navbar navbar-expand-lg navbar-light bg-cream border-bottom border-brown sticky-top shadow-sm">
      <div class="container">
        <a class="navbar-brand text-brown" href="#">
            <i class="fas fa-bread-slice me-2"></i>Panadería Orquidea de Oro
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <router-link exact-active-class="active" to="/" class="nav-link">Inicio</router-link>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Categorías
              </a>
              <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                <li v-for="category in categories" :key="category.id">
                  <router-link :to="{ name: 'category', params: { id: category.id }}" class="dropdown-item">
                    {{ category.name }}
                  </router-link>
                </li>
                <li v-if="categories.length === 0">
                  <span class="dropdown-item">No hay categorías disponibles</span>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#contacto">Contacto</a>
            </li>
          </ul>
          <div class="d-flex">
            <shopping-cart ref="shoppingCart" @checkout="openCheckout" />
          </div>
        </div>
      </div>
    </nav>
    
    <main class="flex-grow-1">
      <router-view @add-to-cart="addToCart" />
    </main>
    
    <footer class="bg-brown text-white py-4 mt-auto">
      <div class="container">
        <div class="row">
          <div class="col-md-4 mb-3 mb-md-0">
              <h5 class="mb-3">Panadería Pasteleria Charcuteria Orquidea de Oro C.A</h5>
              <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i> Centro Comercial Mega Mergado, Flor Amarillo, Valencia, Carabobo</p>
              <p class="mb-1"><i class="fas fa-phone me-2"></i> +58 424 4133486</p>
              <p class="mb-0"><i class="fas fa-envelope me-2"></i> kennytorres4444@gmail.com</p>
          </div>
          <div class="col-md-4 mb-3 mb-md-0">
            <h5 class="mb-3">Horario</h5>
              <p class="mb-1">Lunes a Domingo: 6:00 AM - 9:00 PM</p>

          </div>
          <div class="col-md-4" id="contacto">
            <h5 class="mb-3">Contáctanos</h5>
            <div class="d-flex mb-3">
              <a href="#" class="text-white me-3"><i class="fab fa-facebook-f fa-lg"></i></a>
              <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
              <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
              <a href="#" class="text-white"><i class="fab fa-whatsapp fa-lg"></i></a>
            </div>
            <p>¿Tienes alguna pregunta o sugerencia? ¡Escríbenos!</p>
          </div>
        </div>
        <hr class="my-4 bg-light">
        <div class="text-center">
            <p class="mb-0">&copy; {{ new Date().getFullYear() }} Panadería Orquidea de Oro. Todos los derechos reservados.</p>
        </div>
      </div>
    </footer>
    
    <checkout-form 
      :cart="cart" 
      @order-completed="resetCart" 
      ref="checkoutForm"
    />
  </div>
</template>
  
<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import ShoppingCart from './ShoppingCart.vue';
import CheckoutForm from './CheckoutForm.vue';

export default {
  components: {
    ShoppingCart,
    CheckoutForm
  },
  
  setup() {
    const categories = ref([]);
    const cart = ref({
      items: [],
      total: 0
    });
    const checkoutForm = ref(null);
    const shoppingCart = ref(null);
    
    const fetchCategories = async () => {
      try {
        const response = await axios.get('/api/categories');
        categories.value = response.data;
      } catch (error) {
        console.error('Error al cargar categorías:', error);
        categories.value = [];
      }
    };
    
    const fetchCart = async () => {
      try {
        const response = await axios.get('/api/cart');
        cart.value = response.data.cart;
        cart.value.items = response.data.items;
      } catch (error) {
        console.error('Error al cargar el carrito:', error);
      }
    };
    
    const addToCart = async ({ product, quantity }) => {
      try {
        // Usar el método del componente ShoppingCart
        if (shoppingCart.value) {
          await shoppingCart.value.addToCart(product, quantity);
          
          // Actualizar el carrito local
          await fetchCart();
        }
      } catch (error) {
        console.error('Error al añadir producto al carrito:', error);
        showNotification('Error al añadir producto al carrito', 'danger');
      }
    };
    
    const resetCart = async () => {
      try {
        // Recargar el carrito después de completar la orden
        await fetchCart();
        
        // Notificar al componente ShoppingCart
        if (shoppingCart.value) {
          shoppingCart.value.fetchCart();
        }
        
        // Disparar evento para que otros componentes se actualicen
        document.dispatchEvent(new Event('cart-updated'));
      } catch (error) {
        console.error('Error al resetear el carrito:', error);
      }
    };
    
    const openCheckout = () => {
      // Asegurarse de que no haya backdrops residuales
      const backdrops = document.querySelectorAll('.modal-backdrop');
      backdrops.forEach(backdrop => {
        backdrop.remove();
      });
      
      // Asegurarse de que el body no tenga la clase modal-open
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
      
      // Pequeño retraso para asegurar que todo esté limpio
      setTimeout(() => {
        if (checkoutForm.value) {
          checkoutForm.value.openCheckoutModal();
        }
      }, 100);
    };
    
    const showNotification = (message, type = 'success') => {
      // Crear un elemento de notificación
      const notification = document.createElement('div');
      notification.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
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
      
      // Eliminar la notificación después de ocultarse
      notification.addEventListener('hidden.bs.toast', function () {
        document.body.removeChild(notification);
      });
    };
    
    onMounted(() => {
      fetchCategories();
      fetchCart();
      
      // Escuchar eventos de checkout
      document.addEventListener('checkout', (event) => {
        if (event.detail && event.detail.cart) {
          cart.value = event.detail.cart;
          openCheckout();
        }
      });
      
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
      categories,
      cart,
      checkoutForm,
      shoppingCart,
      addToCart,
      resetCart,
      openCheckout
    };
  }
};
</script>
  
<style>
body {
  background-color: #F5E6D3;
  color: #4A3728;
}
.bg-beige {
  background-color: #F5E6D3;
}
.bg-cream {
  background-color: #FFF8E7;
}
.text-brown {
  color: #8B4513 !important;
}
.border-brown {
  border-color: #8B4513 !important;
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
.navbar-light .navbar-nav .nav-link {
  color: rgba(75, 55, 40, 0.8);
}
.navbar-light .navbar-nav .nav-link:hover,
.navbar-light .navbar-nav .nav-link.active {
  color: #8B4513;
}
:focus-visible {
  outline: 2px solid #8B4513 !important;
  outline-offset: 2px;
}
</style>

