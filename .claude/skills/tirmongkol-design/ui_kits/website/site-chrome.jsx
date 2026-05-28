/* Tirmongkol — website UI kit components.
   Faithful recreation of the marketing site chrome + reusable bits.
   Exports to window for cross-file use. Depends on window.Icon (icons.jsx). */
(function () {
  const { useState, useEffect } = React;
  const Icon = window.Icon;

  // ---- Design tokens (mirror colors_and_type.css) ----
  const T = {
    brand500: '#3366ff', brand600: '#1f47e6', brand700: '#1936b8', brand900: '#1c2e74',
    brand50: '#eef4ff', brand100: '#d9e6ff', brand200: '#bcd3ff', brand300: '#8eb6ff',
    ink: '#0f172a', body: '#475569', meta: '#64748b', faint: '#94a3b8',
    line: '#e2e8f0', bg: '#f8fafc', s100: '#f1f5f9', s900: '#0f172a', s950: '#020617',
    font: "'Inter','Noto Sans Thai',system-ui,sans-serif",
    gradLogo: 'linear-gradient(135deg,#3366ff,#1936b8)',
    gradCta: 'linear-gradient(135deg,#1f47e6,#1936b8 50%,#1c2e74)',
    radCard: 16, radFeature: 24,
  };
  window.T = T;

  const GRID_BG = {
    backgroundImage:
      'linear-gradient(to right, rgba(255,255,255,.18) 1px, transparent 1px),' +
      'linear-gradient(to bottom, rgba(255,255,255,.18) 1px, transparent 1px)',
    backgroundSize: '32px 32px',
  };
  window.GRID_BG = GRID_BG;

  // ---- Logo lockup ----
  function Logo({ light = false, size = 36 }) {
    return (
      <span style={{ display: 'inline-flex', alignItems: 'center', gap: 9 }}>
        <span style={{
          display: 'grid', placeItems: 'center', width: size, height: size,
          borderRadius: size * 0.31, background: T.gradLogo, color: '#fff',
          fontWeight: 800, fontSize: size * 0.5,
          boxShadow: '0 8px 16px -4px rgba(51,102,255,.4)',
        }}>T</span>
        <span style={{
          fontSize: 15, fontWeight: 600, letterSpacing: '-0.01em',
          color: light ? '#fff' : T.ink,
          textShadow: light ? '0 1px 6px rgba(0,0,0,.25)' : 'none',
        }}>Tirmongkol Service</span>
      </span>
    );
  }

  // ---- Eyebrow ----
  function Eyebrow({ children, light = false }) {
    return <span style={{
      fontSize: 12, fontWeight: 600, textTransform: 'uppercase',
      letterSpacing: '0.18em', color: light ? 'rgba(255,255,255,.9)' : T.brand700,
    }}>{children}</span>;
  }

  // ---- Button ----
  function Button({ children, variant = 'brand', size = 'lg', icon, onClick }) {
    const [h, setH] = useState(false);
    const base = {
      display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: 8,
      borderRadius: 9999, fontWeight: 600, cursor: 'pointer', border: '1px solid transparent',
      fontFamily: T.font, transition: 'all .2s', whiteSpace: 'nowrap',
      padding: size === 'lg' ? '13px 26px' : size === 'sm' ? '7px 16px' : '10px 20px',
      fontSize: size === 'sm' ? 13 : size === 'md' ? 14 : 15,
    };
    const variants = {
      brand: { background: h ? T.brand700 : T.brand600, color: '#fff', boxShadow: '0 10px 15px -3px rgba(31,71,230,.3)' },
      dark:  { background: h ? '#1e293b' : T.s900, color: '#fff', boxShadow: '0 10px 15px -3px rgba(0,0,0,.15)' },
      white: { background: h ? '#f1f5f9' : '#fff', color: T.ink, boxShadow: '0 10px 15px -3px rgba(0,0,0,.2)' },
      ghost: { background: h ? '#f1f5f9' : '#fff', color: T.body, borderColor: '#cbd5e1' },
      glass: { background: h ? 'rgba(255,255,255,.2)' : 'rgba(255,255,255,.1)', color: '#fff', borderColor: 'rgba(255,255,255,.4)', backdropFilter: 'blur(6px)' },
    };
    return (
      <button onClick={onClick} onMouseEnter={() => setH(true)} onMouseLeave={() => setH(false)}
        style={{ ...base, ...variants[variant] }}>
        {children}
        {icon && <Icon name={icon} size={size === 'sm' ? 15 : 17} stroke={2.5}
          style={{ transition: 'transform .2s', transform: h ? 'translateX(2px)' : 'none' }} />}
      </button>
    );
  }

  // ---- Navbar ----
  function Navbar({ page, onNav }) {
    const [scrolled, setScrolled] = useState(false);
    const links = [['home', 'Home'], ['services', 'Services'], ['about', 'About'], ['contact', 'Contact']];
    // Scroll state is read from the scrollable shell; index.html passes a scroll container.
    useEffect(() => {
      const el = document.getElementById('site-scroll');
      if (!el) return;
      const onScroll = () => setScrolled(el.scrollTop > 60);
      el.addEventListener('scroll', onScroll); onScroll();
      return () => el.removeEventListener('scroll', onScroll);
    }, []);
    const onPhoto = !scrolled;
    return (
      <header style={{
        position: 'sticky', top: 0, zIndex: 50, transition: 'all .3s',
        background: scrolled ? 'rgba(255,255,255,.9)' : 'transparent',
        backdropFilter: scrolled ? 'blur(10px)' : 'none',
        boxShadow: scrolled ? '0 1px 2px rgba(0,0,0,.06)' : 'none',
        marginBottom: -72,
      }}>
        <div style={{ maxWidth: 1152, margin: '0 auto', padding: '14px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <span onClick={() => onNav('home')} style={{ cursor: 'pointer' }}><Logo light={onPhoto} /></span>
          <nav style={{ display: 'flex', gap: 4 }}>
            {links.map(([k, label]) => {
              const active = page === k;
              const color = onPhoto
                ? (active ? '#fff' : 'rgba(255,255,255,.8)')
                : (active ? T.ink : T.body);
              const bg = active ? (onPhoto ? 'rgba(255,255,255,.2)' : T.s100) : 'transparent';
              return <button key={k} onClick={() => onNav(k)} style={{
                border: 0, cursor: 'pointer', background: bg, color, borderRadius: 9999,
                padding: '8px 16px', fontSize: 14, fontWeight: 500, fontFamily: T.font, transition: 'all .2s',
              }}>{label}</button>;
            })}
          </nav>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <span style={{
              border: onPhoto ? '1px solid rgba(255,255,255,.3)' : `1px solid ${T.line}`,
              color: onPhoto ? 'rgba(255,255,255,.9)' : T.body,
              borderRadius: 9999, padding: '5px 11px', fontSize: 11, fontWeight: 600,
              textTransform: 'uppercase', letterSpacing: '.1em', cursor: 'pointer',
            }}>EN</span>
            <Button variant={onPhoto ? 'white' : 'dark'} size="md" onClick={() => onNav('contact')}>Sign Up Free</Button>
          </div>
        </div>
      </header>
    );
  }

  // ---- Hero ----
  function Hero({ image, eyebrow, title, highlight, subtitle, actions, minH = 560 }) {
    return (
      <section style={{ position: 'relative', minHeight: minH, overflow: 'hidden', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center', padding: '120px 24px 56px' }}>
        <img src={image} alt="" style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover' }} />
        <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(to bottom, rgba(15,23,42,.7), rgba(15,23,42,.4), rgba(15,23,42,.7))' }} />
        <div style={{ position: 'relative', zIndex: 1, maxWidth: 880 }}>
          {eyebrow && <span style={{
            display: 'inline-block', border: '1px solid rgba(255,255,255,.3)', background: 'rgba(255,255,255,.1)',
            backdropFilter: 'blur(6px)', padding: '6px 16px', borderRadius: 9999, color: 'rgba(255,255,255,.9)',
            fontSize: 12, fontWeight: 500, textTransform: 'uppercase', letterSpacing: '.18em',
          }}>{eyebrow}</span>}
          <h1 style={{ margin: '24px 0 0', color: '#fff', fontWeight: 800, letterSpacing: '-0.02em', lineHeight: 1.05, fontSize: 'clamp(40px,6vw,68px)', textShadow: '0 2px 12px rgba(0,0,0,.25)' }}>
            {title}{highlight && ' '}
            {highlight && <span style={{ background: 'linear-gradient(to right,#8eb6ff,#bcd3ff,#fff)', WebkitBackgroundClip: 'text', backgroundClip: 'text', color: 'transparent' }}>{highlight}</span>}
          </h1>
          {subtitle && <p style={{ margin: '24px auto 0', maxWidth: 620, color: 'rgba(255,255,255,.85)', fontSize: 18, lineHeight: 1.55 }}>{subtitle}</p>}
          {actions && <div style={{ marginTop: 40, display: 'flex', gap: 12, justifyContent: 'center', flexWrap: 'wrap' }}>{actions}</div>}
        </div>
      </section>
    );
  }

  // ---- Footer ----
  function Footer({ onNav }) {
    const links = [['home', 'Home'], ['services', 'Services'], ['about', 'About'], ['contact', 'Contact']];
    const head = { fontSize: 12, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.18em', color: 'rgba(255,255,255,.8)' };
    const li = { fontSize: 14, color: '#cbd5e1', cursor: 'pointer', marginTop: 10 };
    return (
      <footer style={{ background: T.s950, color: '#cbd5e1' }}>
        <div style={{ maxWidth: 1152, margin: '0 auto', padding: '64px 24px' }}>
          <div style={{ display: 'grid', gridTemplateColumns: '1.4fr 1fr 1fr', gap: 40 }}>
            <div>
              <Logo light />
              <p style={{ marginTop: 16, maxWidth: 320, fontSize: 14, color: '#94a3b8', lineHeight: 1.6 }}>Building software that moves your business forward</p>
            </div>
            <div>
              <h3 style={head}>Menu</h3>
              <ul style={{ listStyle: 'none', padding: 0, margin: '20px 0 0' }}>
                {links.map(([k, l]) => <li key={k} style={li} onClick={() => onNav(k)}>{l}</li>)}
              </ul>
            </div>
            <div>
              <h3 style={head}>Contact</h3>
              <ul style={{ listStyle: 'none', padding: 0, margin: '20px 0 0' }}>
                <li style={li}>contact@tirmongkol.com</li>
                <li style={li}>+66 00 000 0000</li>
                <li style={li}>LINE: @tirmongkol</li>
              </ul>
            </div>
          </div>
          <div style={{ marginTop: 48, borderTop: '1px solid rgba(255,255,255,.1)', paddingTop: 24, textAlign: 'center', fontSize: 12, color: '#64748b' }}>
            © 2026 Tirmongkol Service. All rights reserved.
          </div>
        </div>
      </footer>
    );
  }

  Object.assign(window, { Logo, Eyebrow, Button, Navbar, Hero, Footer });
})();
