<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Bot,
    Check,
    Code2,
    Copy,
    GraduationCap,
    Lightbulb,
    Mail,
    Send,
    Sparkles,
    ThumbsDown,
    ThumbsUp,
} from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import { useChat } from '@/composables/useChats';
import { chat } from '@/routes';
import type { DashboardInvitation } from '@/types';

defineProps<{
    pendingInvitations?: DashboardInvitation[];
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: chat().url,
            },
        ],
    }),
});

// ─── Chat State (from composable) ─────────────────────────────────
const { messages, sending, sendMessage } = useChat({
    sendEndpoint: '/ai/chat',
});

const inputText = ref('');
const messagesEl = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLTextAreaElement | null>(null);
const copiedMsgId = ref<number | null>(null);

const hasStarted = computed(() => messages.value.length > 0);

const suggestions = [
    { icon: Mail, title: 'Draft an email', desc: 'Draft a professional update for your team.' },
    { icon: Code2, title: 'Debug some code', desc: 'Find errors or debug a block of code.' },
    { icon: GraduationCap, title: 'Explain a concept', desc: 'Learn about a complex topic in simple terms.' },
    { icon: Lightbulb, title: 'Brainstorm ideas', desc: 'Generate unique ideas for your next project.' },
];

function scrollToBottom() {
    nextTick(() => {
        if (messagesEl.value) {
            messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
        }
    });
}

function selectSuggestion(title: string) {
    inputText.value = title;
    handleSend();
}

async function handleSend() {
    const text = inputText.value.trim();

    if (!text || sending.value) {
        return;
    }

    inputText.value = '';
    autoResize();
    scrollToBottom();

    await sendMessage(text);
    scrollToBottom();
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend();
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

function showCopiedFeedback(id?: number) {
    if (id !== undefined) {
        copiedMsgId.value = id;
        setTimeout(() => {
            if (copiedMsgId.value === id) {
                copiedMsgId.value = null;
            }
        }, 2000);
    }
}

function fallbackCopy(text: string, id?: number) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        document.execCommand('copy');
        showCopiedFeedback(id);
    } catch (err) {
        console.error('Copy failed', err);
    }

    textArea.remove();
}

function copyToClipboard(text: string, id?: number) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCopiedFeedback(id);
        }).catch(() => {
            fallbackCopy(text, id);
        });
    } else {
        fallbackCopy(text, id);
    }
}

const canSend = computed(() => inputText.value.trim().length > 0 && !sending.value);
</script>

<template>

    <Head title="Dashboard" />

    <PendingInvitationsModal v-if="pendingInvitations && pendingInvitations.length > 0"
        :invitations="pendingInvitations" />

    <!-- Full height chat area - holds ambient glows -->
    <div class="relative flex h-[calc(100svh-4rem)] flex-col overflow-hidden bg-background">

        <!-- Ambient Atmospheric Glows -->
        <div class="ambient-glow-1"></div>
        <div class="ambient-glow-2"></div>
        <!-- ── Body ── -->
        <div ref="messagesEl" class="relative z-10 flex-1 overflow-y-auto scroll-smooth pb-[120px]">

            <!-- Welcome Screen -->
            <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
                <div v-if="!hasStarted"
                    class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-6">
                    <div class="mx-auto flex max-w-3xl flex-col items-center justify-center py-16 text-center">
                        
                        <div
                            class="mb-6 flex size-20 items-center justify-center rounded-full bg-surface-container border border-primary/20 shadow-[0_0_30px_rgba(99,102,241,0.15)] relative animate-pulse-glow">
                            <div
                                class="absolute inset-0 rounded-full bg-gradient-to-tr from-primary/10 to-secondary/10 blur-md">
                            </div>
                            <Bot class="size-10 text-primary relative z-10" />
                        </div>

                        <h1 class="text-3xl font-bold tracking-tight text-foreground">How can I help you today?</h1>
                        <p class="mt-2 max-w-md text-sm text-muted-foreground leading-relaxed">
                            Interact with Nexus Intelligence. Ask questions, analyze data, or generate creative formats.
                        </p>

                    </div>
                </div>
            </Transition>

            <!-- Messages -->
            <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0">
                <div v-if="hasStarted" class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8 md:px-6">
                    <div v-for="msg in messages" :key="msg.id" :class="['flex gap-3 animate-in fade-in slide-in-from-bottom-1 duration-200',
                        msg.role === 'user' ? 'justify-end' : 'items-start']">
                        <!-- Bot Message -->
                        <template v-if="msg.role === 'assistant'">
                            <div
                                class="mt-1 flex size-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-container to-secondary-container shadow-[0_0_10px_rgba(192,193,255,0.2)]">
                                <Sparkles class="size-4 text-primary-foreground" />
                            </div>
                            <div class="flex max-w-[90%] md:max-w-[75%] flex-col gap-2">
                                <span class="text-[11px] font-semibold text-muted-foreground ml-1">AI Assistant</span>
                                <div class="glass-panel px-5 py-3 rounded-2xl rounded-tl-sm shadow-sm relative">
                                    <Transition name="text-fade" mode="out-in">
                                        <div :key="msg.content.length" class="text-sm leading-relaxed text-foreground"
                                            v-html="msg.content.replace(/\n/g, '<br>')" />
                                    </Transition>

                                    <!-- Action Chips -->
                                    <div class="flex flex-wrap gap-2 mt-4 pt-2 border-t border-border/30">
                                        <button
                                            class="flex items-center gap-1 px-3 py-1 rounded-full border border-border bg-muted/40 text-xs text-muted-foreground hover:bg-muted hover:text-foreground transition-all duration-200"
                                            @click="copyToClipboard(msg.content, msg.id)">
                                            <Check v-if="copiedMsgId === msg.id"
                                                class="size-3 text-emerald-500 animate-in zoom-in-50 duration-150" />
                                            <Copy v-else class="size-3" />
                                            <span :class="copiedMsgId === msg.id ? 'text-emerald-500 font-medium' : ''">
                                                {{ copiedMsgId === msg.id ? 'Copied!' : 'Copy' }}
                                            </span>
                                        </button>
                                        <div class="flex-1"></div>
                                        <div class="flex items-center gap-1.5">
                                            <button
                                                class="p-1 rounded hover:bg-muted text-muted-foreground hover:text-foreground transition-colors">
                                                <ThumbsUp class="size-3.5" />
                                            </button>
                                            <button
                                                class="p-1 rounded hover:bg-muted text-muted-foreground hover:text-foreground transition-colors">
                                                <ThumbsDown class="size-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-[10px] text-muted-foreground ml-1">{{ msg.time }}</span>
                            </div>
                        </template>
                        <!-- User Message -->
                        <template v-else>
                            <div class="flex max-w-[85%] md:max-w-[70%] flex-col items-end gap-1">
                                <div
                                    class="relative overflow-hidden rounded-2xl rounded-tr-sm bg-surface-container-high/80 px-5 py-3 text-sm leading-relaxed text-foreground border border-border/40 shadow-sm">
                                    <!-- Subtle dark indigo tint for user bubble -->
                                    <div class="absolute inset-0 bg-primary/5 pointer-events-none"></div>
                                    <div class="relative z-10" v-html="msg.content.replace(/\n/g, '<br>')" />
                                </div>
                                <span class="text-[10px] text-muted-foreground mr-1">{{ msg.time }}</span>
                            </div>
                        </template>
                    </div>

                    <!-- Typing indicator -->
                    <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                        leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
                        <div v-if="sending" class="flex items-start gap-3">
                            <!-- Glowing perimeter for thinking state -->
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-surface-container border border-primary/20 shadow-[0_0_10px_rgba(99,102,241,0.15)] relative">
                                <div
                                    class="absolute -inset-[1px] rounded-full bg-gradient-to-r from-primary to-secondary opacity-50 blur-[2px] animate-pulse">
                                </div>
                                <Sparkles class="size-4 text-primary relative z-10 animate-pulse" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[11px] font-semibold text-muted-foreground ml-1">AI Assistant</span>
                                <div
                                    class="glass-panel px-4 py-3 rounded-2xl rounded-tl-sm flex items-center gap-1.5 h-10">
                                    <span
                                        class="size-2 animate-bounce rounded-full bg-primary/60 [animation-delay:0ms]" />
                                    <span
                                        class="size-2 animate-bounce rounded-full bg-primary/60 [animation-delay:150ms]" />
                                    <span
                                        class="size-2 animate-bounce rounded-full bg-primary/60 [animation-delay:300ms]" />
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>  
            </Transition>
        </div>

        <!-- ── Input Area (Floating at bottom) ── -->
        <div
            class="absolute bottom-0 left-0 w-full p-4 md:p-6 bg-gradient-to-t from-background via-background/90 to-transparent pointer-events-none z-20 flex justify-center">
            <div
                class="w-full max-w-[800px] pointer-events-auto relative group rounded-2xl shadow-xl transition-all duration-300">
                <!-- Glowing Focus Border Element -->

                <div
                    class="glass-input relative z-10 rounded-2xl flex flex-col focus-within:border-primary/50 focus-within:shadow-[inset_0_0_15px_rgba(192,193,255,0.05)] transition-all duration-300 ease-spring">
                    <textarea ref="inputRef" v-model="inputText" placeholder="Message Nexus AI..." rows="1"
                        maxlength="1000"
                        class="w-full resize-none bg-transparent px-5 pt-4 pb-2 text-sm text-foreground placeholder:text-muted-foreground outline-none max-h-40 overflow-y-auto"
                        @keydown="handleKeydown" @input="autoResize" />
                    <div class="flex items-center justify-between px-4 pb-3">
                        <span class="text-[11px] text-muted-foreground">
                            {{ inputText.length > 0 ? `${inputText.length}/1000` : 'Shift+Enter — new line' }}
                        </span>

                        <button :disabled="!canSend"
                            class="w-9 h-9 rounded-full bg-muted flex items-center justify-center text-primary transition-all duration-300 ease-spring hover:bg-primary hover:text-primary-foreground hover:scale-110 active:scale-95 active:translate-y-[2px] disabled:opacity-50 disabled:hover:scale-100 disabled:hover:bg-muted disabled:hover:text-primary disabled:cursor-not-allowed group-focus-within:bg-primary group-focus-within:text-primary-foreground"
                            @click="handleSend">
                            <Send
                                class="size-4 transition-transform duration-300 group-hover:translate-x-[1px] group-hover:-translate-y-[1px]" />
                        </button>
                    </div>
                </div>
                <p class="mt-2 text-center text-[10px] text-muted-foreground/60">
                    Nexus AI can make mistakes. Consider verifying important information.
                </p>
            </div>
        </div>

    </div>
</template>

<style scoped>
/* Atmospheric Ambient Glows */
.text-fade-enter-active {
  transition: opacity 0.3s ease;
}
.text-fade-enter-from {
  opacity: 0;
}
.ambient-glow-1 {
    position: absolute;
    top: 10%;
    left: 20%;
    width: 40vw;
    height: 40vw;
    background: radial-gradient(circle, rgba(168, 85, 247, 0.12) 0%, rgba(0, 0, 0, 0) 70%);
    border-radius: 50%;
    z-index: 0;
    pointer-events: none;
    filter: blur(60px);
}

.ambient-glow-2 {
    position: absolute;
    bottom: -10%;
    right: 10%;
    width: 35vw;
    height: 35vw;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.08) 0%, rgba(0, 0, 0, 0) 70%);
    border-radius: 50%;
    z-index: 0;
    pointer-events: none;
    filter: blur(50px);
}

/* Light mode: softer ambient glows */
:root:not(.dark) .ambient-glow-1 {
    background: radial-gradient(circle, rgba(168, 85, 247, 0.06) 0%, transparent 70%);
}

:root:not(.dark) .ambient-glow-2 {
    background: radial-gradient(circle, rgba(99, 102, 241, 0.04) 0%, transparent 70%);
}

/* Glassmorphism Classes */
.glass-panel {
    background-color: color-mix(in srgb, var(--background) 60%, transparent);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--border);
}

.glass-input {
    background-color: color-mix(in srgb, var(--muted) 60%, transparent);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--border);
}

/* Light mode overrides */
:root:not(.dark) .glass-panel {
    background-color: color-mix(in srgb, var(--card) 80%, transparent);
    border-color: var(--border);
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}

:root:not(.dark) .glass-input {
    background-color: color-mix(in srgb, var(--background) 90%, transparent);
    border-color: var(--border);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.ease-spring {
    transition-timing-function: cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Keyframe animations */
@keyframes pulseGlow {

    0%,
    100% {
        opacity: 0.5;
        transform: scale(1);
    }

    50% {
        opacity: 1;
        transform: scale(1.04);
        filter: drop-shadow(0 0 15px rgba(99, 102, 241, 0.35));
    }
}

.animate-pulse-glow {
    animation: pulseGlow 3s ease-in-out infinite;
}
</style>
