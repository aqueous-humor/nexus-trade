import client from './client'
import type { User } from '@/stores/auth'

export interface LoginPayload {
  email: string
  password: string
}

export interface RegisterPayload {
  first_name: string
  last_name: string
  email: string
  phone_number?: string
  password: string
  password_confirmation: string
}

export interface LoginResponse {
  data: { user: User; token?: string }
}

export interface ForgotPasswordPayload {
  email: string
}

export interface ResetPasswordPayload {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export const authApi = {
  login: (payload: LoginPayload) =>
    client.post<LoginResponse>('/api/v1/auth/login', payload),

  register: (payload: RegisterPayload) =>
    client.post('/api/v1/auth/register', payload),

  logout: () =>
    client.post('/api/v1/auth/logout'),

  me: () =>
    client.get<{ data: User }>('/api/v1/auth/me'),

  forgotPassword: (payload: ForgotPasswordPayload) =>
    client.post('/api/v1/auth/forgot-password', payload),

  resetPassword: (payload: ResetPasswordPayload) =>
    client.post('/api/v1/auth/reset-password', payload),

  resendVerification: () =>
    client.post('/api/v1/email/resend'),
}
