<template>
  <div class="container-fluid py-4 bg-beige text-brown">
    <div class="row mb-4">
      <div class="col-12">
        <div class="card bg-light-beige text-brown border-brown">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h2 class="card-title mb-0 text-brown">Productos de Panadería</h2>
              <router-link :to="{ name: 'crearBlog' }" class="btn btn-brown">
                <i class="fas fa-plus-circle me-2"></i>Añadir Producto
              </router-link>
            </div>
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <input type="search" class="form-control bg-beige text-brown border-brown" placeholder="Buscar" v-model="searchQuery" @input="filterTable">
              </div>
              <div class="col-md-4">
                <select class="form-select bg-beige text-brown border-brown" v-model="selectedCategory" @change="filterTable">
                  <option value="">Todas las Categorías</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                </select>
              </div>
              <div class="col-md-4">
                <select class="form-select bg-beige text-brown border-brown" v-model="sortBy" @change="filterTable">
                  <option value="">Ordenar por</option>
                  <option value="id">ID</option>
                  <option value="titulo">Nombre</option>
                  <option value="category">Categoría</option>
                  <option value="precio">Precio</option>
                </select>
              </div>
            </div>
            <div class="table-responsive">
              <table id="blogsTable" class="table table-hover">
                <thead class="bg-brown text-beige">
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Marca</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para ver detalles -->
  <div class="modal fade" id="detallesModal" tabindex="-1" aria-labelledby="detallesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content bg-beige text-brown">
        <div class="modal-header bg-brown text-beige">
          <h5 class="modal-title" id="detallesModalLabel">Detalles del Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>ID:</strong> {{ blogSeleccionado.id }}</p>
              <p><strong>Nombre:</strong> {{ blogSeleccionado.titulo }}</p>
              <p><strong>Categoría:</strong> {{ blogSeleccionado.category ? blogSeleccionado.category.name : 'Sin categoría' }}</p>
              <p><strong>Descripción:</strong> {{ blogSeleccionado.contenido }}</p>
              <p><strong>Precio:</strong> ${{ blogSeleccionado.precio }}</p>
              <p><strong>Descuento:</strong> {{ blogSeleccionado.descuento }}%</p>
              <p><strong>Valoración:</strong> {{ '⭐'.repeat(Math.round(blogSeleccionado.valoracion)) }}</p>
              <p><strong>Stock:</strong> {{ blogSeleccionado.stock }}</p>
              <p><strong>Marca:</strong> {{ blogSeleccionado.brand }}</p>
              <p><strong>SKU:</strong> {{ blogSeleccionado.sku }}</p>
              <p><strong>Peso:</strong> {{ blogSeleccionado.weight }} kg</p>
              <p><strong>Dimensiones:</strong> {{ blogSeleccionado.dimensions ? `${blogSeleccionado.dimensions.width} x ${blogSeleccionado.dimensions.height} x ${blogSeleccionado.dimensions.depth} cm` : 'N/A' }}</p>
              <p><strong>Garantía:</strong> {{ blogSeleccionado.warrantyInformation }}</p>
              <p><strong>Envío:</strong> {{ blogSeleccionado.shippingInformation }}</p>
              <p><strong>Disponibilidad:</strong> {{ blogSeleccionado.availabilityStatus }}</p>
              <p><strong>Política de devolución:</strong> {{ blogSeleccionado.returnPolicy }}</p>
              <p><strong>Cantidad mínima de pedido:</strong> {{ blogSeleccionado.minimumOrderQuantity }}</p>
              <p><strong>Etiquetas:</strong> {{ blogSeleccionado.etiquetas ? blogSeleccionado.etiquetas.join(', ') : 'Sin etiquetas' }}</p>
            </div>

              <div class="row mb-3">
                <h6>QR Code</h6>
                <canvas :id="'qrcode-modal-' + blogSeleccionado.id"></canvas>
              </div>
              <div>
                <h6>Barcode</h6>
                <svg :id="'barcode-modal-' + blogSeleccionado.id"></svg>
              </div>
              <div class="col-md-6">
              <div v-if="blogSeleccionado.thumbnail" class="mb-3">
                <h6>Imagen del Producto</h6>
                <img 
                  :src="getThumbnailUrl(blogSeleccionado.thumbnail)" 
                  class="img-fluid" 
                  alt="Thumbnail" 
                  @error="handleImageError"
                  v-show="imageLoaded"
                  @load="handleImageLoad"
                >
                <div v-if="!imageLoaded && !imageError" class="text-center">
                  <div class="spinner-border text-brown" role="status">
                    <span class="visually-hidden">Cargando imagen...</span>
                  </div>
                </div>
                <p v-if="imageError" class="text-danger mt-2">
                  Error al cargar la imagen. Por favor, inténtelo de nuevo más tarde.
                </p>
              </div>
            </div>
          </div>

          <!-- Sección de Reseñas -->
          <div class="mt-4">
            <h5 class="text-brown">Reseñas</h5>
            <div v-if="blogSeleccionado.reviews && blogSeleccionado.reviews.length > 0">
              <div v-for="(review, index) in blogSeleccionado.reviews" :key="index" class="mb-3 p-3 border border-brown rounded">
                <div class="d-flex justify-content-between">
                  <strong>{{ review.reviewerName }}</strong>
                  <span>{{ new Date(review.date).toLocaleDateString() }}</span>
                </div>
                <div>Rating: {{ '⭐'.repeat(review.rating) }}</div>
                <p>{{ review.comment }}</p>
              </div>
            </div>
            <div v-else>
              <p>No hay reseñas para este producto.</p>
            </div>

            <!-- Formulario para agregar reseña -->
            <form @submit.prevent="addReview" class="mt-4">
              <h6 class="text-brown">Agregar Reseña</h6>
              <div class="mb-3">
                <label for="reviewerName" class="form-label">Nombre</label>
                <input v-model="newReview.reviewerName" type="text" class="form-control bg-beige text-brown border-brown" id="reviewerName" required>
              </div>
              <div class="mb-3">
                <label for="reviewerEmail" class="form-label">Email</label>
                <input v-model="newReview.reviewerEmail" type="email" class="form-control bg-beige text-brown border-brown" id="reviewerEmail" required>
              </div>
              <div class="mb-3">
                <label for="rating" class="form-label">Rating</label>
                <select v-model="newReview.rating" class="form-select bg-beige text-brown border-brown" id="rating" required>
                  <option value="1">1 ⭐</option>
                  <option value="2">2 ⭐⭐</option>
                  <option value="3">3 ⭐⭐⭐</option>
                  <option value="4">4 ⭐⭐⭐⭐</option>
                  <option value="5">5 ⭐⭐⭐⭐⭐</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="comment" class="form-label">Comentario</label>
                <textarea v-model="newReview.comment" class="form-control bg-beige text-brown border-brown" id="comment" rows="3" required></textarea>
              </div>
              <button type="submit" class="btn btn-brown">Enviar Reseña</button>
            </form>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import $ from 'jquery';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'datatables.net';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Modal } from 'bootstrap';
import QRCode from 'qrcode';
import JsBarcode from 'jsbarcode';

const router = useRouter();
const blogs = ref([]);
const categories = ref([]);
const searchQuery = ref('');
const selectedCategory = ref('');
const sortBy = ref('');
const blogSeleccionado = ref({});
const newReview = ref({
  reviewerName: '',
  reviewerEmail: '',
  rating: 5,
  comment: '',
});
const imageLoaded = ref(false);
const imageError = ref(false);

const getThumbnailUrl = (thumbnail) => {
  if (!thumbnail) return null;
  
  // Check if the thumbnail is already a full URL
  if (thumbnail.startsWith('http')) {
    return thumbnail;
  }
  
  // If it's not a full URL, construct it based on the current origin
  return `${window.location.origin}/storage/${thumbnail}`;
};

const handleImageError = (event) => {
  console.error('Error loading image:', event);
  imageError.value = true;
  imageLoaded.value = true; // Set to true to hide the loading spinner
};

const handleImageLoad = () => {
  imageLoaded.value = true;
  imageError.value = false;
};

const filteredBlogs = computed(() => {
  let result = blogs.value.filter(blog => {
    const matchesCategory = selectedCategory.value ? blog.category_id == selectedCategory.value : true;
    const matchesSearch = blog.titulo.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          blog.contenido.toLowerCase().includes(searchQuery.value.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  if (sortBy.value) {
    result.sort((a, b) => {
      if (sortBy.value === 'id') {
        return a.id - b.id;
      } else if (sortBy.value ==='titulo') {
        return a.titulo.localeCompare(b.titulo);
      } else if (sortBy.value === 'category') {
        return (a.category?.name || '').localeCompare(b.category?.name || '');
      } else if (sortBy.value === 'precio') {
        return a.precio - b.precio;
      }
      return 0;
    });
  }

  return result;
});

const mostrarBlogs = async () => {
  try {
    const response = await axios.get('/api/blog');
    blogs.value = response.data;
    console.log('Blogs obtenidos:', blogs.value); // Para depuración
    await nextTick();
    if ($.fn.DataTable.isDataTable('#blogsTable')) {
      $('#blogsTable').DataTable().destroy();
    }
    initializeDataTable();
  } catch (error) {
    console.log(error);
    blogs.value = [];
  }
};

const fetchCategories = async () => {
  try {
    const response = await axios.get('/api/categories');
    categories.value = response.data;
  } catch (error) {
    console.log(error);
  }
};

const borrarBlog = async (id) => {
  if (confirm("¿Confirma eliminar el registro?")) {
    try {
      await axios.delete(`/api/blog/${id}`);
      mostrarBlogs();
    } catch (error) {
      console.log(error);
    }
  }
};

const verDetalles = (index) => {
  blogSeleccionado.value = JSON.parse(JSON.stringify(filteredBlogs.value[index]));
  if (!blogSeleccionado.value.reviews) {
    blogSeleccionado.value.reviews = [];
  }
  imageLoaded.value = false;
  imageError.value = false;
  const modal = new Modal(document.getElementById('detallesModal'));
  modal.show();
};

const filterTable = () => {
  const table = $('#blogsTable').DataTable();
  table.clear().rows.add(filteredBlogs.value).draw();
};

const initializeDataTable = () => {
  $('#blogsTable').DataTable({
    data: blogs.value,
    columns: [
      { data: 'id' },
      { data: 'titulo' },
      { data: 'category.name', defaultContent: 'Sin categoría' },
      { data: 'precio', render: (data) => `$${parseFloat(data).toFixed(2)}` },
      { data: 'stock' },
      { data: 'brand' },
      { 
        data: null, 
        render: (data, type, row, meta) => `
          <div class="btn-group" role="group">
            <a href="/editarBlog/${row.id}" class="btn btn-sm btn-outline-brown"><i class="fas fa-edit"></i></a>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="borrarBlog(${row.id})"><i class="fas fa-trash"></i></button>
            <button type="button" class="btn btn-sm btn-outline-brown" onclick="verDetalles(${meta.row})"><i class="fas fa-eye"></i></button>
          </div>
        `
      }
    ],
    destroy: true,
    lengthChange: true,
    searching: false,
    ordering: true,
    language: {
      "sProcessing":     "Procesando...",
      "sLengthMenu":     "Mostrar _MENU_ registros",
      "sZeroRecords":    "No se encontraron resultados",
      "sEmptyTable":     "Ningún dato disponible en esta tabla",
      "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
      "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
      "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
      "sInfoPostFix":    "",
      "sSearch":         "Buscar:",
      "sUrl":            "",
      "sInfoThousands":  ",",
      "sLoadingRecords": "Cargando...",
      "oPaginate": {
        "sFirst":    "Primero",
        "sLast":     "Último",
        "sNext":     "Siguiente",
        "sPrevious": "Anterior"
      },
      "oAria": {
        "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
      }
    }
  });
};

const generateQRCode = (id) => {
  const qrCodeElement = document.getElementById(`qrcode-modal-${id}`);
  if (qrCodeElement) {
    QRCode.toCanvas(qrCodeElement, `https://example.com/blog/${id}`, function (error) {
      if (error) console.error(error);
    });
  }
};

const generateBarcode = (id, barcode) => {
  const barcodeElement = document.getElementById(`barcode-modal-${id}`);
  if (barcodeElement && barcode) {
    JsBarcode(barcodeElement, barcode, {
      format: "CODE128",
      displayValue: true
    });
  }
};

const addReview = async () => {
  try {
    const review = {
      ...newReview.value,
      date: new Date().toISOString(),
      rating: parseInt(newReview.value.rating)
    };

    const response = await axios.post(`/api/blog/${blogSeleccionado.value.id}/review`, review);

    blogSeleccionado.value.reviews.push(review);
    updateProductRating();

    // Actualizar el blog en la lista principal
    const index = blogs.value.findIndex(b => b.id === blogSeleccionado.value.id);
    if (index !== -1) {
      blogs.value[index] = { ...blogSeleccionado.value };
    }

    // Limpiar el formulario
    newReview.value = {
      reviewerName: '',
      reviewerEmail: '',
      rating: 5,
      comment: '',
    };

    alert('Reseña agregada con éxito');
  } catch (error) {
    console.error('Error al agregar la reseña:', error);
    alert('Error al agregar la reseña');
  }
};

const updateProductRating = () => {
  const reviews = blogSeleccionado.value.reviews;
  if (reviews && reviews.length > 0) {
    const totalRating = reviews.reduce((sum, review) => sum + review.rating, 0);
    blogSeleccionado.value.valoracion = totalRating / reviews.length;
  }
};

watch(blogSeleccionado, (newBlog) => {
  if (newBlog.id) {
    nextTick(() => {
      generateQRCode(newBlog.id);
      generateBarcode(newBlog.id, newBlog.barcode);
    });
  }
});

watch(() => blogs.value, (newBlogs) => {
  if ($.fn.DataTable.isDataTable('#blogsTable')) {
    $('#blogsTable').DataTable().destroy();
  }
  nextTick(() => {
    initializeDataTable();
  });
}, { deep: true });

onMounted(() => {
  mostrarBlogs();
  fetchCategories();
});

// Hacer funciones accesibles globalmente
window.borrarBlog = borrarBlog;
window.verDetalles = verDetalles;
</script>

<style scoped>
#blogsTable {
  width: 100%;
}
.table-responsive {
  overflow-x: auto;
}
.btn-group {
  white-space: nowrap;
}
.bg-brown {
  background-color: #8D6E63;
}
.text-brown {
  color: #5D4037;
}
.border-brown {
  border-color: #8D6E63;
}
.btn-brown {
  background-color: #8D6E63;
  border-color: #8D6E63;
  color: #F5E6D3;
}
.btn-brown:hover {
  background-color: #795548;
  border-color: #795548;
  color: #F5E6D3;
}
.btn-outline-brown {
  color: #8D6E63;
  border-color: #8D6E63;
}
.btn-outline-brown:hover {
  background-color: #8D6E63;
  color: #F5E6D3;
}
.bg-beige {
  background-color: #F5E6D3;
}
.bg-light-beige {
  background-color: #D7CCC8;
}
.text-beige {
  color: #F5E6D3;
}
:focus-visible {
  outline: 2px solid #8D6E63 !important;
  outline-offset: 2px;
}
</style>