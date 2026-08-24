'use client';

import { use, useEffect, useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import {
  fetchTenant, updateTenant, fetchApiKeys, createApiKey, toggleApiKey, deleteApiKey,
  fetchTenantUsers, type Tenant, type ApiKey, type User,
} from '@/lib/api';

const PLAN_LABELS: Record<string, string> = { basic: 'أساسي', pro: 'احترافي', enterprise: 'مؤسسي' };
const TABS = ['الإعدادات العامة', 'مفاتيح API', 'المستخدمون'] as const;
type Tab = typeof TABS[number];

// ── Copied key display helper ──────────────────────────────────────────────
function CopyKey({ value }: { value: string }) {
  const [copied, setCopied] = useState(false);
  return (
    <button onClick={async () => { await navigator.clipboard.writeText(value); setCopied(true); setTimeout(() => setCopied(false), 2000); }}
      style={{ background: 'var(--surface-3)', border: '1px solid var(--border)', borderRadius: 8, padding: '6px 14px', color: copied ? '#10b981' : 'var(--text-secondary)', fontSize: 12, cursor: 'pointer', fontFamily: 'monospace', maxWidth: 260, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', display: 'inline-flex', gap: 8, alignItems: 'center', transition: 'all 0.2s' }}>
      {copied ? '✓ تم النسخ' : '📋 ' + value.slice(0, 30) + '...'}
    </button>
  );
}

// ── API Keys Tab ───────────────────────────────────────────────────────────
function ApiKeysTab({ tenantId }: { tenantId: number }) {
  const [keys, setKeys] = useState<ApiKey[]>([]);
  const [loading, setLoading] = useState(true);
  const [newKey, setNewKey] = useState('');
  const [adding, setAdding] = useState(false);
  const [newKeyValue, setNewKeyValue] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try { setKeys(await fetchApiKeys(tenantId)); } finally { setLoading(false); }
  }, [tenantId]);

  useEffect(() => { load(); }, [load]);

  async function addKey() {
    if (!newKey.trim()) return;
    setAdding(true);
    try {
      const created = await createApiKey(tenantId, newKey);
      setNewKeyValue((created as { key: string }).key || '');
      setNewKey('');
      load();
    } finally { setAdding(false); }
  }

  return (
    <div>
      {/* API Keys Info Banner */}
      <div style={{ background: 'var(--accent-glow)', border: '1px solid var(--accent)', borderRadius: 14, padding: 20, marginBottom: 28, display: 'flex', gap: 18, alignItems: 'flex-start' }}>
        <div style={{ fontSize: 32, flexShrink: 0, opacity: 0.9 }}>🔗</div>
        <div>
          <h3 style={{ fontSize: 15, fontWeight: 700, color: 'var(--accent)', margin: '0 0 8px' }}>مفاتيح الربط البرمجي (API Keys)</h3>
          <p style={{ fontSize: 13, color: 'var(--text-secondary)', margin: 0, lineHeight: 1.7 }}>
            تُستخدم هذه المفاتيح لربط أنظمة خارجية (مثل موقع الشركة، المتاجر الإلكترونية، وصفحات الهبوط) بنظام CRM الخاص بهذا المستأجر. 
            عند إنشاء مفتاح ووضعه في الموقع الخارجي، سيتم استقبال العملاء المحتملين (Leads) مباشرة إلى حساب هذا المستأجر.
          </p>
          <div style={{ marginTop: 12, padding: '8px 14px', background: 'var(--surface-3)', borderRadius: 8, fontFamily: 'monospace', fontSize: 12, color: 'var(--accent-dark)', border: '1px solid var(--border)' }}>
            X-API-Key: wkl_xxxxxxxxxxxxxxxx...
          </div>
        </div>
      </div>

      {/* New revealed key */}
      {newKeyValue && (
        <div style={{ background: 'var(--green-glow)', border: '1px solid var(--green)', borderRadius: 14, padding: 20, marginBottom: 24, animation: 'fadeInUp 0.3s ease' }}>
          <div style={{ display: 'flex', gap: 12, alignItems: 'flex-start', marginBottom: 10 }}>
            <div style={{ fontSize: 22 }}>🔑</div>
            <div>
              <div style={{ fontSize: 14, fontWeight: 700, color: 'var(--green)', marginBottom: 4 }}>تم إنشاء المفتاح — انسخه الآن!</div>
              <div style={{ fontSize: 12, color: 'var(--text-muted)' }}>لن يُعرض هذا المفتاح مرة أخرى بعد مغادرة هذه الصفحة.</div>
            </div>
          </div>
          <div style={{ background: 'var(--surface-3)', border: '1px solid var(--border)', borderRadius: 10, padding: '12px 16px', fontFamily: 'monospace', fontSize: 13, color: 'var(--text-primary)', wordBreak: 'break-all', userSelect: 'all', cursor: 'text' }}>
            {newKeyValue}
          </div>
          <button onClick={async () => { await navigator.clipboard.writeText(newKeyValue); }} style={{ marginTop: 12, padding: '8px 18px', borderRadius: 8, background: 'var(--green)', color: 'white', border: 'none', fontSize: 13, cursor: 'pointer', fontFamily: 'inherit', fontWeight: 600 }}>
            نسخ المفتاح
          </button>
        </div>
      )}

      {/* Add key form */}
      <div className="glass-2" style={{ padding: 20, marginBottom: 24, display: 'flex', gap: 12, alignItems: 'center' }}>
        <input className="input-dark" style={{ flex: 1 }} placeholder='اسم المفتاح، مثل: "الموقع الرئيسي" أو "صفحة الهبوط"' value={newKey} onChange={e => setNewKey(e.target.value)} onKeyDown={e => e.key === 'Enter' && addKey()} />
        <button className="btn-primary" onClick={addKey} disabled={adding || !newKey.trim()} style={{ whiteSpace: 'nowrap' }}>
          {adding ? 'جاري الإنشاء...' : '+ مفتاح جديد'}
        </button>
      </div>

      {/* Keys list */}
      {loading ? (
        <div>{[...Array(2)].map((_, i) => <div key={i} className="skeleton" style={{ height: 64, borderRadius: 12, marginBottom: 10 }} />)}</div>
      ) : keys.length === 0 ? (
        <div style={{ textAlign: 'center', padding: 40, color: 'var(--text-muted)', fontSize: 14 }}>لا توجد مفاتيح بعد</div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
          {keys.map(k => (
            <div key={k.id} className="glass-2" style={{ padding: '16px 20px', display: 'flex', gap: 16, alignItems: 'center' }}>
              <div style={{ width: 10, height: 10, borderRadius: '50%', background: k.is_active ? '#10b981' : '#ef4444', flexShrink: 0 }} />
              <div style={{ flex: 1 }}>
                <div style={{ fontWeight: 700, fontSize: 14, color: 'var(--text-primary)', marginBottom: 4 }}>{k.name}</div>
                <CopyKey value={k.key} />
              </div>
              <div style={{ fontSize: 11, color: 'var(--text-muted)', textAlign: 'center' }}>
                {k.last_used_at ? `آخر استخدام: ${new Date(k.last_used_at).toLocaleDateString('ar-SA')}` : 'لم يُستخدم بعد'}
              </div>
              <div style={{ display: 'flex', gap: 8 }}>
                <button onClick={async () => { await toggleApiKey(tenantId, k.id); load(); }} style={{ padding: '6px 12px', borderRadius: 8, border: 'none', fontSize: 12, cursor: 'pointer', fontFamily: 'inherit', fontWeight: 600, ...(k.is_active ? { background: 'var(--red-glow)', color: 'var(--red)' } : { background: 'var(--green-glow)', color: 'var(--green)' }) }}>
                  {k.is_active ? 'تعطيل' : 'تفعيل'}
                </button>
                <button onClick={async () => { if (confirm('هل تريد حذف هذا المفتاح نهائياً؟')) { await deleteApiKey(tenantId, k.id); load(); } }} className="btn-danger" style={{ padding: '6px 10px', fontSize: 12 }}>
                  <svg width="13" height="13" fill="none" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/></svg>
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

// ── Settings Tab ───────────────────────────────────────────────────────────
function SettingsTab({ tenant }: { tenant: Tenant }) {
  const [form, setForm] = useState({
    name: tenant.name, email: tenant.email || '', phone: tenant.phone || '', plan: tenant.plan,
    whatsapp_provider: (tenant.settings?.whatsapp_provider as string) || '',
    whatsapp_api_key: (tenant.settings?.whatsapp_api_key as string) || '',
    whatsapp_phone_number: (tenant.settings?.whatsapp_phone_number as string) || '',
    whatsapp_webhook_secret: (tenant.settings?.whatsapp_webhook_secret as string) || '',
  });
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);

  function update(k: string, v: string) { setForm(f => ({ ...f, [k]: v })); }

  async function save() {
    setSaving(true);
    try {
      await updateTenant(tenant.id, {
        name: form.name, email: form.email, phone: form.phone, plan: form.plan,
        settings: {
          whatsapp_provider: form.whatsapp_provider,
          whatsapp_api_key: form.whatsapp_api_key,
          whatsapp_phone_number: form.whatsapp_phone_number,
          whatsapp_webhook_secret: form.whatsapp_webhook_secret,
        },
      });
      setSaved(true);
      setTimeout(() => setSaved(false), 2500);
    } finally { setSaving(false); }
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 24 }}>
      {/* Basic info */}
      <div className="glass-2" style={{ padding: 24 }}>
        <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--text-secondary)', margin: '0 0 20px', textTransform: 'uppercase', letterSpacing: 1, fontSize: 11 }}>المعلومات الأساسية</h3>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>اسم الشركة</label>
            <input className="input-dark" value={form.name} onChange={e => update('name', e.target.value)} />
          </div>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>الباقة</label>
            <select className="input-dark" value={form.plan} onChange={e => update('plan', e.target.value)} style={{ appearance: 'none' }}>
              {Object.entries(PLAN_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </select>
          </div>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>البريد الإلكتروني</label>
            <input className="input-dark" type="email" value={form.email} onChange={e => update('email', e.target.value)} />
          </div>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>رقم الهاتف</label>
            <input className="input-dark" value={form.phone} onChange={e => update('phone', e.target.value)} />
          </div>
        </div>
      </div>

      {/* WhatsApp Settings */}
      <div className="glass-2" style={{ padding: 24 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 20 }}>
          <span style={{ fontSize: 22, color: 'var(--accent)' }}><svg width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></span>
          <h3 style={{ fontSize: 14, fontWeight: 700, margin: 0, color: 'var(--text-primary)' }}>إعدادات مزود رسائل واتساب</h3>
        </div>
        <div style={{ background: 'var(--accent-glow)', border: '1px solid var(--accent)', borderRadius: 10, padding: '12px 16px', marginBottom: 20, fontSize: 13, color: 'var(--accent-dark)', lineHeight: 1.6 }}>
          هذه الإعدادات تربط المستأجر بمزود خدمة الواتساب لإرسال الإشعارات الفورية للعملاء الجدد.
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>رابط المزود الأساسي (Base URL)</label>
            <input className="input-dark" value={form.whatsapp_provider} onChange={e => update('whatsapp_provider', e.target.value)} placeholder="https://provider.wakeel.cc/api/v1" style={{ direction: 'ltr' }} />
          </div>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>رقم الهاتف (WhatsApp)</label>
            <input className="input-dark" value={form.whatsapp_phone_number} onChange={e => update('whatsapp_phone_number', e.target.value)} placeholder="+966xxxxxxxxx" style={{ direction: 'ltr' }} />
          </div>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>API Key الخاص بالمزود</label>
            <input className="input-dark" type="text" value={form.whatsapp_api_key} onChange={e => update('whatsapp_api_key', e.target.value)} placeholder="instance_key_..." style={{ direction: 'ltr', fontFamily: 'monospace' }} />
          </div>
          <div>
            <label style={{ fontSize: 12, color: 'var(--text-secondary)', display: 'block', marginBottom: 6 }}>Webhook Secret</label>
            <input className="input-dark" type="text" value={form.whatsapp_webhook_secret} onChange={e => update('whatsapp_webhook_secret', e.target.value)} placeholder="webhook_secret_..." style={{ direction: 'ltr', fontFamily: 'monospace' }} />
          </div>
        </div>
      </div>

      <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
        <button className="btn-primary" onClick={save} disabled={saving} style={{ minWidth: 140, justifyContent: 'center', padding: '12px 28px' }}>
          {saved ? '✓ تم الحفظ' : saving ? 'جاري الحفظ...' : 'حفظ التغييرات'}
        </button>
      </div>
    </div>
  );
}

// ── Users Tab ──────────────────────────────────────────────────────────────
function UsersTab({ tenantId }: { tenantId: number }) {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchTenantUsers(tenantId).then(setUsers).finally(() => setLoading(false));
  }, [tenantId]);

  return (
    <div>
      {loading ? (
        <div>{[...Array(3)].map((_, i) => <div key={i} className="skeleton" style={{ height: 56, borderRadius: 10, marginBottom: 10 }} />)}</div>
      ) : users.length === 0 ? (
        <div style={{ textAlign: 'center', padding: 60, color: 'var(--text-muted)' }}>لا يوجد مستخدمون</div>
      ) : (
        <table className="table-dark">
          <thead>
            <tr><th>المستخدم</th><th>البريد</th><th>الفريق</th><th>الدور</th><th>الحالة</th></tr>
          </thead>
          <tbody>
            {users.map(u => (
              <tr key={u.id}>
                <td style={{ fontWeight: 700 }}>{u.name}</td>
                <td style={{ color: 'var(--text-secondary)', fontFamily: 'monospace', fontSize: 12 }}>{u.email}</td>
                <td style={{ color: 'var(--text-secondary)' }}>{u.team?.name || '—'}</td>
                <td style={{ color: 'var(--text-secondary)' }}>{u.role?.name || '—'}</td>
                <td>
                  <span style={{ padding: '4px 10px', borderRadius: 6, fontSize: 12, fontWeight: 600, ...(u.is_active ? { background: 'var(--green-glow)', color: 'var(--green)' } : { background: 'var(--red-glow)', color: 'var(--red)' }) }}>
                    {u.is_active ? 'نشط' : 'معطل'}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}

// ── Main ───────────────────────────────────────────────────────────────────
export default function TenantDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params);
  const router = useRouter();
  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [activeTab, setActiveTab] = useState<Tab>('الإعدادات العامة');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchTenant(Number(id)).then(setTenant).finally(() => setLoading(false));
  }, [id]);

  if (loading) return (
    <div>
      <div className="skeleton" style={{ width: 250, height: 28, marginBottom: 8 }} />
      <div className="skeleton" style={{ width: 350, height: 18, marginBottom: 32 }} />
      <div className="glass" style={{ height: 400 }} />
    </div>
  );

  if (!tenant) return <div style={{ color: '#ef4444', padding: 40 }}>لم يتم العثور على المستأجر.</div>;

  return (
    <div>
      {/* Breadcrumb */}
      <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 24, color: 'var(--text-muted)', fontSize: 13 }}>
        <button onClick={() => router.push('/tenants')} style={{ background: 'none', border: 'none', color: 'var(--text-muted)', cursor: 'pointer', fontFamily: 'inherit', fontSize: 13, padding: 0 }}>المستأجرون</button>
        <span>/</span>
        <span style={{ color: 'var(--text-primary)', fontWeight: 600 }}>{tenant.name}</span>
      </div>

      {/* Header & Analytics */}
      <div className="glass" style={{ padding: 28, marginBottom: 28, borderTop: '4px solid var(--accent)' }}>
        <div style={{ display: 'flex', gap: 20, alignItems: 'center', flexWrap: 'wrap' }}>
          <div style={{ width: 64, height: 64, borderRadius: 16, background: 'var(--surface-3)', border: '1px solid var(--border)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--accent)', flexShrink: 0 }}>
            <svg width="32" height="32" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v1H9V7zm5 0h1v1h-1V7zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1zm-3 4H9v2h2v-2zm5-4h-2m2-4h-2"/></svg>
          </div>
          <div style={{ flex: 1, minWidth: 200 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 6 }}>
              <h1 style={{ fontSize: 24, fontWeight: 800, margin: 0 }}>{tenant.name}</h1>
              <span className={`badge-${tenant.plan}`} style={{ padding: '3px 10px', borderRadius: 6, fontSize: 12, fontWeight: 600 }}>{PLAN_LABELS[tenant.plan]}</span>
              <span className={tenant.is_active ? 'badge-active' : 'badge-inactive'} style={{ padding: '3px 10px', borderRadius: 6, fontSize: 12, fontWeight: 600 }}>
                {tenant.is_active ? 'نشط' : 'معطل'}
              </span>
            </div>
            <div style={{ fontSize: 13, color: 'var(--text-muted)', display: 'flex', gap: 12, alignItems: 'center' }}>
              <span>{tenant.slug}</span>
              {tenant.email && <span>· {tenant.email}</span>}
              <span>· أنشئ في {new Date(tenant.created_at).toLocaleDateString('ar-SA')}</span>
            </div>
          </div>
          <div style={{ display: 'flex', gap: 24, textAlign: 'center', background: 'var(--surface-2)', padding: '16px 24px', borderRadius: 16, border: '1px solid var(--border)' }}>
            {[['المستخدمون', tenant.users_count], ['العملاء', tenant.clients_count], ['المفاتيح', tenant.api_keys_count]].map(([l, v]) => (
              <div key={l as string}>
                <div style={{ fontSize: 24, fontWeight: 800, color: 'var(--text-primary)' }}>{v ?? '—'}</div>
                <div style={{ fontSize: 12, color: 'var(--text-secondary)', marginTop: 4, fontWeight: 600 }}>{l}</div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div style={{ display: 'flex', gap: 4, marginBottom: 24, background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 12, padding: 6 }}>
        {TABS.map(tab => (
          <button key={tab} onClick={() => setActiveTab(tab)} style={{ flex: 1, padding: '10px 20px', borderRadius: 8, border: 'none', cursor: 'pointer', fontFamily: 'inherit', fontSize: 14, fontWeight: activeTab === tab ? 700 : 500, transition: 'all 0.2s', ...(activeTab === tab ? { background: 'var(--surface-2)', color: 'var(--accent)', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' } : { background: 'transparent', color: 'var(--text-secondary)' }) }}>
            {tab}
          </button>
        ))}
      </div>

      {/* Tab content */}
      <div style={{ animation: 'fadeInUp 0.3s ease' }}>
        {activeTab === 'الإعدادات العامة' && <SettingsTab tenant={tenant} />}
        {activeTab === 'مفاتيح API' && <ApiKeysTab tenantId={tenant.id} />}
        {activeTab === 'المستخدمون' && <UsersTab tenantId={tenant.id} />}
      </div>
    </div>
  );
}
