<template>
    <div class="container py-5 text-center">
      <div class="card bg-beige shadow">
        <div class="card-header bg-brown text-white">
          <h1 class="h3 mb-0">Página no encontrada</h1>
        </div>
        <div class="card-body">
          <p>Lo sentimos, la página que estás buscando no existe.</p>
          <div class="mt-4">
            <a href="/" class="btn btn-brown">Volver a la tienda</a>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'NotFound'
  }
  </script>
  
  <style scoped>
  .bg-beige {
    background-color: #F5E6D3;
  }
  .bg-brown {
    background-color: #8B4513;
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