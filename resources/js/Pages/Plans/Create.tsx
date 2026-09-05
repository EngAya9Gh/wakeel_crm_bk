import { useState } from 'react';
import { router } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

export default function PlansCreate({ availableModules }: { availableModules: Record<string, any> }) {
  const [form, setForm] = useState({
    name: '',
    slug: '',
    price: 0,
    description: '',
    modules: [] as string[],
    is_active: true,
    sort_order: 0
  });

  const [loading, setLoading] = useState(false);

  const toggleModule = (modKey: string) => {
    setForm(f => {
      const isSelected = f.modules.includes(modKey);
      if (isSelected) {
        return { ...f, modules: f.modules.filter(m => m !== modKey) };
      } else {
        return { ...f, modules: [...f.modules, modKey] };
      }
    });
  };

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    router.post('/super/plans', form, {
      onFinish: () => setLoading(false),
    });
  };

  return (
    <div>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 28 }}>
        <div>
          <h1 style={{ fontSize: 24, fontWeight: 800, margin: 0 }}>إنشاء باقة جديدة</h1>
        </div>
        <button className="btn-ghost" onClick={() => window.history.back()}>رجوع</button>
      </div>

      <div className="glass" style={{ padding: 24, maxWidth: 800 }}>
        <form onSubmit={submit} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          <div style={{ display: 'flex', gap: 16 }}>
            <div style={{ flex: 1 }}>
              <label style={{ display: 'block', marginBottom: 6, fontSize: 13, color: 'var(--text-secondary)' }}>اسم الباقة</label>
              <input required className="input-dark" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} placeholder="مثال: الأساسية" />
            </div>
            <div style={{ flex: 1 }}>
              <label style={{ display: 'block', marginBottom: 6, fontSize: 13, color: 'var(--text-secondary)' }}>المعرف (Slug)</label>
              <input className="input-dark" value={form.slug} onChange={e => setForm({ ...form, slug: e.target.value })} placeholder="اختياري (basic)" />
            </div>
          </div>

          <div style={{ display: 'flex', gap: 16 }}>
            <div style={{ flex: 1 }}>
              <label style={{ display: 'block', marginBottom: 6, fontSize: 13, color: 'var(--text-secondary)' }}>السعر</label>
              <input type="number" min="0" step="0.01" className="input-dark" value={form.price} onChange={e => setForm({ ...form, price: parseFloat(e.target.value) || 0 })} />
            </div>
            <div style={{ flex: 1 }}>
              <label style={{ display: 'block', marginBottom: 6, fontSize: 13, color: 'var(--text-secondary)' }}>الترتيب</label>
              <input type="number" className="input-dark" value={form.sort_order} onChange={e => setForm({ ...form, sort_order: parseInt(e.target.value) || 0 })} />
            </div>
          </div>

          <div>
            <label style={{ display: 'block', marginBottom: 6, fontSize: 13, color: 'var(--text-secondary)' }}>الوصف</label>
            <textarea className="input-dark" style={{ minHeight: 80 }} value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} />
          </div>

          <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', marginTop: 8 }}>
            <input type="checkbox" checked={form.is_active} onChange={e => setForm({ ...form, is_active: e.target.checked })} style={{ width: 16, height: 16 }} />
            <span style={{ fontSize: 14 }}>باقة نشطة (متاحة للاختيار)</span>
          </label>

          <hr style={{ borderColor: 'rgba(255,255,255,0.1)', margin: '16px 0' }} />

          <div>
            <h3 style={{ fontSize: 16, marginBottom: 12 }}>الميزات המتاحة (Modules)</h3>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12 }}>
              {Object.entries(availableModules).map(([key, mod]: [string, any]) => {
                if (mod.is_core) return null; // Core features are always enabled, no need to show them here, or we can show them disabled
                return (
                  <label key={key} style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '12px 16px', background: 'rgba(255,255,255,0.05)', borderRadius: 8, cursor: 'pointer', width: 'calc(50% - 6px)' }}>
                    <input 
                      type="checkbox" 
                      checked={form.modules.includes(key)} 
                      onChange={() => toggleModule(key)} 
                      style={{ width: 18, height: 18 }} 
                    />
                    <div>
                      <div style={{ fontWeight: 600 }}>{mod.name}</div>
                      <div style={{ fontSize: 12, color: 'var(--text-muted)' }}>{mod.description}</div>
                    </div>
                  </label>
                );
              })}
            </div>
          </div>

          <div style={{ marginTop: 24, textAlign: 'left' }}>
            <button type="submit" className="btn-primary" disabled={loading} style={{ padding: '12px 32px' }}>
              {loading ? 'جاري الحفظ...' : 'حفظ الباقة'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
PlansCreate.layout = (page: React.ReactNode) => <DashboardLayout children={page} />;
