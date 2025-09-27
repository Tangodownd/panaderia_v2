<template>
  <div class="dashboard-home">
    <div class="row">
      <div class="col-12 mb-4">
        <h1 class="text-brown">Panel de Administración</h1>
        <p class="text-muted">Bienvenido al panel de administración de Panadería Orquidea de Oro</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Total Productos</h6>
                <h3 class="text-brown mb-0">{{ stats.totalProducts }}</h3>
              </div>
              <div class="bg-light-brown rounded-circle p-3">
                <i class="fas fa-box fa-2x text-white"></i>
              </div>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0">
            <router-link :to="{ name: 'mostrarBlogs' }" class="text-brown text-decoration-none">
              Ver todos <i class="fas fa-arrow-right ms-1"></i>
            </router-link>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Total Categorías</h6>
                <h3 class="text-brown mb-0">{{ stats.totalCategories }}</h3>
              </div>
              <div class="bg-light-brown rounded-circle p-3">
                <i class="fas fa-tags fa-2x text-white"></i>
              </div>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0">
            <router-link :to="{ name: 'adminCategories' }" class="text-brown text-decoration-none">
              Ver todas <i class="fas fa-arrow-right ms-1"></i>
            </router-link>
          </div>
        </div>
      </div>

<!-- Pedidos Pendientes -->
<div class="col-md-3 mb-3">
  <div class="card border-0 shadow-sm h-100">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h6 class="text-muted mb-1">Pedidos Pendientes</h6>
          <h3 class="text-brown mb-0">{{ stats.pendingOrders }}</h3>
        </div>
        <div class="bg-light-brown rounded-circle p-3">
          <i class="fas fa-shopping-cart fa-2x text-white"></i>
        </div>
      </div>
    </div>
    <div class="card-footer bg-transparent border-0">
      <!-- NUEVO: Ver todos (filtrados a pendientes) -->
      <router-link :to="{ name: 'adminOrders', query: { status: 'pending' } }" class="text-brown text-decoration-none">
        Ver todos <i class="fas fa-arrow-right ms-1"></i>
      </router-link>
    </div>
  </div>
</div>


      <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Productos sin Stock</h6>
                <h3 class="text-brown mb-0">{{ stats.outOfStockProducts }}</h3>
              </div>
              <div class="bg-light-brown rounded-circle p-3">
                <i class="fas fa-exclamation-triangle fa-2x text-white"></i>
              </div>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0">
            <router-link :to="{ name: 'mostrarBlogs', query: { filter: 'out-of-stock' } }" class="text-brown text-decoration-none">
              Ver productos <i class="fas fa-arrow-right ms-1"></i>
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Products -->
    <div class="row mb-4">
      <div class="col-md-6 mb-3">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-brown text-white">
            <h5 class="mb-0">Productos Recientes</h5>
          </div>
          <div class="card-body">
            <div v-if="loading.products" class="text-center py-3">
              <div class="spinner-border text-brown" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
            </div>
            <div v-else-if="recentProducts.length === 0" class="text-center py-3">
              <p class="text-muted mb-0">No hay productos recientes</p>
            </div>
            <ul v-else class="list-group list-group-flush">
              <li v-for="product in recentProducts" :key="product.id" class="list-group-item d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                  <img
                    :src="getProductImage(product?.thumbnail)"
                    class="rounded me-3"
                    alt="Producto"
                    style="width: 40px; height: 40px; object-fit: cover;"
                    @error="handleImageError"
                  >
                  <div>
                    <h6 class="mb-0 text-brown">{{ product?.titulo || 'Sin título' }}</h6>
                    <small class="text-muted">{{ formatPrice(product?.precio || 0) }}</small>
                  </div>
                </div>
                <router-link :to="{ name: 'editarBlog', params: { id: product?.id } }" class="btn btn-sm btn-outline-brown">
                  <i class="fas fa-edit"></i>
                </router-link>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="col-md-6 mb-3">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-brown text-white">
            <h5 class="mb-0">Pedidos Recientes</h5>
          </div>
          <div class="card-body">
            <div class="card-footer bg-transparent border-0">
  <!-- NUEVO: Ver todos (sin filtro) -->
  <router-link :to="{ name: 'adminOrders' }" class="text-brown text-decoration-none">
    Ver todos <i class="fas fa-arrow-right ms-1"></i>
  </router-link>
</div>

            <div v-if="loading.orders" class="text-center py-3">
              <div class="spinner-border text-brown" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
            </div>

            <div v-else-if="recentOrders.length === 0" class="text-center py-3">
              <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
              <p class="text-muted mb-0">No hay pedidos recientes</p>
              <p class="text-muted small">Los pedidos aparecerán aquí cuando los clientes realicen compras</p>
            </div>

            <ul v-else class="list-group list-group-flush">
              <li
                v-for="order in recentOrders"
                :key="order.id"
                class="list-group-item d-flex justify-content-between align-items-center"
              >
                <div>
                  <h6 class="mb-0 text-brown">{{ displayOrderNumber(order) }}</h6>
                  <small class="text-muted">
                    {{ formatDate(order?.created_at || order?.date) }}
                    &nbsp;–&nbsp;
                    {{ displayCustomerName(order) }}
                    <span v-if="order?.payment_method" class="ms-2">
                      • {{ paymentMethodLabel(order.payment_method) }}
                    </span>
                    <span v-if="order?.total != null" class="ms-2">
                      • {{ formatPrice(order.total) }}
                    </span>
                  </small>
                </div>

                <div class="d-flex align-items-center gap-2">
                  <span :class="badgeClassFromOrder(order)">
                    {{ statusTextFromOrder(order) }}
                  </span>

                  <!-- Botón: completar efectivo -->
                  <button
                    v-if="canCompleteCash(order)"
                    class="btn btn-sm btn-brown ms-2"
                    :disabled="completingIds.has(order.id)"
                    @click="completeCash(order)"
                    title="Marcar como completado"
                  >
                    <span v-if="completingIds.has(order.id)" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                    Completar
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

export default {
  setup() {
    const stats = reactive({
      totalProducts: 0,
      totalCategories: 0,
      pendingOrders: 0,
      outOfStockProducts: 0
    });

    const recentProducts = ref([]);
    const recentOrders  = ref([]);

    const loading = reactive({
      stats: true,
      products: true,
      orders: true
    });

    // ids que se están completando (para deshabilitar botón por orden)
    const completingIds = ref(new Set());

    // ---------- FETCHERS ----------
    const fetchStats = async () => {
      loading.stats = true;
      try {
        const { data } = await axios.get('/api/admin/stats');
        Object.assign(stats, data || {});
      } catch (e) {
        console.error('Error fetching stats:', e);
      } finally {
        loading.stats = false;
      }
    };

    const fetchRecentProducts = async () => {
      loading.products = true;
      try {
        const { data } = await axios.get('/api/products?limit=5');
        const arr = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
        recentProducts.value = arr
          .filter(p => p && p.id)
          .slice(0, 10)
          .map(p => ({
            id: p.id,
            titulo: p.name || 'Sin título',
            precio: Number(p.price || 0),
            thumbnail: p.image || null
          }));
      } catch (e) {
        console.error('Error fetching recent products:', e);
        recentProducts.value = [];
      } finally {
        loading.products = false;
      }
    };

    const fetchRecentOrders = async () => {
      loading.orders = true;
      try {
        const { data } = await axios.get('/api/admin/orders/recent');
        // Soporta [{..}] o { data: [{..}] }
        const list = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
        recentOrders.value = list;

        // Opcional: actualizar contador de pendientes con los estados del backend
        const pendingLike = new Set(['pending', 'awaiting_payment', 'cash_on_delivery', 'awaiting_review']);
        stats.pendingOrders = list.filter(o => pendingLike.has((o.status || '').toLowerCase())).length;
      } catch (e) {
        console.error('Error fetching recent orders:', e);
        recentOrders.value = [];
      } finally {
        loading.orders = false;
      }
    };

    // ---------- HELPERS VISUALES ----------
    const getProductImage = (thumbnail) => {
      if (!thumbnail) {
        return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2240%22%20height%3D%2240%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2040%2040%22%20preserveAspectRatio%3D%22none%22%3E%3Crect%20width%3D%2240%22%20height%3D%2240%22%20fill%3D%22%23E9ECEF%22%2F%3E%3Ctext%20x%3D%2213%22%20y%3D%2222%22%20style%3D%22fill%3A%23999%3Bfont-size%3A10pt%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%22%3EN%2FA%3C%2Ftext%3E%3C%2Fsvg%3E';
      }
      if (String(thumbnail).startsWith('http')) return thumbnail;
      return `${window.location.origin}/storage/${thumbnail}`;
    };

    const handleImageError = (e) => {
      e.target.src =
        'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2240%22%20height%3D%2240%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2040%2040%22%20preserveAspectRatio%3D%22none%22%3E%3Crect%20width%3D%2240%22%20height%3D%2240%22%20fill%3D%22%23f8d7da%22%2F%3E%3Ctext%20x%3D%2211%22%20y%3D%2222%22%20style%3D%22fill%3A%23721c24%3Bfont-weight%3Abold%3Bfont-size%3A10pt%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%22%3EErr%3C%2Ftext%3E%3C%2Fsvg%3E';
    };

    const formatPrice = (price) => `$${Number(price || 0).toFixed(2)}`;
    const formatDate  = (d) => (d ? new Date(d).toLocaleDateString() : 'Fecha desconocida');

    // ---------- ORDEN: helpers de mapeo (flexibles con backend) ----------
    const displayOrderNumber = (o) =>
      o?.order_number || o?.orderNumber || o?.code || `ORD-${String(o?.id || '').padStart(4, '0')}`;

    const displayCustomerName = (o) =>
      o?.name || o?.customer_name || o?.customer?.name || 'Cliente desconocido';

    const normalizeStatus = (statusRaw) => String(statusRaw || '').toLowerCase();

    const paymentMethodLabel = (pm) => {
      const m = String(pm || '').toLowerCase();
      if (m === 'cash') return 'Efectivo';
      if (m === 'transfer') return 'Transferencia';
      if (m === 'zelle') return 'Zelle';
      if (m === 'card') return 'Tarjeta';
      if (m === 'mobile') return 'Pago móvil';
      return '—';
    };

    const getOrderStatusBadgeClass = (status) => {
      // Mantengo tus clases .badge y colores
      const base = 'badge ';
      const s = normalizeStatus(status);

      switch (s) {
        case 'paid':
        case 'completed':
          return base + 'bg-success';
        case 'awaiting_payment':
        case 'awaiting_review':
        case 'processing':
          return base + 'bg-info text-dark';
        case 'cash_on_delivery':
        case 'pending':
          return base + 'bg-warning text-dark';
        case 'cancelled':
          return base + 'bg-danger';
        default:
          return base + 'bg-secondary';
      }
    };

    const getOrderStatusText = (status) => {
      const s = normalizeStatus(status);
      switch (s) {
        case 'paid':
        case 'completed':
          return 'Completado';
        case 'awaiting_payment':
          return 'En espera de pago';
        case 'awaiting_review':
          return 'En revisión';
        case 'processing':
          return 'Procesando';
        case 'cash_on_delivery':
          return 'Contra entrega';
        case 'pending':
          return 'Pendiente';
        case 'cancelled':
          return 'Cancelado';
        default:
          return 'Desconocido';
      }
    };

    // Los dos de abajo aceptan tanto order.status* como status_label/badge del backend
    const badgeClassFromOrder = (o) => o?.status_badge || getOrderStatusBadgeClass(o?.status);
    const statusTextFromOrder = (o) => o?.status_label || getOrderStatusText(o?.status);

    // ---------- ACCIÓN: completar órdenes en efectivo ----------
    const canCompleteCash = (o) => {
      const isCash = String(o?.payment_method || '').toLowerCase() === 'cash';
      const s = normalizeStatus(o?.status);
      return (s === 'cash_on_delivery' || s === 'pending');
    };

    const completeCash = async (o) => {
      if (!o?.id) return;

      // feedback rápido
      completingIds.value.add(o.id);
      try {
        await axios.post(`/api/orders/${o.id}/complete-cash`);
        await fetchRecentOrders(); // refrescar lista
      } catch (e) {
        console.error('No se pudo completar la orden:', e);
        alert('No se pudo completar la orden. Intenta nuevamente.');
      } finally {
        completingIds.value.delete(o.id);
      }
    };

    // ---------- INIT ----------
    onMounted(async () => {
      await Promise.all([fetchStats(), fetchRecentProducts(), fetchRecentOrders()]);
    });

    return {
      // state
      stats,
      recentProducts,
      recentOrders,
      loading,
      completingIds,

      // helpers UI
      getProductImage,
      handleImageError,
      formatPrice,
      formatDate,
      getOrderStatusBadgeClass,
      getOrderStatusText,
      badgeClassFromOrder,
      statusTextFromOrder,
      displayOrderNumber,
      displayCustomerName,
      paymentMethodLabel,

      // actions
      canCompleteCash,
      completeCash
    };
  }
};
</script>

<style scoped>
.bg-light-brown {
  background-color: #A67C52;
}
.text-brown {
  color: #8B4513;
}
.border-brown {
  border-color: #8B4513;
}
.btn-outline-brown {
  color: #8B4513;
  border-color: #8B4513;
}
.btn-outline-brown:hover {
  background-color: #8B4513;
  color: #FFF8E7;
}
.bg-brown {
  background-color: #8B4513;
}
</style>
