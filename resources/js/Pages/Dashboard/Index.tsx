'use client';

import { useEffect, useState } from 'react';
import { fetchStats, type Stats } from '@/lib/api';
import DashboardLayout from '../../Layouts/DashboardLayout';

function StatCard({ label, value, sub, color, icon }: {
  label: string; value: number; sub?: string; color: string; icon: React.ReactNode;
}) {
  return (
    <div className="glass" style={{ padding: 24, display: 'flex', gap: 20, alignItems: 'center', animation: 'fadeInUp 0.4s ease forwards' }}>
      <div style={{ width: 56, height: 56, borderRadius: 16, background: `${color}15`, display: 'flex', alignItems: 'center', justifyContent: 'center', color: color, flexShrink: 0, border: `1px solid ${color}30` }}>
        {icon}
      </div>
      <div>
        <div style={{ fontSize: 28, fontWeight: 800, color: 'var(--text-primary)', lineHeight: 1 }}>{value.toLocaleString('ar-SA')}</div>
        <div style={{ fontSize: 13, color: 'var(--text-secondary)', marginTop: 6 }}>{label}</div>
        {sub && <div style={{ fontSize: 12, fontWeight: 600, color: color, marginTop: 4 }}>{sub}</div>}
      </div>
    </div>
  );
}

const PLAN_LABELS: Record<string, string> = { basic: 'أساسي', pro: 'احترافي', enterprise: 'مؤسسي' };
const PLAN_COLORS: Record<string, string> = { basic: '#f59e0b', pro: '#F26522', enterprise: '#3D3D3D' };

export default function DashboardPage() {
  const [stats, setStats] = useState<Stats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats().then(setStats).finally(() => setLoading(false));
  }, []);

  if (loading) return (
    <div>
      <div style={{ marginBottom: 28 }}>
        <div className="skeleton" style={{ width: 200, height: 28, marginBottom: 8 }} />
        <div className="skeleton" style={{ width: 300, height: 18 }} />
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 20 }}>
        {[...Array(4)].map((_, i) => <div key={i} className="skeleton" style={{ height: 100, borderRadius: 16 }} />)}
      </div>
    </div>
  );

  return (
    <div>
      {/* Header */}
      <div style={{ marginBottom: 32 }}>
        <h1 style={{ fontSize: 24, fontWeight: 800, margin: 0 }}>لوحة المعلومات</h1>
        <p style={{ color: 'var(--text-muted)', fontSize: 14, marginTop: 6 }}>نظرة عامة على النظام</p>
      </div>

      {/* Stats Grid */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: 24, marginBottom: 32 }}>
        <StatCard 
          label="إجمالي المستأجرين" 
          value={stats?.tenants.total || 0} 
          sub={`${stats?.tenants.active} نشط`} 
          color="#F26522" 
          icon={<svg width="28" height="28" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v1H9V7zm5 0h1v1h-1V7zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1zm-3 4H9v2h2v-2zm5-4h-2m2-4h-2"/></svg>} 
        />
        <StatCard 
          label="إجمالي المستخدمين" 
          value={stats?.users.total || 0} 
          sub={`${stats?.users.active} نشط`} 
          color="#3D3D3D" 
          icon={<svg width="28" height="28" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>} 
        />
        <StatCard 
          label="إجمالي العملاء" 
          value={stats?.clients.total || 0} 
          color="#10b981" 
          icon={<svg width="28" height="28" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>} 
        />
        <StatCard 
          label="إجمالي الفواتير" 
          value={stats?.invoices.total || 0} 
          color="#f59e0b" 
          icon={<svg width="28" height="28" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>} 
        />
      </div>

      {/* Plans distribution */}
      <div className="glass" style={{ padding: 28 }}>
        <h2 style={{ fontSize: 16, fontWeight: 700, margin: '0 0 24px', color: 'var(--text-primary)' }}>
          توزيع المستأجرين حسب الباقة
        </h2>
        <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap' }}>
          {Object.entries(stats?.tenants_by_plan || {}).map(([plan, count]) => (
            <div key={plan} style={{ flex: 1, minWidth: 140, padding: '20px 24px', borderRadius: 14, background: `${PLAN_COLORS[plan] || '#888'}15`, border: `1px solid ${PLAN_COLORS[plan] || '#888'}30`, textAlign: 'center' }}>
              <div style={{ fontSize: 32, fontWeight: 800, color: PLAN_COLORS[plan] || '#888' }}>{count}</div>
              <div style={{ fontSize: 13, color: 'var(--text-secondary)', marginTop: 4 }}>{PLAN_LABELS[plan] || plan}</div>
            </div>
          ))}
          {Object.keys(stats?.tenants_by_plan || {}).length === 0 && (
            <p style={{ color: 'var(--text-muted)', fontSize: 14 }}>لا توجد بيانات</p>
          )}
        </div>
      </div>
    </div>
  );
}

DashboardPage.layout = (page: React.ReactNode) => <DashboardLayout children={page} />;
