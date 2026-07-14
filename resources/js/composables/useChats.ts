import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { onMounted, onUnmounted, ref, watch } from 'vue'
import type { Ref } from 'vue'

export interface ChatMessage {
  id: number
  role: 'user' | 'assistant'
  content: string
  time: string
}

interface ChatApiResponse {
  reply: string
  conversation_id: number
}

interface HistoryMessage {
  id: number
  role: 'user' | 'assistant'
  content: string
  created_at: string
}

interface HistoryResponse {
  messages: HistoryMessage[]
}

interface UseChatOptions {
  sendEndpoint?: string
  historyEndpoint?: (id: number) => string
}

function formatTime(dateStr?: string): string {
  const date = dateStr ? new Date(dateStr) : new Date()

  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

export function useChat(options: UseChatOptions = {}) {
  const sendEndpoint = options.sendEndpoint ?? '/ai/chat'
  const historyEndpoint =
    options.historyEndpoint ?? ((id: number) => `/ai/conversations/${id}/messages`)

  const page = usePage()
  const urlConversationId = page.props.conversationId as number | null | undefined

  // URL is the source of truth. If we're on /chat with no ID, forget any
  // leftover ID from a previous conversation so new messages don't reuse it.
  if (typeof window !== 'undefined') {
    if (urlConversationId) {
      localStorage.setItem('conversation_id', String(urlConversationId))
    } else {
      localStorage.removeItem('conversation_id')
    }
  }

  const messages: Ref<ChatMessage[]> = ref([])
  const conversationId: Ref<number | null> = ref(urlConversationId ?? null)

  const loading = ref(false)
  const sending = ref(false)
  const error = ref<string | null>(null)

  async function loadHistory(): Promise<void> {
    if (!conversationId.value) {
      return
    }

    loading.value = true
    error.value = null

    try {
      const { data } = await axios.get<HistoryResponse>(historyEndpoint(conversationId.value))
      messages.value = data.messages.map((m) => ({
        id: m.id,
        role: m.role,
        content: m.content,
        time: formatTime(m.created_at),
      }))
    } catch (e) {
      console.error('History load failed:', e)
      // Stale or invalid conversation_id — clear it so we don't keep failing
      if (typeof window !== 'undefined') {
        localStorage.removeItem('conversation_id')
      }
      conversationId.value = null
      messages.value = []
    } finally {
      loading.value = false
    }
  }

  async function sendMessage(text: string): Promise<void> {
    const trimmed = text.trim()

    if (!trimmed || sending.value) {
      return
    }

    error.value = null

    // Optimistic update — show user's message immediately
    messages.value.push({
      id: Date.now(),
      role: 'user',
      content: trimmed,
      time: formatTime(),
    })

    sending.value = true

    const isNewConversation = conversationId.value === null

    try {
      const { data } = await axios.post<ChatApiResponse>(sendEndpoint, {
        message: trimmed,
        conversation_id: conversationId.value,
      })

      // Persist conversation_id so refresh loads the same chat
      conversationId.value = data.conversation_id
      if (typeof window !== 'undefined') {
        localStorage.setItem('conversation_id', String(data.conversation_id))
      }

      // Reflect the new conversation in the URL and reload Inertia props
      if (isNewConversation) {
        router.visit(`/chat/${data.conversation_id}`, {
          preserveState: true,
          replace: true,
        })
      } else {
        router.reload({ only: ['chatHistory'] })
      }

      messages.value.push({
        id: Date.now() + 1,
        role: 'assistant',
        content: data.reply,
        time: formatTime(),
      })
    } catch (e) {
      console.error('Send message failed:', e)
      error.value = 'Message failed to send. Please try again.'
      messages.value.push({
        id: Date.now() + 1,
        role: 'assistant',
        content: 'Sorry, something went wrong. Please try again.',
        time: formatTime(),
      })
    } finally {
      sending.value = false
    }
  }

  function startNewConversation(): void {
    conversationId.value = null
    messages.value = []
    if (typeof window !== 'undefined') {
      localStorage.removeItem('conversation_id')
    }
    window.history.pushState({}, '', '/chat')
  }

  function openConversation(id: number): void {
    conversationId.value = id
    if (typeof window !== 'undefined') {
      localStorage.setItem('conversation_id', String(id))
    }
    window.history.pushState({}, '', `/chat/${id}`)
    loadHistory()
  }

  onMounted(() => {
    window.addEventListener('chat:new', startNewConversation)
    loadHistory()
  })

  onUnmounted(() => {
    window.removeEventListener('chat:new', startNewConversation)
  })

  // Inertia reuses the same Dashboard component instance across navigations,
  // so onMounted alone won't rerun when the URL/prop changes. Watch the prop
  // directly to stay in sync (e.g. after clicking "Start new chat").
  watch(
    () => page.props.conversationId as number | null | undefined,
    (newId) => {
      if (newId) {
        // Only load history if we're actually switching to a different conversation
        if (newId !== conversationId.value) {
          conversationId.value = newId
          if (typeof window !== 'undefined') {
            localStorage.setItem('conversation_id', String(newId))
          }
          loadHistory()
        }
      } else {
        // Navigated to a fresh /chat with no ID — reset everything
        if (conversationId.value !== null) {
          conversationId.value = null
          messages.value = []
          if (typeof window !== 'undefined') {
            localStorage.removeItem('conversation_id')
          }
        }
      }
    },
  )

  return {
    messages,
    conversationId,
    loading,
    sending,
    error,
    loadHistory,
    sendMessage,
    startNewConversation,
    openConversation,
  }
}
