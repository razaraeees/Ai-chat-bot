<script setup lang="ts">
import { ref, nextTick, computed } from 'vue';

interface Message {
    id: number;
    type: 'bot' | 'user';
    text: string;
    time: string;
}

const isOpen = ref(false);
const isTyping = ref(false);
const inputText = ref('');
const messagesArea = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLTextAreaElement | null>(null);
const hasStarted = ref(false);

const messages = ref<Message[]>([]);

const suggestions = [
    { icon: '📊', text: 'Reports kese dekhein?' },
    { icon: '👥', text: 'Team member kaise add karein?' },
    { icon: '⚙️', text: 'Settings kahan hain?' },
    { icon: '🔍', text: 'Data search karne ka tarika?' },
];

function now() {
    return new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function openChat() {
    isOpen.value = true;
    nextTick(() => inputRef.value?.focus());
}

function closeChat() {
    isOpen.value = false;
}

function scrollToBottom() {
    nextTick(() => {
        if (messagesArea.value) {
            messagesArea.value.scrollTop = messagesArea.value.scrollHeight;
        }
    });
}

function selectSuggestion(text: string) {
    inputText.value = text;
    sendMessage();
}

function sendMessage() {
    const text = inputText.value.trim();
    if (!text || isTyping.value) return;

    hasStarted.value = true;

    messages.value.push({
        id: Date.now(),
        type: 'user',
        text,
        time: now(),
    });
    inputText.value = '';
    autoResize();
    scrollToBottom();

    isTyping.value = true;

    // *** YAHAN AI API CALL KAREIN ***
    // const res = await fetch('/api/ai-chat', {
    //   method: 'POST',
    //   headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    //   body: JSON.stringify({ message: text })
    // });
    // const data = await res.json();
    // botReply = data.reply;

    setTimeout(() => {
        isTyping.value = false;
        messages.value.push({
            id: Date.now() + 1,
            type: 'bot',
            text: getDemoReply(text),
            time: now(),
        });
        scrollToBottom();
    }, 1000 + Math.random() * 700);
}

function getDemoReply(text: string): string {
    const t = text.toLowerCase();
    if (t.includes('report')) return '📊 Reports ke liye sidebar mein Reports section mein jayein. Date range select karke PDF ya Excel mein export kar saktey hain.';
    if (t.includes('team') || t.includes('member')) return '👥 Team manage karne ke liye Settings → Team Members mein jayein. Wahan se invite bhej saktey hain naye members ko.';
    if (t.includes('setting')) return '⚙️ Settings top-right corner mein available hain. Wahan se profile, password, appearance aur team settings change ho sakti hain.';
    if (t.includes('search')) return '🔍 Search karne ke liye Ctrl+K shortcut use karein. Ya phir har table mein search input available hota hai.';
    if (t.includes('hello') || t.includes('hi') || t.includes('salaam') || t.includes('assalam')) return 'Wa Alaikum Assalam! 😊 Batao kya help chahiye — main yahan hun!';
    if (t.includes('shukriya') || t.includes('thank') || t.includes('shukria')) return 'Koi baat nahi! 😊 Aur kuch poochhna ho to zaroor likhein.';
    return '🤔 Acha sawaal hai! Ye feature abhi development mein hai. Koi doosra sawaal ho to zaroor poochein. 💡';
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
    if (e.key === 'Escape') {
        closeChat();
    }
}

function autoResize() {
    nextTick(() => {
        if (inputRef.value) {
            inputRef.value.style.height = 'auto';
            inputRef.value.style.height = Math.min(inputRef.value.scrollHeight, 160) + 'px';
        }
    });
}

const canSend = computed(() => inputText.value.trim().length > 0 && !isTyping.value);
</script>

<template>
    <!-- Trigger Button in header area — a clean "Ask AI" button -->
    <button id="ai-trigger-btn" class="ai-trigger" @click="openChat">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <span>Ask AI</span>
        <kbd>⌘K</kbd>
    </button>

    <!-- Full Screen Overlay Chat (Claude style) -->
    <Transition name="chat-overlay">
        <div v-if="isOpen" class="ai-overlay" @click.self="closeChat">
            <div class="ai-chat-window">

                <!-- Top Bar -->
                <div class="ai-topbar">
                    <div class="ai-topbar__brand">
                        <div class="ai-topbar__icon">✦</div>
                        <span>AI Assistant</span>
                        <span class="ai-topbar__badge">Beta</span>
                    </div>
                    <div class="ai-topbar__actions">
                        <button class="ai-icon-btn" title="New Chat" @click="messages = []; hasStarted = false;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                        </button>
                        <button class="ai-icon-btn" title="Close" @click="closeChat">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                <path d="M18 6 6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Messages OR Welcome Screen -->
                <div ref="messagesArea" class="ai-body">

                    <!-- Welcome Screen (no messages yet) -->
                    <Transition name="fade">
                        <div v-if="!hasStarted" class="ai-welcome">
                            <div class="ai-welcome__logo">✦</div>
                            <h1 class="ai-welcome__title">Kya help chahiye?</h1>
                            <p class="ai-welcome__sub">Sawaal poochein, system samjhein, ya kuch dhoondhein</p>

                            <!-- Suggestion Cards -->
                            <div class="ai-suggestions-grid">
                                <button
                                    v-for="s in suggestions"
                                    :key="s.text"
                                    class="ai-suggestion-card"
                                    @click="selectSuggestion(s.text)"
                                >
                                    <span class="ai-suggestion-card__icon">{{ s.icon }}</span>
                                    <span class="ai-suggestion-card__text">{{ s.text }}</span>
                                </button>
                            </div>
                        </div>
                    </Transition>

                    <!-- Chat Messages -->
                    <Transition name="fade">
                        <div v-if="hasStarted" class="ai-messages">
                            <div
                                v-for="msg in messages"
                                :key="msg.id"
                                :class="['ai-msg', `ai-msg--${msg.type}`]"
                            >
                                <!-- Bot Message -->
                                <template v-if="msg.type === 'bot'">
                                    <div class="ai-msg__bot-avatar">✦</div>
                                    <div class="ai-msg__bot-content">
                                        <div class="ai-msg__bot-name">AI Assistant</div>
                                        <div class="ai-msg__bot-text" v-html="msg.text.replace(/\n/g, '<br>')" />
                                        <div class="ai-msg__time">{{ msg.time }}</div>
                                    </div>
                                </template>

                                <!-- User Message -->
                                <template v-else>
                                    <div class="ai-msg__user-bubble">
                                        <div v-html="msg.text.replace(/\n/g, '<br>')" />
                                    </div>
                                </template>
                            </div>

                            <!-- Typing -->
                            <Transition name="fade">
                                <div v-if="isTyping" class="ai-msg ai-msg--bot">
                                    <div class="ai-msg__bot-avatar">✦</div>
                                    <div class="ai-msg__bot-content">
                                        <div class="ai-msg__bot-name">AI Assistant</div>
                                        <div class="ai-typing">
                                            <span/><span/><span/>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </Transition>
                </div>

                <!-- Input Bar (Claude style — bottom center) -->
                <div class="ai-input-section">
                    <div class="ai-input-box">
                        <textarea
                            ref="inputRef"
                            v-model="inputText"
                            placeholder="Message AI Assistant..."
                            rows="1"
                            @keydown="handleKeydown"
                            @input="autoResize"
                        />
                        <div class="ai-input-actions">
                            <span class="ai-char-hint">{{ inputText.length > 0 ? inputText.length + '/500' : 'Shift+Enter for new line' }}</span>
                            <button
                                class="ai-send"
                                :class="{ 'ai-send--active': canSend }"
                                :disabled="!canSend"
                                @click="sendMessage"
                            >
                                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                                    <path d="M12 19V5M5 12l7-7 7 7"/>
                                    <path d="M5 12l7-7 7 7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p class="ai-disclaimer">AI galtiyan kar sakta hai. Zaroori faislon mein check zaroor karein.</p>
                </div>

            </div>
        </div>
    </Transition>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

/* ── Trigger Button ── */
.ai-trigger {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 14px 6px 10px;
    background: transparent;
    border: 1px solid hsl(var(--border));
    border-radius: 10px;
    color: hsl(var(--muted-foreground));
    font-size: 13px;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.ai-trigger:hover {
    background: hsl(var(--accent));
    color: hsl(var(--accent-foreground));
    border-color: hsl(var(--border));
}
.ai-trigger svg { flex-shrink: 0; }
.ai-trigger kbd {
    font-size: 10px;
    padding: 1px 5px;
    background: hsl(var(--muted));
    border-radius: 4px;
    font-family: monospace;
    margin-left: 2px;
    color: hsl(var(--muted-foreground));
}

/* ── Overlay ── */
.ai-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    font-family: 'Inter', sans-serif;
}
.chat-overlay-enter-active { transition: all 0.25s ease; }
.chat-overlay-leave-active { transition: all 0.2s ease; }
.chat-overlay-enter-from { opacity: 0; }
.chat-overlay-leave-to   { opacity: 0; }
.chat-overlay-enter-from .ai-chat-window { transform: scale(0.95) translateY(10px); }
.chat-overlay-leave-to   .ai-chat-window { transform: scale(0.95) translateY(10px); }

/* ── Chat Window (Claude style) ── */
.ai-chat-window {
    width: 100%;
    max-width: 780px;
    height: 85vh;
    max-height: 760px;
    background: hsl(var(--background));
    border: 1px solid hsl(var(--border));
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 40px 120px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.04);
    transition: transform 0.25s ease;
}

/* ── Top Bar ── */
.ai-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid hsl(var(--border));
    flex-shrink: 0;
}
.ai-topbar__brand {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
    font-weight: 600;
    color: hsl(var(--foreground));
}
.ai-topbar__icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}
.ai-topbar__badge {
    font-size: 10px;
    padding: 2px 7px;
    border-radius: 20px;
    background: rgba(99, 102, 241, 0.12);
    color: #6366f1;
    font-weight: 500;
    border: 1px solid rgba(99,102,241,0.25);
}
.ai-topbar__actions {
    display: flex;
    gap: 6px;
}
.ai-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: hsl(var(--muted-foreground));
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}
.ai-icon-btn:hover {
    background: hsl(var(--accent));
    color: hsl(var(--accent-foreground));
}

/* ── Body ── */
.ai-body {
    flex: 1;
    overflow-y: auto;
    position: relative;
    scrollbar-width: thin;
    scrollbar-color: hsl(var(--border)) transparent;
}
.ai-body::-webkit-scrollbar { width: 5px; }
.ai-body::-webkit-scrollbar-thumb { background: hsl(var(--border)); border-radius: 10px; }

/* ── Welcome Screen ── */
.ai-welcome {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 40px 24px;
    text-align: center;
    gap: 16px;
}
.ai-welcome__logo {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
    box-shadow: 0 12px 40px rgba(99,102,241,0.35);
    margin-bottom: 4px;
}
.ai-welcome__title {
    font-size: 28px;
    font-weight: 700;
    color: hsl(var(--foreground));
    margin: 0;
    letter-spacing: -0.5px;
}
.ai-welcome__sub {
    font-size: 14.5px;
    color: hsl(var(--muted-foreground));
    margin: 0;
    max-width: 340px;
    line-height: 1.6;
}

/* ── Suggestion Cards Grid ── */
.ai-suggestions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    width: 100%;
    max-width: 500px;
    margin-top: 12px;
}
.ai-suggestion-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 15px;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s ease;
    color: hsl(var(--foreground));
    font-family: 'Inter', sans-serif;
}
.ai-suggestion-card:hover {
    border-color: #6366f1;
    background: rgba(99,102,241,0.06);
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(99,102,241,0.12);
}
.ai-suggestion-card__icon { font-size: 18px; flex-shrink: 0; }
.ai-suggestion-card__text { font-size: 12.5px; font-weight: 500; line-height: 1.4; }

/* ── Messages ── */
.ai-messages {
    padding: 24px 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    min-height: 100%;
}
.ai-msg {
    display: flex;
    gap: 12px;
    animation: msg-in 0.25s ease;
}
@keyframes msg-in {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.ai-msg--user {
    justify-content: flex-end;
}

/* Bot Message */
.ai-msg__bot-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: white;
    flex-shrink: 0;
    margin-top: 2px;
}
.ai-msg__bot-content { display: flex; flex-direction: column; gap: 4px; max-width: 88%; }
.ai-msg__bot-name {
    font-size: 12px;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
}
.ai-msg__bot-text {
    font-size: 14px;
    line-height: 1.65;
    color: hsl(var(--foreground));
    word-break: break-word;
}
.ai-msg__time {
    font-size: 10.5px;
    color: hsl(var(--muted-foreground));
    margin-top: 2px;
}

/* User Message */
.ai-msg__user-bubble {
    max-width: 75%;
    padding: 11px 16px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    border-radius: 18px;
    border-bottom-right-radius: 5px;
    font-size: 14px;
    line-height: 1.55;
    word-break: break-word;
    box-shadow: 0 4px 16px rgba(99,102,241,0.3);
}

/* ── Typing ── */
.ai-typing {
    display: flex;
    gap: 5px;
    padding: 8px 2px;
    align-items: center;
}
.ai-typing span {
    width: 7px;
    height: 7px;
    background: #6366f1;
    border-radius: 50%;
    animation: bounce 1.2s ease-in-out infinite;
}
.ai-typing span:nth-child(2) { animation-delay: 0.18s; }
.ai-typing span:nth-child(3) { animation-delay: 0.36s; }
@keyframes bounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
    30%            { transform: translateY(-7px); opacity: 1; }
}

/* ── Input Section (Claude style) ── */
.ai-input-section {
    padding: 16px 20px 18px;
    border-top: 1px solid hsl(var(--border));
    flex-shrink: 0;
}
.ai-input-box {
    display: flex;
    flex-direction: column;
    gap: 0;
    background: hsl(var(--muted));
    border: 1.5px solid hsl(var(--border));
    border-radius: 16px;
    padding: 12px 14px 10px;
    transition: all 0.2s ease;
}
.ai-input-box:focus-within {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    background: hsl(var(--background));
}
.ai-input-box textarea {
    width: 100%;
    background: transparent;
    border: none;
    outline: none;
    resize: none;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    color: hsl(var(--foreground));
    line-height: 1.6;
    max-height: 160px;
    overflow-y: auto;
}
.ai-input-box textarea::placeholder {
    color: hsl(var(--muted-foreground));
}
.ai-input-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 10px;
}
.ai-char-hint {
    font-size: 11px;
    color: hsl(var(--muted-foreground));
    font-family: 'Inter', sans-serif;
}
.ai-send {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: none;
    background: hsl(var(--muted-foreground));
    color: hsl(var(--background));
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
    opacity: 0.35;
}
.ai-send--active {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    opacity: 1;
    box-shadow: 0 4px 14px rgba(99,102,241,0.4);
}
.ai-send--active:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 18px rgba(99,102,241,0.5);
}
.ai-send:disabled { cursor: not-allowed; }

.ai-disclaimer {
    text-align: center;
    font-size: 11px;
    color: hsl(var(--muted-foreground));
    margin: 8px 0 0;
    font-family: 'Inter', sans-serif;
    opacity: 0.7;
}

/* ── Transitions ── */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
