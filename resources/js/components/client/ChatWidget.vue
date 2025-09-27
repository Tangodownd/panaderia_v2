<template>
  <div class="chatbox">
    <div class="messages" ref="scroll">
      <div
        v-for="m in messages"
        :key="m.id"
        :class="m.role"
      >
        <template v-for="(seg, i) in renderMessage(m.text ?? '')" :key="`${m.id}-${i}`">
          <a
            v-if="seg.type === 'link'"
            :href="seg.href"
            target="_blank"
            rel="noopener noreferrer"
          >{{ seg.label }}</a>
          <span v-else>{{ seg.text }}</span>
        </template>
      </div>
    </div>

    <form @submit.prevent="send">
      <input v-model="text" placeholder="Escribe tu pedido..." />
      <button type="submit">Enviar</button>
    </form>
  </div>
</template>

<script>
import axios from '@/axios-config' // asegúrate de usar tu config

export default {
  name: 'ChatWidget',
  data() {
    return {
      text: '',
      messages: [],
      lastId: 0,
      pollTimer: null,
      conversationId: null
    }
  },
  methods: {
    scrollToBottom() {
      this.$nextTick(() => {
        if (this.$refs.scroll) {
          this.$refs.scroll.scrollTop = this.$refs.scroll.scrollHeight;
        }
      });
    },

    // Parser robusto de URLs:
    // - Detecta http(s):// hasta el primer espacio o cierre ) ] } o final de línea
    // - Evita arrastrar puntuación final común como .,;:
    linkifySegments(raw) {
      const text = String(raw ?? '')
      const lines = text.split(/\r?\n/)
      //      url
      const urlRe = /(https?:\/\/[^\s)\]}]+[^\s)\]}.,;:!?])/gi

      const out = []
      lines.forEach((line, idx) => {
        if (idx > 0) out.push({ type: 'text', text: '\n' })

        let last = 0
        let match
        while ((match = urlRe.exec(line)) !== null) {
          const url = match[0]
          const start = match.index
          const end = start + url.length

          if (start > last) {
            out.push({ type: 'text', text: line.slice(last, start) })
          }

          const isInvoice = /\/api\/orders\/\d+\/invoice(?:\?|$)/i.test(url) || /\/orders\/\d+\/invoice(?:\?|$)/i.test(url) || /\/invoice\.pdf(?:\?|$)/i.test(url)
          out.push({
            type: 'link',
            href: url,
            label: isInvoice ? 'Aqui' : url
          })

          last = end
        }

        if (last < line.length) {
          out.push({ type: 'text', text: line.slice(last) })
        }
      })

      // Debug: comenta esta línea si no lo quieres
      console.debug('[linkifySegments]', { raw: text, segments: out })
      return out
    },

    renderMessage(raw) {
      return this.linkifySegments(raw)
    },

    async send() {
      const t = this.text.trim()
      if (!t) return
      this.messages.push({ id: `u-${Date.now()}`, role: 'user', text: t })
      this.scrollToBottom()
      this.text = ''

      try {
        const { data } = await axios.post('/api/chat/process', { text: t })
        if (data?.reply) {
          this.messages.push({ id: `a-${Date.now()}`, role: 'assistant', text: String(data.reply ?? '') })
          this.scrollToBottom()
        }
        if (data?.conversation_id) this.conversationId = data.conversation_id
        await this.pull()
      } catch {
        this.messages.push({
          id: `e-${Date.now()}`,
          role: 'assistant',
          text: 'Hubo un problema al enviar tu mensaje. Inténtalo de nuevo.'
        })
        this.scrollToBottom()
      }
    },

    async pull() {
      try {
        const params = { after_id: this.lastId }
        if (this.conversationId) params.conversation_id = this.conversationId

        const { data } = await axios.get('/api/chat/messages', { params })
        if (data?.conversation_id && !this.conversationId) {
          this.conversationId = data.conversation_id
        }
        if (Array.isArray(data?.messages) && data.messages.length) {
          data.messages.forEach(m => {
            this.messages.push({
              id: m.id,
              role: m.role === 'user' ? 'user' : 'assistant',
              text: String(m.text ?? '')
            })
            this.lastId = Math.max(this.lastId, m.id)
          })
          this.scrollToBottom()
        }
      } catch {
        // silencioso
      }
    },

    async initialLoad() {
      try {
        const { data } = await axios.get('/api/chat/messages', { params: { limit: 50 } })
        if (data?.conversation_id) this.conversationId = data.conversation_id
        if (Array.isArray(data?.messages)) {
          data.messages.forEach(m => {
            this.messages.push({
              id: m.id,
              role: m.role === 'user' ? 'user' : 'assistant',
              text: String(m.text ?? '')
            })
            this.lastId = Math.max(this.lastId, m.id)
          })
          this.scrollToBottom()
        }
      } catch {}
    }
  },
  async mounted() {
    await this.initialLoad()
    this.pollTimer = setInterval(this.pull, 3000)
  },
  beforeUnmount() {
    if (this.pollTimer) clearInterval(this.pollTimer)
  }
}
</script>

<style scoped>
.chatbox{border:1px solid #ddd;border-radius:10px;max-width:380px;display:flex;flex-direction:column;height:420px}
.messages{flex:1;overflow:auto;padding:8px;white-space:pre-wrap}
.user{text-align:right;margin:6px 0}
.assistant{text-align:left;margin:6px 0}
.assistant a{
  color:#7b4b2a;
  font-weight:600;
  text-decoration:underline;
  cursor:pointer;
  word-break:break-word;
}
form{display:flex;gap:8px;padding:8px;border-top:1px solid #eee}
input{flex:1;padding:8px;border:1px solid #ddd;border-radius:8px}
button{padding:8px 12px;border:0;border-radius:8px;background:#7b4b2a;color:#fff}
</style>
