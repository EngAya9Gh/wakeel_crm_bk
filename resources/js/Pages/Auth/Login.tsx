'use client';

import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

export default function LoginPage() {
  const { errors } = usePage().props;
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);

    router.post('/super/login', { email, password }, {
      onError: (errors) => {
        setError(errors.email || 'خطأ في بيانات الدخول');
        setLoading(false);
      },
      onSuccess: () => {
        // Successful login will automatically redirect via Laravel
      }
    });
  }

  return (
    <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 24 }}>
      {/* Background with Brand Colors */}
      <div style={{ position: 'fixed', inset: 0, overflow: 'hidden', zIndex: 0 }}>
        <div style={{ position: 'absolute', top: '-20%', right: '-10%', width: 600, height: 600, borderRadius: '50%', background: 'radial-gradient(circle, rgba(242,101,34,0.12), transparent 70%)' }} />
        <div style={{ position: 'absolute', bottom: '-10%', left: '-10%', width: 500, height: 500, borderRadius: '50%', background: 'radial-gradient(circle, rgba(212,85,26,0.1), transparent 70%)' }} />
      </div>

      <div style={{ position: 'relative', zIndex: 1, width: '100%', maxWidth: 440 }}>
        {/* Logo and Title */}
        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', marginBottom: 40 }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', width: 80, height: 80, borderRadius: 20, background: 'var(--surface-3)', border: '1px solid var(--border)', marginBottom: 20, boxShadow: '0 0 40px var(--accent-glow)' }}>
            <div className="brand-icon" title="Wakeel" style={{ width: 48, height: 48 }} />
          </div>
          <div style={{ display: 'flex', justifyContent: 'center', width: '100%' }}>
            <div className="brand-logo" title="Wakeel CRM" style={{ width: '100%', height: 32 }} />
          </div>
          <p style={{ color: 'var(--text-muted)', fontSize: 13, marginTop: 10, letterSpacing: '0.5px', textAlign: 'center' }}>لوحة الإدارة العليا</p>
        </div>

        {/* Card with Orange Lip */}
        <div className="glass" style={{ padding: 36, borderTop: '4px solid #F26522', borderRadius: '16px' }}>
          <h2 style={{ fontSize: 18, fontWeight: 700, margin: '0 0 24px', color: 'var(--text-primary)' }}>
            تسجيل الدخول
          </h2>

          {error && (
            <div style={{ background: 'rgba(239,68,68,0.1)', border: '1px solid rgba(239,68,68,0.25)', borderRadius: 10, padding: '12px 16px', marginBottom: 20, color: '#ef4444', fontSize: 14 }}>
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit}>
            <div style={{ marginBottom: 18 }}>
              <label style={{ display: 'block', fontSize: 13, color: 'var(--text-secondary)', marginBottom: 8, fontWeight: 500 }}>البريد الإلكتروني</label>
              <input
                className="input-dark"
                type="email"
                value={email}
                onChange={e => setEmail(e.target.value)}
                placeholder="superadmin@wakeel.system"
                required
              />
            </div>

            <div style={{ marginBottom: 28 }}>
              <label style={{ display: 'block', fontSize: 13, color: 'var(--text-secondary)', marginBottom: 8, fontWeight: 500 }}>كلمة المرور</label>
              <input
                className="input-dark"
                type="password"
                value={password}
                onChange={e => setPassword(e.target.value)}
                placeholder="••••••••"
                required
              />
            </div>

            <button className="btn-primary" type="submit" style={{ width: '100%', justifyContent: 'center', padding: '13px 20px', fontSize: 15 }} disabled={loading}>
              {loading ? (
                <>
                  <span style={{ width: 16, height: 16, border: '2px solid rgba(255,255,255,0.3)', borderTopColor: 'white', borderRadius: '50%', display: 'inline-block', animation: 'spin 0.8s linear infinite' }} />
                  جاري الدخول...
                </>
              ) : (
                <>
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/></svg>
                  دخول
                </>
              )}
            </button>
          </form>
        </div>
      </div>

      <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
    </div>
  );
}
