// Bus de eventos para Vue 3
import { ref } from "vue"

// Crear un objeto para almacenar los eventos
const listeners = ref({})

export const eventBus = {
  /**
   * Emitir un evento
   * @param {string} event - Nombre del evento
   * @param {any} args - Argumentos para pasar a los listeners
   */
  emit(event, ...args) {
    if (listeners.value[event]) {
      listeners.value[event].forEach((callback) => {
        callback(...args)
      })
    }
  },

  /**
   * Escuchar un evento
   * @param {string} event - Nombre del evento
   * @param {Function} callback - Función a ejecutar cuando se emite el evento
   */
  on(event, callback) {
    if (!listeners.value[event]) {
      listeners.value[event] = []
    }
    listeners.value[event].push(callback)
  },

  /**
   * Dejar de escuchar un evento
   * @param {string} event - Nombre del evento
   * @param {Function} callback - Función a eliminar
   */
  off(event, callback) {
    if (listeners.value[event]) {
      listeners.value[event] = listeners.value[event].filter((cb) => cb !== callback)
    }
  },
}

