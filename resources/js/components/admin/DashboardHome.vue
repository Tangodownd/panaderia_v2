<template>
    <div class="dashboard-home">
      <div class="row">
        <div class="col-12 mb-4">
          <h1 class="text-brown">Panel de Administración</h1>
          <p class="text-muted">Bienvenido al panel de administración de Panadería El Buen Gusto</p>
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
              <span class="text-muted">Funcionalidad no disponible</span>
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
                      :src="getProductImage(product.thumbnail)" 
                      class="rounded me-3" 
                      alt="Producto" 
                      style="width: 40px; height: 40px; object-fit: cover;"
                      @error="handleImageError"
                    >
                    <div>
                      <h6 class="mb-0 text-brown">{{ product.titulo }}</h6>
                      <small class="text-muted">{{ formatPrice(product.precio) }}</small>
                    </div>
                  </div>
                  <router-link :to="{ name: 'editarBlog', params: { id: product.id } }" class="btn btn-sm btn-outline-brown">
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
              <div v-if="loading.orders" class="text-center py-3">
                <div class="spinner-border text-brown" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
              </div>
              <div v-else-if="recentOrders.length === 0" class="text-center py-3">
                <p class="text-muted mb-0">No hay pedidos recientes</p>
              </div>
              <ul v-else class="list-group list-group-flush">
                <li v-for="order in recentOrders" :key="order.id" class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-0 text-brown">{{ order.orderNumber }}</h6>
                    <small class="text-muted">{{ formatDate(order.date) }} - {{ order.customer.name }}</small>
                  </div>
                  <div>
                    <span :class="getOrderStatusBadgeClass(order.status)">{{ getOrderStatusText(order.status) }}</span>
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
      const recentOrders = ref([]);
      
      const loading = reactive({
        stats: true,
        products: true,
        orders: true
      });
  
      const fetchStats = async () => {
        loading.stats = true;
        try {
          // En un entorno real, esto sería una llamada a la API
          // Por ahora, simulamos datos
          const response = await axios.get('/api/admin/stats');
          Object.assign(stats, response.data);
        } catch (error) {
          console.error('Error fetching stats:', error);
          // Datos de ejemplo para demostración
          stats.totalProducts = 24;
          stats.totalCategories = 5;
          stats.pendingOrders = 3;
          stats.outOfStockProducts = 2;
        } finally {
          loading.stats = false;
        }
      };
  
      const fetchRecentProducts = async () => {
        loading.products = true;
        try {
          const response = await axios.get('/api/blog?limit=5');
          recentProducts.value = response.data.slice(0, 5);
        } catch (error) {
          console.error('Error fetching recent products:', error);
          recentProducts.value = [];
        } finally {
          loading.products = false;
        }
      };
  
      const fetchRecentOrders = async () => {
        loading.orders = true;
        try {
          // En un entorno real, esto sería una llamada a la API
          // Por ahora, simulamos datos
          const response = await axios.get('/api/admin/orders/recent');
          recentOrders.value = response.data;
        } catch (error) {
          console.error('Error fetching recent orders:', error);
          // Datos de ejemplo para demostración
          recentOrders.value = [
            {
              id: 1,
              orderNumber: 'ORD-1234',
              date: new Date().toISOString(),
              customer: { name: 'Juan Pérez' },
              status: 'pending'
            },
            {
              id: 2,
              orderNumber: 'ORD-1235',
              date: new Date(Date.now() - 86400000).toISOString(), // Ayer
              customer: { name: 'María López' },
              status: 'completed'
            }
          ];
        } finally {
          loading.orders = false;
        }
      };
  
      const getProductImage = (thumbnail) => {
        if (!thumbnail) return 'https://via.placeholder.com/40x40?text=No+Image';
        
        if (thumbnail.startsWith('http')) {
          return thumbnail;
        }
        
        return `${window.location.origin}/storage/${thumbnail}`;
      };
  
      const handleImageError = (event) => {
        event.target.src = 'https://via.placeholder.com/40x40?text=Error';
      };
  
      const formatPrice = (price) => {
        return `$${parseFloat(price).toFixed(2)}`;
      };
  
      const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString();
      };
  
      const getOrderStatusBadgeClass = (status) => {
        const classes = 'badge ';
        switch (status) {
          case 'pending':
            return classes + 'bg-warning text-dark';
          case 'processing':
            return classes + 'bg-info text-dark';
          case 'completed':
            return classes + 'bg-success';
          case 'cancelled':
            return classes + 'bg-danger';
          default:
            return classes + 'bg-secondary';
        }
      };
  
      const getOrderStatusText = (status) => {
        switch (status) {
          case 'pending':
            return 'Pendiente';
          case 'processing':
            return 'Procesando';
          case 'completed':
            return 'Completado';
          case 'cancelled':
            return 'Cancelado';
          default:
            return 'Desconocido';
        }
      };
  
      onMounted(() => {
        fetchStats();
        fetchRecentProducts();
        fetchRecentOrders();
      });
  
      return {
        stats,
        recentProducts,
        recentOrders,
        loading,
        getProductImage,
        handleImageError,
        formatPrice,
        formatDate,
        getOrderStatusBadgeClass,
        getOrderStatusText
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
  
  