<template>
  <div class="checkout-form">
    <!-- Modal personalizado de checkout -->
    <div v-if="isCheckoutOpen" class="custom-modal-backdrop" @click="closeModal">
      <div class="custom-modal-content bg-beige" @click.stop>
        <div class="custom-modal-header bg-brown text-white">
          <h5 class="modal-title">Finalizar Compra</h5>
          <button type="button" class="btn-close btn-close-white" @click="closeModal" aria-label="Close"></button>
        </div>
        <div class="custom-modal-body">
          <div v-if="loading" class="text-center py-4">
            <div class="spinner-border text-brown" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Procesando tu pedido...</p>
          </div>
          
          <div v-else-if="orderCompleted" class="text-center py-4">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h5 class="text-success">¡Pedido completado con éxito!</h5>
            <p>Tu número de pedido es: <strong>{{ orderNumber }}</strong></p>
            <p>Te hemos enviado un correo con los detalles de tu pedido.</p>
            <button class="btn btn-brown mt-3" @click="closeModal">Continuar comprando</button>
          </div>
          
          <div v-else>
            <div class="row">
              <div class="col-md-6">
                <h6 class="mb-3">Información de contacto</h6>
                <div class="mb-3">
                  <label for="name" class="form-label">Nombre completo</label>
                  <input type="text" class="form-control" id="name" v-model="formData.name" required>
                </div>
                <div class="mb-3">
                  <label for="email" class="form-label">Correo electrónico</label>
                  <input type="email" class="form-control" id="email" v-model="formData.email" required>
                </div>
                <div class="mb-3">
                  <label for="phone" class="form-label">Teléfono</label>
                  <input type="tel" class="form-control" id="phone" v-model="formData.phone" required>
                </div>
                <div class="mb-3">
                  <label for="shipping_address" class="form-label">Dirección de entrega</label>
                  <textarea class="form-control" id="shipping_address" rows="3" v-model="formData.shipping_address" required></textarea>
                </div>
              </div>
              
              <div class="col-md-6">
                <h6 class="mb-3">Resumen del pedido</h6>
                <div class="list-group mb-3">
                  <div v-for="item in cart.items" :key="item.id" class="list-group-item bg-cream d-flex justify-content-between align-items-center">
                    <div>
                      <span>{{ item.product.name }}</span>
                      <small class="d-block text-muted">{{ item.quantity }} x ${{ item.price }}</small>
                    </div>
                    <span>${{ (item.quantity * item.price).toFixed(2) }}</span>
                  </div>
                </div>
                
                <div class="card bg-cream mb-3">
                  <div class="card-body">
                    <h6 class="card-title">Total</h6>
                    <div class="d-flex justify-content-between fw-bold">
                      <span>Total a pagar</span>
                      <span>${{ cart.total.toFixed(2) }}</span>
                    </div>
                  </div>
                </div>
                
                <h6 class="mb-3">Método de pago</h6>
                <div class="mb-3">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash" v-model="formData.payment_method" checked>
                    <label class="form-check-label" for="payment_cash">
                      Efectivo
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="payment_transfer" value="transfer" v-model="formData.payment_method">
                    <label class="form-check-label" for="payment_transfer">
                      Transferencia bancaria
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="payment_card" value="card" v-model="formData.payment_method">
                    <label class="form-check-label" for="payment_card">
                      Tarjeta de crédito/débito
                    </label>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label for="notes" class="form-label">Notas adicionales</label>
                  <textarea class="form-control" id="notes" rows="2" v-model="formData.notes"></textarea>
                </div>
              </div>
            </div>
            
            <div class="d-grid gap-2 mt-3">
              <button @click="submitOrder" class="btn btn-brown" :disabled="loading">
                <span v-if="loading">
                  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                  Procesando...
                </span>
                <span v-else>Confirmar pedido</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </template>
  
  <script>
  import { ref, computed, watch } from 'vue';
  import axios from 'axios';
  
  export default {
  props: {
    cart: {
      type: Object,
      required: true
    }
  },
  
  emits: ['order-completed'],
  
  setup(props, { emit }) {
    const formData = ref({
      name: '',
      email: '',
      phone: '',
      shipping_address: '',
      payment_method: 'cash',
      notes: ''
    });
    
    const loading = ref(false);
    const orderCompleted = ref(false);
    const orderNumber = ref('');
    const isCheckoutOpen = ref(false);
    
    const submitOrder = async () => {
    loading.value = true;
    
    try {
      console.log('Iniciando proceso de orden con datos:', formData.value);
      console.log('Carrito actual:', props.cart);
      
      // Verificar que el carrito tenga items
      if (!props.cart.items || props.cart.items.length === 0) {
        throw new Error('El carrito está vacío');
      }
      
      // Preparar los items del carrito para enviar al backend
      const cartItems = props.cart.items.map(item => ({
        product_id: item.product_id || (item.product && item.product.id),
        quantity: item.quantity,
        price: item.price
      }));
      
      console.log('Items preparados para enviar:', cartItems);
      
      // Verificar que todos los campos requeridos estén completos
      if (!formData.value.name || !formData.value.email || !formData.value.phone || !formData.value.shipping_address) {
        throw new Error('Por favor completa todos los campos requeridos');
      }
      
      // Configurar opciones de Axios para mejor depuración
      const axiosConfig = {
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      };
      
      // Enviar la orden al backend
      console.log('Enviando solicitud a /api/orders con datos:', {
        ...formData.value,
        cart_items: cartItems
      });
      
      const response = await axios.post('/api/orders', {
        ...formData.value,
        cart_items: cartItems
      }, axiosConfig);
      
      console.log('Respuesta del servidor:', response.data);
      
      // Marcar como completado
      orderCompleted.value = true;
      orderNumber.value = response.data.order_id || response.data.order_number || response.data.id || 'N/A';
      
      // Emitir evento para que el componente padre sepa que la orden se completó
      emit('order-completed');
      
      // Mostrar mensaje de éxito
      alert('¡Pedido completado con éxito! Tu número de orden es: ' + orderNumber.value);
    } catch (error) {
      console.error('Error al procesar la orden:', error);
      
      // Mostrar información detallada del error
      if (error.response) {
        // El servidor respondió con un código de estado fuera del rango 2xx
        console.error('Respuesta del servidor con error:', error.response.data);
        console.error('Código de estado:', error.response.status);
        console.error('Encabezados:', error.response.headers);
        
        let errorMessage = 'Error del servidor: ';
        if (error.response.data && error.response.data.message) {
          errorMessage += error.response.data.message;
        } else {
          errorMessage += 'Código ' + error.response.status;
        }
        
        alert(errorMessage);
      } else if (error.request) {
        // La solicitud se realizó pero no se recibió respuesta
        console.error('No se recibió respuesta del servidor:', error.request);
        alert('No se pudo conectar con el servidor. Por favor, verifica tu conexión a internet.');
      } else {
        // Algo ocurrió al configurar la solicitud que desencadenó un error
        console.error('Error de configuración de la solicitud:', error.message);
        alert('Error al procesar tu pedido: ' + error.message);
      }
    } finally {
      loading.value = false;
    }
  };
    
    const openCheckoutModal = () => {
      isCheckoutOpen.value = true;
    };
    
    const closeModal = () => {
      // Si la orden se completó, resetear el formulario
      if (orderCompleted.value) {
        formData.value = {
          name: '',
          email: '',
          phone: '',
          shipping_address: '',
          payment_method: 'cash',
          notes: ''
        };
        
        // Resetear el estado
        orderCompleted.value = false;
        orderNumber.value = '';
      }
      
      // Cerrar el modal
      isCheckoutOpen.value = false;
    };
    
    return {
      formData,
      loading,
      orderCompleted,
      orderNumber,
      isCheckoutOpen,
      submitOrder,
      openCheckoutModal,
      closeModal
    };
  },
  
  methods: {
    openCheckoutModal() {
      this.isCheckoutOpen = true;
    }
  }
  };
  </script>
  
  <style scoped>
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
    z-index: 1060; /* Mayor que el z-index del modal del carrito */
  }
  
  .custom-modal-content {
    width: 100%;
    max-width: 800px;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
    max-height: 90vh;
    display: flex;
    flex-direction: column;
  }
  
  .custom-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
  }
  
  .custom-modal-body {
    padding: 1rem;
    overflow-y: auto;
    flex-grow: 1;
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
  .bg-brown {
    background-color: #8B4513;
  }
  </style>
  
  