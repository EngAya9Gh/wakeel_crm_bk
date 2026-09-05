import { router } from '@inertiajs/react';
import DashboardLayout from '../Layouts/DashboardLayout';

export default function PlansIndex({ plans }: { plans: any[] }) {
  return (
    <div>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 28 }}>
        <div>
          <h1 style={{ fontSize: 24, fontWeight: 800, margin: 0 }}>الباقات</h1>
          <p style={{ color: 'var(--text-muted)', fontSize: 14, marginTop: 6 }}>{plans.length} باقة مسجلة</p>
        </div>
        <button className="btn-primary" onClick={() => router.visit('/super/plans/create')}>+ باقة جديدة</button>
      </div>

      <div className="glass" style={{ padding: 8 }}>
        {plans.length === 0 ? (
          <div style={{ textAlign: 'center', padding: 60, color: 'var(--text-muted)' }}>
            <div style={{ fontSize: 48, marginBottom: 12 }}>📦</div>
            <p>لا توجد باقات حالياً</p>
          </div>
        ) : (
          <table className="table-dark">
            <thead>
              <tr>
                <th>اسم الباقة</th>
                <th>المعرف (Slug)</th>
                <th>السعر</th>
                <th>الميزات</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
              </tr>
            </thead>
            <tbody>
              {plans.map(p => (
                <tr key={p.id}>
                  <td>
                    <div style={{ fontWeight: 700, color: 'var(--text-primary)' }}>{p.name}</div>
                  </td>
                  <td style={{ color: 'var(--text-secondary)' }}>{p.slug}</td>
                  <td style={{ color: 'var(--text-secondary)' }}>{p.price}</td>
                  <td style={{ color: 'var(--text-secondary)' }}>
                    {p.modules && p.modules.length > 0 ? (
                      <div style={{ display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                        {p.modules.map((m: string) => (
                          <span key={m} style={{ background: 'rgba(255,255,255,0.1)', padding: '2px 6px', borderRadius: 4, fontSize: 11 }}>
                            {m}
                          </span>
                        ))}
                      </div>
                    ) : (
                      'لا توجد ميزات'
                    )}
                  </td>
                  <td>
                    <span style={{ padding: '5px 12px', borderRadius: 6, fontSize: 12, fontWeight: 600, ...(p.is_active ? { background: 'rgba(16,185,129,0.1)', color: '#10b981' } : { background: 'rgba(239,68,68,0.1)', color: '#ef4444' }) }}>
                      {p.is_active ? '● نشط' : '○ معطل'}
                    </span>
                  </td>
                  <td>
                    <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                      <button onClick={() => router.visit(`/super/plans/${p.id}/edit`)} style={{ padding: '6px 14px', borderRadius: 8, background: 'rgba(79,110,247,0.1)', color: '#4f6ef7', border: '1px solid rgba(79,110,247,0.2)', fontSize: 12, cursor: 'pointer', fontFamily: 'inherit', fontWeight: 600 }}>
                        تعديل
                      </button>
                      <button onClick={() => {
                        if (confirm('هل أنت متأكد من حذف هذه الباقة؟')) {
                          router.delete(`/super/plans/${p.id}`);
                        }
                      }} className="btn-danger" style={{ padding: '6px 10px', fontSize: 12 }}>
                        حذف
                      </button>
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
PlansIndex.layout = (page: React.ReactNode) => <DashboardLayout children={page} />;
