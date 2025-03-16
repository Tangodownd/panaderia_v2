/**
* Script para depurar problemas con las llamadas a la API
*
* Instrucciones:
* 1. Copia este archivo en tu proyecto en resources/js/debug-api.js
* 2. Compila tus assets: npm run dev
* 3. Abre la consola del navegador (F12 o Ctrl+Shift+I)
* 4. Pega el siguiente código en la consola:
*    fetch('/js/debug-api.js').then(r => r.text()).then(t => eval(t))
* 5. Presiona Enter para ejecutar
* 6. Revisa los resultados en la consola
*/

;(() => {
    console.log("=== DEPURACIÓN DE LLAMADAS A LA API ===")
    console.log("Fecha y hora:", new Date().toLocaleString())
  
    // 1. Verificar token CSRF
    console.log("\n--- VERIFICANDO TOKEN CSRF ---")
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
    if (csrfToken) {
      console.log("✅ Token CSRF encontrado:", csrfToken)
    } else {
      console.log("❌ No se encontró el token CSRF en el documento")
      console.log("Asegúrate de tener la siguiente etiqueta en tu <head>:")
      console.log('<meta name="csrf-token" content="{{ csrf_token() }}">')
    }
  
    // 2. Verificar configuración de Axios
    console.log("\n--- VERIFICANDO CONFIGURACIÓN DE AXIOS ---")
    if (window.axios) {
      console.log("✅ Axios está disponible globalmente")
      
      // Verificar si Axios tiene configurado el token CSRF
      if (window.axios.defaults.headers.common['X-CSRF-TOKEN'] === csrfToken) {
        console.log("✅ Axios tiene configurado el token CSRF correctamente")
      } else {
        console.log("❌ Axios NO tiene configurado el token CSRF correctamente")
        console.log("Actual:", window.axios.defaults.headers.common['X-CSRF-TOKEN'])
        console.log("Esperado:", csrfToken)
      }
      
      // Verificar si Axios tiene configurado withCredentials
      if (window.axios.defaults.withCredentials === true) {
        console.log("✅ Axios tiene configurado withCredentials = true")
      } else {
        console.log("❌ Axios NO tiene configurado withCredentials = true")
        console.log("Esto es necesario para enviar cookies en las peticiones")
      }
    } else {
      console.log("❌ Axios no está disponible globalmente")
    }
  
    // 3. Probar una petición GET a la API
    console.log("\n--- PROBANDO PETICIÓN GET A LA API ---")
    console.log("Enviando petición GET a /api/cart...")
  
    fetch("/api/cart", {
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include'
    })
      .then((response) => {
        console.log("✅ Respuesta recibida con status:", response.status)
        return response.json()
      })
      .then((data) => {
        console.log("✅ Datos recibidos:", data)
      })
      .catch((error) => {
        console.error("❌ Error al conectar con la API:", error)
      })
  
    // 4. Probar una petición POST a la API (simulando añadir al carrito)
    console.log("\n--- PROBANDO PETICIÓN POST A LA API ---")
    console.log("Enviando petición POST a /api/cart/add...")
  
    fetch("/api/cart/add", {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include',
      body: JSON.stringify({
        product_id: 1, // Asegúrate de que este ID exista
        quantity: 1
      })
    })
      .then((response) => {
        console.log("✅ Respuesta recibida con status:", response.status)
        return response.json()
      })
      .then((data) => {
        console.log("✅ Datos recibidos:", data)
      })
      .catch((error) => {
        console.error("❌ Error al conectar con la API:", error)
      })
  
    // 5. Verificar cookies
    console.log("\n--- VERIFICANDO COOKIES ---")
    const cookies = document.cookie.split(';').map(cookie => cookie.trim())
    console.log("Cookies disponibles:", cookies)
    
    const sessionCookie = cookies.find(cookie => cookie.startsWith('cart_session_id='))
    if (sessionCookie) {
      console.log("✅ Cookie de sesión del carrito encontrada:", sessionCookie)
    } else {
      console.log("❌ No se encontró la cookie de sesión del carrito")
      console.log("Esto puede causar problemas para mantener el carrito entre sesiones")
    }
  
    // 6. Verificar localStorage
    console.log("\n--- VERIFICANDO LOCALSTORAGE ---")
    const savedCart = localStorage.getItem("panaderia-cart")
    if (savedCart) {
      try {
        const parsedCart = JSON.parse(savedCart)
        console.log("✅ Carrito encontrado en localStorage:", parsedCart)
        console.log("Total de productos:", parsedCart.items ? parsedCart.items.length : 0)
      } catch (e) {
        console.error("❌ Error al parsear el carrito de localStorage:", e)
      }
    } else {
      console.log("❌ No se encontró ningún carrito en localStorage")
    }
  
    // 7. Verificar si el componente ShoppingCart está usando Vue 2 o Vue 3
    console.log("\n--- VERIFICANDO VERSIÓN DE VUE ---")
    if (window.Vue) {
      console.log("✅ Vue 2 está disponible globalmente")
      console.log("Versión de Vue:", window.Vue.version)
    } else if (window.__VUE__) {
      console.log("✅ Vue 3 está disponible globalmente")
    } else {
      console.log("❌ No se pudo detectar la versión de Vue")
    }
  
    // 8. Verificar si el componente ShoppingCart está montado
    console.log("\n--- VERIFICANDO COMPONENTE SHOPPINGCART ---")
    const shoppingCartElement = document.querySelector('.shopping-cart-container')
    if (shoppingCartElement) {
      console.log("✅ Componente ShoppingCart encontrado en el DOM")
      
      // Intentar acceder a la instancia de Vue
      if (window.Vue && shoppingCartElement.__vue__) {
        console.log("✅ Instancia de Vue 2 encontrada en el componente")
        console.log("Datos del carrito:", shoppingCartElement.__vue__.cart)
      } else if (shoppingCartElement.__vueParentComponent) {
        console.log("✅ Instancia de Vue 3 encontrada en el componente")
      } else {
        console.log("❌ No se pudo acceder a la instancia de Vue del componente")
      }
    } else {
      console.log("❌ No se encontró el componente ShoppingCart en el DOM")
    }
  
    console.log("\n=== RECOMENDACIONES ===")
    console.log("1. Verifica que estés usando la misma versión de Vue en todos tus componentes")
    console.log("2. Asegúrate de que Axios esté configurado correctamente con el token CSRF")
    console.log("3. Verifica que las rutas de la API sean correctas")
    console.log("4. Revisa los logs del servidor para ver si hay errores en el backend")
    console.log("5. Usa las herramientas de desarrollo del navegador para ver las peticiones de red")
  
    return "✅ Depuración completada. Revisa la consola para más detalles."
  })()