// ====== Types ======
export interface Tenant {
  id: number;
  name: string;
  slug: string;
  email: string | null;
  phone: string | null;
  logo: string | null;
  is_active: boolean;
  plan: 'basic' | 'pro' | 'enterprise';
  settings: Record<string, unknown> | null;
  features?: string[] | null;
  users_count?: number;
  clients_count?: number;
  invoices_count?: number;
  api_keys_count?: number;
  created_at: string;
  updated_at: string;
}

export interface ApiKey {
  id: number;
  tenant_id: number;
  name: string;
  key: string;
  is_active: boolean;
  last_used_at: string | null;
  created_at: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  is_active: boolean;
  created_at: string;
  role?: { name: string };
  team?: { name: string };
}

export interface Stats {
  tenants: { total: number; active: number };
  users: { total: number; active: number };
  clients: { total: number };
  invoices: { total: number };
  tenants_by_plan: Record<string, number>;
}

import axios from 'axios';

// ====== API Client ======
const API_BASE = import.meta.env.VITE_API_URL || '/api';

async function request<T>(path: string, options: { method?: string, data?: any } = {}): Promise<T> {
  try {
    const res = await axios({
      url: `${API_BASE}${path}`,
      method: options.method || 'GET',
      data: options.data,
      withCredentials: true,
      headers: {
        'Accept': 'application/json'
      }
    });
    return res.data;
  } catch (error: any) {
    if (error.response) {
      throw new Error(error.response.data.message || 'حدث خطأ في الاتصال بالخادم');
    }
    throw error;
  }
}

// ====== Auth ======
export async function login(email: string, password: string) {
  const res = await request<{ data: { access_token: string; user: User } }>(
    '/v1/auth/login',
    {
      method: 'POST',
      data: { email, password },
    }
  );
  return res.data;
}

// ====== Stats ======
export async function fetchStats() {
  const res = await request<{ data: Stats }>('/super/v1/stats');
  return res.data;
}

// ====== Tenants ======
export async function fetchTenants(params?: {
  search?: string;
  is_active?: boolean;
  plan?: string;
  page?: number;
}) {
  const qs = new URLSearchParams();
  if (params?.search) qs.set('search', params.search);
  if (params?.is_active !== undefined) qs.set('is_active', String(params.is_active));
  if (params?.plan) qs.set('plan', params.plan);
  if (params?.page) qs.set('page', String(params.page));

  const res = await request<{ data: { data: Tenant[] } }>(`/super/v1/tenants?${qs}`);
  return res.data.data;
}

export async function fetchTenant(id: number) {
  const res = await request<{ data: Tenant }>(`/super/v1/tenants/${id}`);
  return res.data;
}

export async function createTenant(data: Partial<Tenant> & {
  admin_name?: string;
  admin_email?: string;
  admin_password?: string;
}) {
  const res = await request<{ data: { tenant: Tenant } }>('/super/v1/tenants', {
    method: 'POST',
    data,
  });
  return res.data;
}

export async function updateTenant(id: number, data: Partial<Tenant> & { settings?: Record<string, string> }) {
  const res = await request<{ data: Tenant }>(`/super/v1/tenants/${id}`, {
    method: 'PATCH',
    data,
  });
  return res.data;
}

export async function toggleTenant(id: number) {
  const res = await request<{ data: { is_active: boolean } }>(`/super/v1/tenants/${id}/toggle`, {
    method: 'PATCH',
  });
  return res.data;
}

export async function deleteTenant(id: number) {
  return request(`/super/v1/tenants/${id}`, { method: 'DELETE' });
}

// ====== API Keys ======
export async function fetchApiKeys(tenantId: number) {
  const res = await request<{ data: { api_keys: ApiKey[] } }>(`/super/v1/tenants/${tenantId}/api-keys`);
  return res.data.api_keys;
}

export async function createApiKey(tenantId: number, name: string) {
  const res = await request<{ data: ApiKey }>(`/super/v1/tenants/${tenantId}/api-keys`, {
    method: 'POST',
    data: { name },
  });
  return res.data;
}

export async function toggleApiKey(tenantId: number, keyId: number) {
  return request(`/super/v1/tenants/${tenantId}/api-keys/${keyId}/toggle`, { method: 'PATCH' });
}

export async function deleteApiKey(tenantId: number, keyId: number) {
  return request(`/super/v1/tenants/${tenantId}/api-keys/${keyId}`, { method: 'DELETE' });
}

// ====== Users per Tenant ======
export async function fetchTenantUsers(tenantId: number) {
  const res = await request<{ data: { users: User[] } }>(`/super/v1/tenants/${tenantId}/users`);
  return res.data.users;
}
