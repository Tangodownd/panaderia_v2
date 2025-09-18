// resources/js/app.js
require("./bootstrap")

// Solo legacy (si existe #app en la vista legacy):
try {
  const mountTarget = document.getElementById("app")
  if (mountTarget) {
    const Vue = require("vue").default
    new Vue({ el: "#app" })
  }
} catch (e) {
  console.warn("app.js (legacy) no se montó:", e)
}

// Mueve la inicialización de tooltips/popovers a legacy-ui.js (ver abajo)
