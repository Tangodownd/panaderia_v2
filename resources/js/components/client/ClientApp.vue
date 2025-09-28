<template>
  <div class="bg-beige min-vh-100 d-flex flex-column">
    <nav v-if="showNavbar" class="navbar navbar-expand-lg navbar-light bg-cream border-bottom border-brown sticky-top shadow-sm">
      <div class="container">
        <a class="navbar-brand text-brown" href="#">
          <i class="fas fa-bread-slice me-2"></i>Panadería Orquidea de Oro
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <router-link exact-active-class="active" to="/" class="nav-link">Inicio</router-link>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Categorías
              </a>
              <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                <li v-for="category in categories" :key="category.id">
                  <router-link :to="{ name: 'category', params: { id: category.id }}" class="dropdown-item">
                    {{ category.name }}
                  </router-link>
                </li>
                <li v-if="categories.length === 0">
                  <span class="dropdown-item">No hay categorías disponibles</span>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#contacto">Contacto</a>
            </li>
          </ul>
<div class="d-flex align-items-center gap-2">
  <router-link
    v-if="!isAuth"
    :to="{ name:'customerLogin' }"
    class="btn btn-outline-brown me-2"
  >
    Iniciar sesión
  </router-link>

  <template v-else>
    <router-link :to="{ name:'myOrders' }" class="btn btn-outline-brown me-2">
      Mis compras
    </router-link>
    <button class="btn btn-outline-brown" @click="logout">
      Cerrar sesión
    </button>
  </template>

  <shopping-cart ref="shoppingCart" @checkout="openCheckout" />
</div>


        </div>
      </div>
    </nav>

    <main class="flex-grow-1">
      <router-view @add-to-cart="addToCart" />
    </main>

    <footer class="bg-brown text-white py-4 mt-auto">
      <div class="container">
        <div class="row">
          <div class="col-md-4 mb-3 mb-md-0">
            <h5 class="mb-3">Panadería Pasteleria Charcuteria Orquidea de Oro C.A</h5>
            <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i> Centro Comercial Mega Mergado, Flor Amarillo, Valencia, Carabobo</p>
            <p class="mb-1"><i class="fas fa-phone me-2"></i> +58 424 4133486</p>
            <p class="mb-0"><i class="fas fa-envelope me-2"></i> kennytorres4444@gmail.com</p>
          </div>
          <div class="col-md-4 mb-3 mb-md-0">
            <h5 class="mb-3">Horario</h5>
            <p class="mb-1">Lunes a Domingo: 6:00 AM - 9:00 PM</p>
          </div>
          <div class="col-md-4" id="contacto">
            <h5 class="mb-3">Contáctanos</h5>
            <div class="d-flex mb-3">
              <a href="#" class="text-white me-3"><i class="fab fa-facebook-f fa-lg"></i></a>
              <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
              <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
              <a href="#" class="text-white"><i class="fab fa-whatsapp fa-lg"></i></a>
            </div>
            <p>¿Tienes alguna pregunta o sugerencia? ¡Escríbenos!</p>
          </div>
        </div>
        <hr class="my-4 bg-light">
        <div class="text-center">
          <p class="mb-0">&copy; {{ new Date().getFullYear() }} Panadería Orquidea de Oro. Todos los derechos reservados.</p>
        </div>
      </div>
    </footer>

    <checkout-form
      :cart="cart"
      @order-completed="resetCart"
      ref="checkoutForm"
    />

    <!-- === CHAT: FAB + Panel flotante (sin overlay) === -->
    <button
      v-if="isAuth"
      class="chat-fab"
      type="button"
      @click="toggleChat"
      :aria-expanded="chatOpen ? 'true' : 'false'"
      aria-label="Abrir chat"
    >
      <i class="fas fa-comment-dots"></i>
      <span v-if="unread > 0" class="badge">{{ unread }}</span>
    </button>

    <transition name="chat-fade">
      <div v-if="isAuth && chatOpen" class="chat-panel" role="dialog" aria-modal="true">
        <div class="chat-header">
          <div class="title">
            <i class="fas fa-bread-slice me-2"></i> Asistente Orquídea de Oro
          </div>
          <button class="btn-close" type="button" @click="toggleChat" aria-label="Cerrar"></button>
        </div>

        <div class="chat-messages" ref="chatScroll">
          <div
            v-for="m in messages"
            :key="m.id"
            class="msg"
            :class="m.role"
          >
            <span class="bubble">
              <template v-for="(seg, i) in renderMessage(m.text ?? '')" :key="`${m.id}-${i}`">
                <a v-if="seg.type === 'link'" :href="seg.href" target="_blank" rel="noopener noreferrer">{{ seg.label }}</a>
                <span v-else>{{ seg.text }}</span>
              </template>
            </span>
          </div>
        </div>

        <form class="chat-input" @submit.prevent="sendChat">
          <input
            v-model="text"
            type="text"
            placeholder="Escribe tu pedido..."
            :disabled="sending"
          />
          <button class="btn-brown" type="submit" :disabled="sending || !text.trim()">
            {{ sending ? '...' : 'Enviar' }}
          </button>
        </form>
      </div>
    </transition>
  </div>
</template>

<script>
import { onMounted } from 'vue'
import axios from "@/axios-config"
import ShoppingCart from './ShoppingCart.vue'
import CheckoutForm from './CheckoutForm.vue'
import auth from '../../services/auth'          // tu servicio auth.js (el que guarda token)
import { eventBus } from "../../services/event-bus"

const SID_KEY = 'chat_sid_v1'

export default {
  name: 'ClientApp',
  components: { ShoppingCart, CheckoutForm },

  data() {
    return {
      categories: [],
      cart: { items: [], total: 0 },

      chatOpen: false,
      sending: false,
      text: '',
      messages: [],
      sid: localStorage.getItem(SID_KEY) || (crypto?.randomUUID ? crypto.randomUUID() : String(Date.now())),
      unread: 0,

      // reactividad para login/logout
      authVersion: 0,

      _onCheckout: null,
      _onAuthChanged: null,
      _onStorage: null,
    }
  },

  computed: {
    isAuth() {
    void this.authVersion
    try { return !!localStorage.getItem('auth_token') } catch { return false }
  },
  showNavbar() {
    // No muestres navbar en login/registro, o cuando no hay sesión
    return this.isAuth && !this.$route.meta?.hideNavbar
  },
  },

  created() {
    // Inicializa header Authorization si había token
    auth.initializeAuth()

    this.fetchCategories()
    this.fetchCart()

    if (typeof bootstrap === 'undefined') {
      const script = document.createElement('script')
      script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'
      document.head.appendChild(script)
    }
  },

  mounted() {
    localStorage.setItem(SID_KEY, this.sid)

    // checkout listener
    this._onCheckout = (ev) => {
      try { if (ev?.detail?.cart) this.cart = ev.detail.cart } catch {}
      this.openCheckout()
    }
    document.addEventListener('checkout', this._onCheckout)

    // react a eventos de login/logout
    this._onAuthChanged = () => { this.authVersion++ }
    eventBus.on('auth:changed', this._onAuthChanged)

    // react a cambios en localStorage (otra pestaña, etc.)
    this._onStorage = (e) => {
      if (e.key === 'auth_token' || e.key === 'user') this.authVersion++
    }
    window.addEventListener('storage', this._onStorage)
  },

  unmounted() {
    if (this._onCheckout) document.removeEventListener('checkout', this._onCheckout)
    if (this._onAuthChanged) eventBus.off('auth:changed', this._onAuthChanged)
    if (this._onStorage) window.removeEventListener('storage', this._onStorage)
  },

  methods: {
    // === AUTH ===
    async logout() {
      try {
        // si tienes /api/logout funcionando, úsalo:
        await axios.post('/api/logout').catch(() => {})
      } catch (_) {}
      auth.clearAuth()
      this.authVersion++
      this.$router.push({ name: 'customerLogin' })
    },

    // === CHAT ===
  toggleChat() {
    if (!this.isAuth) return this.$router.push({ name: 'customerLogin' })
    this.chatOpen = !this.chatOpen
    if (this.chatOpen) { this.unread = 0; this.$nextTick(this.scrollChatToBottom) }
  },
    scrollChatToBottom() {
      const el = this.$refs.chatScroll
      if (el) el.scrollTop = el.scrollHeight
    },
    async sendChat() {
      const t = this.text.trim()
      if (!t) return
      this.sending = true
      this.messages.push({ id: Date.now(), role: 'user', text: t })
      this.text = ''

      try {
        const { data } = await axios.post('/api/chat/process', { session_id: this.sid, text: t })
        const reply = data?.reply || 'Entendido.'
        this.messages.push({ id: Date.now() + 1, role: 'assistant', text: reply })
        if (!this.chatOpen) this.unread++
      } catch (e) {
        this.messages.push({ id: Date.now() + 2, role: 'assistant', text: 'Hubo un problema al enviar tu mensaje. Inténtalo de nuevo.' })
        if (!this.chatOpen) this.unread++
      } finally {
        this.sending = false
        this.$nextTick(this.scrollChatToBottom)
      }
    },
    linkifySegments(raw) {
      const text = String(raw ?? '')
      const lines = text.split(/\r?\n/)
      const urlRe = /(https?:\/\/[^\s)\]}]+[^\s)\]}.,;:!?])/gi
      const out = []
      lines.forEach((line, idx) => {
        if (idx > 0) out.push({ type: 'text', text: '\n' })
        let last = 0; let m
        while ((m = urlRe.exec(line)) !== null) {
          const url = m[0], start = m.index, end = start + url.length
          if (start > last) out.push({ type: 'text', text: line.slice(last, start) })
          const isInvoice = /\/api\/orders\/\d+\/invoice(?:\?|$)/i.test(url)
                         || /\/orders\/\d+\/invoice(?:\?|$)/i.test(url)
                         || /\/invoice\.pdf(?:\?|$)/i.test(url)
          out.push({ type: 'link', href: url, label: isInvoice ? 'Aqui' : url })
          last = end
        }
        if (last < line.length) out.push({ type: 'text', text: line.slice(last) })
      })
      return out
    },
    renderMessage(raw) { return this.linkifySegments(raw) },

    // === DATA ===
    async fetchCategories() {
      try {
        const { data } = await axios.get('/api/categories')
        this.categories = Array.isArray(data) ? data : []
      } catch (error) {
        console.error('Error al cargar categorías:', error)
        this.categories = []
      }
    },
    async fetchCart() {
      try {
        const { data } = await axios.get('/api/cart')
        this.cart = data.cart || { items: [], total: 0 }
        this.cart.items = data.items || []
      } catch (error) {
        console.error('Error al cargar el carrito:', error)
      }
    },
    async addToCart({ product, quantity }) {
      try {
        if (this.$refs.shoppingCart) {
          await this.$refs.shoppingCart.addToCart(product, quantity)
          await this.fetchCart()
        }
      } catch (error) {
        console.error('Error al añadir producto al carrito:', error)
        this.showNotification('Error al añadir producto al carrito', 'danger')
      }
    },
    async resetCart() {
      try {
        await this.fetchCart()
        if (this.$refs.shoppingCart) this.$refs.shoppingCart.fetchCart()
        document.dispatchEvent(new Event('cart-updated'))
      } catch (error) {
        console.error('Error al resetear el carrito:', error)
      }
    },
    openCheckout() {
      setTimeout(() => this.$refs.checkoutForm?.openCheckoutModal(), 250)
    },
    showNotification(message, type = 'success') {
      const notification = document.createElement('div')
      notification.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`
      notification.setAttribute('role', 'alert')
      notification.setAttribute('aria-live', 'assertive')
      notification.setAttribute('aria-atomic', 'true')
      notification.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>`
      document.body.appendChild(notification)
      if (typeof bootstrap !== 'undefined') new bootstrap.Toast(notification, { delay: 3000 }).show()
      notification.addEventListener('hidden.bs.toast', () => document.body.removeChild(notification))
    },
  },
}
</script>



<style>
body {
  background-color: #F5E6D3;
  color: #4A3728;
}
.bg-beige { background-color: #F5E6D3; }
.bg-cream { background-color: #FFF8E7; }
.text-brown { color: #8B4513 !important; }
.border-brown { border-color: #8B4513 !important; }
.btn-brown { background-color: #8B4513; border-color: #8B4513; color: #FFF8E7; }
.btn-brown:hover { background-color: #6B3E0A; border-color: #6B3E0A; color: #FFF8E7; }
.navbar-light .navbar-nav .nav-link { color: rgba(75, 55, 40, 0.8); }
.navbar-light .navbar-nav .nav-link:hover,
.navbar-light .navbar-nav .nav-link.active { color: #8B4513; }
:focus-visible { outline: 2px solid #8B4513 !important; outline-offset: 2px; }

/* ===== CHAT styles (compacto y a juego con tu paleta) ===== */
.chat-fab{
  position: fixed;
  right: 18px;
  bottom: 18px;
  width: 56px;
  height: 56px;
  border: 0;
  border-radius: 50%;
  background: #8B4513;
  color: #FFF8E7;
  box-shadow: 0 6px 20px rgba(0,0,0,.25);
  cursor: pointer;
  z-index: 2147483000;
  display: grid;
  place-items: center;
}
.chat-fab .badge{
  position: absolute;
  top: -4px;
  right: -4px;
  background: #dc3545;
  color: #fff;
  border-radius: 999px;
  padding: 2px 6px;
  font-size: 11px;
  line-height: 1;
}

.chat-panel{
  position: fixed;
  right: 18px;
  bottom: 84px;
  width: 360px;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
  background: #FFF8E7;
  border: 1px solid #8B4513;
  border-radius: 14px;
  box-shadow: 0 10px 28px rgba(0,0,0,.25);
  z-index: 2147482999;
  overflow: hidden;
}

.chat-header{
  display:flex;
  align-items:center;
  justify-content: space-between;
  padding: 10px 12px;
  background: #F5E6D3;
  border-bottom: 1px solid rgba(0,0,0,.06);
}
.chat-header .title{ font-weight: 600; color: #8B4513; }
.chat-header .btn-close{
  border: 0; background: transparent; font-size: 18px; line-height: 1;
  color: #4A3728; opacity: .7; cursor: pointer;
}
.chat-header .btn-close::before{ content: '×'; }

.chat-messages{
  padding: 10px;
  overflow-y: auto;
  flex: 1;
  background: #FFF8E7;
}

.msg{ display:flex; margin: 6px 0; }
.msg.user{ justify-content: flex-end; }
.msg.assistant{ justify-content: flex-start; }
.bubble{
  max-width: 78%;
  padding: 8px 10px;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0,0,0,.08);
  word-break: break-word;
  white-space: pre-wrap;
}
.user .bubble{
  background: #8B4513; color: #FFF8E7; border-bottom-right-radius: 4px;
}
.assistant .bubble{
  background: #F5E6D3; color: #4A3728; border-bottom-left-radius: 4px; border: 1px solid rgba(0,0,0,.06);
}

/* Enlaces dentro de burbujas: se muestran en color marrón y con subrayado */
.bubble a{
  color: #7b4b2a;
  font-weight: 600;
  text-decoration: underline;
  cursor: pointer;
  word-break: break-word;
}

.chat-input{
  display:flex; gap: 8px; padding: 10px;
  border-top: 1px solid rgba(0,0,0,.06); background: #FFF8E7;
}
.chat-input input{
  flex:1; padding: 10px 12px; border: 1px solid #E3D6C4; border-radius: 10px; background: #fff; color: #4A3728;
}
.chat-input input:focus{ outline: 2px solid #8B4513; outline-offset: 1px; }

.chat-fade-enter-active, .chat-fade-leave-active{
  transition: opacity .18s ease, transform .18s ease;
}
.chat-fade-enter-from, .chat-fade-leave-to{
  opacity: 0; transform: translateY(8px);
}

@media (max-width: 576px){
  .chat-panel{
    right: 12px; left: 12px; width: auto; bottom: 80px; max-height: 68vh;
  }
}
</style>
