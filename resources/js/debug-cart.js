/**
 * Script para depurar problemas con el carrito de compras
 *
 * Instrucciones:
 * 1. Copia este archivo en tu proyecto en resources/js/debug-cart.js
 * 2. Abre la consola del navegador (F12 o Ctrl+Shift+I)
 * 3. Pega el siguiente código en la consola:
 *    fetch('/js/debug-cart.js').then(r => r.text()).then(t => eval(t))
 * 4. Presiona Enter para ejecutar
 * 5. Revisa los resultados en la consola
 */

;(() => {
    console.log("=== DEPURACIÓN DEL CARRITO DE COMPRAS ===")
    console.log("Fecha y hora:", new Date().toLocaleString())
  
    // 1. Verificar localStorage
    console.log("\n--- VERIFICANDO LOCALSTORAGE ---")
    const savedCart = localStorage.getItem("panaderia-cart")
    if (savedCart) {
      try {
        const parsedCart = JSON.parse(savedCart)
        console.log("✅ Carrito encontrado en localStorage:", parsedCart)
        console.log("Total de productos:", parsedCart.items ? parsedCart.items.length : 0)
        console.log("Total del carrito:", parsedCart.total)
      } catch (e) {
        console.error("❌ Error al parsear el carrito de localStorage:", e)
      }
    } else {
      console.log("❌ No se encontró ningún carrito en localStorage")
    }
  
    // 2. Verificar cookies
    console.log("\n--- VERIFICANDO COOKIES ---")
    const cookies = document.cookie.split(";").map((cookie) => cookie.trim())
    const sessionCookie = cookies.find((cookie) => cookie.startsWith("cart_session_id="))
    if (sessionCookie) {
      console.log("✅ Cookie de sesión encontrada:", sessionCookie)
    } else {
      console.log("❌ No se encontró la cookie de sesión del carrito")
    }
  
    // 3. Verificar token CSRF
    console.log("\n--- VERIFICANDO TOKEN CSRF ---")
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
    if (csrfToken) {
      console.log("✅ Token CSRF encontrado:", csrfToken)
    } else {
      console.log("❌ No se encontró el token CSRF en el documento")
      console.log("Asegúrate de tener la siguiente etiqueta en tu <head>:")
      console.log('<meta name="csrf-token" content="{{ csrf_token() }}">')
    }
  
    // 4. Verificar Vue
    console.log("\n--- VERIFICANDO VUE ---")
    if (window.__VUE__) {
      console.log("✅ Vue 3 está disponible globalmente")
  
      // Verificar instancia de Vue
      const appElement = document.querySelector("#app")
      if (appElement && appElement.__vue_app__) {
        console.log("✅ Instancia de Vue 3 encontrada en #app")
  
        // Verificar componentes registrados
        try {
          const app = appElement.__vue_app__
          console.log("Componentes registrados:", app._component.components)
  
          if (app._component.components && app._component.components["shopping-cart"]) {
            console.log("✅ El componente shopping-cart está registrado")
          } else {
            console.log("❌ El componente shopping-cart NO está registrado")
          }
        } catch (e) {
          console.error("❌ Error al verificar componentes registrados:", e)
        }
      } else {
        console.log("❓ No se pudo acceder a la instancia de Vue 3 en #app")
      }
    } else {
      console.log("❌ Vue 3 no está disponible globalmente")
    }
  
    // 5. Probar una petición a la API
    console.log("\n--- PROBANDO CONEXIÓN A LA API ---")
    console.log("Enviando petición GET a /api/products...")
  
    fetch("/api/products")
      .then((response) => {
        console.log("✅ Respuesta recibida con status:", response.status)
        return response.json()
      })
      .then((data) => {
        console.log("✅ Datos recibidos:", data)
        console.log("Total de productos:", data.length)
      })
      .catch((error) => {
        console.error("❌ Error al conectar con la API:", error)
      })
  
    // 6. Verificar rutas
    console.log("\n--- VERIFICANDO RUTAS ---")
    console.log("Ruta actual:", window.location.pathname)
  
    // 7. Resumen y recomendaciones
    console.log("\n=== RESUMEN Y RECOMENDACIONES ===")
    console.log("1. Verifica que estés usando Vue 3 correctamente en app.js")
    console.log("2. Asegúrate de que los componentes estén registrados con createApp().component()")
    console.log("3. Compila tus assets con: npm run dev")
    console.log("4. Limpia la caché del navegador y reinicia el servidor Laravel")
    console.log("5. Verifica los logs de Laravel en storage/logs/laravel.log")
  
    console.log("\n=== FIN DE LA DEPURACIÓN ===")
  
    return "✅ Depuración completada. Revisa la consola para más detalles."
  })()
  
  