'use client';

import { useState } from 'react';
import { login } from '@/lib/api';
import { useRouter } from 'next/navigation';

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const data = await login(email, password);
      if (!data.user || !(data.user as { is_super_admin?: boolean }).is_super_admin) {
        setError('هذه اللوحة مخصصة لمديري النظام فقط.');
        return;
      }
      localStorage.setItem('super_token', data.access_token);
      localStorage.setItem('super_user', JSON.stringify(data.user));
      router.push('/dashboard');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'خطأ في بيانات الدخول');
    } finally {
      setLoading(false);
    }
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
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', width: 80, height: 80, borderRadius: 20, background: '#1c1c1c', border: '1px solid rgba(242,101,34,0.25)', marginBottom: 20, boxShadow: '0 0 40px rgba(242,101,34,0.2)' }}>
            <img src="/icon.png" alt="Wakeel" style={{ width: 48, height: 48, objectFit: 'contain' }} />
          </div>
          <div style={{ display: 'flex', justifyContent: 'center', width: '100%' }}>
            <img src="/logo.png" alt="Wakeel CRM" style={{ height: 32, objectFit: 'contain', filter: 'brightness(0) invert(1)' }} />
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
