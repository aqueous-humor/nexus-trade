<script setup lang="ts">
import NexusLogo from '@/components/NexusLogo.vue'
import AppIcon from '@/components/AppIcon.vue'

const features = [
  { icon: 'bar-chart',  title: 'Institutional-Grade Analytics', desc: 'Real-time portfolio tracking and performance metrics.' },
  { icon: 'zap',        title: 'AI-Powered Signals',            desc: 'Algorithmic signals backed by quantitative analysis.' },
  { icon: 'lock',       title: 'Bank-Level Security',           desc: 'Segregated accounts with end-to-end encryption.' },
  { icon: 'globe',      title: 'Global Markets',                desc: 'Access Forex, commodities, and indices worldwide.' },
]
</script>

<template>
  <div class="guest-layout">
    <!-- ── Left branding panel ────────────────────────────────────────── -->
    <aside class="guest-layout__brand-panel" aria-hidden="true">
      <!-- Background mesh / glow -->
      <div class="brand-panel__glow brand-panel__glow--tl" />
      <div class="brand-panel__glow brand-panel__glow--br" />

      <div class="brand-panel__content">
        <NexusLogo :width="160" />

        <div class="brand-panel__hero">
          <h1 class="brand-panel__headline">
            Trade Smarter.<br />
            <span class="brand-panel__headline--accent">Grow Faster.</span>
          </h1>
          <p class="brand-panel__sub">
            Institutional-grade forex &amp; investment platform built for
            serious investors. Start growing your wealth today.
          </p>
        </div>

        <ul class="brand-panel__features" role="list">
          <li
            v-for="f in features"
            :key="f.icon"
            class="brand-panel__feature"
          >
            <span class="brand-panel__feature-icon">
              <AppIcon :name="f.icon" :size="16" />
            </span>
            <div>
              <strong class="brand-panel__feature-title">{{ f.title }}</strong>
              <span class="brand-panel__feature-desc">{{ f.desc }}</span>
            </div>
          </li>
        </ul>

        <div class="brand-panel__stats">
          <div class="brand-panel__stat">
            <span class="brand-panel__stat-value">$2.4B+</span>
            <span class="brand-panel__stat-label">Assets Managed</span>
          </div>
          <div class="brand-panel__stat-divider" />
          <div class="brand-panel__stat">
            <span class="brand-panel__stat-value">48K+</span>
            <span class="brand-panel__stat-label">Active Investors</span>
          </div>
          <div class="brand-panel__stat-divider" />
          <div class="brand-panel__stat">
            <span class="brand-panel__stat-value">99.9%</span>
            <span class="brand-panel__stat-label">Uptime</span>
          </div>
        </div>
      </div>
    </aside>

    <!-- ── Right form panel ───────────────────────────────────────────── -->
    <main class="guest-layout__form-panel">
      <!-- Mobile-only logo -->
      <div class="guest-layout__mobile-brand">
        <NexusLogo :width="140" />
      </div>
      <div class="guest-layout__card">
        <slot />
      </div>
    </main>
  </div>
</template>

<style lang="scss" scoped>
// ── Layout shell ──────────────────────────────────────────────────────────────
.guest-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 100vh;

  @media (max-width: 900px) {
    grid-template-columns: 1fr;
  }
}

// ── Left panel ────────────────────────────────────────────────────────────────
.guest-layout__brand-panel {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #080C14;
  overflow: hidden;
  padding: var(--space-10) var(--space-8);

  @media (max-width: 900px) {
    display: none;
  }
}

.brand-panel__glow {
  position: absolute;
  border-radius: var(--radius-full);
  pointer-events: none;

  &--tl {
    top: -120px;
    left: -120px;
    width: 420px;
    height: 420px;
    background: radial-gradient(circle, rgba(0, 212, 170, 0.18) 0%, transparent 70%);
  }

  &--br {
    bottom: -80px;
    right: -80px;
    width: 320px;
    height: 320px;
    background: radial-gradient(circle, rgba(108, 99, 255, 0.15) 0%, transparent 70%);
  }
}

.brand-panel__content {
  position: relative;
  z-index: 1;
  max-width: 440px;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: var(--space-8);
  color: #E8F0FE;
}

.brand-panel__hero {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.brand-panel__headline {
  font-size: clamp(1.75rem, 3vw, 2.5rem);
  font-weight: 800;
  line-height: 1.15;
  color: #E8F0FE;
  margin: 0;
  letter-spacing: -0.03em;

  &--accent {
    background: linear-gradient(90deg, #00D4AA, #6C63FF);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
}

.brand-panel__sub {
  font-size: var(--text-base);
  line-height: 1.7;
  color: #7B93B8;
  margin: 0;
}

// Feature list
.brand-panel__features {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  list-style: none;
  padding: 0;
  margin: 0;
}

.brand-panel__feature {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
}

.brand-panel__feature-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 2rem;
  height: 2rem;
  border-radius: var(--radius-md);
  background: rgba(0, 212, 170, 0.12);
  color: #00D4AA;
  margin-top: 1px;
}

.brand-panel__feature-title {
  display: block;
  font-size: var(--text-sm);
  font-weight: 600;
  color: #E8F0FE;
  margin-bottom: 2px;
}

.brand-panel__feature-desc {
  display: block;
  font-size: var(--text-xs);
  color: #4A6080;
  line-height: 1.5;
}

// Stats bar
.brand-panel__stats {
  display: flex;
  align-items: center;
  gap: var(--space-5);
  padding: var(--space-5);
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: var(--radius-xl);
}

.brand-panel__stat {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
  text-align: center;
}

.brand-panel__stat-value {
  font-size: var(--text-xl);
  font-weight: 700;
  color: #00D4AA;
  letter-spacing: -0.02em;
}

.brand-panel__stat-label {
  font-size: var(--text-xs);
  color: #4A6080;
}

.brand-panel__stat-divider {
  width: 1px;
  height: 2.5rem;
  background: rgba(255, 255, 255, 0.07);
}

// ── Right form panel ──────────────────────────────────────────────────────────
.guest-layout__form-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--color-bg);
  padding: var(--space-8) var(--space-6);
  min-height: 100vh;

  @media (max-width: 900px) {
    min-height: 100vh;
  }
}

.guest-layout__mobile-brand {
  display: none;
  margin-bottom: var(--space-8);

  @media (max-width: 900px) {
    display: flex;
    justify-content: center;
  }
}

.guest-layout__card {
  width: 100%;
  max-width: 420px;
  background: var(--color-surface);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-lg);
  padding: var(--space-8);
  border: 1px solid var(--color-border);
}
</style>
