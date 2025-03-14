/**
 * Servicio personalizado para mostrar notificaciones toast
 * Esta implementación no depende de Bootstrap para la eliminación de elementos
 */
export const ToastService = {
  /**
   * Muestra una notificación toast
   * @param {string} message - Mensaje a mostrar
   * @param {string} type - Tipo de notificación (success, danger, warning, info)
   * @param {number} duration - Duración en milisegundos
   */
  show(message, type = "success", duration = 3000) {
    try {
      // Crear un contenedor para las notificaciones si no existe
      let toastContainer = document.getElementById("custom-toast-container")
      if (!toastContainer) {
        toastContainer = document.createElement("div")
        toastContainer.id = "custom-toast-container"
        toastContainer.style.position = "fixed"
        toastContainer.style.bottom = "20px"
        toastContainer.style.right = "20px"
        toastContainer.style.zIndex = "9999"
        document.body.appendChild(toastContainer)
      }

      // Crear un elemento de notificación con ID único
      const toastId = "toast-" + Date.now() + "-" + Math.floor(Math.random() * 1000)
      const toast = document.createElement("div")
      toast.id = toastId
      toast.style.minWidth = "250px"
      toast.style.backgroundColor = this._getBackgroundColor(type)
      toast.style.color = "#fff"
      toast.style.borderRadius = "4px"
      toast.style.padding = "12px 20px"
      toast.style.marginBottom = "10px"
      toast.style.boxShadow = "0 4px 8px rgba(0, 0, 0, 0.2)"
      toast.style.display = "flex"
      toast.style.justifyContent = "space-between"
      toast.style.alignItems = "center"
      toast.style.opacity = "0"
      toast.style.transition = "opacity 0.3s ease-in-out"

      // Crear el contenido del toast
      const messageSpan = document.createElement("span")
      messageSpan.textContent = message

      // Crear el botón de cierre
      const closeButton = document.createElement("button")
      closeButton.textContent = "×"
      closeButton.style.background = "none"
      closeButton.style.border = "none"
      closeButton.style.color = "#fff"
      closeButton.style.fontSize = "20px"
      closeButton.style.fontWeight = "bold"
      closeButton.style.cursor = "pointer"
      closeButton.style.marginLeft = "10px"

      // Añadir el evento de cierre
      closeButton.addEventListener("click", () => {
        this._removeToast(toastId)
      })

      // Añadir elementos al toast
      toast.appendChild(messageSpan)
      toast.appendChild(closeButton)

      // Añadir el toast al contenedor
      toastContainer.appendChild(toast)

      // Mostrar el toast con una pequeña animación
      setTimeout(() => {
        toast.style.opacity = "1"
      }, 10)

      // Configurar el temporizador para eliminar el toast
      setTimeout(() => {
        this._removeToast(toastId)
      }, duration)

      return toastId
    } catch (error) {
      console.error("Error al mostrar notificación:", error)
    }
  },

  /**
   * Elimina un toast de forma segura
   * @param {string} toastId - ID del toast a eliminar
   */
  _removeToast(toastId) {
    try {
      const toast = document.getElementById(toastId)
      if (!toast) return

      // Animación de desvanecimiento
      toast.style.opacity = "0"

      // Eliminar después de la animación
      setTimeout(() => {
        try {
          const container = document.getElementById("custom-toast-container")
          if (container && toast && container.contains(toast)) {
            container.removeChild(toast)

            // Si no hay más toasts, eliminar el contenedor
            if (container.children.length === 0) {
              document.body.removeChild(container)
            }
          }
        } catch (e) {
          console.error("Error al eliminar toast después de la animación:", e)
        }
      }, 300)
    } catch (error) {
      console.error("Error al eliminar toast:", error)
    }
  },

  /**
   * Obtiene el color de fondo según el tipo de notificación
   * @param {string} type - Tipo de notificación
   * @returns {string} - Color de fondo
   */
  _getBackgroundColor(type) {
    switch (type) {
      case "success":
        return "#28a745"
      case "danger":
        return "#dc3545"
      case "warning":
        return "#ffc107"
      case "info":
        return "#17a2b8"
      default:
        return "#28a745"
    }
  },

  /**
   * Muestra una notificación de éxito
   * @param {string} message - Mensaje a mostrar
   * @param {number} duration - Duración en milisegundos
   */
  success(message, duration = 3000) {
    return this.show(message, "success", duration)
  },

  /**
   * Muestra una notificación de error
   * @param {string} message - Mensaje a mostrar
   * @param {number} duration - Duración en milisegundos
   */
  error(message, duration = 3000) {
    return this.show(message, "danger", duration)
  },

  /**
   * Muestra una notificación de advertencia
   * @param {string} message - Mensaje a mostrar
   * @param {number} duration - Duración en milisegundos
   */
  warning(message, duration = 3000) {
    return this.show(message, "warning", duration)
  },

  /**
   * Muestra una notificación informativa
   * @param {string} message - Mensaje a mostrar
   * @param {number} duration - Duración en milisegundos
   */
  info(message, duration = 3000) {
    return this.show(message, "info", duration)
  },
}

