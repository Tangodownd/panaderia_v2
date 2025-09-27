<template>
  <div class="container py-5">
    <h2 class="mb-4">Mis compras</h2>
    <div v-if="loading">Cargando...</div>
    <div v-else-if="orders.length === 0" class="alert alert-info">Aún no tienes pedidos.</div>
    <div v-else class="table-responsive">
      <table class="table">
        <thead>
          <tr><th>ID</th><th>Fecha</th><th>Estatus</th><th>Total</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="o in orders" :key="o.id">
            <td>#{{ o.id }}</td>
            <td>{{ new Date(o.created_at).toLocaleString() }}</td>
            <td>{{ o.status }}</td>
            <td>${{ Number(o.total).toFixed(2) }}</td>
            <td>
              <a class="btn btn-sm btn-outline-brown" :href="`/api/orders/${o.id}/invoice`">Descargar factura</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
  </div>
</template>

<script>
import axios from "@/axios-config"

export default {
  name: "MyOrders",
  data: () => ({ loading: false, error: "", orders: [] }),
  async created() {
    this.loading = true
    try {
      // Puedes usar /api/me/orders (alias) o /api/orders/user (ya existente)
      const { data } = await axios.get("/api/me/orders")
      this.orders = data?.data ?? data?.orders ?? []
    } catch (e) {
      this.error = e?.response?.data?.message || "No se pudieron cargar tus pedidos"
    } finally {
      this.loading = false
    }
  }
}
</script>
