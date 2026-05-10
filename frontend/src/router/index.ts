import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Guest routes (auth pages)
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

    // Authenticated user routes
    {
      path: '/',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/accounts',
      name: 'accounts',
      component: () => import('../views/AccountListView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/accounts/:id',
      name: 'account-detail',
      component: () => import('../views/AccountDetailView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/plans',
      name: 'plans',
      component: () => import('../views/PlansView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/investments',
      name: 'investments',
      component: () => import('../views/InvestmentHistoryView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/wallet',
      name: 'wallet',
      component: () => import('../views/WalletView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/signals',
      name: 'signals',
      component: () => import('../views/SignalsView.vue'),
      meta: { requiresAuth: true },
    },

    // Admin routes
    {
      path: '/admin',
      name: 'admin-dashboard',
      component: () => import('../views/admin/AdminDashboardView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/users',
      name: 'admin-users',
      component: () => import('../views/admin/AdminUsersView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/accounts',
      name: 'admin-accounts',
      component: () => import('../views/admin/AdminAccountsView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/investments',
      name: 'admin-investments',
      component: () => import('../views/admin/AdminInvestmentsView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/plans',
      name: 'admin-plans',
      component: () => import('../views/admin/AdminPlansView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/brokers',
      name: 'admin-brokers',
      component: () => import('../views/admin/AdminBrokersView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/signals',
      name: 'admin-signals',
      component: () => import('../views/admin/AdminSignalsView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/fraud',
      name: 'admin-fraud',
      component: () => import('../views/admin/AdminFraudReviewView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/audit-logs',
      name: 'admin-audit-logs',
      component: () => import('../views/admin/AdminAuditLogView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
  ],
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login')
  }

  // Check if route requires guest (redirect authenticated users)
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    return next('/')
  }

  // Check if route requires admin role
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return next('/')
  }

  next()
})

export default router
