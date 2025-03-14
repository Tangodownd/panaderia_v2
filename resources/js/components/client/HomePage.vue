<template>
  <div class="bg-beige min-vh-100">
    <!-- Hero Section -->
    <section class="hero-section py-5 bg-brown text-white">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <h1 class="display-4 fw-bold mb-4">Panadería Orquidea de Oro</h1>
            <p class="lead mb-4">Descubre nuestros deliciosos productos horneados con los mejores ingredientes y con todo el amor de nuestra tradición familiar.</p>
            <a href="#productos" class="btn btn-light btn-lg px-4">Ver Productos</a>
          </div>
          <div class="col-lg-6 text-center">
            <img src="https://images.unsplash.com/photo-1509440159596-0249088772ff?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1000&q=80" alt="Pan recién horneado" class="img-fluid rounded-3 shadow-lg" 
                 onerror="this.onerror=null; this.src='https://via.placeholder.com/600x400?text=Panadería+El+Buen+Gusto'">
          </div>
        </div>
      </div>
    </section>

    <!-- Categorías destacadas -->
    <section class="py-5">
      <div class="container">
        <h2 class="text-center text-brown mb-5">Nuestras Categorías</h2>
        <div class="row g-4">
          <div v-for="category in featuredCategories" :key="category.id" class="col-md-4">
            <div class="card h-100 border-0 shadow-sm bg-cream hover-card">
              <div class="card-body text-center p-4">
                <div class="category-icon mb-3">
                  <i :class="category.icon" class="fa-3x text-brown"></i>
                </div>
                <h3 class="card-title h5 text-brown">{{ category.name }}</h3>
                <p class="card-text text-muted">{{ category.description }}</p>
                <a @click.prevent="filterByCategory(category.id)" href="#productos" class="btn btn-outline-brown">Ver productos</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Productos Section -->
    <section id="productos" class="py-5 bg-light-beige">
      <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="text-brown">Nuestros Productos</h2>
          <div class="d-flex gap-2">
            <button @click="openCart" class="btn btn-brown position-relative">
              <i class="fas fa-shopping-cart me-2"></i>Carrito
              <span v-if="cartItemCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ cartItemCount }}
              </span>
            </button>
          </div>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="input-group">
              <span class="input-group-text bg-brown text-white"><i class="fas fa-search"></i></span>
              <input 
                type="search" 
                class="form-control border-brown" 
                placeholder="Buscar productos..." 
                v-model="searchQuery"
              >
            </div>
          </div>
          <div class="col-md-3">
            <select class="form-select border-brown" v-model="selectedCategory">
              <option value="">Todas las categorías</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <select class="form-select border-brown" v-model="sortBy">
              <option value="titulo">Nombre (A-Z)</option>
              <option value="titulo-desc">Nombre (Z-A)</option>
              <option value="precio-asc">Precio (menor a mayor)</option>
              <option value="precio-desc">Precio (mayor a menor)</option>
              <option value="valoracion">Mejor valorados</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select border-brown" v-model="availabilityFilter">
              <option value="">Todos</option>
              <option value="In Stock">En stock</option>
              <option value="Low Stock">Bajo stock</option>
            </select>
          </div>
        </div>

        <!-- Productos Grid -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-brown" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>
        
        <div v-else-if="filteredProducts.length === 0" class="text-center py-5">
          <i class="fas fa-search fa-3x text-muted mb-3"></i>
          <h3 class="text-muted">No se encontraron productos</h3>
          <p>Intenta con otra búsqueda o categoría</p>
        </div>
        
        <div v-else class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          <div v-for="product in filteredProducts" :key="product.id" class="col">
            <div class="card h-100 border-0 shadow-sm product-card">
              <div class="position-relative">
                <img 
                  :src="getProductImage(product)" 
                  class="card-img-top" 
                  :alt="product.titulo"
                  style="height: 200px; object-fit: cover;"
                  @error="handleImageError"
                >
                <div v-if="product.descuento > 0" class="position-absolute top-0 end-0 bg-danger text-white m-2 px-2 py-1 rounded">
                  -{{ product.descuento }}%
                </div>
              </div>
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h5 class="card-title text-brown">{{ product.titulo }}</h5>
                  <div class="badge bg-light-brown text-white">
                    {{ product.category ? product.category.name : 'Sin categoría' }}
                  </div>
                </div>
                <p class="card-text small text-muted mb-2">{{ truncateText(product.contenido, 80) }}</p>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div>
                    <span class="text-brown fw-bold">
                      {{ formatPrice(calculateDiscountedPrice(product)) }}
                    </span>
                    <span v-if="product.descuento > 0" class="text-muted text-decoration-line-through ms-2 small">
                      {{ formatPrice(product.precio) }}
                    </span>
                  </div>
                  <div class="text-warning">
                    {{ '★'.repeat(Math.round(product.valoracion)) }}{{ '☆'.repeat(5 - Math.round(product.valoracion)) }}
                  </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <span :class="getAvailabilityClass(product.availabilityStatus)">
                    {{ getAvailabilityText(product.availabilityStatus) }}
                  </span>
                  <button 
                    @click="addToCart(product)" 
                    class="btn btn-sm btn-brown"
                    :disabled="product.availabilityStatus === 'Out of Stock'"
                  >
                    <i class="fas fa-cart-plus me-1"></i> Añadir
                  </button>
                </div>
              </div>
              <div class="card-footer bg-transparent border-top-0">
                <button @click="showProductDetails(product)" class="btn btn-link text-brown p-0">
                  Ver detalles
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Product Detail Modal -->
    <div class="modal fade" id="productDetailModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content bg-beige">
          <div class="modal-header bg-brown text-white">
            <h5 class="modal-title">{{ selectedProduct.titulo }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <img 
                  :src="getProductImage(selectedProduct)" 
                  class="img-fluid rounded mb-3" 
                  :alt="selectedProduct.titulo"
                  @error="handleImageError"
                >
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div>
                    <span class="fs-4 text-brown fw-bold">
                      {{ formatPrice(calculateDiscountedPrice(selectedProduct)) }}
                    </span>
                    <span v-if="selectedProduct.descuento > 0" class="text-muted text-decoration-line-through ms-2">
                      {{ formatPrice(selectedProduct.precio) }}
                    </span>
                  </div>
                  <div class="text-warning fs-5">
                    {{ '★'.repeat(Math.round(selectedProduct.valoracion || 0)) }}{{ '☆'.repeat(5 - Math.round(selectedProduct.valoracion || 0)) }}
                  </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                  <div class="input-group input-group-sm me-3" style="width: 120px;">
                    <button class="btn btn-outline-brown" type="button" @click="decrementQuantity">-</button>
                    <input type="number" class="form-control text-center" v-model="quantity" min="1">
                    <button class="btn btn-outline-brown" type="button" @click="incrementQuantity">+</button>
                  </div>
                  <button 
                    @click="addToCartWithQuantity(selectedProduct, quantity)" 
                    class="btn btn-brown"
                    :disabled="selectedProduct.availabilityStatus === 'Out of Stock'"
                  >
                    <i class="fas fa-cart-plus me-1"></i> Añadir al carrito
                  </button>
                </div>
                <div :class="getAvailabilityClass(selectedProduct.availabilityStatus, 'mb-3')">
                  {{ getAvailabilityText(selectedProduct.availabilityStatus) }}
                </div>
              </div>
              <div class="col-md-6">
                <h5 class="text-brown">Descripción</h5>
                <p>{{ selectedProduct.contenido }}</p>
                
                <div class="mb-3">
                  <h6 class="text-brown">Detalles del producto</h6>
                  <ul class="list-group list-group-flush bg-transparent">
                    <li class="list-group-item bg-transparent px-0 py-1 border-brown">
                      <strong>Categoría:</strong> {{ selectedProduct.category ? selectedProduct.category.name : 'Sin categoría' }}
                    </li>
                    <li class="list-group-item bg-transparent px-0 py-1 border-brown">
                      <strong>Marca:</strong> {{ selectedProduct.brand || 'No especificada' }}
                    </li>
                    <li class="list-group-item bg-transparent px-0 py-1 border-brown">
                      <strong>Peso:</strong> {{ selectedProduct.weight ? `${selectedProduct.weight} kg` : 'No especificado' }}
                    </li>
                    <li v-if="selectedProduct.dimensions" class="list-group-item bg-transparent px-0 py-1 border-brown">
                      <strong>Dimensiones:</strong> {{ `${selectedProduct.dimensions?.width || 0} × ${selectedProduct.dimensions?.height || 0} × ${selectedProduct.dimensions?.depth || 0} cm` }}
                    </li>
                    <li class="list-group-item bg-transparent px-0 py-1 border-brown">
                      <strong>SKU:</strong> {{ selectedProduct.sku || 'No especificado' }}
                    </li>
                    <li v-if="selectedProduct.etiquetas && selectedProduct.etiquetas.length > 0" class="list-group-item bg-transparent px-0 py-1 border-brown">
                      <strong>Etiquetas:</strong> 
                      <span v-for="(tag, index) in selectedProduct.etiquetas" :key="index" class="badge bg-light-brown text-white me-1">
                        {{ tag }}
                      </span>
                    </li>
                  </ul>
                </div>
                
                <div v-if="selectedProduct.shippingInformation" class="mb-3">
                  <h6 class="text-brown">Información de envío</h6>
                  <p class="small">{{ selectedProduct.shippingInformation }}</p>
                </div>
                
                <div v-if="selectedProduct.returnPolicy" class="mb-3">
                  <h6 class="text-brown">Política de devolución</h6>
                  <p class="small">{{ selectedProduct.returnPolicy }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Shopping Cart Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="shoppingCart">
      <div class="offcanvas-header bg-brown text-white">
        <h5 class="offcanvas-title">Carrito de Compras</h5>
        <button type="button" class="btn-close text-reset btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <div v-if="cart.items.length === 0" class="text-center py-5">
          <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
          <h5 class="text-muted">Tu carrito está vacío</h5>
          <p>Agrega algunos productos deliciosos</p>
          <button class="btn btn-brown" data-bs-dismiss="offcanvas">Continuar comprando</button>
        </div>
        
        <div v-else>
          <div class="list-group mb-3">
            <div v-for="(item, index) in cart.items" :key="index" class="list-group-item bg-beige border-brown">
              <div class="d-flex">
                <div class="flex-shrink-0">
                  <img 
                    :src="getProductImage(item.product)" 
                    class="img-thumbnail" 
                    :alt="item.product.titulo"
                    style="width: 60px; height: 60px; object-fit: cover;"
                    @error="handleImageError"
                  >
                </div>
                <div class="flex-grow-1 ms-3">
                  <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-brown">{{ item.product.titulo }}</h6>
                    <button @click="removeFromCart(index)" class="btn btn-sm text-danger">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="input-group input-group-sm" style="width: 100px;">
                      <button class="btn btn-outline-brown" type="button" @click="decrementCartItem(index)">-</button>
                      <input type="number" class="form-control text-center" v-model="item.quantity" min="1" @change="updateCartItem(index, item.quantity)">
                      <button class="btn btn-outline-brown" type="button" @click="incrementCartItem(index)">+</button>
                    </div>
                    <span class="text-brown fw-bold">{{ formatPrice(calculateItemTotal(item)) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="card bg-cream mb-3">
            <div class="card-body">
              <h6 class="card-title text-brown">Resumen del pedido</h6>
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
          
          <div class="d-grid gap-2">
            <button @click="proceedToCheckout" class="btn btn-brown">
              Proceder al pago
            </button>
            <button class="btn btn-outline-brown" data-bs-dismiss="offcanvas">
              Continuar comprando
            </button>
          </div>
        </div>
      </div>
    </div>

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
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import axios from 'axios';

export default {
  emits: ['add-to-cart'],
  
  setup(props, { emit }) {
    // Estado
    const products = ref([]);
    const categories = ref([]);
    const loading = ref(true);
    const searchQuery = ref('');
    const selectedCategory = ref('');
    const sortBy = ref('titulo');
    const availabilityFilter = ref('');
    const selectedProduct = ref({});
    const quantity = ref(1);
    const cart = ref({
      items: [],
      total: 0
    });

    
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

    // Categorías destacadas (simuladas)
    const featuredCategories = ref([
      { id: 1, name: 'Panes', description: 'Panes artesanales recién horneados', icon: 'fas fa-bread-slice' },
      { id: 2, name: 'Pasteles', description: 'Deliciosos pasteles para toda ocasión', icon: 'fas fa-birthday-cake' },
      { id: 3, name: 'Galletas', description: 'Crujientes y dulces para acompañar el café', icon: 'fas fa-cookie' }
    ]);

    // Cargar productos y categorías
    const fetchProducts = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/products');
    products.value = response.data;
    
    // Asegurarse de que todos los productos tengan valores por defecto
    products.value = products.value.map(product => ({
      ...product,
      availabilityStatus: product.availabilityStatus || 'In Stock',
      valoracion: product.rating || 0,
      descuento: product.discount || 0,
      precio: product.price,
      titulo: product.name,
      contenido: product.description,
      thumbnail: product.image, // Usar image como thumbnail
      image: product.image      // Asegurarse de que image esté disponible
    }));
  } catch (error) {
    console.error('Error al cargar productos:', error);
    products.value = [];
  } finally {
    loading.value = false;
  }
};

    const fetchCategories = async () => {
      try {
        const response = await axios.get('/api/categories');
        categories.value = response.data;
      } catch (error) {
        console.error('Error al cargar categorías:', error);
        categories.value = [];
      }
    };

    // Filtrado y ordenación de productos
    const filteredProducts = computed(() => {
      
      let result = products.value.filter(product => {
        const matchesSearch = product.titulo.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                             (product.contenido && product.contenido.toLowerCase().includes(searchQuery.value.toLowerCase()));
        const matchesCategory = selectedCategory.value ? product.category_id == selectedCategory.value : true;
        const matchesAvailability = availabilityFilter.value ? product.availabilityStatus === availabilityFilter.value : true;
        
        return matchesSearch && matchesCategory && matchesAvailability;
        
      });

      // Ordenar productos
      if (sortBy.value === 'titulo') {
        result.sort((a, b) => a.titulo.localeCompare(b.titulo));
      } else if (sortBy.value === 'titulo-desc') {
        result.sort((a, b) => b.titulo.localeCompare(a.titulo));
      } else if (sortBy.value === 'precio-asc') {
        result.sort((a, b) => a.precio - b.precio);
      } else if (sortBy.value === 'precio-desc') {
        result.sort((a, b) => b.precio - a.precio);
      } else if (sortBy.value === 'valoracion') {
        result.sort((a, b) => b.valoracion - a.valoracion);
      }

      return result;
    });


    // Funciones de utilidad
    const truncateText = (text, length) => {
      if (!text) return '';
      return text.length > length ? text.substring(0, length) + '...' : text;
    };

    const formatPrice = (price) => {
      return `$${parseFloat(price).toFixed(2)}`;
    };

    const calculateDiscountedPrice = (product) => {
      if (!product || !product.precio) return 0;
      const discount = product.descuento || 0;
      return product.precio * (1 - discount / 100);
    };

    

    const getProductImage = (product) => {
  if (!product || (!product.thumbnail && !product.image)) {
    // Use a data URI instead of an external service
    return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22300%22%20height%3D%22200%22%20viewBox%3D%220%200%20300%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A15pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%22300%22%20height%3D%22200%22%20fill%3D%22%23E9ECEF%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2256.1953%22%20y%3D%22107.2%22%3EProducto%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
  }
  
  // Check for image property first, then thumbnail
  const imagePath = product.image || product.thumbnail;
  
  // Check if the image is already a full URL
  if (imagePath.startsWith('http')) {
    return imagePath;
  }
  
  // If it's not a full URL, construct it based on the current origin
  return `${window.location.origin}/storage/${imagePath}`;
};

    const handleImageError = (event) => {
      // Use a data URI for the error image as well
      event.target.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22300%22%20height%3D%22200%22%20viewBox%3D%220%200%20300%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23721c24%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A15pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%22300%22%20height%3D%22200%22%20fill%3D%22%23f8d7da%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2261.4687%22%20y%3D%22107.2%22%3EImagen%20no%20disponible%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
    };

    const getAvailabilityClass = (status, additionalClass = '') => {
      let className = additionalClass + ' badge ';
      
      switch (status) {
        case 'In Stock':
          className += 'bg-success';
          break;
        case 'Low Stock':
          className += 'bg-warning text-dark';
          break;
        case 'Out of Stock':
          className += 'bg-danger';
          break;
        default:
          className += 'bg-secondary';
      }
      
      return className;
    };

    const getAvailabilityText = (status) => {
      switch (status) {
        case 'In Stock':
          return 'En stock';
        case 'Low Stock':
          return 'Pocas unidades';
        case 'Out of Stock':
          return 'Agotado';
        default:
          return 'Disponibilidad desconocida';
      }
    };

    // Funciones del carrito
    const cartItemCount = computed(() => {
      return cart.value.items.reduce((total, item) => total + parseInt(item.quantity || 0), 0);
    });

// Funciones del carrito
const addToCart = (product) => {
  const existingItemIndex = cart.value.items.findIndex(item => item.product.id === product.id);
  
  if (existingItemIndex !== -1) {
    cart.value.items[existingItemIndex].quantity += 1;
  } else {
    cart.value.items.push({
      product: product,
      quantity: 1
    });
  }
  
  // Guardar carrito en localStorage
  saveCartToLocalStorage();
  
  // Mostrar notificación
  showNotification('Producto añadido al carrito');
};

const addToCartWithQuantity = (product, qty) => {
  const existingItemIndex = cart.value.items.findIndex(item => item.product.id === product.id);
  
  if (existingItemIndex !== -1) {
    cart.value.items[existingItemIndex].quantity += parseInt(qty);
  } else {
    cart.value.items.push({
      product: product,
      quantity: parseInt(qty)
    });
  }
  
  // Guardar carrito en localStorage
  saveCartToLocalStorage();
  
  // Cerrar modal y mostrar notificación
  const modal = document.getElementById('productDetailModal');
  if (modal && typeof bootstrap !== 'undefined') {
    const modalInstance = bootstrap.Modal.getInstance(modal);
    if (modalInstance) modalInstance.hide();
  }
  
  // Mostrar notificación
  showNotification('Producto añadido al carrito');
};

const removeFromCart = (index) => {
  cart.value.items.splice(index, 1);
  saveCartToLocalStorage();
};

const updateCartItem = (index, quantity) => {
  cart.value.items[index].quantity = parseInt(quantity);
  if (cart.value.items[index].quantity < 1) {
    cart.value.items[index].quantity = 1;
  }
  saveCartToLocalStorage();
};

const incrementCartItem = (index) => {
  cart.value.items[index].quantity = parseInt(cart.value.items[index].quantity) + 1;
  saveCartToLocalStorage();
};

const decrementCartItem = (index) => {
  if (cart.value.items[index].quantity > 1) {
    cart.value.items[index].quantity = parseInt(cart.value.items[index].quantity) - 1;
    saveCartToLocalStorage();
  }
};

const saveCartToLocalStorage = () => {
  localStorage.setItem('panaderia-cart', JSON.stringify(cart.value));
};

const loadCartFromLocalStorage = () => {
  const savedCart = localStorage.getItem('panaderia-cart');
  if (savedCart) {
    try {
      cart.value = JSON.parse(savedCart);
    } catch (e) {
      console.error('Error al cargar el carrito desde localStorage:', e);
      cart.value = { items: [], total: 0 };
    }
  }
};

const resetCart = () => {
  cart.value = {
    items: [],
    total: 0
  };
  saveCartToLocalStorage();
  
  // Recargar los productos después de completar un pedido
  setTimeout(() => {
    fetchProducts();
  }, 500);
};

const handleOrderCompleted = () => {
  // Recargar explícitamente el carrito después de completar un pedido
  resetCart();
  
  // Recargar productos con un pequeño retraso
  setTimeout(() => {
    fetchProducts();
  }, 500);
};

    // Funciones de UI
    const showProductDetails = (product) => {
      selectedProduct.value = product;
      quantity.value = 1;
      
      const modal = document.getElementById('productDetailModal');
      if (modal && typeof bootstrap !== 'undefined') {
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
      }
    };

    const openCart = () => {
      const offcanvas = document.getElementById('shoppingCart');
      if (offcanvas && typeof bootstrap !== 'undefined') {
        const offcanvasInstance = new bootstrap.Offcanvas(offcanvas);
        offcanvasInstance.show();
      }
    };

    const proceedToCheckout = () => {
      const offcanvas = document.getElementById('shoppingCart');
      if (offcanvas && typeof bootstrap !== 'undefined') {
        const offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvas);
        if (offcanvasInstance) offcanvasInstance.hide();
      }
      
      const modal = document.getElementById('checkoutModal');
      if (modal && typeof bootstrap !== 'undefined') {
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
      }
    };

    const incrementQuantity = () => {
      quantity.value = parseInt(quantity.value) + 1;
    };

    const decrementQuantity = () => {
      if (quantity.value > 1) {
        quantity.value = parseInt(quantity.value) - 1;
      }
    };

    const filterByCategory = (categoryId) => {
      selectedCategory.value = Number(categoryId);
      nextTick(() => {
    const productosSection = document.getElementById('productos');
    if (productosSection) {
      productosSection.scrollIntoView({ behavior: 'smooth' });
    }
      }, 100);
    };
    const showNotification = (message) => {
  // Crear un elemento de notificación con ID único
  const toastId = 'toast-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
  const notification = document.createElement('div');
  notification.id = toastId;
  notification.className = 'toast align-items-center text-white bg-success border-0 position-fixed bottom-0 end-0 m-3';
  notification.setAttribute('role', 'alert');
  notification.setAttribute('aria-live', 'assertive');
  notification.setAttribute('aria-atomic', 'true');
  
  notification.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">
        ${message}
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `;
  
  document.body.appendChild(notification);
  
  // Mostrar la notificación
  if (typeof bootstrap !== 'undefined') {
    const toast = new bootstrap.Toast(notification, { delay: 3000 });
    
    // Manejar el evento hidden.bs.toast de forma segura
    notification.addEventListener('hidden.bs.toast', function () {
      // Verificar si el elemento todavía existe en el DOM
      const toastElement = document.getElementById(toastId);
      if (toastElement && document.body.contains(toastElement)) {
        try {
          document.body.removeChild(toastElement);
        } catch (e) {
          console.error('Error al eliminar toast:', e);
        }
      }
    });
    
    toast.show();
  } else {
    // Fallback si bootstrap no está disponible
    setTimeout(() => {
      if (document.getElementById(toastId) && document.body.contains(notification)) {
        try {
          document.body.removeChild(notification);
        } catch (e) {
          console.error('Error al eliminar toast (fallback):', e);
        }
      }
    }, 3000);
  }
};
    // Funciones para calcular totales del carrito
    const calculateItemTotal = (item) => {
      return calculateDiscountedPrice(item.product) * item.quantity;
    };

    const calculateSubtotal = () => {
      return cart.value.items.reduce((total, item) => {
        return total + (item.product.precio * item.quantity);
      }, 0);
    };

    const calculateDiscount = () => {
      return cart.value.items.reduce((total, item) => {
        const discount = item.product.descuento || 0;
        return total + ((item.product.precio * discount / 100) * item.quantity);
      }, 0);
    };

    const calculateTotal = () => {
      return cart.value.items.reduce((total, item) => {
        return total + calculateItemTotal(item);
      }, 0);
    };
    // Envío de pedido
    const submitOrder = async () => {
      if (cart.value.items.length === 0) {
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
          items: cart.value.items,
          total: calculateTotal()
        });
        
        // Mostrar confirmación
        const checkoutModal = document.getElementById('checkoutModal');
        if (checkoutModal && typeof bootstrap !== 'undefined') {
          const modalInstance = bootstrap.Modal.getInstance(checkoutModal);
          if (modalInstance) modalInstance.hide();
        }
        
        const confirmationModal = document.getElementById('orderConfirmationModal');
        if (confirmationModal && typeof bootstrap !== 'undefined') {
          const modalInstance = new bootstrap.Modal(confirmationModal);
          modalInstance.show();
        }
        
      } catch (error) {
        console.error('Error al procesar el pedido:', error);
        alert('Ha ocurrido un error al procesar tu pedido. Por favor, inténtalo de nuevo.');
      } finally {
        isSubmitting.value = false;
      }
    };

    // Ciclo de vida
    onMounted(() => {
      fetchProducts();
      fetchCategories();
      loadCartFromLocalStorage();
      
      // Asegurarse de que bootstrap esté disponible
      if (typeof bootstrap === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js';
        script.onload = () => {
          console.log('Bootstrap cargado dinámicamente');
        };
        document.head.appendChild(script);
      }
    });

    return {
  products,
  categories,
  featuredCategories,
  loading,
  searchQuery,
  selectedCategory,
  sortBy,
  availabilityFilter,
  filteredProducts,
  selectedProduct,
  
  quantity,
  cart,
  cartItemCount,
  checkout,
  isSubmitting,
  orderNumber,
  
  // Métodos
  truncateText,
  formatPrice,
  calculateDiscountedPrice,
  getProductImage,
  handleImageError,
  getAvailabilityClass,
  getAvailabilityText,
  filterByCategory,
  addToCart,
  addToCartWithQuantity,
  removeFromCart,
  updateCartItem,
  incrementCartItem,
  decrementCartItem,
  showProductDetails,
  incrementQuantity,
  decrementQuantity,
  openCart,
  proceedToCheckout,
  submitOrder,
  resetCart,
  handleOrderCompleted,
  calculateItemTotal,
  calculateSubtotal,
  calculateDiscount,
  calculateTotal
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
.bg-light-brown {
  background-color: #A67C52;
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
.bg-brown {
  background-color: #8B4513;
}
.hero-section {
  background-color: #8B4513;
  background-image: linear-gradient(135deg, #8B4513 0%, #A67C52 100%);
}
.product-card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
.hover-card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.hover-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
</style>

