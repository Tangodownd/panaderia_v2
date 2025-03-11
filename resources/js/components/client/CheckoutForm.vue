<template>
    <div class="checkout-form">
      <!-- Checkout Modal -->
      <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content bg-beige">
            <div class="modal-header bg-brown text-white">
              <h5 class="modal-title">Finalizar Compra</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-6">
                  <h5 class="text-brown mb-3">Información de Contacto</h5>
                  <form @submit.prevent="submitOrder">
                    <div class="mb-3">
                      <label for="name" class="form-label">Nombre completo</label>
                      <input type="text" class="form-control border-brown" id="name" v-model="checkout.name" required>
                    </div>
                    <div class="mb-3">
                      <label for="email" class="form-label">Correo electrónico</label>
                      <input type="email" class="form-control border-brown" id="email" v-model="checkout.email" required>
                    </div>
                    <div class="mb-3">
                      <label for="phone" class="form-label">Teléfono (WhatsApp)</label>
                      <input type="tel" class="form-control border-brown" id="phone" v-model="checkout.phone" required>
                    </div>
                    <div class="mb-3">
                      <label for="address" class="form-label">Dirección de entrega</label>
                      <textarea class="form-control border-brown" id="address" rows="3" v-model="checkout.address" required></textarea>
                    </div>
                    <div class="mb-3">
                      <label for="notes" class="form-label">Notas adicionales (opcional)</label>
                      <textarea class="form-control border-brown" id="notes" rows="2" v-model="checkout.notes"></textarea>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Método de pago</label>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="paymentMethod" id="cashOnDelivery" value="cash" v-model="checkout.paymentMethod" checked>
                        <label class="form-check-label" for="cashOnDelivery">
                          Efectivo contra entrega
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="paymentMethod" id="transfer" value="transfer" v-model="checkout.paymentMethod">
                        <label class="form-check-label" for="transfer">
                          Transferencia bancaria
                        </label>
                      </div>
                    </div>
                    <button type="submit" class="btn btn-brown" :disabled="isSubmitting">
                      <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                      {{ isSubmitting ? 'Procesando...' : 'Confirmar Pedido' }}
                    </button>
                  </form>
                </div>
                <div class="col-md-6">
                  <h5 class="text-brown mb-3">Resumen del Pedido</h5>
                  <div class="list-group mb-3">
                    <div v-for="(item, index) in cart.items" :key="index" class="list-group-item bg-cream border-brown d-flex justify-content-between align-items-center">
                      <div>
                        <span class="fw-bold">{{ item.quantity }}x</span> {{ item.product.titulo }}
                      </div>
                      <span>{{ formatPrice(calculateItemTotal(item)) }}</span>
                    </div>
                  </div>
                  
                  <div class="card bg-cream mb-3">
                    <div class="card-body">
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
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Order Confirmation Modal -->
      <div class="modal fade" id="orderConfirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content bg-beige">
            <div class="modal-header bg-success text-white">
              <h5 class="modal-title">¡Pedido Confirmado!</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
              <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
              <h4 class="text-brown mb-3">Gracias por tu compra</h4>
              <p>Tu pedido ha sido recibido y está siendo procesado.</p>
              <p>Hemos enviado un mensaje de WhatsApp al número proporcionado con los detalles de tu pedido.</p>
              <p class="mb-0"><strong>Número de pedido:</strong> {{ orderNumber }}</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-brown" data-bs-dismiss="modal" @click="resetCart">Continuar</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, computed, watch } from 'vue';
  
  export default {
    props: {
      cart: {
        type: Object,
        required: true
      }
    },
    
    emits: ['order-completed'],
    
    setup(props, { emit }) {
      const checkout = ref({
        name: '',
        email: '',
        phone: '',
        address: '',
        notes: '',
        paymentMethod: 'cash'
      });
      
      const isSubmitting = ref(false);
      const orderNumber = ref('');
  
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
        return props.cart.items.reduce((total, item) => {
          return total + (item.product.precio * item.quantity);
        }, 0);
      };
  
      const calculateDiscount = () => {
        return props.cart.items.reduce((total, item) => {
          const discount = item.product.descuento || 0;
          return total + ((item.product.precio * discount / 100) * item.quantity);
        }, 0);
      };
  
      const calculateTotal = () => {
        return props.cart.items.reduce((total, item) => {
          return total + calculateItemTotal(item);
        }, 0);
      };
  
      const submitOrder = async () => {
        if (props.cart.items.length === 0) {
          alert('Tu carrito está vacío. Agrega productos antes de confirmar el pedido.');
          return;
        }
        
        isSubmitting.value = true;
        
        try {
          // Generar número de pedido aleatorio
          orderNumber.value = 'ORD-' + Math.floor(Math.random() * 10000).toString().padStart(4, '0');
          
          // Simular envío de pedido
          await new Promise(resolve => setTimeout(resolve, 2000));
          
          // Simular envío de WhatsApp
          console.log('Enviando WhatsApp a:', checkout.value.phone);
          console.log('Detalles del pedido:', {
            orderNumber: orderNumber.value,
            customer: checkout.value,
            items: props.cart.items,
            total: calculateTotal()
          });
          
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
          
        } catch (error) {
          console.error('Error al procesar el pedido:', error);
          alert('Ha ocurrido un error al procesar tu pedido. Por favor, inténtalo de nuevo.');
        } finally {
          isSubmitting.value = false;
        }
      };
  
      const resetCart = () => {
        emit('order-completed');
        
        // Limpiar formulario
        checkout.value = {
          name: '',
          email: '',
          phone: '',
          address: '',
          notes: '',
          paymentMethod: 'cash'
        };
      };
  
      const openCheckoutModal = () => {
        const modalElement = document.getElementById('checkoutModal');
        if (modalElement && typeof bootstrap !== 'undefined') {
          const modal = new bootstrap.Modal(modalElement);
          modal.show();
        } else {
          console.error('No se pudo abrir el modal de checkout');
        }
      };
  
      // Observar cambios en el carrito para actualizar cálculos
      watch(() => props.cart, () => {
        // Actualizar cálculos si es necesario
      }, { deep: true });
  
      return {
        checkout,
        isSubmitting,
        orderNumber,
        formatPrice,
        calculateItemTotal,
        calculateSubtotal,
        calculateDiscount,
        calculateTotal,
        submitOrder,
        resetCart,
        openCheckoutModal
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
  </style>
  
  