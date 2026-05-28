/* Tirmongkol — website pages (Home / Services / About / Contact).
   Content lifted verbatim from lang/en/site.php. Depends on site-chrome.jsx + icons.jsx. */
(function () {
  const { useState } = React;
  const Icon = window.Icon;
  const { T, GRID_BG, Eyebrow, Button, Hero } = window;

  const HERO = {
    home: 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=2000&q=80',
    services: 'https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&w=2000&q=80',
    about: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=2000&q=80',
    contact: 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=2000&q=80',
  };

  const SERVICES = [
    { title: 'Queue Booking System', desc: 'Online queue booking for restaurants, clinics, and salons. Customers book ahead from their phones with automated reminders, eliminating long lines.' },
    { title: 'Product Management', desc: 'Real-time inventory and stock management. Track inflow and outflow, get alerts for low stock, and manage multiple branches in one place.' },
    { title: 'POS System', desc: 'Easy-to-use point-of-sale connected to your inventory, with tax calculation, receipt printing, and support for cash, card, and QR payments.' },
    { title: 'Membership / CRM', desc: 'CRM and membership management — track customer data, purchase history, loyalty points, and run targeted promotions to grow repeat business.' },
    { title: 'Business Websites', desc: 'Modern, fast, SEO-friendly websites that work on every device — from landing pages to full e-commerce stores.' },
    { title: 'HR / Employee System', desc: 'End-to-end HR — time tracking, payroll, leave management, and monthly reports for organizations of any size.' },
  ];
  const STATS = [['50+', 'Happy Clients'], ['120+', 'Projects Delivered'], ['8+', 'Years of Experience'], ['24/7', 'Post-launch Support']];
  const WHY = [
    ['Fully Customizable', 'Every system can be tailored to match your business workflow.'],
    ['Easy to Use', 'Thoughtful UX/UI so your team can get productive on day one.'],
    ['Ongoing Support', 'Our team is here to support and grow features with you.'],
    ['Fair Pricing', 'Pricing that fits SME budgets, with installment options.'],
  ];
  const VALUES = [
    ['Quality', "We're committed to delivering high-quality, thoroughly tested work."],
    ['Care', 'We listen, we understand the business, and we tailor systems accordingly.'],
    ['Integrity', 'Transparent pricing and timelines, no hidden fees.'],
    ['Innovation', 'We choose the right, modern, and sustainable technologies.'],
  ];

  const wrap = { maxWidth: 1152, margin: '0 auto', padding: '0 24px' };
  const cardBase = { background: '#fff', borderRadius: T.radCard, boxShadow: 'inset 0 0 0 1px ' + T.line + ', 0 1px 2px rgba(0,0,0,.05)' };

  function Card({ children, style, hover, onMouse }) {
    const [h, setH] = useState(false);
    return <div onMouseEnter={() => setH(true)} onMouseLeave={() => setH(false)} style={{
      ...cardBase, ...style, transition: 'all .2s',
      transform: hover && h ? 'translateY(-2px)' : 'none',
      boxShadow: hover && h ? '0 20px 25px -5px rgba(0,0,0,.1), inset 0 0 0 1px ' + T.brand300 : cardBase.boxShadow,
    }}>{children}</div>;
  }

  function SectionHead({ eyebrow, title, sub, center = true }) {
    return <div style={{ maxWidth: center ? 640 : 'none', margin: center ? '0 auto' : 0, textAlign: center ? 'center' : 'left' }}>
      {eyebrow && <Eyebrow>{eyebrow}</Eyebrow>}
      <h2 style={{ margin: '12px 0 0', fontSize: 'clamp(30px,3.4vw,46px)', fontWeight: 700, letterSpacing: '-0.02em', color: T.ink, lineHeight: 1.12 }}>{title}</h2>
      {sub && <p style={{ margin: '12px auto 0', maxWidth: 560, fontSize: 16, color: T.body, lineHeight: 1.6 }}>{sub}</p>}
    </div>;
  }

  // ---- Floating stat strip ----
  function StatStrip() {
    return <section style={{ ...wrap, position: 'relative', zIndex: 20, marginTop: -96, maxWidth: 1100 }}>
      <div style={{ display: 'grid', gridTemplateColumns: '2fr 3fr', gap: 48, background: '#fff', borderRadius: T.radFeature, padding: 48, boxShadow: '0 25px 50px -12px rgba(15,23,42,.18)' }}>
        <div>
          <Eyebrow>End-to-end Business Software</Eyebrow>
          <h2 style={{ margin: '12px 0 0', fontSize: 28, fontWeight: 700, color: T.ink, lineHeight: 1.2 }}>Software designed specifically for your business</h2>
        </div>
        <dl style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 24, margin: 0 }}>
          {STATS.map(([n, l]) => <div key={l}>
            <dt style={{ fontSize: 36, fontWeight: 800, letterSpacing: '-0.02em', color: T.ink, fontVariantNumeric: 'tabular-nums' }}>{n}</dt>
            <dd style={{ margin: '4px 0 0', fontSize: 12, color: T.meta, lineHeight: 1.3 }}>{l}</dd>
          </div>)}
        </dl>
      </div>
    </section>;
  }

  function ServiceCard({ i, title, desc, muted }) {
    return <Card hover={!muted} style={{ padding: 24 }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <span style={{ display: 'grid', placeItems: 'center', width: muted ? 40 : 44, height: muted ? 40 : 44, borderRadius: 12,
          background: muted ? T.s100 : T.gradLogo, color: muted ? T.meta : '#fff', fontWeight: 700, fontSize: 14,
          boxShadow: muted ? 'none' : '0 6px 12px -3px rgba(51,102,255,.25)' }}>{String(i + 1).padStart(2, '0')}</span>
        {muted && <span style={{ background: '#fffbeb', color: '#b45309', fontSize: 12, fontWeight: 500, padding: '2px 10px', borderRadius: 9999 }}>Coming soon</span>}
      </div>
      <h3 style={{ margin: '18px 0 0', fontSize: 18, fontWeight: 600, color: T.ink }}>{title}</h3>
      <p style={{ margin: '8px 0 0', fontSize: 14, color: T.body, lineHeight: 1.6 }}>{desc}</p>
    </Card>;
  }

  function CtaCard() {
    return <section style={{ ...wrap, padding: '80px 24px', maxWidth: 1100 }}>
      <div style={{ position: 'relative', overflow: 'hidden', borderRadius: T.radFeature, background: T.gradCta, padding: '64px 32px', textAlign: 'center', color: '#fff', boxShadow: '0 25px 50px -12px rgba(28,46,116,.4)' }}>
        <div style={{ position: 'absolute', inset: 0, opacity: 0.1, ...GRID_BG }} />
        <div style={{ position: 'relative' }}>
          <h2 style={{ margin: 0, maxWidth: 680, marginInline: 'auto', fontSize: 'clamp(30px,3.6vw,46px)', fontWeight: 700, letterSpacing: '-0.02em', lineHeight: 1.12 }}>Ready to level up your business?</h2>
          <p style={{ margin: '20px auto 0', maxWidth: 480, fontSize: 17, color: T.brand100 }}>Free consultation — let's analyze your needs together.</p>
          <div style={{ marginTop: 40, display: 'flex', justifyContent: 'center' }}><Button variant="white" icon="arrow-right">Start a Free Consultation</Button></div>
        </div>
      </div>
    </section>;
  }

  // ===== PAGES =====
  function HomePage({ onNav }) {
    return <div>
      <Hero image={HERO.home} eyebrow="Software Solutions"
        title="Build Software That Powers Your" highlight="Business"
        subtitle="We build end-to-end business management systems — from queue booking and product management to custom websites and applications tailored to your business."
        minH={620}
        actions={[<Button key="a" variant="white" icon="arrow-right" onClick={() => onNav('services')}>Explore Services</Button>,
                  <Button key="b" variant="glass" onClick={() => onNav('contact')}>Contact Us</Button>]} />
      <StatStrip />
      <section style={{ ...wrap, marginTop: 112 }}>
        <SectionHead eyebrow="Our Services" title="Software designed specifically for your business" />
        <div style={{ marginTop: 48, display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 16 }}>
          {SERVICES.map((s, i) => <ServiceCard key={i} i={i} {...s} />)}
        </div>
        <div style={{ marginTop: 40, textAlign: 'center' }}><Button variant="ghost" icon="arrow-right" onClick={() => onNav('services')}>Explore Services</Button></div>
      </section>
      <section style={{ background: T.s100, padding: '80px 0', marginTop: 112 }}>
        <div style={wrap}>
          <SectionHead eyebrow="Why Choose Us" title="We don't just write code — we understand business" />
          <div style={{ marginTop: 56, display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 16 }}>
            {WHY.map(([t, d], i) => <Card key={i} style={{ padding: 28 }}>
              <div style={{ fontSize: 12, fontWeight: 700, letterSpacing: '.18em', color: T.brand600 }}>0{i + 1}</div>
              <h3 style={{ margin: '16px 0 0', fontSize: 18, fontWeight: 600, color: T.ink }}>{t}</h3>
              <p style={{ margin: '8px 0 0', fontSize: 14, color: T.body, lineHeight: 1.6 }}>{d}</p>
            </Card>)}
          </div>
        </div>
      </section>
      <CtaCard />
    </div>;
  }

  function ServicesPage({ onNav }) {
    const features = ['Upload Excel roster → auto barcodes', 'Scan with a phone camera (no scanner needed)', 'Hardware scanner supported too', 'Live count: submitted vs not yet', 'Export scores to Excel for your school'];
    const included = ['Free consultation and requirements analysis', 'Modern UX/UI design', 'Development with latest technologies', 'Thorough QA and testing', 'Team training on the new system', '6 months of post-launch support'];
    return <div>
      <Hero image={HERO.services} eyebrow="Services" title="Our Services"
        subtitle="End-to-end systems and solutions for businesses of any size" minH={440} />
      {/* Available product: Homework Scanner */}
      <section style={{ ...wrap, position: 'relative', zIndex: 20, marginTop: -96, maxWidth: 1100 }}>
        <div style={{ display: 'grid', gridTemplateColumns: '2fr 3fr', borderRadius: T.radFeature, overflow: 'hidden', background: '#fff', boxShadow: '0 25px 50px -12px rgba(15,23,42,.18)' }}>
          <div style={{ position: 'relative', background: T.gradCta, color: '#fff', padding: 40, display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }}>
            <div style={{ position: 'absolute', inset: 0, opacity: 0.1, ...GRID_BG }} />
            <div style={{ position: 'relative' }}>
              <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, background: 'rgba(52,211,153,.2)', color: '#a7f3d0', boxShadow: 'inset 0 0 0 1px rgba(110,231,183,.4)', padding: '4px 12px', borderRadius: 9999, fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.06em' }}>
                <span style={{ width: 6, height: 6, borderRadius: '50%', background: '#34d399' }} />Available</span>
              <h2 style={{ margin: '20px 0 0', fontSize: 34, fontWeight: 800, lineHeight: 1.1 }}>Homework Scanner</h2>
              <p style={{ margin: '12px 0 0', fontSize: 16, color: T.brand100 }}>Scan to check, done in seconds</p>
            </div>
            <div style={{ position: 'relative', marginTop: 32 }}>
              <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, background: 'rgba(255,255,255,.1)', backdropFilter: 'blur(6px)', padding: '4px 12px', borderRadius: 9999, fontSize: 12, fontWeight: 500 }}>💸 Free forever</span>
            </div>
          </div>
          <div style={{ padding: 40 }}>
            <p style={{ margin: 0, fontSize: 15, color: T.body, lineHeight: 1.6 }}>Upload your student roster → the system auto-generates a barcode/QR per student → print and stick on their books → scan instead of ticking off names by hand. 10× faster.</p>
            <ul style={{ margin: '24px 0 0', padding: 0, listStyle: 'none', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
              {features.map((f, i) => <li key={i} style={{ display: 'flex', gap: 8, fontSize: 14, color: T.ink, alignItems: 'flex-start' }}>
                <Icon name="check" size={16} stroke={2.5} style={{ color: T.brand600, marginTop: 2, flexShrink: 0 }} />{f}</li>)}
            </ul>
            <div style={{ marginTop: 32, display: 'flex', gap: 12 }}>
              <Button variant="brand" icon="arrow-right" onClick={() => onNav('app')}>Try Free</Button>
              <Button variant="ghost" onClick={() => onNav('app')}>Log In</Button>
            </div>
          </div>
        </div>
      </section>
      {/* Coming soon services */}
      <section style={{ ...wrap, marginTop: 80 }}>
        <div style={{ textAlign: 'center' }}>
          <span style={{ display: 'inline-block', background: '#fffbeb', color: '#b45309', padding: '4px 12px', borderRadius: 9999, fontSize: 12, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.06em' }}>Coming soon</span>
          <h2 style={{ margin: '16px 0 0', fontSize: 32, fontWeight: 700, letterSpacing: '-0.02em', color: T.ink }}>Our Services</h2>
          <p style={{ margin: '12px 0 0', fontSize: 16, color: T.body }}>Software designed specifically for your business</p>
        </div>
        <div style={{ marginTop: 48, display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 16 }}>
          {SERVICES.map((s, i) => <ServiceCard key={i} i={i} {...s} muted />)}
        </div>
      </section>
      {/* Included */}
      <section style={{ ...wrap, marginTop: 64 }}>
        <div style={{ background: T.s900, borderRadius: T.radFeature, padding: 48, color: '#fff' }}>
          <h2 style={{ margin: 0, textAlign: 'center', fontSize: 32, fontWeight: 700, letterSpacing: '-0.02em' }}>Every project includes</h2>
          <ul style={{ margin: '40px 0 0', padding: 0, listStyle: 'none', display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 12 }}>
            {included.map((it, i) => <li key={i} style={{ display: 'flex', gap: 12, alignItems: 'flex-start', background: 'rgba(255,255,255,.05)', boxShadow: 'inset 0 0 0 1px rgba(255,255,255,.1)', borderRadius: 16, padding: 16 }}>
              <span style={{ display: 'grid', placeItems: 'center', width: 28, height: 28, borderRadius: '50%', background: T.brand500, flexShrink: 0 }}><Icon name="check" size={14} stroke={3} /></span>
              <span style={{ fontSize: 14 }}>{it}</span></li>)}
          </ul>
        </div>
      </section>
      <CtaCard />
    </div>;
  }

  function AboutPage({ onNav }) {
    return <div>
      <Hero image={HERO.about} eyebrow="About" title="About Us"
        subtitle="A software team that understands Thai business" minH={440} />
      <section style={{ ...wrap, position: 'relative', zIndex: 20, marginTop: -96, maxWidth: 1100 }}>
        <div style={{ background: '#fff', borderRadius: T.radFeature, padding: 48, boxShadow: '0 25px 50px -12px rgba(15,23,42,.18)', display: 'grid', gridTemplateColumns: '3fr 2fr', gap: 48 }}>
          <div>
            <Eyebrow>Our Story</Eyebrow>
            <h2 style={{ margin: '12px 0 0', fontSize: 32, fontWeight: 700, letterSpacing: '-0.02em', color: T.ink }}>Tirmongkol Service</h2>
            <p style={{ margin: '24px 0 0', fontSize: 17, color: T.body, lineHeight: 1.6 }}>Tirmongkol Service was founded with one goal: to give Thai SMEs modern, easy-to-use, and affordable systems. We believe technology shouldn't feel out of reach, and every business deserves great software to grow.</p>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
            {STATS.map(([n, l]) => <div key={l} style={{ background: 'linear-gradient(135deg,#eef4ff,#d9e6ff)', borderRadius: 16, padding: 20 }}>
              <div style={{ fontSize: 30, fontWeight: 800, letterSpacing: '-0.02em', color: T.brand700 }}>{n}</div>
              <div style={{ marginTop: 4, fontSize: 12, color: T.meta }}>{l}</div></div>)}
          </div>
        </div>
      </section>
      <section style={{ ...wrap, marginTop: 64, maxWidth: 1100 }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
          <div style={{ ...cardBase, padding: 40 }}>
            <div style={{ display: 'grid', placeItems: 'center', width: 48, height: 48, borderRadius: 16, background: T.gradLogo, color: '#fff', boxShadow: '0 6px 12px -3px rgba(51,102,255,.25)' }}><Icon name="play" size={24} /></div>
            <h3 style={{ margin: '24px 0 0', fontSize: 20, fontWeight: 700, color: T.ink }}>Our Mission</h3>
            <p style={{ margin: '12px 0 0', fontSize: 15, color: T.body, lineHeight: 1.6 }}>Build software that makes our clients' work easier, faster, and sustainably scalable — with the right technology and genuine care.</p>
          </div>
          <div style={{ background: T.s900, borderRadius: 16, padding: 40, color: '#fff' }}>
            <div style={{ display: 'grid', placeItems: 'center', width: 48, height: 48, borderRadius: 16, background: 'rgba(255,255,255,.1)', boxShadow: 'inset 0 0 0 1px rgba(255,255,255,.2)' }}><Icon name="eye" size={24} /></div>
            <h3 style={{ margin: '24px 0 0', fontSize: 20, fontWeight: 700 }}>Our Vision</h3>
            <p style={{ margin: '12px 0 0', fontSize: 15, color: '#cbd5e1', lineHeight: 1.6 }}>To be the trusted technology partner for Thai SMEs, helping them compete and grow in the digital age.</p>
          </div>
        </div>
      </section>
      <section style={{ ...wrap, marginTop: 64, maxWidth: 1100 }}>
        <h2 style={{ textAlign: 'center', fontSize: 32, fontWeight: 700, letterSpacing: '-0.02em', color: T.ink, margin: 0 }}>Our Values</h2>
        <div style={{ marginTop: 40, display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 16 }}>
          {VALUES.map(([t, d], i) => <Card key={i} style={{ padding: 24 }}>
            <span style={{ fontSize: 12, fontWeight: 700, letterSpacing: '.18em', color: T.brand600 }}>0{i + 1}</span>
            <h3 style={{ margin: '12px 0 0', fontSize: 18, fontWeight: 600, color: T.ink }}>{t}</h3>
            <p style={{ margin: '8px 0 0', fontSize: 14, color: T.body, lineHeight: 1.6 }}>{d}</p></Card>)}
        </div>
      </section>
      <CtaCard />
    </div>;
  }

  function ContactPage() {
    const [sent, setSent] = useState(false);
    const info = [['mail', 'Email', 'contact@tirmongkol.com'], ['phone', 'Phone', '+66 00 000 0000'], ['chat', 'LINE', '@tirmongkol'], ['clock', 'Office Hours', 'Mon – Fri, 9:00 AM – 6:00 PM']];
    const field = { width: '100%', border: '1px solid #cbd5e1', borderRadius: 12, padding: '10px 14px', fontSize: 14, color: T.ink, fontFamily: T.font, boxSizing: 'border-box' };
    const label = { display: 'block', fontSize: 13, fontWeight: 500, color: '#334155', marginBottom: 6 };
    return <div>
      <Hero image={HERO.contact} eyebrow="Contact" title="Contact Us"
        subtitle="We're here to answer any question — free consultation" minH={440} />
      <section style={{ ...wrap, position: 'relative', zIndex: 20, marginTop: -96, marginBottom: 96, maxWidth: 1100 }}>
        <div style={{ display: 'grid', gridTemplateColumns: '2fr 3fr', borderRadius: T.radFeature, overflow: 'hidden', background: '#fff', boxShadow: '0 25px 50px -12px rgba(15,23,42,.18)' }}>
          <div style={{ background: T.s900, color: '#fff', padding: 40 }}>
            <h2 style={{ margin: 0, fontSize: 22, fontWeight: 700 }}>Get in Touch</h2>
            <ul style={{ margin: '24px 0 0', padding: 0, listStyle: 'none', display: 'flex', flexDirection: 'column', gap: 20 }}>
              {info.map(([ic, l, v]) => <li key={l} style={{ display: 'flex', gap: 16 }}>
                <span style={{ display: 'grid', placeItems: 'center', width: 40, height: 40, borderRadius: 12, background: 'rgba(255,255,255,.1)', boxShadow: 'inset 0 0 0 1px rgba(255,255,255,.15)', flexShrink: 0 }}><Icon name={ic} size={18} /></span>
                <div><div style={{ fontSize: 11, fontWeight: 500, textTransform: 'uppercase', letterSpacing: '.14em', color: '#94a3b8' }}>{l}</div><div style={{ marginTop: 2, fontSize: 14, fontWeight: 500 }}>{v}</div></div></li>)}
            </ul>
          </div>
          <div style={{ padding: 40 }}>
            {sent && <p style={{ marginBottom: 20, background: '#ecfdf5', color: '#047857', boxShadow: 'inset 0 0 0 1px #a7f3d0', padding: '12px 16px', borderRadius: 12, fontSize: 14, fontWeight: 500 }}>Message sent — we'll get back to you as soon as possible.</p>}
            <h3 style={{ margin: 0, fontSize: 18, fontWeight: 600, color: T.ink }}>Message</h3>
            <div style={{ marginTop: 20, display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
              <div><label style={label}>Full Name</label><input style={field} placeholder="" /></div>
              <div><label style={label}>Email</label><input style={field} placeholder="you@example.com" /></div>
              <div><label style={label}>Phone Number</label><input style={field} /></div>
              <div><label style={label}>Company / Shop</label><input style={field} /></div>
            </div>
            <div style={{ marginTop: 16 }}><label style={label}>Message</label>
              <textarea rows={4} style={{ ...field, resize: 'vertical' }} placeholder="Tell us what kind of system your business needs" /></div>
            <div style={{ marginTop: 24 }}><Button variant="dark" icon="arrow-right" onClick={() => setSent(true)}>Send Message</Button></div>
          </div>
        </div>
      </section>
    </div>;
  }

  Object.assign(window, { HomePage, ServicesPage, AboutPage, ContactPage });
})();
