import { useState } from 'react';
import { router } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

export default function PlansEdit({ plan, availableModules }: { plan: any, availableModules: Record<string, any> }) {
  const [form, setForm] = useState({
    name: plan.name || '',
    slug: plan.slug || '',
    price: plan.price || 0,
    description: plan.description || '',
    modules: plan.modules || [],
    is_active: plan.is_active,
    sort_order: plan.sort_order || 0
  });

  const [loading, setLoading] = useState(false);

  const toggleModule = (modKey: string) => {
    setForm(f => {
      const isSelected = f.modules.includes(modKey);
      if (isSelected) {
        return { ...f, modules: f.modules.filter((m: string) => m !== modKey) };
      } else {
        return { ...f, modules: [...f.modules, modKey] };
      }
    });
  };

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    router.put(`/super/plans/${plan.id}`, form, {
      onFinish: () => setLoading(false),
    });
  };

  return (
    <div>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 28 }}>
        <div>
          <h1 style={{ fontSize: 24, fontWeight: 800, margin: 0 }}>تعديل باقة: {plan.name}</h1>
        </div>
        <button className="btn-ghost" onClick={() => window.history.back()}>رجوع</button>
      </div>

      <div className="glass" style={{ padding: 24, maxWidth: 800 }}>
        <form onSubmit={submit} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          <div style={{ display: 'flex', gap: 16 }}>
            <div style={{ flex: 1 }}>
              <label style={{ display: 'block', marginBottom: 6, fontSize: 13, color: 'var(--text-secondary)' }}>اسم الباقة</label>
              <input required className="input-dark" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} />
            </div>
            <div style={{ flex: 1 }}>
              <label style={{ display: 'block', marginBottom: 6, fontSize: 13, color: 'var(--text-secondary)' }}>المعرف (Slug)</label>
              <input required className="input-dark" value={form.slug} onChange={e => setForm({ ...form, slug: e.target.value })} />
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
            <h3 style={{ fontSize: 16, marginBottom: 12 }}>الميزات المتاحة (Modules)</h3>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12 }}>
              {Object.entries(availableModules).map(([key, mod]: [string, any]) => {
                const isCore = mod.is_core === true;
                return (
                  <label key={key} style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '12px 16px', background: isCore ? 'rgba(255,255,255,0.02)' : 'rgba(255,255,255,0.05)', borderRadius: 8, cursor: isCore ? 'default' : 'pointer', width: 'calc(50% - 6px)', opacity: isCore ? 0.7 : 1 }}>
                    <input 
                      type="checkbox" 
                      checked={isCore ? true : form.modules.includes(key)} 
                      onChange={() => { if (!isCore) toggleModule(key); }} 
                      disabled={isCore}
                      style={{ width: 18, height: 18, cursor: isCore ? 'default' : 'pointer' }} 
                    />
                    <div>
                      <div style={{ fontWeight: 600 }}>{mod.name_ar} {isCore && <span style={{ fontSize: 10, background: 'rgba(255,255,255,0.1)', padding: '2px 6px', borderRadius: 4, marginRight: 6 }}>أساسي</span>}</div>
                      <div style={{ fontSize: 12, color: 'var(--text-muted)' }}>{mod.name_en}</div>
                    </div>
                  </label>
                );
              })}
            </div>
          </div>

          <div style={{ marginTop: 24, textAlign: 'left' }}>
            <button type="submit" className="btn-primary" disabled={loading} style={{ padding: '12px 32px' }}>
              {loading ? 'جاري الحفظ...' : 'حفظ التعديلات'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
PlansEdit.layout = (page: React.ReactNode) => <DashboardLayout children={page} />;
