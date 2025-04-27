<template>
    <div class="container py-5">
      <div class="card bg-beige shadow">
        <div class="card-header bg-brown text-white">
          <h1 class="h3 mb-0">Términos y Condiciones</h1>
        </div>
        <div class="card-body">
          <p class="text-muted mb-4">Última actualización: {{ new Date().toLocaleDateString() }}</p>
          
          <h5 class="mt-4">1. Introducción</h5>
          <p>Bienvenido a Panadería Orquidea de Oro. Estos Términos y Condiciones rigen el uso de nuestro sitio web y la compra de productos a través de nuestra plataforma y WhatsApp.</p>
          
          <h5 class="mt-4">2. Pedidos</h5>
          <p>2.1. Los pedidos realizados a través de nuestra plataforma serán confirmados mediante WhatsApp.</p>
          <p>2.2. Nos reservamos el derecho de rechazar pedidos en caso de falta de disponibilidad de productos o por cualquier otra razón justificada.</p>
          <p>2.3. Los precios mostrados incluyen impuestos pero no incluyen gastos de envío, que se calcularán según la dirección de entrega.</p>
          
          <h5 class="mt-4">3. Entrega</h5>
          <p>3.1. Realizamos entregas en el área metropolitana según disponibilidad.</p>
          <p>3.2. Los tiempos de entrega son estimados y pueden variar según la demanda y la ubicación.</p>
          <p>3.3. El cliente es responsable de proporcionar una dirección de entrega correcta y completa.</p>
          
          <h5 class="mt-4">4. Pagos</h5>
          <p>4.1. Aceptamos pagos en efectivo, transferencia bancaria y tarjetas de crédito/débito.</p>
          <p>4.2. En caso de pago por transferencia, el pedido se procesará una vez confirmado el pago.</p>
          
          <h5 class="mt-4">5. Calidad y Frescura</h5>
          <p>5.1. Garantizamos la frescura de nuestros productos al momento de la entrega.</p>
          <p>5.2. Recomendamos consumir nuestros productos en el mismo día de la entrega para disfrutar de su máxima calidad.</p>
          
          <h5 class="mt-4">6. Devoluciones</h5>
          <p>6.1. Por la naturaleza perecedera de nuestros productos, no aceptamos devoluciones.</p>
          <p>6.2. En caso de recibir un producto en mal estado o incorrecto, contacte inmediatamente a nuestro servicio al cliente a través de WhatsApp para resolver la situación.</p>
          
          <h5 class="mt-4">7. Comunicación por WhatsApp</h5>
          <p>7.1. Al proporcionar su número de teléfono, acepta recibir comunicaciones relacionadas con su pedido a través de WhatsApp.</p>
          <p>7.2. Utilizamos WhatsApp como medio principal de comunicación para confirmar pedidos, informar sobre el estado de la entrega y resolver cualquier incidencia.</p>
          
          <h5 class="mt-4">8. Modificaciones</h5>
          <p>8.1. Nos reservamos el derecho de modificar estos términos y condiciones en cualquier momento.</p>
          <p>8.2. Las modificaciones entrarán en vigor desde su publicación en nuestra plataforma.</p>
          
          <div class="mt-5 text-center">
            <a href="/" class="btn btn-brown">Volver a la tienda</a>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'TerminosCondiciones'
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