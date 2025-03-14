<template>
  <div class="checkout-form">
    <!-- Modal de Checkout -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content bg-beige">
          <div class="modal-header bg-brown text-white">
            <h5 class="modal-title" id="checkoutModalLabel">Finalizar Compra</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitOrder">
              <!-- Información de contacto -->
              <div class="mb-4">
                <h5 class="text-brown">Información de contacto</h5>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="name" class="form-label">Nombre completo</label>
                    <input type="text" class="form-control border-brown" id="name" v-model="checkout.name" required>
                  </div>
                  <div class="col-md-6">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control border-brown" id="email" v-model="checkout.email" required>
                  </div>
                  <div class="col-md-6">
                    <label for="phone" class="form-label">Teléfono</label>
                    <input type="tel" class="form-control border-brown" id="phone" v-model="checkout.phone" required>
                  </div>
                </div>
              </div>
              
              <!-- Información de envío -->
              <div class="mb-4">
                <h5 class="text-brown">Información de envío</h5>
                <div class="mb-3">
                  <label for="shipping_address" class="form-label">Dirección de envío</label>
                  <textarea class="form-control border-brown" id="shipping_address" v-model="checkout.shipping_address" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                  <label for="notes" class="form-label">Notas adicionales (opcional)</label>
                  <textarea class="form-control border-brown" id="notes" v-model="checkout.notes" rows="2"></textarea>
                </div>
              </div>
              
              <!-- Método de pago -->
              <div class="mb-4">
                <h5 class="text-brown">Método de pago</h5>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash" v-model="checkout.payment_method" checked>
                  <label class="form-check-label" for="payment_cash">
                    <i class="fas fa-money-bill-wave me-2"></i>Efectivo al recibir
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="payment_method" id="payment_transfer" value="transfer" v-model="checkout.payment_method">
                  <label class="form-check-label" for="payment_transfer">
                    <i class="fas fa-university me-2"></i>Transferencia bancaria
                  </label>
                </div>
              </div>
              
              <!-- Resumen del pedido -->
              <div class="card bg-cream mb-4">
                <div class="card-body">
                  <h5 class="card-title text-brown">Resumen del pedido</h5>
                  <div class="table-responsive">
                    <table class="table table-sm">
                      <thead class="table-light">
                        <tr>
                          <th>Producto</th>
                          <th class="text-center">Cantidad</th>
                          <th class="text-end">Precio</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="item in cart.items" :key="item.id">
                          <td>{{ item.product.name || item.product.titulo }}</td>
                          <td class="text-center">{{ item.quantity }}</td>
                          <td class="text-end">{{ formatPrice(item.price * item.quantity) }}</td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr>
                          <th colspan="2" class="text-end">Total:</th>
                          <th class="text-end">{{ formatPrice(cart.total) }}</th>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>
              
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-brown" :disabled="isSubmitting">
                  <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  {{ isSubmitting ? 'Procesando...' : 'Confirmar Pedido' }}
                </button>
                <button type="button" class="btn btn-outline-brown" data-bs-dismiss="modal">Cancelar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal de Confirmación de Pedido -->
    <div class="modal fade" id="orderConfirmationModal" tabindex="-1" aria-labelledby="orderConfirmationModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content bg-beige">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="orderConfirmationModalLabel">¡Pedido Confirmado!</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
            <h4 class="text-brown">Gracias por tu compra</h4>
            <p>Tu pedido ha sido recibido y está siendo procesado.</p>
            <p class="fw-bold">Número de pedido: {{ orderNumber }}</p>
            <p>Te hemos enviado un correo electrónico con los detalles de tu pedido.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-brown" data-bs-dismiss="modal" @click="closeAndReset">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue';
import axios from 'axios';

export default {
  props: {
    cart: {
      type: Object,
      required: true,
      default: () => ({ items: [], total: 0 })
    }
  },
  
  emits: ['order-completed'],
  
  setup(props, { emit }) {
    const checkout = ref({
      name: '',
      email: '',
      phone: '',
      shipping_address: '',
      payment_method: 'cash',
      notes: ''
    });
    
    const isSubmitting = ref(false);
    const orderNumber = ref(null);
    
    const formatPrice = (price) => {
      return `$${parseFloat(price).toFixed(2)}`;
    };
    
    const openCheckoutModal = () => {
      console.log('Abriendo modal de checkout');
      console.log('Datos del carrito:', props.cart);
      
      const modal = document.getElementById('checkoutModal');
      if (modal && typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
      } else {
        console.error('No se pudo abrir el modal de checkout');
      }
    };
    
    const submitOrder = async () => {
      console.log('Iniciando proceso de checkout');
      
      if (!props.cart.items || props.cart.items.length === 0) {
        console.error('El carrito está vacío');
        alert('Tu carrito está vacío. Agrega productos antes de confirmar el pedido.');
        return;
      }
      
      isSubmitting.value = true;
      console.log('Estado de envío:', isSubmitting.value);
      
      try {
        // Preparar datos para la petición
        const orderData = { 
          ...checkout.value,
          // Incluir los items del carrito para asegurar que se procesen correctamente
          cart_items: props.cart.items.map(item => ({
            product_id: item.product.id,
            quantity: item.quantity,
            price: item.price || item.product.price
          }))
        };
        
        console.log('Enviando datos de pedido:', orderData);
        
        // Obtener el token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log('Token CSRF:', csrfToken);
        
        // Realizar la solicitud usando fetch para mayor compatibilidad
        const response = await fetch('/api/orders', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(orderData),
          credentials: 'same-origin'
        });
        
        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(`Error del servidor: ${response.status} - ${errorData.error || response.statusText}`);
        }
        
        const data = await response.json();
        console.log('Respuesta del servidor:', data);
        
        // Guardar número de pedido
        orderNumber.value = data.order_number || 'ORD-' + Math.floor(Math.random() * 10000);
        
        // Mostrar confirmación
        const checkoutModal = document.getElementById('checkoutModal');
        if (checkoutModal && typeof bootstrap !== 'undefined') {
          const modal = bootstrap.Modal.getInstance(checkoutModal);
          if (modal) modal.hide();
        }
        
        const confirmationModal = document.getElementById('orderConfirmationModal');
        if (confirmationModal && typeof bootstrap !== 'undefined') {
          const modal = new bootstrap.Modal(confirmationModal);
          modal.show();
        }
        
        // Notificar que la orden se completó
        emit('order-completed');
        
      } catch (error) {
        console.error('Error al procesar el pedido:', error);
        alert('Ha ocurrido un error al procesar tu pedido: ' + error.message);
      } finally {
        isSubmitting.value = false;
        console.log('Finalizado proceso de checkout, estado de envío:', isSubmitting.value);
      }
    };
    
    const closeAndReset = () => {
      // Limpiar el formulario
      checkout.value = {
        name: '',
        email: '',
        phone: '',
        shipping_address: '',
        payment_method: 'cash',
        notes: ''
      };
      
      // Notificar que la orden se completó para que se actualice el carrito
      emit('order-completed');
    };
    
    return {
      checkout,
      isSubmitting,
      orderNumber,
      formatPrice,
      openCheckoutModal,
      submitOrder,
      closeAndReset
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
</style>