<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import NexusLogo from '@/components/NexusLogo.vue'
import AppIcon from '@/components/AppIcon.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
import { useScrollReveal, useCounterAnimation } from '@/composables/useScrollReveal'

useScrollReveal()
useCounterAnimation()

const scrolled = ref(false)
const mobileMenuOpen = ref(false)

function onScroll() {
  scrolled.value = window.scrollY > 40
}

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))

const tickers = [
  { pair: 'EUR/USD', price: '1.0872', change: '+0.18%', up: true },
  { pair: 'GBP/USD', price: '1.2641', change: '+0.31%', up: true },
  { pair: 'USD/JPY', price: '154.82', change: '-0.09%', up: false },
  { pair: 'XAU/USD', price: '2,341.40', change: '+0.54%', up: true },
  { pair: 'BTC/USD', price: '62,104', change: '-1.22%', up: false },
  { pair: 'USD/CHF', price: '0.9048', change: '+0.07%', up: true },
  { pair: 'AUD/USD', price: '0.6519', change: '-0.14%', up: false },
  { pair: 'USD/CAD', price: '1.3648', change: '+0.22%', up: true },
]

const features = [
  {
    icon: 'bar-chart',
    title: 'Advanced Analytics',
    desc: 'Real-time charts, technical indicators, and portfolio analytics built for professional traders.',
  },
  {
    icon: 'zap',
    title: 'AI-Powered Signals',
    desc: 'Algorithmic trading signals generated from multi-factor quantitative models and machine learning.',
  },
  {
    icon: 'lock',
    title: 'Bank-Level Security',
    desc: 'Segregated client funds, 2FA, end-to-end encryption, and 24/7 fraud monitoring.',
  },
  {
    icon: 'globe',
    title: 'Global Markets',
    desc: 'Trade Forex, commodities, indices, and digital assets across 50+ instruments worldwide.',
  },
  {
    icon: 'wallet',
    title: 'Instant Deposits',
    desc: 'Fund your account instantly with crypto, bank transfer, or card — withdraw in under 24 hours.',
  },
  {
    icon: 'trending-up',
    title: 'Proven Returns',
    desc: 'Professionally managed investment plans with transparent historical performance records.',
  },
]

const plans = [
  {
    name: 'Starter',
    roi: '8–12%',
    period: 'monthly',
    min: '$500',
    max: '$4,999',
    highlight: false,
    features: ['Forex & Commodities', 'Weekly reports', 'Email support', 'Manual trading signals'],
  },
  {
    name: 'Professional',
    roi: '15–22%',
    period: 'monthly',
    min: '$5,000',
    max: '$49,999',
    highlight: true,
    features: ['All markets access', 'Real-time analytics', 'Priority support', 'AI signals', 'Daily reports'],
  },
  {
    name: 'Elite',
    roi: '25–40%',
    period: 'monthly',
    min: '$50,000',
    max: 'Unlimited',
    highlight: false,
    features: ['Dedicated account manager', 'Custom strategies', '24/7 VIP support', 'All AI features', 'Tax reporting'],
  },
]

const stats = [
  { counter: '2.4',  suffix: 'B+', prefix: '$', decimals: '1', label: 'Assets Under Management' },
  { counter: '48',   suffix: 'K+', prefix: '',  decimals: '0', label: 'Active Investors'         },
  { counter: '99.9', suffix: '%',  prefix: '',  decimals: '1', label: 'Platform Uptime'           },
  { counter: '50',   suffix: '+',  prefix: '',  decimals: '0', label: 'Tradeable Instruments'     },
]

const completions = [
  { initials: 'JD', name: 'James D.', plan: 'Professional', amount: '$12,500', profit: '+$2,187', pct: '+17.5%', days: 30 },
  { initials: 'SR', name: 'Sofia R.', plan: 'Elite',        amount: '$80,000', profit: '+$28,000', pct: '+35%', days: 30 },
  { initials: 'MK', name: 'Marcus K.',plan: 'Starter',      amount: '$2,000',  profit: '+$220',  pct: '+11%', days: 30 },
  { initials: 'AL', name: 'Anya L.', plan: 'Professional', amount: '$25,000', profit: '+$5,250', pct: '+21%', days: 30 },
  { initials: 'TO', name: 'Tom O.',  plan: 'Elite',        amount: '$100,000',profit: '+$38,000', pct: '+38%', days: 30 },
  { initials: 'RC', name: 'Rachel C.',plan: 'Starter',     amount: '$3,500',  profit: '+$385',  pct: '+11%', days: 30 },
]

const steps = [
  { n: '01', icon: 'user',         title: 'Create Account',  desc: 'Complete our secure KYC verification in minutes. 2FA enabled by default.',    points: ['Quick KYC', 'Secure login', '2FA enabled'] },
  { n: '02', icon: 'plans',        title: 'Select Plan',     desc: 'Choose from Starter, Professional, or Elite plans tailored to your goals.', points: ['Multiple options', 'Flexible terms', 'Clear returns'] },
  { n: '03', icon: 'wallet',       title: 'Fund Account',    desc: 'Deposit via crypto, bank transfer, or card. Funds credited instantly.',     points: ['Instant deposits', 'Multiple methods', 'No hidden fees'] },
  { n: '04', icon: 'trending-up',  title: 'Earn Returns',    desc: 'Track performance in real-time, with auto-compounding and easy withdrawals.', points: ['Real-time tracking', 'Auto compounding', 'Easy withdrawals'] },
]

const perfMonths = [
  { month: 'Jan', pct: 18 },
  { month: 'Feb', pct: 22 },
  { month: 'Mar', pct: 16 },
  { month: 'Apr', pct: 28 },
  { month: 'May', pct: 24 },
  { month: 'Jun', pct: 31 },
  { month: 'Jul', pct: 26 },
  { month: 'Aug', pct: 35 },
  { month: 'Sep', pct: 29 },
  { month: 'Oct', pct: 38 },
  { month: 'Nov', pct: 32 },
  { month: 'Dec', pct: 40 },
]
</script>

<template>
  <div class="home">

    <!-- ── Sticky Navbar ──────────────────────────────────────────────────── -->
    <header class="home-nav" :class="{ 'home-nav--scrolled': scrolled }">
      <div class="home-nav__inner">
        <RouterLink to="/" class="home-nav__brand">
          <NexusLogo :width="148" />
        </RouterLink>

        <nav class="home-nav__links" :class="{ 'is-open': mobileMenuOpen }">
          <a href="#features" class="home-nav__link" @click="mobileMenuOpen = false">Features</a>
          <a href="#plans"    class="home-nav__link" @click="mobileMenuOpen = false">Plans</a>
          <a href="#markets"  class="home-nav__link" @click="mobileMenuOpen = false">Markets</a>
        </nav>

        <div class="home-nav__actions">
          <ThemeToggle />
          <RouterLink to="/login"    class="home-nav__btn home-nav__btn--ghost">Sign In</RouterLink>
          <RouterLink to="/register" class="home-nav__btn home-nav__btn--primary">Get Started</RouterLink>
        </div>

        <button
          class="home-nav__hamburger"
          :aria-expanded="mobileMenuOpen"
          aria-label="Toggle menu"
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <AppIcon :name="mobileMenuOpen ? 'close' : 'menu'" :size="22" />
        </button>
      </div>
    </header>

    <!-- ── Hero ─────────────────────────────────────────────────────────── -->
    <section class="hero">
      <!-- Noise overlay -->
      <div class="hero__noise" />
      <!-- Dot grid -->
      <div class="hero__dots" />
      <!-- Glows -->
      <div class="hero__glow hero__glow--l" />
      <div class="hero__glow hero__glow--r" />
      <!-- Pulsing orb -->
      <div class="hero__orb" />
      <!-- Rising particles -->
      <div class="hero__particles">
        <span v-for="n in 12" :key="n" class="hero__particle" :style="{ '--px': (n * 8) + '%', '--dur': (6 + n % 5) + 's', '--delay': (n * 0.7) + 's', '--size': (4 + n % 4) + 'px' }" />
      </div>

      <div class="hero__inner">
        <div class="hero__badge" data-reveal="fade">
          <AppIcon name="zap" :size="13" />
          Trusted by 48,000+ investors worldwide
        </div>

        <h1 class="hero__headline" data-reveal data-delay="100">
          Trade Smarter.<br />
          <span class="hero__headline--accent">Grow Your Wealth.</span>
        </h1>

        <p class="hero__sub" data-reveal data-delay="200">
          Institutional-grade forex and investment platform. Access global
          markets, AI-powered signals, and managed investment plans — all in one
          place.
        </p>

        <div class="hero__ctas" data-reveal data-delay="300">
          <RouterLink to="/register" class="btn btn--primary btn--lg">
            Start Investing Free
            <AppIcon name="arrow-right" :size="16" />
          </RouterLink>
          <a href="#plans" class="btn btn--ghost btn--lg">
            View Plans
          </a>
        </div>

        <div class="hero__trust" data-reveal="fade" data-delay="450">
          <span class="hero__trust-item">
            <AppIcon name="lock" :size="14" />
            Bank-level security
          </span>
          <span class="hero__trust-divider" />
          <span class="hero__trust-item">
            <AppIcon name="zap" :size="14" />
            Instant setup
          </span>
          <span class="hero__trust-divider" />
          <span class="hero__trust-item">
            <AppIcon name="trending-up" :size="14" />
            Proven returns
          </span>
        </div>
      </div>
    </section>

    <!-- ── Market Ticker ─────────────────────────────────────────────────── -->
    <div id="markets" class="ticker-wrap">
      <div class="ticker">
        <div
          v-for="(t, i) in [...tickers, ...tickers]"
          :key="i"
          class="ticker__item"
        >
          <span class="ticker__pair">{{ t.pair }}</span>
          <span class="ticker__price">{{ t.price }}</span>
          <span class="ticker__change" :class="t.up ? 'is-up' : 'is-down'">
            {{ t.change }}
          </span>
        </div>
      </div>
    </div>

    <!-- ── Features ──────────────────────────────────────────────────────── -->
    <section id="features" class="section">
      <div class="section__inner">
        <div class="section__head" data-reveal>
          <span class="section__eyebrow">Why NexusTrade</span>
          <h2 class="section__title">Everything you need to trade professionally</h2>
          <p class="section__sub">
            From real-time analytics to automated signals, NexusTrade gives
            every investor an institutional edge.
          </p>
        </div>

        <div class="features-grid">
          <div
            v-for="(f, i) in features"
            :key="f.icon"
            class="feature-card"
            data-reveal
            :data-delay="i * 80"
          >
            <div class="feature-card__icon">
              <AppIcon :name="f.icon" :size="20" />
            </div>
            <h3 class="feature-card__title">{{ f.title }}</h3>
            <p class="feature-card__desc">{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Plans ─────────────────────────────────────────────────────────── -->
    <section id="plans" class="section section--alt">
      <div class="section__inner">
        <div class="section__head" data-reveal>
          <span class="section__eyebrow">Investment Plans</span>
          <h2 class="section__title">Choose the plan that fits your goals</h2>
          <p class="section__sub">
            Transparent performance, no hidden fees. Select a plan and start
            growing with professional portfolio management.
          </p>
        </div>

        <div class="plans-grid">
          <div
            v-for="(plan, i) in plans"
            :key="plan.name"
            class="plan-card"
            :class="{ 'plan-card--highlight': plan.highlight }"
            data-reveal="scale"
            :data-delay="i * 120"
          >
            <div v-if="plan.highlight" class="plan-card__badge">Most Popular</div>

            <div class="plan-card__header">
              <h3 class="plan-card__name">{{ plan.name }}</h3>
              <div class="plan-card__roi">
                {{ plan.roi }}
                <span class="plan-card__roi-label">/ {{ plan.period }}</span>
              </div>
            </div>

            <div class="plan-card__range">
              <span class="plan-card__range-label">Min</span>
              <span class="plan-card__range-value">{{ plan.min }}</span>
              <span class="plan-card__range-sep">→</span>
              <span class="plan-card__range-label">Max</span>
              <span class="plan-card__range-value">{{ plan.max }}</span>
            </div>

            <ul class="plan-card__features">
              <li v-for="feat in plan.features" :key="feat">
                <AppIcon name="check" :size="14" />
                {{ feat }}
              </li>
            </ul>

            <RouterLink
              to="/register"
              class="btn btn--lg"
              :class="plan.highlight ? 'btn--primary' : 'btn--outline'"
            >
              Get Started
            </RouterLink>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Stats ─────────────────────────────────────────────────────────── -->
    <section class="stats-section">
      <div class="stats-section__inner">
        <div
          v-for="(s, i) in stats"
          :key="s.label"
          class="stat-item"
          data-reveal
          :data-delay="i * 100"
        >
          <span
            class="stat-item__value"
            :data-counter="s.counter"
            :data-suffix="s.suffix"
            :data-prefix="s.prefix"
            :data-decimals="s.decimals"
          >{{ s.prefix }}{{ s.counter }}{{ s.suffix }}</span>
          <span class="stat-item__label">{{ s.label }}</span>
        </div>
      </div>
    </section>

    <!-- ── Recent Completions ─────────────────────────────────────────────── -->
    <section class="section">
      <div class="section__inner">
        <div class="section__head" data-reveal>
          <span class="section__eyebrow">Recent Completions</span>
          <h2 class="section__title">See How Investors Are Performing</h2>
          <p class="section__sub">
            Real completed investment cycles from our platform. Verified results,
            transparent returns.
          </p>
        </div>

        <div class="completions-grid">
          <div
            v-for="(c, i) in completions"
            :key="c.name"
            class="completion-card"
            data-reveal
            :data-delay="i * 80"
          >
            <div class="completion-card__header">
              <div class="completion-card__avatar">{{ c.initials }}</div>
              <div>
                <div class="completion-card__name">{{ c.name }}</div>
                <div class="completion-card__plan">{{ c.plan }} Plan · {{ c.days }}d</div>
              </div>
              <span class="completion-card__pct">{{ c.pct }}</span>
            </div>
            <div class="completion-card__body">
              <div class="completion-card__row">
                <span>Invested</span>
                <strong>{{ c.amount }}</strong>
              </div>
              <div class="completion-card__row">
                <span>Profit</span>
                <strong class="is-green">{{ c.profit }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Monthly Performance ───────────────────────────────────────────── -->
    <section class="section section--alt">
      <div class="section__inner">
        <div class="section__head" data-reveal>
          <span class="section__eyebrow">Track Record</span>
          <h2 class="section__title">Monthly Performance History</h2>
          <p class="section__sub">
            Consistent positive returns across all market conditions.
            Audited monthly results since inception.
          </p>
        </div>

        <div class="perf-chart" data-reveal>
          <div class="perf-chart__bars">
            <div
              v-for="(m, i) in perfMonths"
              :key="m.month"
              class="perf-bar"
              :style="{ '--bar-h': m.pct + '%', '--bar-delay': (i * 60) + 'ms' }"
            >
              <div class="perf-bar__fill" />
              <span class="perf-bar__pct">{{ m.pct }}%</span>
              <span class="perf-bar__month">{{ m.month }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── 4-Step Process ────────────────────────────────────────────────── -->
    <section class="section">
      <div class="section__inner">
        <div class="section__head" data-reveal>
          <span class="section__eyebrow">How It Works</span>
          <h2 class="section__title">Simple 4-Step Investment Process</h2>
          <p class="section__sub">
            Our streamlined onboarding gets you started with forex investing in
            minutes, not days.
          </p>
        </div>

        <div class="steps-grid">
          <div
            v-for="(step, i) in steps"
            :key="step.n"
            class="step-card"
            data-reveal
            :data-delay="i * 120"
          >
            <div class="step-card__number">{{ step.n }}</div>
            <div class="step-card__icon">
              <AppIcon :name="step.icon" :size="22" />
            </div>
            <h3 class="step-card__title">{{ step.title }}</h3>
            <p class="step-card__desc">{{ step.desc }}</p>
            <ul class="step-card__points">
              <li v-for="pt in step.points" :key="pt">
                <AppIcon name="check" :size="12" />
                {{ pt }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- ── CTA Banner ─────────────────────────────────────────────────────── -->
    <section class="cta-banner">
      <div class="cta-banner__glow" />
      <div class="cta-banner__inner">
        <h2 class="cta-banner__title">Ready to start your investment journey?</h2>
        <p class="cta-banner__sub">
          Join thousands of investors already growing their portfolios with NexusTrade.
        </p>
        <div class="cta-banner__actions">
          <RouterLink to="/register" class="btn btn--primary btn--lg">
            Create Free Account
          </RouterLink>
          <RouterLink to="/login" class="btn btn--ghost btn--lg">
            Sign In
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- ── Footer ────────────────────────────────────────────────────────── -->
    <footer class="home-footer">
      <div class="home-footer__inner">

        <!-- Brand column -->
        <div class="home-footer__brand">
          <NexusLogo :width="148" />
          <p class="home-footer__tagline">
            The world's most powerful forex and investment platform.
            Trusted by over 48,000 investors across 120+ countries.
          </p>
          <!-- Regulated badge -->
          <div class="home-footer__badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Licensed &amp; Regulated · SSL Secured
          </div>
          <!-- Socials -->
          <div class="home-footer__socials">
            <a href="#" aria-label="Twitter / X" class="home-footer__social">
              <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L2.2 2.25h6.961l4.261 5.636 5.822-5.636Zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
            <a href="#" aria-label="Telegram" class="home-footer__social">
              <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
            </a>
            <a href="#" aria-label="LinkedIn" class="home-footer__social">
              <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
            </a>
            <a href="#" aria-label="Facebook" class="home-footer__social">
              <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
            <a href="#" aria-label="Instagram" class="home-footer__social">
              <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
            </a>
          </div>
        </div>

        <!-- Quick Links -->
        <nav class="home-footer__links">
          <span class="home-footer__col-title">Quick Links</span>
          <a href="#">Platform Overview</a>
          <a href="#plans">Investment Plans</a>
          <a href="#">Forex Trading</a>
          <a href="#">Trading Signals</a>
          <a href="#">Copy Trading</a>
          <a href="#">MT5 Platform</a>
        </nav>

        <!-- Company -->
        <nav class="home-footer__links">
          <span class="home-footer__col-title">Company</span>
          <a href="#">About Us</a>
          <a href="#">Blog</a>
          <a href="#">Careers</a>
          <a href="#">Press &amp; Media</a>
          <a href="#">Contact Us</a>
          <a href="#">Partner Program</a>
        </nav>

        <!-- Support -->
        <nav class="home-footer__links">
          <span class="home-footer__col-title">Support</span>
          <a href="#">Help Center</a>
          <a href="#">FAQ</a>
          <RouterLink to="/register">Open Account</RouterLink>
          <RouterLink to="/login">Client Login</RouterLink>
          <a href="#">Deposit Methods</a>
          <a href="#">Withdrawal Guide</a>
        </nav>

        <!-- Legal -->
        <nav class="home-footer__links">
          <span class="home-footer__col-title">Legal</span>
          <a href="#">Terms of Service</a>
          <a href="#">Privacy Policy</a>
          <a href="#">Risk Disclosure</a>
          <a href="#">AML / KYC Policy</a>
          <a href="#">Cookie Policy</a>
          <a href="#">Regulatory Info</a>
        </nav>

      </div>

      <!-- Bottom bar -->
      <div class="home-footer__bottom">
        <div class="home-footer__bottom-inner">
          <p>© {{ new Date().getFullYear() }} NexusTrade Ltd. All rights reserved.</p>
          <div class="home-footer__bottom-socials">
            <a href="#" aria-label="Twitter / X" class="home-footer__social home-footer__social--sm">
              <svg viewBox="0 0 24 24" fill="currentColor" width="13" height="13"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L2.2 2.25h6.961l4.261 5.636 5.822-5.636Zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
            <a href="#" aria-label="Telegram" class="home-footer__social home-footer__social--sm">
              <svg viewBox="0 0 24 24" fill="currentColor" width="13" height="13"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
            </a>
            <a href="#" aria-label="LinkedIn" class="home-footer__social home-footer__social--sm">
              <svg viewBox="0 0 24 24" fill="currentColor" width="13" height="13"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
            </a>
            <a href="#" aria-label="Facebook" class="home-footer__social home-footer__social--sm">
              <svg viewBox="0 0 24 24" fill="currentColor" width="13" height="13"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
            <a href="#" aria-label="Instagram" class="home-footer__social home-footer__social--sm">
              <svg viewBox="0 0 24 24" fill="currentColor" width="13" height="13"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
            </a>
          </div>
        </div>
        <p class="home-footer__disclaimer">
          <strong>Risk Warning:</strong> Trading forex and CFDs on margin carries a high level of risk and may not
          be suitable for all investors. The high degree of leverage can work against you as well as for you.
          Before deciding to trade forex or any other financial instrument you should carefully consider your
          investment objectives, level of experience, and risk appetite. There is a possibility that you could
          sustain a loss of some or all of your initial investment and therefore you should not invest money that
          you cannot afford to lose. NexusTrade Ltd is licensed and regulated. Past performance is not indicative
          of future results.
        </p>
      </div>
    </footer>

  </div>
</template>

<style lang="scss" scoped>
// ── Shared button primitives ──────────────────────────────────────────────────
.btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: 0.6rem 1.4rem;
  border-radius: var(--radius-lg);
  font-weight: 600;
  font-size: var(--text-sm);
  text-decoration: none;
  cursor: pointer;
  border: 1px solid transparent;
  transition: background var(--transition-fast), color var(--transition-fast),
    border-color var(--transition-fast), box-shadow var(--transition-fast),
    transform var(--transition-fast);

  &:hover { transform: translateY(-1px); }
  &:active { transform: translateY(0); }

  &--lg { padding: 0.75rem 1.75rem; font-size: var(--text-base); }

  &--primary {
    background: var(--color-primary);
    color: #fff;
    &:hover { background: var(--color-primary-dark); box-shadow: 0 0 20px rgba(0,212,170,.35); }
  }

  &--ghost {
    background: transparent;
    color: var(--color-text);
    border-color: var(--color-border);
    &:hover { background: var(--color-surface-2); border-color: var(--color-primary); color: var(--color-primary); }
  }

  &--outline {
    background: transparent;
    color: var(--color-primary);
    border-color: var(--color-primary);
    &:hover { background: rgba(0,212,170,.08); box-shadow: 0 0 16px rgba(0,212,170,.2); }
  }
}

// ── Page shell ────────────────────────────────────────────────────────────────
.home {
  min-height: 100vh;
  background: var(--color-bg);
  color: var(--color-text);
  overflow-x: hidden;
}

// ── Navbar ────────────────────────────────────────────────────────────────────
.home-nav {
  position: fixed;
  top: 0;
  inset-inline: 0;
  z-index: 100;
  transition: background var(--transition-base), box-shadow var(--transition-base),
    backdrop-filter var(--transition-base);

  // Top accent gradient line (Bicrypto style)
  &::before {
    content: '';
    position: absolute;
    top: 0;
    inset-inline: 0;
    height: 1px;
    background: linear-gradient(to right, transparent, rgba(0,212,170,.6), rgba(0,188,212,.6), transparent);
    z-index: 1;
    pointer-events: none;
  }

  &--scrolled {
    background: rgba(var(--color-surface-rgb, 15 22 36), 0.85);
    backdrop-filter: blur(16px);
    box-shadow: var(--shadow-md);
  }

  &__inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--space-4) var(--space-6);
    display: flex;
    align-items: center;
    gap: var(--space-8);
  }

  &__brand { flex-shrink: 0; text-decoration: none; }

  &__links {
    display: flex;
    align-items: center;
    gap: var(--space-6);
    margin-left: auto;

    @media (max-width: 768px) {
      display: none;
      position: fixed;
      inset: 0;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: var(--color-bg);
      gap: var(--space-8);
      z-index: 99;

      &.is-open { display: flex; }
    }
  }

  &__link {
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--color-text-muted);
    text-decoration: none;
    transition: color var(--transition-fast);

    &:hover { color: var(--color-text); }
  }

  &__actions {
    display: flex;
    align-items: center;
    gap: var(--space-3);

    @media (max-width: 768px) {
      .home-nav__btn { display: none; }
    }
  }

  &__btn {
    &--ghost {
      padding: 0.45rem 1rem;
      border-radius: var(--radius-md);
      font-size: var(--text-sm);
      font-weight: 600;
      color: var(--color-text-muted);
      text-decoration: none;
      border: 1px solid var(--color-border);
      transition: all var(--transition-fast);
      &:hover { color: var(--color-text); background: var(--color-surface-2); }
    }

    &--primary {
      padding: 0.45rem 1rem;
      border-radius: var(--radius-md);
      font-size: var(--text-sm);
      font-weight: 600;
      background: var(--color-primary);
      color: #fff;
      text-decoration: none;
      border: 1px solid transparent;
      transition: all var(--transition-fast);
      &:hover { background: var(--color-primary-dark); box-shadow: 0 0 16px rgba(0,212,170,.3); }
    }
  }

  &__hamburger {
    display: none;
    background: transparent;
    border: none;
    color: var(--color-text);
    margin-left: auto;
    z-index: 100;
    padding: var(--space-1);

    @media (max-width: 768px) { display: flex; }
  }
}

// ── Hero ──────────────────────────────────────────────────────────────────────
.hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: calc(var(--navbar-height) + var(--space-16)) var(--space-6) var(--space-16);
  overflow: hidden;

  &__noise {
    position: absolute;
    inset: 0;
    opacity: 0.025;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='a'%3E%3CfeTurbulence baseFrequency='.75' stitchTiles='stitch' type='fractalNoise'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23a)' opacity='1'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 0;
  }

  &__dots {
    position: absolute;
    inset: 0;
    opacity: 0.025;
    background-image: radial-gradient(circle at 1px 1px, var(--color-text) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 0;
  }

  &__orb {
    position: absolute;
    top: 25%;
    left: 50%;
    transform: translateX(-50%);
    width: min(600px, 80vw);
    height: min(600px, 80vw);
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0,212,170,.18) 0%, rgba(0,212,170,.08) 30%, transparent 70%);
    filter: blur(40px);
    pointer-events: none;
    z-index: 0;
    animation: orbPulse 8s ease-in-out infinite;
  }

  &__particles {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 0;
  }

  &__particle {
    position: absolute;
    bottom: -10px;
    left: var(--px, 50%);
    width: var(--size, 6px);
    height: var(--size, 6px);
    border-radius: 50%;
    background: rgba(0, 212, 170, 0.5);
    animation: particleRise var(--dur, 8s) var(--delay, 0s) ease-in infinite;
    opacity: 0;
  }

  &__glow {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    filter: blur(80px);

    &--l {
      top: 10%;
      left: -10%;
      width: 50vw;
      height: 50vw;
      max-width: 600px;
      max-height: 600px;
      background: radial-gradient(circle, rgba(0,212,170,.12) 0%, transparent 70%);
    }

    &--r {
      bottom: 5%;
      right: -10%;
      width: 40vw;
      height: 40vw;
      max-width: 480px;
      max-height: 480px;
      background: radial-gradient(circle, rgba(108,99,255,.10) 0%, transparent 70%);
    }
  }

  &__inner {
    position: relative;
    z-index: 1;
    max-width: 760px;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-6);
  }

  &__badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: 0.35rem 1rem;
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: 600;
    color: var(--color-primary);
    background: rgba(0, 212, 170, 0.1);
    border: 1px solid rgba(0, 212, 170, 0.25);
    letter-spacing: 0.02em;
  }

  &__headline {
    font-size: clamp(2.5rem, 7vw, 4.5rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.04em;
    color: var(--color-text);
    margin: 0;

    &--accent {
      background: linear-gradient(135deg, #00D4AA 0%, #6C63FF 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
  }

  &__sub {
    font-size: clamp(var(--text-base), 2vw, var(--text-lg));
    color: var(--color-text-muted);
    line-height: 1.7;
    max-width: 560px;
    margin: 0;
  }

  &__ctas {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    flex-wrap: wrap;
    justify-content: center;
  }

  &__trust {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    flex-wrap: wrap;
    justify-content: center;
  }

  &__trust-item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-xs);
    color: var(--color-text-muted);
    font-weight: 500;
  }

  &__trust-divider {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--color-border);
  }
}

// ── Ticker ────────────────────────────────────────────────────────────────────
.ticker-wrap {
  background: var(--color-surface);
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
  overflow: hidden;
  padding: var(--space-3) 0;
}

.ticker {
  display: flex;
  width: max-content;
  animation: ticker-scroll 40s linear infinite;

  &:hover { animation-play-state: paused; }

  &__item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: 0 var(--space-6);
    border-right: 1px solid var(--color-border);
    white-space: nowrap;
  }

  &__pair {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--color-text);
  }

  &__price {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    font-variant-numeric: tabular-nums;
  }

  &__change {
    font-size: var(--text-xs);
    font-weight: 700;
    padding: 0.15rem 0.45rem;
    border-radius: var(--radius-full);

    &.is-up   { color: var(--color-success); background: rgba(34,197,94,.1); }
    &.is-down { color: var(--color-danger);  background: rgba(239,68,68,.1); }
  }
}

@keyframes ticker-scroll {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

@keyframes orbPulse {
  0%, 100% { transform: translateX(-50%) scale(1) translateY(0); }
  25%       { transform: translateX(-50%) scale(1.04) translateY(-10px); }
  50%       { transform: translateX(-50%) scale(1.08) translateY(-5px); }
  75%       { transform: translateX(-50%) scale(1.02) translateY(-12px); }
}

@keyframes particleRise {
  0%   { transform: translateY(0) scale(0.3);   opacity: 0; }
  5%   { transform: translateY(-5vh) scale(0.7); opacity: 0.6; }
  10%  { transform: translateY(-10vh) scale(1);  opacity: 0.6; }
  80%  { transform: translateY(-85vh) scale(0.8);opacity: 0.15; }
  100% { transform: translateY(-110vh) scale(0.3);opacity: 0; }
}

// ── Section helpers ───────────────────────────────────────────────────────────
.section {
  padding: var(--space-20) var(--space-6);

  &--alt { background: var(--color-surface); }

  &__inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: var(--space-12);
  }

  &__head {
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__eyebrow {
    font-size: var(--text-xs);
    font-weight: 700;
    color: var(--color-primary);
    text-transform: uppercase;
    letter-spacing: 0.1em;
  }

  &__title {
    font-size: clamp(1.75rem, 3.5vw, 2.5rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.2;
    color: var(--color-text);
    margin: 0;
  }

  &__sub {
    font-size: var(--text-base);
    color: var(--color-text-muted);
    line-height: 1.7;
    margin: 0;
  }
}

// ── Features grid ─────────────────────────────────────────────────────────────
.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-5);

  @media (max-width: 900px)  { grid-template-columns: repeat(2, 1fr); }
  @media (max-width: 600px)  { grid-template-columns: 1fr; }
}

.feature-card {
  position: relative;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-xl);
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  overflow: hidden;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast),
    transform var(--transition-fast);

  [data-theme="light"] & {
    background: var(--color-surface);
    border-color: var(--color-border);
  }

  &::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: var(--radius-xl);
    opacity: 0;
    transition: opacity var(--transition-fast);
    background: linear-gradient(135deg, rgba(0,212,170,.08), rgba(0,212,170,.04));
    pointer-events: none;
  }

  &:hover {
    border-color: rgba(0, 212, 170, 0.35);
    box-shadow: 0 0 32px rgba(0, 212, 170, 0.08);
    transform: translateY(-3px);
    &::after { opacity: 1; }
  }

  &__icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: var(--radius-lg);
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    box-shadow: 0 8px 24px rgba(0, 212, 170, 0.3);
    color: #fff;
    flex-shrink: 0;

    &::before {
      content: '';
      position: absolute;
      inset: 0;
      border-radius: inherit;
      background: inherit;
      filter: blur(12px);
      opacity: 0.5;
      z-index: -1;
    }
  }

  &__title {
    font-size: var(--text-base);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
  }

  &__desc {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    line-height: 1.65;
    margin: 0;
  }
}

// ── Plans grid ────────────────────────────────────────────────────────────────
.plans-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-5);
  align-items: start;

  @media (max-width: 900px) { grid-template-columns: 1fr; max-width: 480px; margin: 0 auto; }
}

.plan-card {
  position: relative;
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
  transition: box-shadow var(--transition-fast), transform var(--transition-fast);

  &:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

  &--highlight {
    border-color: var(--color-primary);
    box-shadow: 0 0 32px rgba(0, 212, 170, 0.15);
  }

  &__badge {
    position: absolute;
    top: -1px;
    right: var(--space-5);
    background: var(--color-primary);
    color: #fff;
    font-size: var(--text-xs);
    font-weight: 700;
    padding: 0.25rem 0.75rem;
    border-radius: 0 0 var(--radius-md) var(--radius-md);
    letter-spacing: 0.03em;
  }

  &__header {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
  }

  &__name {
    font-size: var(--text-xl);
    font-weight: 800;
    color: var(--color-text);
    margin: 0;
  }

  &__roi {
    font-size: var(--text-3xl);
    font-weight: 800;
    color: var(--color-primary);
    letter-spacing: -0.03em;
    line-height: 1;
  }

  &__roi-label {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    font-weight: 400;
  }

  &__range {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3);
    background: var(--color-surface-2);
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
  }

  &__range-label {
    color: var(--color-text-muted);
    font-weight: 500;
  }

  &__range-value {
    font-weight: 700;
    color: var(--color-text);
  }

  &__range-sep {
    color: var(--color-text-muted);
    margin: 0 auto;
  }

  &__features {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    list-style: none;
    padding: 0;
    margin: 0;

    li {
      display: flex;
      align-items: center;
      gap: var(--space-2);
      font-size: var(--text-sm);
      color: var(--color-text-muted);

      svg { color: var(--color-primary); flex-shrink: 0; }
    }
  }
}

// ── Stats ─────────────────────────────────────────────────────────────────────
.stats-section {
  padding: var(--space-16) var(--space-6);
  background: linear-gradient(135deg, #00D4AA15 0%, #6C63FF10 100%);
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);

  &__inner {
    max-width: 900px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--space-8);

    @media (max-width: 600px) { grid-template-columns: repeat(2, 1fr); }
  }
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-2);
  text-align: center;

  &__value {
    font-size: clamp(var(--text-2xl), 3vw, var(--text-4xl));
    font-weight: 800;
    color: var(--color-primary);
    letter-spacing: -0.03em;
  }

  &__label {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    font-weight: 500;
  }
}

// ── CTA Banner ────────────────────────────────────────────────────────────────
.cta-banner {
  position: relative;
  padding: var(--space-20) var(--space-6);
  text-align: center;
  overflow: hidden;

  &__glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at center, rgba(0,212,170,.06) 0%, transparent 70%);
    pointer-events: none;
  }

  &__inner {
    position: relative;
    z-index: 1;
    max-width: 560px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-5);
  }

  &__title {
    font-size: clamp(1.75rem, 3.5vw, 2.5rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--color-text);
    margin: 0;
  }

  &__sub {
    font-size: var(--text-base);
    color: var(--color-text-muted);
    line-height: 1.65;
    margin: 0;
  }

  &__actions {
    display: flex;
    gap: var(--space-4);
    flex-wrap: wrap;
    justify-content: center;
  }
}

// ── Recent Completions ────────────────────────────────────────────────────────
.completions-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-4);

  @media (max-width: 900px) { grid-template-columns: repeat(2, 1fr); }
  @media (max-width: 560px) { grid-template-columns: 1fr; }
}

.completion-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: var(--space-5);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  transition: box-shadow var(--transition-fast), transform var(--transition-fast);

  &:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

  &__header {
    display: flex;
    align-items: center;
    gap: var(--space-3);
  }

  &__avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    color: #fff;
    font-size: var(--text-xs);
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  &__name {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--color-text);
  }

  &__plan {
    font-size: var(--text-xs);
    color: var(--color-text-muted);
  }

  &__pct {
    margin-left: auto;
    font-size: var(--text-sm);
    font-weight: 700;
    color: var(--color-success);
    background: rgba(34, 197, 94, 0.1);
    padding: 0.2rem 0.55rem;
    border-radius: var(--radius-full);
  }

  &__body {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding-top: var(--space-3);
    border-top: 1px solid var(--color-border);
  }

  &__row {
    display: flex;
    justify-content: space-between;
    font-size: var(--text-sm);

    span { color: var(--color-text-muted); }
    strong { color: var(--color-text); font-weight: 600; }
    .is-green { color: var(--color-success); }
  }
}

// ── Performance bar chart ─────────────────────────────────────────────────────
.perf-chart {
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: var(--space-6);

  &__bars {
    display: flex;
    align-items: flex-end;
    gap: var(--space-3);
    height: 200px;

    @media (max-width: 600px) { gap: var(--space-2); }
  }
}

.perf-bar {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-1);
  height: 100%;
  justify-content: flex-end;

  &__fill {
    width: 100%;
    border-radius: var(--radius-md) var(--radius-md) 0 0;
    background: linear-gradient(180deg, var(--color-primary) 0%, rgba(0, 212, 170, 0.4) 100%);
    height: 0;
    transition: height 1s cubic-bezier(0.22, 1, 0.36, 1) var(--bar-delay, 0ms);
  }

  &__pct {
    font-size: 0.6rem;
    color: var(--color-text-muted);
    font-weight: 600;

    @media (max-width: 600px) { display: none; }
  }

  &__month {
    font-size: 0.6rem;
    color: var(--color-text-muted);
    font-weight: 500;
  }
}

// Trigger bar animation when parent gets .is-revealed
.perf-chart.is-revealed .perf-bar__fill {
  height: var(--bar-h, 0%);
}

// ── Steps ─────────────────────────────────────────────────────────────────────
.steps-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: var(--space-5);
  position: relative;

  // Connecting line between steps
  &::before {
    content: '';
    position: absolute;
    top: 3rem;
    left: calc(12.5% + 1.25rem);
    right: calc(12.5% + 1.25rem);
    height: 1px;
    background: linear-gradient(90deg, var(--color-primary) 0%, var(--color-border) 100%);
    z-index: 0;
  }

  @media (max-width: 900px) {
    grid-template-columns: repeat(2, 1fr);
    &::before { display: none; }
  }

  @media (max-width: 480px) {
    grid-template-columns: 1fr;
  }
}

.step-card {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-3);
  padding: var(--space-5);

  &__number {
    font-size: var(--text-xs);
    font-weight: 800;
    color: var(--color-primary);
    letter-spacing: 0.05em;
    opacity: 0.6;
  }

  &__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 50%;
    background: rgba(0, 212, 170, 0.1);
    border: 2px solid rgba(0, 212, 170, 0.25);
    color: var(--color-primary);
    position: relative;
    z-index: 1;
  }

  &__title {
    font-size: var(--text-base);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
  }

  &__desc {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    line-height: 1.6;
    margin: 0;
  }

  &__points {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;

    li {
      display: flex;
      align-items: center;
      gap: var(--space-2);
      font-size: var(--text-xs);
      color: var(--color-text-muted);
      font-weight: 500;
      svg { color: var(--color-primary); flex-shrink: 0; }
    }
  }
}

// ── Footer ────────────────────────────────────────────────────────────────────
.home-footer {
  background: var(--color-surface);
  border-top: 1px solid var(--color-border);
  padding: var(--space-12) var(--space-6) var(--space-6);

  &__inner {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
    gap: var(--space-8);
    padding-bottom: var(--space-10);
    border-bottom: 1px solid var(--color-border);

    @media (max-width: 1024px) { grid-template-columns: 2fr 1fr 1fr; }
    @media (max-width: 640px)  { grid-template-columns: 1fr 1fr; }
    @media (max-width: 400px)  { grid-template-columns: 1fr; }
  }

  &__brand {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
  }

  &__tagline {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    line-height: 1.65;
    max-width: 280px;
  }

  &__badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-xs);
    color: var(--color-primary);
    font-weight: 600;
    background: rgba(0,212,170,.08);
    border: 1px solid rgba(0,212,170,.2);
    border-radius: var(--radius-full);
    padding: var(--space-1) var(--space-3);
  }

  &__socials {
    display: flex;
    gap: var(--space-3);
    margin-top: var(--space-2);
  }

  &__social {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: var(--radius-md);
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    color: var(--color-text-muted);
    text-decoration: none;
    transition: all var(--transition-fast);

    [data-theme="light"] & {
      background: var(--color-surface-2);
      border-color: var(--color-border);
    }

    &:hover {
      color: var(--color-primary);
      border-color: rgba(0,212,170,.4);
      background: rgba(0,212,170,.08);
    }
  }

  &__links {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);

    a, :deep(.router-link-active), :deep(a) {
      font-size: var(--text-sm);
      color: var(--color-text-muted);
      text-decoration: none;
      transition: color var(--transition-fast);
      &:hover { color: var(--color-primary); }
    }
  }

  &__col-title {
    font-size: var(--text-xs);
    font-weight: 700;
    color: var(--color-text);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: var(--space-1);
    display: block;
  }

  &__bottom {
    max-width: 1200px;
    margin: var(--space-6) auto 0;
  }

  &__bottom-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--space-4);
    font-size: var(--text-xs);
    color: var(--color-text-muted);
    margin-bottom: var(--space-4);

    p { margin: 0; }
  }

  &__bottom-socials {
    display: flex;
    gap: var(--space-2);
  }

  &__social--sm {
    width: 1.75rem;
    height: 1.75rem;
  }

  &__disclaimer {
    font-size: 0.7rem;
    color: var(--color-text-muted);
    opacity: 0.6;
    line-height: 1.6;
    border-top: 1px solid var(--color-border);
    padding-top: var(--space-4);
    margin: 0;
  }
}
</style>
