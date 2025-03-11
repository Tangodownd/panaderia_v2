<template>
  <div class="container-fluid py-5 bg-beige text-brown">
    <div class="row">
      <div class="col text-center">
        <h1 class="display-4 text-brown">Bienvenido a la Gestión de Productos</h1>
        <p class="lead mt-3">Sistema de administración de inventario y catálogo</p>
      </div>
    </div>
    <div class="row mt-5">
      <div class="col-md-6 offset-md-3">
        <div class="card bg-cream border-brown">
          <div class="card-body text-center">
            <h5 class="card-title text-brown">Acceso Rápido</h5>
            <router-link to="/blogs" class="btn btn-brown mt-3">
              <i class="fas fa-list-ul me-2"></i>Ver Productos
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// No se necesita lógica adicional para este componente
</script>

<style scoped>
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
:focus-visible {
  outline: 2px solid #8B4513 !important;
  outline-offset: 2px;
}
</style>