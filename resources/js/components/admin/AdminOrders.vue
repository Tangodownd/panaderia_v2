<template>
  <div class="p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2 class="text-brown mb-0">Todos los pedidos</h2>
        <small class="text-muted">Listado administrativo</small>
      </div>

      <div class="d-flex gap-2">
        <select v-model="filters.status" class="form-select">
          <option value="">Todos los estados</option>
          <option value="pending">Pendiente</option>
          <option value="cash_on_delivery">Contra entrega</option>
          <option value="awaiting_payment">En espera de pago</option>
          <option value="awaiting_review">En revisión</option>
          <option value="processing">Procesando</option>
          <option value="paid">Completado</option>
          <option value="cancelled">Cancelado</option>
        </select>
        <button class="btn btn-brown" @click="load()">Aplicar</button>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-brown" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>

        <template v-else>
          <div v-if="orders.length === 0" class="p-4 text-center text-muted">
            No hay pedidos para mostrar.
          </div>

          <div v-else class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="bg-brown text-white">
                <tr>
                  <th style="width: 110px;">Pedido</th>
                  <th>Cliente</th>
                  <th style="width: 130px;">Total</th>
                  <th style="width: 150px;">Pago</th>
                  <th style="width: 140px;">Estado</th>
                  <th style="width: 170px;">Fecha</th>
                  <th style="width: 120px;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="o in orders" :key="o.id">
                  <td class="text-brown fw-semibold">{{ o.code || ('ORD-' + String(o.id).padStart(4,'0')) }}</td>
                  <td>{{ o.customer_name || '—' }}</td>
                  <td>\${{ Number(o.total || 0).toFixed(2) }}</td>
                  <td>{{ paymentMethod(o.payment_method) }}</td>
                  <td>
                    <span :class="badgeClass(o.status_badge)">{{ o.status_label }}</span>
                  </td>
                  <td>{{ formatDate(o.created_at) }}</td>
                  <td>
                    <button class="btn btn-sm btn-outline-brown" @click="openDetails(o)">
                      Ver detalles
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div class="d-flex justify-content-between align-items-center p-3 border-top">
            <small class="text-muted">
              Página {{ meta.current_page }} de {{ meta.last_page }} — {{ meta.total }} pedidos
            </small>

            <div class="btn-group">
              <button class="btn btn-outline-brown btn-sm" :disabled="meta.current_page <= 1" @click="go(meta.current_page - 1)">
                Anterior
              </button>
              <button class="btn btn-outline-brown btn-sm" :disabled="meta.current_page >= meta.last_page" @click="go(meta.current_page + 1)">
                Siguiente
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Modal Detalles -->
    <div class="modal fade" ref="detailsModal" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title text-brown">Detalle del pedido {{ selected?.code || ('ORD-' + String(selected?.id||'').padStart(4,'0')) }}</h5>
            <button type="button" class="btn-close" @click="hideModal()"></button>
          </div>
          <div class="modal-body">
            <div v-if="detailsLoading" class="text-center py-3">
              <div class="spinner-border text-brown"></div>
            </div>
            <template v-else-if="orderDetails">
              <div class="mb-3">
                <strong>Cliente:</strong> {{ orderDetails.name }}<br>
                <strong>Teléfono:</strong> {{ orderDetails.phone }}<br>
                <strong>Dirección:</strong> {{ orderDetails.shipping_address || '—' }}
              </div>

              <div class="table-responsive">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Producto</th>
                      <th style="width: 100px;">Cant.</th>
                      <th style="width: 120px;">Precio</th>
                      <th style="width: 120px;">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="it in orderDetails.items" :key="it.id">
                      <td>{{ it.name }}</td>
                      <td>{{ it.quantity }}</td>
                      <td>\${{ Number(it.price || it.unit_price || 0).toFixed(2) }}</td>
                      <td>\${{ (Number(it.price || it.unit_price || 0) * Number(it.quantity||0)).toFixed(2) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="d-flex justify-content-end">
                <h5 class="mb-0">Total: <span class="text-brown">\${{ Number(orderDetails.total || 0).toFixed(2) }}</span></h5>
              </div>
            </template>
            <div v-else class="text-muted">No se pudo cargar el detalle.</div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-outline-brown" @click="hideModal()">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';

export default {
  name: 'AdminOrders',
  setup(_, { attrs }) {
    const loading = ref(true);
    const orders  = ref([]);
    const meta    = reactive({ current_page: 1, last_page: 1, total: 0, per_page: 20 });

    const filters = reactive({
      status: new URLSearchParams(window.location.search).get('status') || ''
    });

    // Detalles
    const selected       = ref(null);
    const orderDetails   = ref(null);
    const detailsLoading = ref(false);
    const detailsModal   = ref(null);

    const formatDate = (d) => (d ? new Date(d).toLocaleString() : '—');
    const paymentMethod = (pm) => {
      const m = String(pm || '').toLowerCase();
      if (m === 'cash') return 'Efectivo';
      if (m === 'transfer') return 'Transferencia';
      if (m === 'zelle') return 'Zelle';
      if (m === 'card') return 'Tarjeta';
      if (m === 'mobile') return 'Pago móvil';
      return '—';
    };
    const badgeClass = (b) => {
      return `badge ${b === 'success' ? 'bg-success' :
                      b === 'warning' ? 'bg-warning text-dark' :
                      b === 'danger'  ? 'bg-danger' :
                      b === 'info'    ? 'bg-info text-dark' : 'bg-secondary'}`;
    };

    const load = async (page = 1) => {
      loading.value = true;
      try {
        const { data } = await axios.get('/api/admin/orders', {
          params: { status: filters.status || undefined, page, perPage: meta.per_page }
        });
        orders.value = data?.data || [];
        meta.current_page = data?.meta?.current_page || page;
        meta.last_page    = data?.meta?.last_page || 1;
        meta.total        = data?.meta?.total || orders.value.length;
      } catch (e) {
        console.error('Error cargando pedidos:', e);
        orders.value = [];
      } finally {
        loading.value = false;
      }
    };

    const go = (page) => load(page);

    const openDetails = async (o) => {
      selected.value = o;
      orderDetails.value = null;
      detailsLoading.value = true;
      showModal();

      try {
        const { data } = await axios.get(`/api/orders/${o.id}`);
        orderDetails.value = data;
      } catch (e) {
        console.error('No se pudieron cargar los detalles:', e);
      } finally {
        detailsLoading.value = false;
      }
    };

    const showModal = () => {
      if (!detailsModal.value) {
        detailsModal.value = new bootstrap.Modal(document.querySelector('.modal'), { backdrop: 'static' });
      }
      detailsModal.value.show();
    };
    const hideModal = () => detailsModal.value?.hide();

    onMounted(() => load());

    return {
      loading, orders, meta, filters,
      formatDate, paymentMethod, badgeClass,
      go, load,
      selected, orderDetails, openDetails,
      detailsLoading, detailsModal, hideModal
    };
  }
}
</script>

<style scoped>
.text-brown { color: #8B4513; }
.bg-brown { background-color: #8B4513; }
.btn-brown { background-color: #8B4513; color: #FFF8E7; }
.btn-brown:hover { filter: brightness(0.95); }
.btn-outline-brown { color: #8B4513; border-color: #8B4513; }
.btn-outline-brown:hover { background-color: #8B4513; color: #FFF8E7; }
</style>
