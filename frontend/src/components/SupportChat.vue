<script setup lang="ts">
import { ref, nextTick, onMounted } from 'vue'

interface Message {
  id: number
  from: 'user' | 'agent'
  text: string
  time: string
}

const STORAGE_KEY = 'nexus_support_chat'

const isOpen      = ref(false)
const unread      = ref(0)
const isTyping    = ref(false)
const inputText   = ref('')
const messagesEl  = ref<HTMLElement | null>(null)
const messages    = ref<Message[]>([])
let   msgId       = 1

function now() {
  return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

function push(from: 'user' | 'agent', text: string) {
  messages.value.push({ id: msgId++, from, text, time: now() })
  persist()
  scrollBottom()
}

function scrollBottom() {
  nextTick(() => {
    if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
  })
}

function persist() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(messages.value))
}

function open() {
  isOpen.value = true
  unread.value = 0
  scrollBottom()
}

function close() {
  isOpen.value = false
}

function toggle() {
  isOpen.value ? close() : open()
}

// ── Bot responses ──────────────────────────────────────────────────────────────
const BOT_RESPONSES: Array<{ keywords: string[]; replies: string[] }> = [
  {
    keywords: ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening'],
    replies: [
      'Hello! Welcome to NexusTrade Support. How can I assist you today?',
      'Hi there! Great to hear from you. What can I help you with?',
    ],
  },
  {
    keywords: ['deposit', 'fund', 'add money', 'top up', 'payment'],
    replies: [
      'You can deposit funds via your dashboard under Wallet → Deposit. We accept bank transfers, credit/debit cards, and major cryptocurrencies. Deposits are typically credited within minutes.',
      'To fund your account, go to Wallet → Deposit inside the platform. If you experience any issues, please share the transaction reference and we\'ll investigate promptly.',
    ],
  },
  {
    keywords: ['withdraw', 'withdrawal', 'cash out', 'payout', 'take out'],
    replies: [
      'Withdrawals are processed within 1–3 business days. Navigate to Wallet → Withdraw inside your dashboard. Please ensure your identity is verified to avoid delays.',
      'To withdraw, go to Wallet → Withdraw. Minimum withdrawal is $50. Funds are sent to your registered payment method.',
    ],
  },
  {
    keywords: ['invest', 'plan', 'plans', 'starter', 'professional', 'elite', 'roi', 'return'],
    replies: [
      'We offer three managed investment plans: Starter (11–15% monthly, from $500), Professional (17–25% monthly, from $5,000), and Elite (30–40% monthly, from $50,000). You can view them on the Plans page after logging in.',
      'Our investment plans are fully managed by our trading team. Returns are credited monthly directly to your wallet. Which plan would you like to know more about?',
    ],
  },
  {
    keywords: ['account', 'register', 'sign up', 'create', 'open'],
    replies: [
      'Opening an account is free and takes under 2 minutes. Click "Get Started" on the homepage or visit /register. You\'ll need a valid email and government-issued ID for KYC verification.',
      'To create your account, click "Get Started" at the top of the page. Once registered, complete the KYC process to unlock full trading and investment features.',
    ],
  },
  {
    keywords: ['login', 'sign in', 'password', 'forgot', 'reset', 'access'],
    replies: [
      'If you\'ve forgotten your password, click "Forgot Password" on the login page. A reset link will be emailed to your registered address within a few minutes.',
      'Having trouble logging in? Try resetting your password via the login page. If the issue persists, share your registered email and we\'ll assist you directly.',
    ],
  },
  {
    keywords: ['kyc', 'verify', 'verification', 'identity', 'document', 'id'],
    replies: [
      'KYC verification requires a government-issued photo ID (passport or national ID) and proof of address (utility bill or bank statement, dated within 3 months). Upload via your account settings.',
      'Our KYC process is typically completed within 24 hours. You\'ll receive an email notification once approved. Is there a specific verification issue you need help with?',
    ],
  },
  {
    keywords: ['signal', 'signals', 'mt5', 'trading signal', 'copy'],
    replies: [
      'NexusTrade provides institutional-grade MT5 trading signals from our expert analysts. Subscribe via the Signals section in your dashboard after logging in.',
      'Our signals include entry/exit points, stop-loss levels, and expected targets. They\'re updated in real-time and delivered directly to your MT5 platform.',
    ],
  },
  {
    keywords: ['profit', 'earnings', 'interest', 'yield', 'gain'],
    replies: [
      'Profits are credited to your wallet at the end of each investment cycle. You can view your earnings history under Wallet → Transactions in your dashboard.',
      'Your profit earnings depend on your chosen plan and market performance. Historical returns for our plans range from 11% to 40% per month. Past performance is not a guarantee of future results.',
    ],
  },
  {
    keywords: ['safe', 'secure', 'regulated', 'licensed', 'trust', 'legit', 'scam'],
    replies: [
      'NexusTrade is a licensed and regulated investment platform. Client funds are held in segregated accounts and the platform uses 256-bit SSL encryption. Your security is our top priority.',
      'We are fully regulated and compliant with financial regulations. Our platform uses bank-level security, 2FA, and regular third-party audits. Rest assured your investment is in safe hands.',
    ],
  },
  {
    keywords: ['fee', 'fees', 'charge', 'cost', 'commission'],
    replies: [
      'There are no hidden fees. Deposits and withdrawals may incur standard network/processing fees depending on the method. Our investment plans have no management fees — returns shown are net.',
      'NexusTrade operates on a performance-based model with no entry or management fees for standard plans. Contact us for Enterprise pricing.',
    ],
  },
  {
    keywords: ['contact', 'email', 'phone', 'speak', 'human', 'agent', 'person', 'staff', 'team'],
    replies: [
      'You\'re chatting with our AI support assistant. For complex issues, our human support team is available Monday–Friday 9am–6pm UTC via email at support@nexustrade.com.',
      'I\'m an automated assistant available 24/7. To reach a human agent, email support@nexustrade.com or use the in-app ticket system after logging in.',
    ],
  },
  {
    keywords: ['thank', 'thanks', 'bye', 'goodbye', 'great', 'awesome', 'perfect'],
    replies: [
      'You\'re welcome! Is there anything else I can help you with?',
      'Happy to help! Feel free to reach out anytime. Have a great trading session!',
    ],
  },
]

const FALLBACK = [
  'I\'m not sure I fully understand your question. Could you provide more detail? Alternatively, email us at support@nexustrade.com.',
  'That\'s a great question — let me connect you with the right information. Could you elaborate a bit more?',
  'I want to make sure I give you the right answer. Can you rephrase or provide more context?',
]

function getBotReply(input: string): string {
  const lower = input.toLowerCase()
  for (const group of BOT_RESPONSES) {
    if (group.keywords.some((k) => lower.includes(k))) {
      return group.replies[Math.floor(Math.random() * group.replies.length)] ?? ''
    }
  }
  return FALLBACK[Math.floor(Math.random() * FALLBACK.length)] ?? ''
}

function sendMessage() {
  const text = inputText.value.trim()
  if (!text) return
  inputText.value = ''
  push('user', text)

  isTyping.value = true
  const delay = 800 + Math.random() * 800
  setTimeout(() => {
    isTyping.value = false
    push('agent', getBotReply(text))
    if (!isOpen.value) unread.value++
  }, delay)
}

function handleKey(e: KeyboardEvent) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    sendMessage()
  }
}

function clearChat() {
  messages.value = []
  localStorage.removeItem(STORAGE_KEY)
  setTimeout(() => {
    push('agent', 'Hello! Welcome to NexusTrade Support. How can I help you today? You can ask about deposits, withdrawals, investment plans, account setup, or any other queries.')
  }, 300)
}

onMounted(() => {
  const saved = localStorage.getItem(STORAGE_KEY)
  if (saved) {
    try {
      const parsed = JSON.parse(saved) as Message[]
      messages.value = parsed
      msgId = parsed.length ? Math.max(...parsed.map((m) => m.id)) + 1 : 1
    } catch {
      // ignore
    }
  }
  if (!messages.value.length) {
    setTimeout(() => {
      push('agent', 'Hello! Welcome to NexusTrade Support 👋. How can I help you today? You can ask about deposits, withdrawals, investment plans, account setup, or any other queries.')
    }, 600)
  }
})
</script>

<template>
  <!-- ── Floating Button ─────────────────────────────────────────────────── -->
  <button
    class="sc-fab"
    :class="{ 'sc-fab--open': isOpen }"
    aria-label="Open support chat"
    @click="toggle"
  >
    <span v-if="!isOpen" class="sc-fab__icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
    </span>
    <span v-else class="sc-fab__icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
      </svg>
    </span>
    <span v-if="unread > 0 && !isOpen" class="sc-fab__badge">{{ unread }}</span>
  </button>

  <!-- ── Chat Panel ──────────────────────────────────────────────────────── -->
  <Transition name="sc-slide">
    <div v-if="isOpen" class="sc-panel" role="dialog" aria-label="Customer support chat">

      <!-- Header -->
      <div class="sc-panel__header">
        <div class="sc-panel__agent">
          <div class="sc-panel__avatar">NT</div>
          <div>
            <p class="sc-panel__agent-name">NexusTrade Support</p>
            <p class="sc-panel__agent-status">
              <span class="sc-panel__status-dot" />
              Online · Typically replies instantly
            </p>
          </div>
        </div>
        <div class="sc-panel__header-actions">
          <button class="sc-panel__icon-btn" title="Clear conversation" @click="clearChat">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.1"/></svg>
          </button>
          <button class="sc-panel__icon-btn" aria-label="Close chat" @click="close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          </button>
        </div>
      </div>

      <!-- Messages -->
      <div ref="messagesEl" class="sc-panel__messages">
        <TransitionGroup name="sc-msg">
          <div
            v-for="msg in messages"
            :key="msg.id"
            class="sc-msg"
            :class="msg.from === 'user' ? 'sc-msg--user' : 'sc-msg--agent'"
          >
            <div v-if="msg.from === 'agent'" class="sc-msg__avatar">NT</div>
            <div class="sc-msg__body">
              <p class="sc-msg__text">{{ msg.text }}</p>
              <span class="sc-msg__time">{{ msg.time }}</span>
            </div>
          </div>
        </TransitionGroup>

        <!-- Typing indicator -->
        <Transition name="sc-msg">
          <div v-if="isTyping" class="sc-msg sc-msg--agent sc-msg--typing">
            <div class="sc-msg__avatar">NT</div>
            <div class="sc-msg__body">
              <span class="sc-typing">
                <span /><span /><span />
              </span>
            </div>
          </div>
        </Transition>
      </div>

      <!-- Input -->
      <div class="sc-panel__input-row">
        <textarea
          v-model="inputText"
          class="sc-panel__input"
          placeholder="Type your message…"
          rows="1"
          @keydown="handleKey"
        />
        <button
          class="sc-panel__send"
          :disabled="!inputText.trim()"
          aria-label="Send message"
          @click="sendMessage"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
      </div>

    </div>
  </Transition>
</template>

<style lang="scss" scoped>
// ── FAB ───────────────────────────────────────────────────────────────────────
.sc-fab {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 9999;
  width: 3.25rem;
  height: 3.25rem;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-primary), #00b894);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 4px 20px rgba(0, 212, 170, 0.45);
  transition: transform 0.2s ease, box-shadow 0.2s ease;

  &:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 28px rgba(0, 212, 170, 0.6);
  }

  &--open {
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.15);
    box-shadow: 0 4px 16px rgba(0,0,0,0.3);
  }

  &__icon { display: flex; align-items: center; }

  &__badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 1.1rem;
    height: 1.1rem;
    border-radius: 999px;
    background: #FF4D6D;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
    border: 2px solid var(--color-bg);
  }
}

// ── Panel ─────────────────────────────────────────────────────────────────────
.sc-panel {
  position: fixed;
  bottom: 5.5rem;
  right: 1.5rem;
  z-index: 9998;
  width: min(380px, calc(100vw - 2rem));
  height: min(520px, calc(100vh - 8rem));
  display: flex;
  flex-direction: column;
  border-radius: 1rem;
  overflow: hidden;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255,255,255,0.04);

  // Header
  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1rem;
    background: linear-gradient(135deg, rgba(0,212,170,.12), rgba(108,99,255,.08));
    border-bottom: 1px solid var(--color-border);
    flex-shrink: 0;
  }

  &__agent { display: flex; align-items: center; gap: 0.75rem; }

  &__avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: 0.02em;
    flex-shrink: 0;
  }

  &__agent-name {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 2px;
  }

  &__agent-status {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.7rem;
    color: var(--color-text-muted);
    margin: 0;
  }

  &__status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--color-success);
    animation: statusPulse 2s ease-in-out infinite;
    flex-shrink: 0;
  }

  &__header-actions { display: flex; gap: 0.25rem; }

  &__icon-btn {
    width: 2rem;
    height: 2rem;
    border-radius: var(--radius-md);
    border: none;
    background: transparent;
    color: var(--color-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s, color 0.15s;

    &:hover {
      background: rgba(255,255,255,0.08);
      color: var(--color-text);
    }
  }

  // Messages area
  &__messages {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    scroll-behavior: smooth;

    &::-webkit-scrollbar { width: 4px; }
    &::-webkit-scrollbar-track { background: transparent; }
    &::-webkit-scrollbar-thumb {
      background: rgba(255,255,255,0.1);
      border-radius: 99px;
    }
  }

  // Input row
  &__input-row {
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--color-border);
    background: var(--color-surface);
    flex-shrink: 0;
  }

  &__input {
    flex: 1;
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--color-border);
    border-radius: 0.625rem;
    padding: 0.625rem 0.875rem;
    color: var(--color-text);
    font-size: 0.875rem;
    font-family: inherit;
    resize: none;
    line-height: 1.5;
    max-height: 100px;
    overflow-y: auto;
    transition: border-color 0.15s;

    &:focus {
      outline: none;
      border-color: rgba(0,212,170,.5);
    }

    &::placeholder { color: var(--color-text-muted); }

    [data-theme="light"] & {
      background: var(--color-surface-2);
    }
  }

  &__send {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-primary), #00b894);
    border: none;
    cursor: pointer;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: opacity 0.15s, transform 0.15s;

    &:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }

    &:not(:disabled):hover { transform: scale(1.08); }
  }
}

// ── Messages ──────────────────────────────────────────────────────────────────
.sc-msg {
  display: flex;
  gap: 0.5rem;
  align-items: flex-end;
  max-width: 88%;

  &--user {
    flex-direction: row-reverse;
    align-self: flex-end;

    .sc-msg__text {
      background: linear-gradient(135deg, var(--color-primary), #00b894);
      color: #fff;
      border-radius: 1rem 1rem 0.25rem 1rem;
    }

    .sc-msg__time { text-align: right; }
  }

  &--agent {
    align-self: flex-start;

    .sc-msg__text {
      background: rgba(255,255,255,0.06);
      color: var(--color-text);
      border-radius: 1rem 1rem 1rem 0.25rem;

      [data-theme="light"] & {
        background: var(--color-surface-2);
        border: 1px solid var(--color-border);
      }
    }
  }

  &__avatar {
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.55rem;
    font-weight: 800;
    color: #fff;
    flex-shrink: 0;
  }

  &__body { display: flex; flex-direction: column; gap: 2px; }

  &__text {
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
    line-height: 1.55;
    margin: 0;
    word-break: break-word;
  }

  &__time {
    font-size: 0.65rem;
    color: var(--color-text-muted);
    opacity: 0.7;
    padding: 0 0.25rem;
  }
}

// ── Typing dots ───────────────────────────────────────────────────────────────
.sc-typing {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 0.5rem 0.75rem;
  background: rgba(255,255,255,0.06);
  border-radius: 1rem 1rem 1rem 0.25rem;

  span {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--color-primary);
    animation: typingBounce 1.2s ease-in-out infinite;

    &:nth-child(2) { animation-delay: 0.2s; }
    &:nth-child(3) { animation-delay: 0.4s; }
  }
}

// ── Transitions ───────────────────────────────────────────────────────────────
.sc-slide-enter-active { animation: scSlideIn 0.28s cubic-bezier(0.34, 1.56, 0.64, 1); }
.sc-slide-leave-active { animation: scSlideIn 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) reverse; }

.sc-msg-enter-active { transition: all 0.25s ease; }
.sc-msg-enter-from   { opacity: 0; transform: translateY(8px); }

// ── Keyframes ─────────────────────────────────────────────────────────────────
@keyframes scSlideIn {
  from { opacity: 0; transform: translateY(20px) scale(0.95); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes typingBounce {
  0%, 60%, 100% { transform: translateY(0); }
  30%           { transform: translateY(-5px); }
}

@keyframes statusPulse {
  0%, 100% { opacity: 1; }
  50%      { opacity: 0.4; }
}
</style>
