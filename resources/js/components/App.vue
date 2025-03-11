<template>
  <div class="bg-beige min-vh-100 d-flex flex-column">
    <nav class="navbar navbar-expand-lg navbar-light bg-cream border-bottom border-brown">
      <div class="container-fluid">
        <a class="navbar-brand text-brown" href="#">
          <i class="fas fa-bread-slice me-2"></i>Panaderia El Buen Gusto
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <router-link exact-active-class="active" to="/" class="nav-link">Inicio</router-link>
            </li>
            <li class="nav-item">
              <router-link exact-active-class="active" to="/blogs" class="nav-link">Productos</router-link>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <main class="flex-grow-1">
      <router-view></router-view>
    </main>
    <footer class="bg-cream text-brown text-center py-3 mt-auto">
      <p class="mb-0">&copy; </p>
    </footer>
  </div>
</template>
 
<script setup>
// No se necesita lógica adicional para este componente
</script>

<style>
body {
  background-color: #F5E6D3;
  color: #4A3728;
}
.bg-beige {
  background-color: #F5E6D3;
}
.bg-cream {
  background-color: #FFF8E7;
}
.text-brown {
  color: #8B4513 !important;
}
.border-brown {
  border-color: #8B4513 !important;
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
.navbar-light .navbar-nav .nav-link {
  color: rgba(75, 55, 40, 0.8);
}
.navbar-light .navbar-nav .nav-link:hover,
.navbar-light .navbar-nav .nav-link.active {
  color: #8B4513;
}
:focus-visible {
  outline: 2px solid #8B4513 !important;
  outline-offset: 2px;
}
</style>