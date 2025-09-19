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
  <p>Tu pedido ha sido recibido y está siendo procesado.</p>
  <p v-if="whatsappSent" class="mt-2">
    <i class="fab fa-whatsapp text-success"></i> 
    Hemos enviado un mensaje de WhatsApp al número proporcionado con los detalles de tu pedido.
  </p>
  <p v-else class="mt-2 text-muted small">
    <i class="fas fa-info-circle"></i>
    Nota: Para recibir mensajes de WhatsApp, asegúrate de enviar primero "join [código]" al número de WhatsApp de Twilio.
  </p>

  <!-- NUEVO: botones de acción -->
  <div class="d-flex gap-2 justify-content-center mt-3">
    <button class="btn btn-outline-secondary" @click="printInvoice" :disabled="!orderNumber">
      Imprimir factura
    </button>
    <button class="btn btn-brown" @click="closeModal">
      Continuar comprando
    </button>
  </div>
</div>

          
          <div v-else>
            <div class="row">
              <div class="col-md-6">
                <h6 class="mb-3">Información de contacto</h6>
                <div class="mb-3">
                  <label for="name" class="form-label">Nombre completo</label>
                  <input type="text" class="form-control" id="name" v-model="formData.name" required>
                  <div v-if="validationErrors.name" class="text-danger mt-1">{{ validationErrors.name }}</div>
                </div>
                <div class="mb-3">
                  <label for="email" class="form-label">Correo electrónico</label>
                  <input type="email" class="form-control" id="email" v-model="formData.email" required>
                  <div v-if="validationErrors.email" class="text-danger mt-1">{{ validationErrors.email }}</div>
                </div>
                <div class="mb-3">
                  <label for="phone" class="form-label">Teléfono</label>
                  <div class="input-group">
                    <select class="form-select" style="max-width: 120px;" v-model="selectedCountryCode">
                      <option value="+58">Venezuela (+58)</option>
                      <option value="+1">Estados Unidos (+1)</option>
                      <option value="+57">Colombia (+57)</option>
                      <option value="+34">España (+34)</option>
                      <option value="+52">México (+52)</option>
                      <option value="+51">Perú (+51)</option>
                      <option value="+56">Chile (+56)</option>
                      <option value="+54">Argentina (+54)</option>
                      <option value="+55">Brasil (+55)</option>
                    </select>
                    <input 
                      type="tel" 
                      class="form-control" 
                      id="phone" 
                      v-model="phoneNumber" 
                      placeholder="Ej: 4121234567"
                      @input="validatePhone"
                      required
                    >
                  </div>
                  <div v-if="validationErrors.phone" class="text-danger mt-1">{{ validationErrors.phone }}</div>
                </div>
                <div class="mb-3">
                  <label for="shipping_address" class="form-label">Dirección de entrega</label>
                  <textarea class="form-control" id="shipping_address" rows="3" v-model="formData.shipping_address" required></textarea>
                  <div v-if="validationErrors.shipping_address" class="text-danger mt-1">{{ validationErrors.shipping_address }}</div>
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

                <!-- NUEVO: Subida de comprobante si NO es efectivo -->
                <div class="mb-3" v-if="formData.payment_method !== 'cash'">
                  <label class="form-label">Comprobante (JPG, PNG o PDF, máx. 5MB)</label>
                  <input
                    type="file"
                    accept=".jpg,.jpeg,.png,.pdf"
                    class="form-control"
                    @change="onFile"
                    :required="formData.payment_method !== 'cash'"
                  />
                  <small class="text-muted d-block mt-1">
                    Al subir el comprobante, el sistema intentará confirmar tu pago automáticamente.
                  </small>
                  <div v-if="validationErrors.payment_proof" class="text-danger mt-1">
                    {{ validationErrors.payment_proof }}
                  </div>
                </div>
                
                <div class="mb-3">
                  <label for="notes" class="form-label">Notas adicionales</label>
                  <textarea class="form-control" id="notes" rows="2" v-model="formData.notes"></textarea>
                </div>
                
                <!-- Términos y condiciones con enlaces a páginas nuevas -->
                <div class="mb-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="terms" v-model="formData.termsAccepted">
                    <label class="form-check-label" for="terms">
                      He leído y acepto los 
                      <a href="/terminos-y-condiciones" target="_blank">Términos y Condiciones</a> 
                      y la 
                      <a href="/politica-de-privacidad" target="_blank">Política de Privacidad</a>
                    </label>
                  </div>
                  <div v-if="validationErrors.terms" class="text-danger mt-1">{{ validationErrors.terms }}</div>
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

            <!-- NUEVO: mensajes de error/éxito no intrusivos -->
            <div v-if="submitError" class="alert alert-danger mt-3" role="alert">
              {{ submitError }}
            </div>
            <div v-if="submitSuccess" class="alert alert-success mt-3" role="alert">
              {{ submitSuccess }}
            </div>
            <!-- /NUEVO -->
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
      notes: '',
      termsAccepted: false
    });
    
    const loading = ref(false);
    const orderCompleted = ref(false);
    const orderNumber = ref('');
    const isCheckoutOpen = ref(false);
    const selectedCountryCode = ref('+58'); // Código de país por defecto (Venezuela)
    const phoneNumber = ref(''); // Número de teléfono sin código de país
    const whatsappSent = ref(false);
    // ---- Helpers de formato ----
const fmtMoney = (n) =>
  Number(n || 0).toLocaleString(undefined, { style: 'currency', currency: 'USD', minimumFractionDigits: 2 });

// Si orderNumber ya es el ID, puedes simplificar esto
const resolvedOrderId = () => String(orderNumber.value || '').replace(/[^\d]/g, '') || orderNumber.value;

// ---- Acción principal: imprimir factura ----
const printInvoice = async () => {
  try {
    const id = resolvedOrderId();
    if (!id) return;

    // 1) Cargar detalles de la orden
    const { data: order } = await axios.get(`/api/orders/${id}`);

    // 2) Construir HTML de la factura
    const html = buildInvoiceHTML(order);

    // 3) Abrir nueva ventana e imprimir
    const w = window.open('', '_blank', 'width=900,height=700');
    if (!w) {
      alert('Tu navegador bloqueó la ventana de impresión. Habilita pop-ups para continuar.');
      return;
    }
    w.document.open();
    w.document.write(html);
    w.document.close();
    w.onload = () => {
      w.focus();
      w.print();
      // w.close(); // opcional si quieres cerrar automáticamente
    };
  } catch (e) {
    console.error('No se pudo imprimir la factura:', e);
    alert('No se pudo generar la factura. Intenta nuevamente.');
  }
};

// ---- Plantilla de HTML (puede ir como template string en el script) ----
const buildInvoiceHTML = (order) => {
  // Preparar ítems (ajusta si tus claves difieren)
  const items = (order.items || order.order_items || []).map((it) => {
    const name  = it.product?.name || it.name || `Producto #${it.product_id}`;
    const qty   = it.quantity || 0;
    const price = it.price || it.product?.price || 0;
    const line  = qty * price;
    return { name, qty, price, line };
  });

  const subtotal = items.reduce((s, x) => s + x.line, 0);
  const taxRate  = 0.16; // mismo que CartService (IVA 16%) :contentReference[oaicite:1]{index=1}
  const tax      = subtotal * taxRate;
  const total    = subtotal + tax;

  const invoiceNumber = order.id || order.order_id || '—';
  const createdAt     = order.created_at ? new Date(order.created_at) : new Date();
  const dateStr       = createdAt.toLocaleDateString() + ' ' + createdAt.toLocaleTimeString();

  const customerName  = order.name || order.customer_name || '—';
  const customerPhone = order.phone || order.customer_phone || '—';
  const customerEmail = order.email || order.customer_email || '—';
  const address       = order.shipping_address || order.address || '—';
  const status        = (order.status || '').toUpperCase();

  // A partir de aquí es puro HTML+CSS (sí, va aquí dentro como string)
  return `<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"/>
<title>Factura #${invoiceNumber}</title>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<style>
  :root{ --brown:#8B4513; --cream:#FFF8E7; --beige:#F5E6D3; --ink:#222; }
  *{ box-sizing:border-box; } body{ font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Arial; color:var(--ink); margin:0; }
  .invoice{ max-width:850px; margin:24px auto; padding:24px; border:1px solid #eee; border-radius:10px; }
  .head{ display:flex; align-items:center; justify-content:space-between; gap:16px; }
  .brand{ display:flex; align-items:center; gap:14px; }
  .logo{ width:54px;height:54px;border-radius:12px;background:var(--brown);display:inline-flex;align-items:center;justify-content:center;color:#fff;font-weight:800; }
  h1{ margin:0; font-size:22px; letter-spacing:.3px; }
  .meta{ text-align:right; font-size:13px; color:#444; }
  .badge{ display:inline-block; padding:3px 8px; border-radius:999px; background:var(--beige); color:var(--brown); font-weight:600; font-size:12px; }
  .row{ display:flex; gap:24px; margin-top:18px; }
  .card{ flex:1; background:var(--cream); border:1px solid #f0e6d8; border-radius:10px; padding:14px 16px; }
  .card h3{ margin:0 0 8px; font-size:14px; color:#333; text-transform:uppercase; letter-spacing:.4px; }
  table{ width:100%; border-collapse:collapse; margin-top:16px; }
  th, td{ padding:10px 8px; text-align:left; border-bottom:1px solid #eee; font-size:14px; }
  th{ background:#faf7f0; font-size:12px; text-transform:uppercase; color:#555; letter-spacing:.4px; }
  .tright{ text-align:right; }
  .totals{ max-width:340px; margin-left:auto; margin-top:12px; }
  .totals .line{ display:flex; justify-content:space-between; padding:6px 0; }
  .totals .grand{ font-weight:800; border-top:2px solid #eee; padding-top:10px; font-size:16px; }
  .note{ margin-top:18px; font-size:12px; color:#666; }
  .footer{ margin-top:20px; font-size:12px; color:#777; display:flex; justify-content:space-between; }
  @media print{ .invoice{ border:none; margin:0; border-radius:0; } }
</style>
</head>
<body>
  <div class="invoice">
    <div class="head">
      <div class="brand">
        <div class="logo">PO</div>
        <div>
          <h1>Panadería Orquídea de Oro</h1>
          <div style="font-size:12px;color:#666">RIF: J-12345678-9 · +58 412-0000000 · Av. Principal, Valencia</div>
        </div>
      </div>
      <div class="meta">
        <div><strong>Factura #</strong> ${invoiceNumber}</div>
        <div><strong>Fecha:</strong> ${dateStr}</div>
        <div><span class="badge">Estado: ${status || '—'}</span></div>
      </div>
    </div>

    <div class="row">
      <div class="card">
        <h3>Cliente</h3>
        <div><strong>${customerName}</strong></div>
        <div>${address}</div>
        <div>Tel: ${customerPhone}</div>
        <div>Email: ${customerEmail}</div>
      </div>
      <div class="card">
        <h3>Vendedor</h3>
        <div><strong>Panadería Orquídea de Oro</strong></div>
        <div>RIF: J-12345678-9</div>
        <div>Whatsapp: +58 412-0000000</div>
        <div>Dirección: Av. Principal, Valencia</div>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Producto</th>
          <th class="tright">Cant.</th>
          <th class="tright">Precio unit.</th>
          <th class="tright">Importe</th>
        </tr>
      </thead>
      <tbody>
        ${items.map(x => `
          <tr>
            <td>${x.name}</td>
            <td class="tright">${x.qty}</td>
            <td class="tright">${fmtMoney(x.price)}</td>
            <td class="tright">${fmtMoney(x.line)}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>

    <div class="totals">
      <div class="line"><span>Subtotal</span><span>${fmtMoney(subtotal)}</span></div>
      <div class="line"><span>IVA (16%)</span><span>${fmtMoney(tax)}</span></div>
      <div class="line grand"><span>Total</span><span>${fmtMoney(total)}</span></div>
    </div>

    <div class="note">* Gracias por su compra. Para máxima frescura, consumir el mismo día.</div>
    <div class="footer">
      <div>Atendido por: Sistema</div>
      <div>Este documento es válido como comprobante de compra.</div>
    </div>
  </div>
</body>
</html>`;
};


    const validationErrors = ref({
      name: '',
      email: '',
      phone: '',
      shipping_address: '',
      terms: '',
      // NUEVO
      payment_proof: ''
      // /NUEVO
    });

    // NUEVO: archivo de comprobante y mensajes
    const paymentFile = ref(null);
    const submitError = ref('');
    const submitSuccess = ref('');
    // /NUEVO
    
    // Actualizar el número de teléfono completo cuando cambie el código de país o el número
    watch([selectedCountryCode, phoneNumber], () => {
      formData.value.phone = selectedCountryCode.value + phoneNumber.value;
      console.log('Número de teléfono actualizado:', formData.value.phone);
    });
    
    // Validar el formato del correo electrónico
    const validateEmail = () => {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!formData.value.email) {
        validationErrors.value.email = 'El correo electrónico es requerido';
        return false;
      } else if (!emailRegex.test(formData.value.email)) {
        validationErrors.value.email = 'Ingresa un correo electrónico válido';
        return false;
      } else {
        validationErrors.value.email = '';
        return true;
      }
    };
    
    // Validar el número de teléfono según el país seleccionado
    const validatePhone = () => {
      // Permitir 0 inicial en VE; eliminar otros no numéricos
      phoneNumber.value = phoneNumber.value.replace(/[^\d0]/g, '');
      
      let isValid = false;
      let errorMessage = '';
      
      if (selectedCountryCode.value === '+58') {
        if (!phoneNumber.value) {
          errorMessage = 'El número de teléfono es requerido';
        } else if (phoneNumber.value.length === 10) {
          isValid = true;
        } else if (phoneNumber.value.length === 11 && phoneNumber.value.startsWith('0')) {
          isValid = true;
        } else {
          errorMessage = 'El número debe tener 10 dígitos o 11 dígitos si comienza con 0';
        }
      } else if (selectedCountryCode.value === '+1') {
        if (!phoneNumber.value) {
          errorMessage = 'El número de teléfono es requerido';
        } else if (phoneNumber.value.length !== 10) {
          errorMessage = 'El número debe tener 10 dígitos';
        } else {
          isValid = true;
        }
      } else {
        if (!phoneNumber.value) {
          errorMessage = 'El número de teléfono es requerido';
        } else if (phoneNumber.value.length < 8 || phoneNumber.value.length > 15) {
          errorMessage = 'El número debe tener entre 8 y 15 dígitos';
        } else {
          isValid = true;
        }
      }
      
      validationErrors.value.phone = errorMessage;
      return isValid;
    };
    
    // Validar todos los campos del formulario
    const validateForm = () => {
      let isValid = true;
      
      if (!formData.value.name) {
        validationErrors.value.name = 'El nombre es requerido';
        isValid = false;
      } else {
        validationErrors.value.name = '';
      }
      
      if (!validateEmail()) {
        isValid = false;
      }
      
      if (!validatePhone()) {
        isValid = false;
      }
      
      if (!formData.value.shipping_address) {
        validationErrors.value.shipping_address = 'La dirección de entrega es requerida';
        isValid = false;
      } else {
        validationErrors.value.shipping_address = '';
      }
      
      if (!formData.value.termsAccepted) {
        validationErrors.value.terms = 'Debes aceptar los términos y condiciones';
        isValid = false;
      } else {
        validationErrors.value.terms = '';
      }

      // NUEVO: si no es efectivo, exigir archivo
      if (formData.value.payment_method !== 'cash' && !paymentFile.value) {
        validationErrors.value.payment_proof = 'Debes adjuntar el comprobante (JPG, PNG o PDF).';
        isValid = false;
      } else {
        validationErrors.value.payment_proof = '';
      }
      // /NUEVO
      
      return isValid;
    };

    // NUEVO: handler de archivo
    const onFile = (e) => {
      paymentFile.value = e.target.files?.[0] || null;
    };
    // /NUEVO
    
    const submitOrder = async () => {
      loading.value = true;
      submitError.value = '';
      submitSuccess.value = '';
      
      try {
        console.log('Iniciando proceso de orden con datos:', formData.value);
        console.log('Carrito actual:', props.cart);
        
        if (!props.cart.items || props.cart.items.length === 0) {
          throw new Error('El carrito está vacío');
        }
        
        if (!validateForm()) {
          throw new Error('Por favor corrige los errores en el formulario');
        }
        
        const cartItems = props.cart.items.map(item => ({
          product_id: item.product_id || (item.product && item.product.id),
          quantity: item.quantity,
          price: item.price
        }));
        
        const axiosConfig = {
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        };
        
        // 1) Crear la orden (awaiting_payment o cash_on_delivery)
        const response = await axios.post('/api/orders', {
          ...formData.value,
          cart_items: cartItems
        }, axiosConfig);
        
        console.log('Respuesta /api/orders:', response.data);
        const created = response.data || {};
        const orderId = created.order_id || created.id;

        // 2) AUTO-CONFIRMAR subiendo comprobante (si no es efectivo)
        if (formData.value.payment_method !== 'cash') {
          if (!paymentFile.value) {
            throw new Error('Debes adjuntar el comprobante para este método de pago.');
          }
          const fd = new FormData();
          fd.append('payment_proof', paymentFile.value);

          const proofRes = await axios.post(`/api/orders/${orderId}/payment-proof`, fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
          });
          console.log('Respuesta /payment-proof:', proofRes.data);

          const finalStatus = proofRes?.data?.order_status || created?.status;
          submitSuccess.value = finalStatus === 'paid'
            ? '¡Pago confirmado! Tu orden ha sido procesada con éxito.'
            : 'Comprobante recibido. Tu pago está en revisión.';
        } else {
          // Efectivo: flujo a WhatsApp/entrega
          submitSuccess.value = 'Orden creada. Pagarás en efectivo al recibir tu pedido.';
        }

        // 3) Marcar como completado en UI
        orderCompleted.value = true;
        orderNumber.value = created.order_id || created.order_number || created.id || 'N/A';
        whatsappSent.value = created.whatsapp_sent || false;
        
        emit('order-completed');
        
      } catch (error) {
        console.error('Error al procesar la orden:', error);
        
        if (error.message === 'Por favor corrige los errores en el formulario') {
          console.warn('Errores de validación:', validationErrors.value);
          submitError.value = error.message;
          loading.value = false;
          return;
        }
        
        if (error.response) {
          let msg = 'Error del servidor';
          if (error.response.data?.message) {
            msg = error.response.data.message;
          } else if (error.response.status) {
            msg += ` (código ${error.response.status})`;
          }
          submitError.value = msg;
        } else if (error.request) {
          submitError.value = 'No se pudo conectar con el servidor. Verifica tu conexión.';
        } else {
          submitError.value = 'Error al procesar tu pedido: ' + error.message;
        }
      } finally {
        loading.value = false;
      }
    };
    
    const openCheckoutModal = () => {
      isCheckoutOpen.value = true;
    };
    
    const closeModal = () => {
      if (orderCompleted.value) {
        formData.value = {
          name: '',
          email: '',
          phone: '',
          shipping_address: '',
          payment_method: 'cash',
          notes: '',
          termsAccepted: false
        };
        
        orderCompleted.value = false;
        orderNumber.value = '';
        phoneNumber.value = '';
        whatsappSent.value = false;
        validationErrors.value = {
          name: '',
          email: '',
          phone: '',
          shipping_address: '',
          terms: '',
          payment_proof: '' // NUEVO
        };
        // NUEVO
        paymentFile.value = null;
        submitError.value = '';
        submitSuccess.value = '';
        // /NUEVO
      }
      isCheckoutOpen.value = false;
    };
    
    return {
      formData,
      loading,
      orderCompleted,
      orderNumber,
      isCheckoutOpen,
      selectedCountryCode,
      phoneNumber,
      whatsappSent,
      validationErrors,
      submitOrder,
      openCheckoutModal,
      closeModal,
      // NUEVO
      onFile,
      submitError,
      submitSuccess,
      printInvoice
      // /NUEVO
      
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
  z-index: 1050;
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
  
.text-danger {
  color: #dc3545;
  font-size: 0.875rem;
}

/* NUEVO: estilos opcionales para alerts (si usas Bootstrap ya están) */
.alert { margin-bottom: 0; }
/* /NUEVO */
</style>
