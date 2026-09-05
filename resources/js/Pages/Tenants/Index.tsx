'use client';

import { useEffect, useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { fetchTenants, toggleTenant, deleteTenant, createTenant, type Tenant } from '@/lib/api';
import DashboardLayout from '../../Layouts/DashboardLayout';

const PLAN_LABELS: Record<string, string> = { basic: 'أساسي', pro: 'احترافي', enterprise: 'مؤسسي' };

function Modal({ onClose, children }: { onClose: () => void; children: React.ReactNode }) {
  return (
    <div onClick={onClose} style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.6)', backdropFilter: 'blur(4px)', zIndex: 100, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }}>
      <div onClick={e => e.stopPropagation()} className="glass" style={{ width: '100%', maxWidth: 540, padding: 32, maxHeight: '90vh', overflowY: 'auto', animation: 'fadeInUp 0.25s ease' }}>
        {children}
      </div>
    </div>
  );
}

function AddTenantModal({ onClose, onSuccess }: { onClose: () => void; onSuccess: () => void }) {
  const [form, setForm] = useState({ name: '', slug: '', email: '', plan: 'basic', admin_name: '', admin_email: '', admin_password: 'Password@123' });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  function update(k: string, v: string) { setForm(f => ({ ...f, [k]: v })); }
  function autoSlug(name: string) {
    return name.toLowerCase().replace(/\s+/g, '-').replace(/[^\w\-]/g, '').replace(/\-+/g, '-');
  }

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await createTenant(form);
      onSuccess();
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'حدث خطأ');
    } finally {
      setLoading(false);
    }
  }

  return (
    <Modal onClose={onClose}>
      <h2 style={{ fontSize: 18, fontWeight: 800, margin: '0 0 24px' }}>إضافة مستأجر جديد</h2>
      {error && <div style={{ background: 'rgba(239,68,68,0.1)', border: '1px solid rgba(239,68,68,0.25)', borderRadius: 10, padding: '10px 14px', marginBottom: 20, color: '#ef4444', fontSize: 13 }}>{error}</div>}
      <form onSubmit={submit}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 16 }}>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>اسم الشركة *</label>
            <input className="input-dark" value={form.name} onChange={e => { update('name', e.target.value); if (!form.slug) update('slug', autoSlug(e.target.value)); }} required placeholder="شركة نموذجية" />
          </div>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>المعرف الفريد (slug) *</label>
            <input className="input-dark" value={form.slug} onChange={e => update('slug', e.target.value)} required placeholder="company-name" style={{ direction: 'ltr' }} />
          </div>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 16 }}>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>البريد الإلكتروني</label>
            <input className="input-dark" type="email" value={form.email} onChange={e => update('email', e.target.value)} placeholder="info@company.com" />
          </div>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>الباقة *</label>
            <select className="input-dark" value={form.plan} onChange={e => update('plan', e.target.value)} style={{ appearance: 'none' }}>
              <option value="basic">أساسي</option>
              <option value="pro">احترافي</option>
              <option value="enterprise">مؤسسي</option>
            </select>
          </div>
        </div>
        <div style={{ borderTop: '1px solid var(--border)', paddingTop: 20, marginBottom: 16 }}>
          <p style={{ fontSize: 12, color: 'var(--text-muted)', marginBottom: 14 }}>إجباري: إنشاء حساب لمدير النظام</p>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
            <div>
              <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>اسم المدير *</label>
              <input className="input-dark" type="text" value={form.admin_name || ''} onChange={e => update('admin_name', e.target.value)} required placeholder="مدير النظام" />
            </div>
            <div>
              <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>بريد المدير *</label>
              <input className="input-dark" type="email" value={form.admin_email} onChange={e => update('admin_email', e.target.value)} required placeholder="admin@company.com" />
            </div>
            <div>
              <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>كلمة المرور *</label>
              <input className="input-dark" type="text" value={form.admin_password} onChange={e => update('admin_password', e.target.value)} required placeholder="Password@123" />
            </div>
          </div>
        </div>
        <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end', marginTop: 24 }}>
          <button type="button" className="btn-ghost" onClick={onClose}>إلغاء</button>
          <button type="submit" className="btn-primary" disabled={loading}>
            {loading ? 'جاري الإنشاء...' : '+ إنشاء المستأجر'}
          </button>
        </div>
      </form>
    </Modal>
  );
}

export default function TenantsPage({ plans = [] }: { plans: any[] }) {
  // router is imported from inertia
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [planFilter, setPlanFilter] = useState('');
  const [showAdd, setShowAdd] = useState(false);
  const [toDelete, setToDelete] = useState<Tenant | null>(null);
  const [actionLoading, setActionLoading] = useState<number | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchTenants({ search: search || undefined, plan: planFilter || undefined });
      setTenants(data);
    } finally {
      setLoading(false);
    }
  }, [search, planFilter]);

  useEffect(() => { load(); }, [load]);

  async function handleToggle(t: Tenant) {
    setActionLoading(t.id);
    try {
      await toggleTenant(t.id);
      setTenants(prev => prev.map(x => x.id === t.id ? { ...x, is_active: !x.is_active } : x));
    } finally { setActionLoading(null); }
  }

  async function handleDelete() {
    if (!toDelete) return;
    setActionLoading(toDelete.id);
    try {
      await deleteTenant(toDelete.id);
      setTenants(prev => prev.filter(x => x.id !== toDelete.id));
      setToDelete(null);
    } finally { setActionLoading(null); }
  }

  return (
    <div>
      {showAdd && <AddTenantModal onClose={() => setShowAdd(false)} onSuccess={load} />}

      {/* Confirm Delete */}
      {toDelete && (
        <Modal onClose={() => setToDelete(null)}>
          <div style={{ textAlign: 'center' }}>
            <div style={{ fontSize: 48, marginBottom: 16 }}>⚠️</div>
            <h2 style={{ fontSize: 18, fontWeight: 800, marginBottom: 10 }}>تأكيد الحذف النهائي</h2>
            <p style={{ color: 'var(--text-secondary)', fontSize: 14, marginBottom: 24 }}>
              سيتم حذف المستأجر <strong style={{ color: 'var(--text-primary)' }}>{toDelete.name}</strong> وجميع بياناته بشكل نهائي. لا يمكن التراجع عن هذا الإجراء.
            </p>
            <div style={{ display: 'flex', gap: 12, justifyContent: 'center' }}>
              <button className="btn-ghost" onClick={() => setToDelete(null)}>إلغاء</button>
              <button className="btn-danger" onClick={handleDelete} disabled={actionLoading === toDelete.id} style={{ padding: '10px 24px', fontSize: 14, fontWeight: 700 }}>
                {actionLoading === toDelete.id ? 'جاري الحذف...' : 'نعم، احذف نهائياً'}
              </button>
            </div>
          </div>
        </Modal>
      )}

      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 28 }}>
        <div>
          <h1 style={{ fontSize: 24, fontWeight: 800, margin: 0 }}>المستأجرون</h1>
          <p style={{ color: 'var(--text-muted)', fontSize: 14, marginTop: 6 }}>{tenants.length} مستأجر مسجل</p>
        </div>
        <button className="btn-primary" onClick={() => setShowAdd(true)}>+ مستأجر جديد</button>
      </div>

      {/* Filters */}
      <div style={{ display: 'flex', gap: 14, marginBottom: 24 }}>
        <div style={{ flex: 1, position: 'relative' }}>
          <svg style={{ position: 'absolute', right: 14, top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} width="16" height="16" fill="none" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke="currentColor" strokeWidth="2"/><path d="M21 21l-4.35-4.35" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/></svg>
          <input className="input-dark" style={{ paddingRight: 42 }} placeholder="بحث بالاسم، السلاق، أو البريد..." value={search} onChange={e => setSearch(e.target.value)} />
        </div>
        <select className="input-dark" style={{ width: 160, appearance: 'none' }} value={planFilter} onChange={e => setPlanFilter(e.target.value)}>
          <option value="">كل الباقات</option>
          {plans.map((p: any) => (
            <option key={p.slug} value={p.slug}>{p.name}</option>
          ))}
        </select>
      </div>

      {/* Table */}
      <div className="glass" style={{ padding: 8 }}>
        {loading ? (
          <div style={{ padding: 40 }}>
            {[...Array(4)].map((_, i) => <div key={i} className="skeleton" style={{ height: 56, marginBottom: 8, borderRadius: 10 }} />)}
          </div>
        ) : tenants.length === 0 ? (
          <div style={{ textAlign: 'center', padding: 60, color: 'var(--text-muted)' }}>
            <div style={{ fontSize: 48, marginBottom: 12 }}>🏢</div>
            <p>لا يوجد مستأجرون مطابقون للبحث</p>
          </div>
        ) : (
          <table className="table-dark">
            <thead>
              <tr>
                <th>المستأجر</th>
                <th>الباقة</th>
                <th>المستخدمون</th>
                <th>العملاء</th>
                <th>المفاتيح</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              {tenants.map(t => (
                <tr key={t.id} style={{ cursor: 'default' }}>
                  <td>
                    <div style={{ fontWeight: 700, color: 'var(--text-primary)' }}>{t.name}</div>
                    <div style={{ fontSize: 12, color: 'var(--text-muted)', marginTop: 2 }}>{t.slug} {t.email ? `· ${t.email}` : ''}</div>
                  </td>
                  <td>
                    <span className={`badge-${t.plan}`} style={{ padding: '4px 10px', borderRadius: 6, fontSize: 12, fontWeight: 600 }}>
                      {plans.find((p: any) => p.slug === t.plan)?.name || t.plan}
                    </span>
                  </td>
                  <td style={{ color: 'var(--text-secondary)', fontWeight: 600 }}>{t.users_count ?? '—'}</td>
                  <td style={{ color: 'var(--text-secondary)', fontWeight: 600 }}>{t.clients_count ?? '—'}</td>
                  <td style={{ color: 'var(--text-secondary)', fontWeight: 600 }}>{t.api_keys_count ?? '—'}</td>
                  <td>
                    <button onClick={() => handleToggle(t)} disabled={actionLoading === t.id} style={{ padding: '5px 12px', borderRadius: 6, fontSize: 12, fontWeight: 600, border: 'none', cursor: 'pointer', fontFamily: 'inherit', transition: 'all 0.2s', ...(t.is_active ? { background: 'rgba(16,185,129,0.1)', color: '#10b981' } : { background: 'rgba(239,68,68,0.1)', color: '#ef4444' }) }}>
                      {t.is_active ? '● نشط' : '○ معطل'}
                    </button>
                  </td>
                  <td>
                    <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                      <button onClick={() => router.visit(`/super/tenants/${t.id}`)} style={{ padding: '6px 14px', borderRadius: 8, background: 'rgba(79,110,247,0.1)', color: '#4f6ef7', border: '1px solid rgba(79,110,247,0.2)', fontSize: 12, cursor: 'pointer', fontFamily: 'inherit', fontWeight: 600, whiteSpace: 'nowrap' }}>
                        إدارة
                      </button>
                      {t.slug !== 'default' && (
                        <button onClick={() => setToDelete(t)} className="btn-danger" style={{ padding: '6px 10px', fontSize: 12 }}>
                          <svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/></svg>
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}
TenantsPage.layout = (page: React.ReactNode) => <DashboardLayout children={page} />;
