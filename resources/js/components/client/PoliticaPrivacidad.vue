<template>
    <div class="container py-5">
      <div class="card bg-beige shadow">
        <div class="card-header bg-brown text-white">
          <h1 class="h3 mb-0">Política de Privacidad</h1>
        </div>
        <div class="card-body">
          <p class="text-muted mb-4">Última actualización: {{ new Date().toLocaleDateString() }}</p>
          
          <h5 class="mt-4">1. Información que Recopilamos</h5>
          <p>1.1. Recopilamos información personal como nombre, dirección de correo electrónico, número de teléfono y dirección de entrega para procesar sus pedidos.</p>
          <p>1.2. También podemos recopilar información sobre sus preferencias de compra para mejorar nuestro servicio.</p>
          
          <h5 class="mt-4">2. Uso de la Información</h5>
          <p>2.1. Utilizamos su información para procesar pedidos, gestionar entregas y comunicarnos con usted sobre su compra.</p>
          <p>2.2. Su número de teléfono se utiliza para comunicaciones a través de WhatsApp relacionadas con sus pedidos.</p>
          <p>2.3. Podemos utilizar su correo electrónico para enviar información sobre promociones y nuevos productos, siempre que haya dado su consentimiento.</p>
          
          <h5 class="mt-4">3. Protección de Datos</h5>
          <p>3.1. Implementamos medidas de seguridad para proteger su información personal.</p>
          <p>3.2. No compartimos su información con terceros excepto cuando es necesario para completar su pedido (por ejemplo, con servicios de entrega).</p>
          
          <h5 class="mt-4">4. WhatsApp</h5>
          <p>4.1. Las comunicaciones a través de WhatsApp están sujetas también a la política de privacidad de WhatsApp.</p>
          <p>4.2. Utilizamos WhatsApp como herramienta de comunicación para brindarle un mejor servicio.</p>
          
          <h5 class="mt-4">5. Sus Derechos</h5>
          <p>5.1. Tiene derecho a acceder, rectificar o eliminar sus datos personales.</p>
          <p>5.2. Para ejercer estos derechos, contacte con nosotros a través de los canales de comunicación proporcionados.</p>
          
          <h5 class="mt-4">6. Cookies</h5>
          <p>6.1. Nuestro sitio web utiliza cookies para mejorar su experiencia de navegación.</p>
          <p>6.2. Puede configurar su navegador para rechazar cookies, aunque esto puede afectar a la funcionalidad del sitio.</p>
          
          <h5 class="mt-4">7. Cambios en la Política de Privacidad</h5>
          <p>7.1. Nos reservamos el derecho de modificar esta política de privacidad en cualquier momento.</p>
          <p>7.2. Las modificaciones entrarán en vigor desde su publicación en nuestra plataforma.</p>
          
          <div class="mt-5 text-center">
            <a href="/" class="btn btn-brown">Volver a la tienda</a>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'PoliticaPrivacidad'
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