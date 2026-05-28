/* Tirmongkol — Homework Scanner app UI kit: shell + primitives.
   Recreated from resources/views/{layouts/navigation, classrooms, assignments, auth}.
   Exports to window. Depends on window.Icon (icons.jsx). */
(function () {
  const { useState } = React;
  const Icon = window.Icon;

  const T = {
    brand500: '#3366ff', brand600: '#1f47e6', brand700: '#1936b8', brand800: '#1a3093',
    brand50: '#eef4ff', brand100: '#d9e6ff',
    ink: '#0f172a', body: '#475569', meta: '#64748b', faint: '#94a3b8',
    line: '#e2e8f0', line2: '#f1f5f9', bg: '#f8fafc', s50: '#f8fafc', s100: '#f1f5f9', s900: '#0f172a',
    font: "'Inter','Noto Sans Thai',system-ui,sans-serif",
    mono: "ui-monospace,Menlo,Consolas,monospace",
    gradLogo: 'linear-gradient(135deg,#3366ff,#1936b8)',
    gradAction: 'linear-gradient(135deg,#1f47e6,#1a3093)',
  };
  window.AT = T;

  const CATEGORY = {
    homework:  ['#eef4ff', '#1936b8'],
    classwork: ['#f0f9ff', '#0369a1'],
    quiz:      ['#fffbeb', '#b45309'],
    exam:      ['#fff1f2', '#be123c'],
    project:   ['#ecfdf5', '#047857'],
    other:     ['#f1f5f9', '#475569'],
  };
  window.CATEGORY = CATEGORY;

  function CategoryBadge({ category }) {
    const [bg, fg] = CATEGORY[category] || CATEGORY.other;
    const label = category.charAt(0).toUpperCase() + category.slice(1);
    return <span style={{ background: bg, color: fg, fontSize: 11, fontWeight: 600, padding: '2px 9px', borderRadius: 9999 }}>{label}</span>;
  }

  // ---- App button ----
  function AButton({ children, variant = 'brand', size = 'md', icon, iconLeft, onClick, style }) {
    const [h, setH] = useState(false);
    const base = {
      display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: 7,
      borderRadius: variant === 'brand' || variant === 'dark' ? 6 : 6, fontWeight: 600, cursor: 'pointer',
      fontFamily: T.font, transition: 'all .15s', border: '1px solid transparent', whiteSpace: 'nowrap',
      padding: size === 'sm' ? '6px 12px' : size === 'lg' ? '13px 20px' : '8px 16px',
      fontSize: size === 'sm' ? 13 : 14,
    };
    const variants = {
      brand: { background: h ? T.brand700 : T.brand600, color: '#fff' },
      dark:  { background: h ? '#1e293b' : T.s900, color: '#fff' },
      ghost: { background: h ? T.s50 : '#fff', color: '#334155', borderColor: '#cbd5e1' },
      subtle:{ background: h ? T.s100 : 'transparent', color: T.body },
    };
    return <button onClick={onClick} onMouseEnter={() => setH(true)} onMouseLeave={() => setH(false)} style={{ ...base, ...variants[variant], ...style }}>
      {iconLeft && <Icon name={iconLeft} size={15} stroke={2} />}{children}{icon && <Icon name={icon} size={15} stroke={2.5} />}</button>;
  }

  // ---- Card ----
  function Card({ children, style, pad = 0 }) {
    return <div style={{ background: '#fff', borderRadius: 16, border: `1px solid ${T.line}`, boxShadow: '0 1px 2px rgba(0,0,0,.05)', padding: pad, ...style }}>{children}</div>;
  }

  // ---- Stat card (icon tile + label + number) ----
  function StatCard({ icon, tint = 'brand', label, value }) {
    const tints = { brand: ['#eef4ff', '#1f47e6'], amber: ['#fffbeb', '#d97706'], emerald: ['#ecfdf5', '#059669'] };
    const [bg, fg] = tints[tint];
    return <Card style={{ padding: 20 }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
        <span style={{ display: 'grid', placeItems: 'center', width: 40, height: 40, borderRadius: 12, background: bg, color: fg, flexShrink: 0 }}><Icon name={icon} size={20} stroke={2} /></span>
        <div>
          <div style={{ fontSize: 11, textTransform: 'uppercase', letterSpacing: '.08em', color: T.meta }}>{label}</div>
          <div style={{ fontSize: 24, fontWeight: 700, color: T.ink, fontVariantNumeric: 'tabular-nums', lineHeight: 1.1, marginTop: 2 }}>{value}</div>
        </div>
      </div>
    </Card>;
  }

  // ---- Top navigation (in-app) ----
  function TopNav({ user, onHome, active = 'classrooms' }) {
    const [menu, setMenu] = useState(false);
    const initials = user.name.split(' ').filter(Boolean).slice(0, 2).map(s => s[0].toUpperCase()).join('');
    return <nav style={{ position: 'sticky', top: 0, zIndex: 30, borderBottom: `1px solid ${T.line}`, background: 'rgba(255,255,255,.95)', backdropFilter: 'blur(8px)' }}>
      <div style={{ maxWidth: 1120, margin: '0 auto', padding: '0 24px', height: 64, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 24 }}>
          <span onClick={onHome} style={{ display: 'flex', alignItems: 'center', gap: 9, cursor: 'pointer' }}>
            <span style={{ display: 'grid', placeItems: 'center', width: 36, height: 36, borderRadius: 11, background: T.gradLogo, color: '#fff', fontWeight: 800, fontSize: 18, boxShadow: '0 6px 12px -3px rgba(51,102,255,.3)' }}>T</span>
            <span style={{ fontSize: 14, fontWeight: 600, color: T.ink }}>Tirmongkol Service</span>
          </span>
          <span style={{ background: active === 'classrooms' ? T.s900 : 'transparent', color: active === 'classrooms' ? '#fff' : T.body, borderRadius: 9999, padding: '6px 16px', fontSize: 14, fontWeight: 500, cursor: 'pointer' }} onClick={onHome}>My Classrooms</span>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, position: 'relative' }}>
          <span style={{ border: `1px solid ${T.line}`, color: T.body, borderRadius: 9999, padding: '5px 11px', fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.1em', cursor: 'pointer' }}>TH</span>
          <button onClick={() => setMenu(m => !m)} style={{ display: 'flex', alignItems: 'center', gap: 8, border: `1px solid ${T.line}`, background: '#fff', borderRadius: 9999, padding: '4px 12px 4px 4px', cursor: 'pointer', fontFamily: T.font }}>
            <span style={{ display: 'grid', placeItems: 'center', width: 28, height: 28, borderRadius: '50%', background: T.gradLogo, color: '#fff', fontSize: 12, fontWeight: 700 }}>{initials}</span>
            <span style={{ fontSize: 14, fontWeight: 500, color: '#334155' }}>{user.name}</span>
            <Icon name="chevron-down" size={14} style={{ color: T.faint }} />
          </button>
          {menu && <div style={{ position: 'absolute', right: 0, top: 48, width: 224, background: '#fff', border: `1px solid ${T.line}`, borderRadius: 12, boxShadow: '0 10px 25px -5px rgba(15,23,42,.15)', overflow: 'hidden', zIndex: 40 }}>
            <div style={{ padding: '12px 16px', borderBottom: `1px solid ${T.line2}` }}>
              <div style={{ fontSize: 14, fontWeight: 500, color: T.ink }}>{user.name}</div>
              <div style={{ fontSize: 12, color: T.meta }}>{user.email}</div>
            </div>
            {['Profile', 'Manage workspaces', 'Plans'].map(i => <div key={i} style={{ padding: '8px 16px', fontSize: 14, color: '#334155', cursor: 'pointer' }} onMouseDown={e => e.preventDefault()}>{i}</div>)}
            <div style={{ padding: '8px 16px', fontSize: 14, color: '#e11d48', cursor: 'pointer', borderTop: `1px solid ${T.line2}` }}>Log Out</div>
          </div>}
        </div>
      </div>
    </nav>;
  }

  // ---- Page header band ----
  function PageHeader({ children }) {
    return <div style={{ background: '#fff', borderBottom: `1px solid ${T.line}` }}>
      <div style={{ maxWidth: 1120, margin: '0 auto', padding: '20px 24px' }}>{children}</div>
    </div>;
  }

  function Toast({ kind = 'success', children }) {
    const map = { success: ['#ecfdf5', '#047857', '#a7f3d0'], error: ['#fff1f2', '#be123c', '#fecdd3'] };
    const [bg, fg, ring] = map[kind];
    return <div style={{ display: 'flex', alignItems: 'center', gap: 10, background: bg, color: fg, boxShadow: `inset 0 0 0 1px ${ring}`, borderRadius: 10, padding: '10px 14px', fontSize: 14, fontWeight: 500 }}>
      <Icon name={kind === 'success' ? 'check' : 'x'} size={16} stroke={3} />{children}</div>;
  }

  Object.assign(window, { CategoryBadge, AButton, Card, StatCard, TopNav, PageHeader, Toast });
})();
