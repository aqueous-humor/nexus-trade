import { ref } from 'vue'
import { defineStore } from 'pinia'

export interface Investment {
  id: number
  amount_cents: number
  profit_cents: number
  status: string
  result?: string
  plan: {
    id: number
    name: string
  }
}

export interface InvestmentPlan {
  id: number
  name: string
  description: string
  min_amount_cents: number
  max_amount_cents: number
  roi_percentage: number
  status: string
}

export const useInvestmentStore = defineStore('investment', () => {
  const investments = ref<Investment[]>([])
  const activeInvestments = ref<Investment[]>([])
  const plans = ref<InvestmentPlan[]>([])
  const isLoading = ref(false)

  async function fetchInvestments(filters?: Record<string, unknown>): Promise<void> {
    // TODO: implement in investment phase — call GET /api/v1/investments
    isLoading.value = true
    try {
      // Will update investments from response
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPlans(): Promise<void> {
    // TODO: implement in investment phase — call GET /api/v1/plans
    isLoading.value = true
    try {
      // Will update plans from response
    } finally {
      isLoading.value = false
    }
  }

  async function createInvestment(data: {
    account_id: number
    plan_id: number
    duration_id: number
    amount_cents: number
    terms_version: string
  }): Promise<void> {
    // TODO: implement in investment phase — call POST /api/v1/investments
    isLoading.value = true
    try {
      // Will add new investment and refresh activeInvestments
    } finally {
      isLoading.value = false
    }
  }

  async function cancelInvestment(id: number): Promise<void> {
    // TODO: implement in investment phase — call DELETE /api/v1/investments/:id
    isLoading.value = true
    try {
      // Will remove investment from activeInvestments
    } finally {
      isLoading.value = false
    }
  }

  return {
    investments,
    activeInvestments,
    plans,
    isLoading,
    fetchInvestments,
    fetchPlans,
    createInvestment,
    cancelInvestment,
  }
})
