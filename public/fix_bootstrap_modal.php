<?php
// Este script ayuda a diagnosticar y solucionar problemas con los modales de Bootstrap

// Configurar el buffer de salida para evitar problemas de "headers already sent"
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Solución para Modales de Bootstrap</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Incluir Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2, h3 {
            color: #8B4513;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
        }
        code {
            font-family: Consolas, Monaco, 'Andale Mono', monospace;
            background-color: #f5f5f5;
            padding: 2px 4px;
            border-radius: 3px;
        }
        button {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Solución para Problemas con Modales de Bootstrap</h1>
        
        <div class="card">
            <h2>Prueba de Modal</h2>
            <p>Haz clic en los botones para probar diferentes comportamientos de los modales:</p>
            
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">
                Abrir Modal Normal
            </button>
            
            <button type="button" class="btn btn-success" onclick="openModalProgrammatically()">
                Abrir Modal Programáticamente
            </button>
            
            <button type="button" class="btn btn-warning" onclick="openModalWithCleanup()">
                Abrir Modal con Limpieza
            </button>
            
            <button type="button" class="btn btn-danger" onclick="cleanupModals()">
                Limpiar Modales
            </button>
        </div>
        
        <div class="card">
            <h2>Diagnóstico</h2>
            <div id="diagnosticOutput">
                <p>Haz clic en un botón para ver el diagnóstico...</p>
            </div>
        </div>
        
        <div class="card">
            <h2>Solución para Vue.js</h2>
            <div class="alert alert-info">
                <p>El problema con los modales en tu aplicación Vue.js puede deberse a varias causas:</p>
                <ol>
                    <li>Múltiples backdrops que se superponen</li>
                    <li>El modal no se cierra correctamente antes de abrir otro</li>
                    <li>Problemas con el z-index</li>
                    <li>Conflictos entre diferentes versiones de Bootstrap</li>
                </ol>
            </div>
            
            <h3>Solución para ShoppingCart.vue</h3>
            <pre><code>// En el método proceedToCheckout:
const proceedToCheckout = () => {
  // Cerrar el modal del carrito correctamente
  const modalElement = document.getElementById('cartModal');
  if (modalElement) {
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) {
      modal.hide();
      // Dar tiempo para que el backdrop se elimine completamente
      setTimeout(() => {
        // Eliminar cualquier backdrop residual
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
          backdrop.remove();
        });
        
        // Eliminar la clase modal-open del body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // Mostrar el formulario de checkout
        showCheckoutForm.value = true;
        
        // Emitir evento
        const event = new CustomEvent('checkout', { detail: { cart: cart.value } });
        document.dispatchEvent(event);
      }, 300);
    }
  }
};</code></pre>
            
            <h3>Solución para CheckoutForm.vue</h3>
            <pre><code>// En el método openCheckoutModal:
const openCheckoutModal = () => {
  const modal = document.getElementById('checkoutModal');
  if (modal && typeof bootstrap !== 'undefined') {
    // Asegurarse de que no haya backdrops residuales
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
      backdrop.remove();
    });
    
    // Asegurarse de que el body no tenga la clase modal-open
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // Crear una nueva instancia del modal y mostrarlo
    const bsModal = new bootstrap.Modal(modal, {
      backdrop: true,
      keyboard: true,
      focus: true
    });
    
    // Pequeño retraso para asegurar que todo esté limpio
    setTimeout(() => {
      bsModal.show();
    }, 100);
  }
};</code></pre>
        </div>
    </div>
    
    <!-- Modal de prueba -->
    <div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testModalLabel">Modal de Prueba</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Este es un modal de prueba para diagnosticar problemas.</p>
                    <p>Puedes interactuar con este modal para verificar su funcionamiento.</p>
                    
                    <button type="button" class="btn btn-primary" onclick="showNestedModal()">
                        Abrir Modal Anidado
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal anidado -->
    <div class="modal fade" id="nestedModal" tabindex="-1" aria-labelledby="nestedModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nestedModalLabel">Modal Anidado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Este es un modal anidado para probar la transición entre modales.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Función para mostrar diagnóstico
        function updateDiagnostic() {
            const output = document.getElementById('diagnosticOutput');
            const modalBackdrops = document.querySelectorAll('.modal-backdrop');
            const bodyHasModalOpen = document.body.classList.contains('modal-open');
            
            let html = '<h3>Estado actual:</h3>';
            html += `<p>Número de backdrops: ${modalBackdrops.length}</p>`;
            html += `<p>Body tiene clase modal-open: ${bodyHasModalOpen}</p>`;
            html += `<p>Body overflow: ${document.body.style.overflow}</p>`;
            html += `<p>Body padding-right: ${document.body.style.paddingRight}</p>`;
            
            output.innerHTML = html;
        }
        
        // Actualizar diagnóstico cada segundo
        setInterval(updateDiagnostic, 1000);
        
        // Función para abrir modal programáticamente
        function openModalProgrammatically() {
            const modal = document.getElementById('testModal');
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
        
        // Función para abrir modal con limpieza previa
        function openModalWithCleanup() {
            // Limpiar primero
            cleanupModals();
            
            // Luego abrir el modal
            setTimeout(() => {
                const modal = document.getElementById('testModal');
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }, 100);
        }
        
        // Función para limpiar modales
        function cleanupModals() {
            // Eliminar backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.remove();
            });
            
            // Eliminar clase modal-open
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            // Cerrar modales abiertos
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                modal.classList.remove('show');
                modal.style.display = 'none';
            });
            
            updateDiagnostic();
        }
        
        // Función para mostrar modal anidado
        function showNestedModal() {
            const modal = document.getElementById('nestedModal');
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    </script>
</body>
</html>
<?php
// Liberar el buffer de salida
ob_end_flush();
?>

