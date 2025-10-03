<template>
  <div class="admin-root">
    <!-- Contenido del admin -->
    <router-view v-slot="{ Component }">
      <transition name="fade" mode="out-in">
        <component :is="Component" />
      </transition>
    </router-view>

    <!-- === CHAT: FAB + Panel flotante (idéntico al del cliente) === -->
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
            <i class="fas fa-bread-slice me-2"></i> Asistente (Operaciones)
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
            placeholder="Pregúntame por top productos, horas pico, RFM, market basket, stock recomendado, plan de producción…"
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
import axios from "@/axios-config"

const SID_KEY = 'admin_chat_sid_v1'

export default {
  name: 'AdminApp',
  data() {
    return {
      chatOpen: false,
      sending: false,
      text: '',
      messages: [],
      sid: localStorage.getItem(SID_KEY) || (crypto?.randomUUID ? crypto.randomUUID() : String(Date.now())),
      unread: 0,
      _onStorage: null,
      authVersion: 0, // fuerza recomputar isAuth cuando cambie
      _onAuthChangedEvt: null,
    }
  },
  computed: {
    isAuth() {
      // Dependemos de authVersion para invalidar cache cuando emitimos evento
      void this.authVersion
      try { return !!localStorage.getItem('auth_token') } catch { return false }
    }
  },
  created() {
    // persistimos session_id local para conversación
    localStorage.setItem(SID_KEY, this.sid)
  },
  mounted() {
    // (opcional) mensaje de bienvenida al abrir por primera vez
    if (!sessionStorage.getItem('admin_chat_welcome')) {
      this.messages.push({
        id: Date.now(),
        role: 'assistant',
        text: 'Hola 👋 Soy tu asistente de *operaciones*. Pídeme: *top productos*, *horas pico*, *RFM*, *market basket*, *stock recomendado*, *plan de producción*.'
      })
      sessionStorage.setItem('admin_chat_welcome', '1')
    }

    // Si cambia el token en otra pestaña, no desaparezca el botón
    this._onStorage = (e) => {
      if (e.key === 'auth_token' || e.key === 'user') {
        this.$forceUpdate()
      }
    }
    window.addEventListener('storage', this._onStorage)

    // Evento interno para cambios de auth dentro de la misma pestaña
    this._onAuthChangedEvt = () => { this.authVersion++ }
    window.addEventListener('auth-changed', this._onAuthChangedEvt)
  },
  beforeUnmount() {
    if (this._onStorage) window.removeEventListener('storage', this._onStorage)
    if (this._onAuthChangedEvt) window.removeEventListener('auth-changed', this._onAuthChangedEvt)
  },
  methods: {
    toggleChat() {
      if (!this.isAuth) return
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
        // ⬇️ usa el endpoint ADMIN
        const { data } = await axios.post('/api/admin/chat', { session_id: this.sid, text: t })
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
    renderMessage(raw) { return this.linkifySegments(raw) }
  }
}
</script>

<style>
/* transición base del shell */
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

/* paleta/admin base (igual que ClientApp) */
body { background-color: #F5E6D3; color: #4A3728; }
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

/* ===== CHAT styles (idénticos al cliente) ===== */
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
