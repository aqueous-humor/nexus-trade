import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, _from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0, behavior: 'smooth' }
  },
  routes: [
    // ── Public home ───────────────────────────────────────────────────
    {
      path: '/',
      name: 'home',
      component: () => import('../views/HomeView.vue'),
    },

    // ── Guest routes ──────────────────────────────────────────────────
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/auth/LoginView.vue'),
      meta: { requiresGuest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/auth/RegisterView.vue'),
      meta: { requiresGuest: true },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('../views/auth/ForgotPasswordView.vue'),
      meta: { requiresGuest: true },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('../views/auth/ResetPasswordView.vue'),
      meta: { requiresGuest: true },
    },

    // ── 404 ──────────────────────────────────────────────────────────────
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('../views/NotFoundView.vue'),
    },

    // ── Authenticated routes ──────────────────────────────────────────
    // Nested under AppLayout which renders TheSidebar + TheNavbar + <RouterView />.
    {
      path: '/app',
      component: () => import('../layouts/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('../views/DashboardView.vue'),
        },
        {
          path: 'accounts',
          name: 'accounts',
          component: () => import('../views/AccountListView.vue'),
        },
        {
          path: 'accounts/:id',
          name: 'account-detail',
          component: () => import('../views/AccountDetailView.vue'),
        },
        {
          path: 'plans',
          name: 'plans',
          component: () => import('../views/PlansView.vue'),
        },
        {
          path: 'invest',
          name: 'invest',
          component: () => import('../views/InvestView.vue'),
        },
        {
          path: 'investments',
          name: 'investments',
          component: () => import('../views/InvestmentHistoryView.vue'),
        },
        {
          path: 'wallet',
          name: 'wallet',
          component: () => import('../views/WalletView.vue'),
        },
        {
          path: 'wallet/deposit',
          name: 'wallet-deposit',
          component: () => import('../views/DepositView.vue'),
        },
        {
          path: 'wallet/withdraw',
          name: 'wallet-withdraw',
          component: () => import('../views/WithdrawView.vue'),
        },
        {
          path: 'wallet/transactions',
          name: 'wallet-transactions',
          component: () => import('../views/TransactionHistoryView.vue'),
        },
        {
          path: 'signals',
          name: 'signals',
          component: () => import('../views/SignalsView.vue'),
        },

        // ── Admin routes (wrapped in AdminLayout) ───────────────────
        {
          path: 'admin',
          component: () => import('../layouts/AdminLayout.vue'),
          meta: { requiresAdmin: true },
          children: [
            {
              path: '',
              name: 'admin-dashboard',
              component: () => import('../views/admin/AdminDashboardView.vue'),
            },
            {
              path: 'users',
              name: 'admin-users',
              component: () => import('../views/admin/AdminUsersView.vue'),
            },
            {
              path: 'accounts',
              name: 'admin-accounts',
              component: () => import('../views/admin/AdminAccountsView.vue'),
            },
            {
              path: 'investments',
              name: 'admin-investments',
              component: () => import('../views/admin/AdminInvestmentsView.vue'),
            },
            {
              path: 'plans',
              name: 'admin-plans',
              component: () => import('../views/admin/AdminPlansView.vue'),
            },
            {
              path: 'brokers',
              name: 'admin-brokers',
              component: () => import('../views/admin/AdminBrokersView.vue'),
            },
            {
              path: 'signals',
              name: 'admin-signals',
              component: () => import('../views/admin/AdminSignalsView.vue'),
            },
            {
              path: 'deposits',
              name: 'admin-deposits',
              component: () => import('../views/admin/AdminDepositsView.vue'),
            },
            {
              path: 'withdrawals',
              name: 'admin-withdrawals',
              component: () => import('../views/admin/AdminWithdrawalsView.vue'),
            },
            {
              path: 'fraud',
              name: 'admin-fraud',
              component: () => import('../views/admin/AdminFraudReviewView.vue'),
            },
            {
              path: 'audit-logs',
              name: 'admin-audit-logs',
              component: () => import('../views/admin/AdminAuditLogView.vue'),
            },
          ],
        },
      ],
    },
  ],
})

// ── Navigation guard ──────────────────────────────────────────────────────────

let authInitialized = false

router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()

  // On the very first navigation, try to restore the session from the cookie.
  // This handles hard refreshes where Pinia state has been reset.
  if (!authInitialized) {
    authInitialized = true
    try {
      await authStore.fetchMe()
    } catch {
      // 401 / network error — user is not authenticated; continue normally.
    }
  }

  const requiresAuth  = to.matched.some((r) => r.meta.requiresAuth)
  const requiresGuest = to.matched.some((r) => r.meta.requiresGuest)
  const requiresAdmin = to.matched.some((r) => r.meta.requiresAdmin)

  if (requiresAuth && !authStore.isAuthenticated) {
    return next({ path: '/login', query: to.fullPath !== '/app' ? { redirect: to.fullPath } : {} })
  }

  if (requiresGuest && authStore.isAuthenticated) {
    return next('/app')
  }

  if (requiresAdmin && !authStore.isAdmin) {
    return next('/app')
  }

  next()
})

export default router
