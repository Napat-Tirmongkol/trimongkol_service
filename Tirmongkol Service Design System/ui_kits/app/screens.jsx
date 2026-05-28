/* Tirmongkol — Homework Scanner screens. Depends on app-shell.jsx + icons.jsx. */
(function () {
  const { useState } = React;
  const Icon = window.Icon;
  const { AT: T, AButton, Card, StatCard, TopNav, PageHeader, CategoryBadge, Toast } = window;

  const wrap = { maxWidth: 1120, margin: '0 auto', padding: '32px 24px' };

  // ---------- Sample data ----------
  const USER = { name: 'Khun Ploy', email: 'ploy@school.ac.th' };
  const STUDENTS = [
    { n: 1, name: 'Anan Wong', code: 'K7F3A1' }, { n: 2, name: 'Bua Srisai', code: 'M2Q8B4' },
    { n: 3, name: 'Chai Suwan', code: 'P5K2C9' }, { n: 4, name: 'Dao Pimchai', code: 'R3X7D6' },
    { n: 5, name: 'Fern Jaidee', code: 'T9L4E2' }, { n: 6, name: 'Gan Thongdee', code: 'W1H6F8' },
    { n: 7, name: 'Mali Phon', code: 'Y4N2G3' }, { n: 8, name: 'Nok Ratana', code: 'Z8B5H7' },
  ];
  const ASSIGNMENTS = [
    { id: 1, name: 'Vocabulary Week 5', category: 'homework', mode: 'check', due: '12 Mar 2026', submitted: 6 },
    { id: 2, name: 'Reading Quiz — Unit 3', category: 'quiz', mode: 'custom', due: '08 Mar 2026', submitted: 8 },
    { id: 3, name: 'Midterm Exam', category: 'exam', mode: 'custom', due: '20 Mar 2026', submitted: 3 },
    { id: 4, name: 'Class Presentation', category: 'project', mode: 'fixed', due: null, submitted: 5 },
  ];
  const CLASSROOMS = [
    { id: 1, name: 'Grade 5/2 English', grade: 'Grade 5', students: 8, assignments: 4 },
    { id: 2, name: 'Grade 6/1 Math', grade: 'Grade 6', students: 31, assignments: 6 },
    { id: 3, name: 'M.2 Science', grade: 'M.2', students: 24, assignments: 3 },
  ];
  const MODE_LABEL = { check: 'Check only', fixed: 'Quick score', custom: 'Custom score' };
  window.APP_DATA = { USER, STUDENTS, ASSIGNMENTS, CLASSROOMS };

  // ====================== LOGIN ======================
  function LoginScreen({ onSignIn }) {
    const [step, setStep] = useState('identify');
    const [email, setEmail] = useState('');
    const field = { width: '100%', border: '1px solid #cbd5e1', borderRadius: 6, padding: '9px 12px', fontSize: 14, fontFamily: T.font, color: T.ink, boxSizing: 'border-box' };
    const label = { display: 'block', fontSize: 14, fontWeight: 500, color: T.ink, marginBottom: 8 };
    return <div style={{ minHeight: '100vh', display: 'flex', flexDirection: 'column', background: '#fff' }}>
      <header style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderBottom: `1px solid ${T.line2}`, padding: '16px 24px' }}>
        <span style={{ display: 'flex', alignItems: 'center', gap: 9 }}>
          <span style={{ display: 'grid', placeItems: 'center', width: 36, height: 36, borderRadius: 11, background: T.gradLogo, color: '#fff', fontWeight: 800, fontSize: 18, boxShadow: '0 6px 12px -3px rgba(51,102,255,.3)' }}>T</span>
          <span style={{ fontSize: 14, fontWeight: 600, color: T.ink }}>Tirmongkol Service</span>
        </span>
        <span style={{ border: `1px solid ${T.line}`, color: T.body, borderRadius: 9999, padding: '5px 11px', fontSize: 11, fontWeight: 600, letterSpacing: '.1em' }}>TH</span>
      </header>
      <main style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '40px 16px' }}>
        <div style={{ width: '100%', maxWidth: 360 }}>
          {step === 'identify' && <>
            <h2 style={{ textAlign: 'center', fontSize: 20, fontWeight: 600, color: T.ink, margin: 0 }}>Log in or create account</h2>
            <p style={{ textAlign: 'center', fontSize: 14, color: T.meta, margin: '6px 0 0', lineHeight: 1.5 }}>Enter your email to get started — we'll sign you in if you have an account, otherwise we'll set one up.</p>
            <div style={{ marginTop: 24 }}>
              <label style={label}>Email</label>
              <input style={field} value={email} onChange={e => setEmail(e.target.value)} placeholder="you@example.com" autoFocus />
              <div style={{ marginTop: 16 }}><AButton variant="dark" size="lg" style={{ width: '100%' }} onClick={() => setStep('signin')}>Continue</AButton></div>
            </div>
            <p style={{ marginTop: 24, textAlign: 'center', fontSize: 12, color: T.meta, lineHeight: 1.6 }}>By signing in, you agree to our <u>terms of service</u> and <u>privacy policy</u>.</p>
          </>}
          {step === 'signin' && <>
            <h2 style={{ textAlign: 'center', fontSize: 20, fontWeight: 600, color: T.ink, margin: 0 }}>Welcome back</h2>
            <p style={{ textAlign: 'center', fontSize: 14, color: T.meta, margin: '6px 0 0' }}>Signing in as {email || 'ploy@school.ac.th'} <span style={{ color: '#334155', textDecoration: 'underline', cursor: 'pointer' }} onClick={() => setStep('identify')}>change</span></p>
            <div style={{ marginTop: 24 }}>
              <div style={{ display: 'flex', justifyContent: 'space-between' }}><label style={label}>Password</label><span style={{ fontSize: 12, color: T.meta, textDecoration: 'underline' }}>Forgot?</span></div>
              <input style={field} type="password" defaultValue="password" autoFocus />
              <label style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, color: T.body, marginTop: 14 }}><input type="checkbox" defaultChecked /> Remember me</label>
              <div style={{ marginTop: 16 }}><AButton variant="dark" size="lg" style={{ width: '100%' }} onClick={onSignIn}>Sign in</AButton></div>
            </div>
          </>}
        </div>
      </main>
    </div>;
  }

  // ====================== DASHBOARD ======================
  function Dashboard({ onOpenClassroom }) {
    return <div style={{ minHeight: '100vh', background: T.bg }}>
      <TopNav user={USER} onHome={() => {}} />
      <div style={wrap}>
        <div style={{ fontSize: 13, color: T.meta }}>Welcome back</div>
        <h1 style={{ margin: '4px 0 0', fontSize: 28, fontWeight: 700, color: T.ink, letterSpacing: '-0.01em' }}>Hi, Ploy 👋</h1>
        <p style={{ margin: '8px 0 0', fontSize: 15, color: T.body, maxWidth: 620, lineHeight: 1.55 }}>Manage your classrooms, add assignments, and scan student barcodes to track submissions in seconds.</p>

        <div style={{ marginTop: 28, display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 16 }}>
          <StatCard icon="grid" tint="brand" label="Classrooms" value="3" />
          <StatCard icon="users" tint="emerald" label="Students" value="63" />
          <StatCard icon="clipboard" tint="amber" label="Assignments" value="13" />
        </div>

        <div style={{ marginTop: 32, display: 'grid', gridTemplateColumns: '2fr 1fr', gap: 16, alignItems: 'start' }}>
          <div>
            <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between' }}>
              <h2 style={{ fontSize: 16, fontWeight: 600, color: T.ink, margin: 0 }}>Your classrooms <span style={{ fontSize: 14, fontWeight: 400, color: T.meta }}>3 total</span></h2>
            </div>
            <div style={{ marginTop: 14, display: 'flex', flexDirection: 'column', gap: 12 }}>
              {CLASSROOMS.map(c => <ClassroomRow key={c.id} c={c} onClick={() => onOpenClassroom(c.id)} />)}
              <button style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, background: '#fff', border: `1.5px dashed ${T.line}`, borderRadius: 16, padding: 16, fontSize: 14, fontWeight: 600, color: T.brand600, cursor: 'pointer', fontFamily: T.font }}>
                <Icon name="plus" size={16} stroke={2.5} /> Add Classroom</button>
            </div>
          </div>
          <Card style={{ padding: 24 }}>
            <div style={{ fontSize: 12, fontWeight: 700, textTransform: 'uppercase', letterSpacing: '.12em', color: T.brand600 }}>Get started</div>
            <ol style={{ margin: '16px 0 0', padding: 0, listStyle: 'none', display: 'flex', flexDirection: 'column', gap: 14 }}>
              {['Create a classroom — name + grade level', 'Add students one by one or paste the whole roster', 'Print QR labels, stick them on notebooks, then scan'].map((s, i) =>
                <li key={i} style={{ display: 'flex', gap: 12, alignItems: 'flex-start' }}>
                  <span style={{ flexShrink: 0, display: 'grid', placeItems: 'center', width: 24, height: 24, borderRadius: '50%', background: T.brand50, color: T.brand700, fontSize: 12, fontWeight: 700 }}>{i + 1}</span>
                  <span style={{ fontSize: 14, color: T.body, lineHeight: 1.45 }}>{s}</span></li>)}
            </ol>
          </Card>
        </div>
      </div>
    </div>;
  }

  function ClassroomRow({ c, onClick }) {
    const [h, setH] = useState(false);
    return <div onClick={onClick} onMouseEnter={() => setH(true)} onMouseLeave={() => setH(false)} style={{
      display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: '#fff', borderRadius: 16,
      border: `1px solid ${h ? '#8eb6ff' : T.line}`, boxShadow: h ? '0 12px 20px -8px rgba(15,23,42,.12)' : '0 1px 2px rgba(0,0,0,.05)',
      padding: '16px 20px', cursor: 'pointer', transition: 'all .15s', transform: h ? 'translateY(-1px)' : 'none' }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
        <span style={{ display: 'grid', placeItems: 'center', width: 44, height: 44, borderRadius: 12, background: T.gradLogo, color: '#fff', fontWeight: 700, fontSize: 16 }}>{c.name.match(/\d+/)?.[0] || c.name[0]}</span>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <span style={{ fontSize: 15, fontWeight: 600, color: T.ink }}>{c.name}</span>
            <span style={{ background: T.brand50, color: T.brand700, fontSize: 11, fontWeight: 600, padding: '2px 8px', borderRadius: 9999 }}>{c.grade}</span>
          </div>
          <div style={{ fontSize: 13, color: T.meta, marginTop: 3 }}>{c.students} students · {c.assignments} assignments</div>
        </div>
      </div>
      <Icon name="chevron-right" size={18} style={{ color: T.faint }} />
    </div>;
  }

  // ====================== CLASSROOM ======================
  function ClassroomScreen({ onBack, onOpenAssignment }) {
    return <div style={{ minHeight: '100vh', background: T.bg }}>
      <TopNav user={USER} onHome={onBack} />
      <PageHeader>
        <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12 }}>
          <div>
            <span onClick={onBack} style={{ display: 'inline-flex', alignItems: 'center', gap: 4, fontSize: 12, color: T.meta, cursor: 'pointer' }}><Icon name="chevron-left" size={12} stroke={2.5} /> My Classrooms</span>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginTop: 6 }}>
              <h1 style={{ fontSize: 24, fontWeight: 700, color: T.ink, margin: 0 }}>Grade 5/2 English</h1>
              <span style={{ background: T.brand50, color: T.brand700, fontSize: 12, fontWeight: 600, padding: '3px 10px', borderRadius: 9999 }}>Grade 5</span>
            </div>
          </div>
          <AButton variant="ghost" size="sm">Edit</AButton>
        </div>
      </PageHeader>
      <div style={wrap}>
        {/* quick stats / actions */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 12 }}>
          <StatCard icon="users" tint="brand" label="Students" value="8" />
          <StatCard icon="clipboard" tint="amber" label="Assignments" value="4" />
          <Card style={{ padding: 20, display: 'flex', alignItems: 'center', justifyContent: 'space-between', cursor: 'pointer' }}>
            <div><div style={{ fontSize: 11, textTransform: 'uppercase', letterSpacing: '.08em', color: T.meta }}>Gradebook</div><div style={{ fontSize: 15, fontWeight: 700, color: T.ink, marginTop: 4 }}>Open →</div></div>
            <span style={{ display: 'grid', placeItems: 'center', width: 40, height: 40, borderRadius: 12, background: '#ecfdf5', color: '#059669' }}><Icon name="chart" size={20} /></span>
          </Card>
          <div style={{ display: 'flex', flexDirection: 'column', justifyContent: 'space-between', borderRadius: 16, padding: 20, color: '#fff', background: T.gradAction, boxShadow: '0 10px 20px -6px rgba(31,71,230,.3)', cursor: 'pointer' }}>
            <div style={{ fontSize: 11, textTransform: 'uppercase', letterSpacing: '.08em', color: T.brand100 }}>Add Assignment</div>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: 8 }}><span style={{ fontSize: 15, fontWeight: 700 }}>Create →</span><Icon name="plus" size={18} stroke={2.5} /></div>
          </div>
        </div>

        {/* assignments */}
        <Card style={{ marginTop: 24, padding: 0 }}>
          <div style={{ padding: '16px 24px', borderBottom: `1px solid ${T.line}` }}><h3 style={{ margin: 0, fontSize: 15, fontWeight: 600, color: T.ink }}>Assignments <span style={{ fontWeight: 400, color: T.meta }}>(4)</span></h3></div>
          <div>
            {ASSIGNMENTS.map((a, i) => <div key={a.id} onClick={() => onOpenAssignment(a.id)} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 24px', borderTop: i ? `1px solid ${T.line2}` : 'none', cursor: 'pointer' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                <span style={{ display: 'grid', placeItems: 'center', width: 36, height: 36, borderRadius: 10, background: '#fffbeb', color: '#d97706', flexShrink: 0 }}><Icon name="clipboard" size={16} /></span>
                <div>
                  <div style={{ fontSize: 14, fontWeight: 500, color: T.ink }}>{a.name}</div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 4 }}>
                    <CategoryBadge category={a.category} />
                    <span style={{ background: T.s100, color: T.body, fontSize: 11, fontWeight: 500, padding: '2px 8px', borderRadius: 9999 }}>{MODE_LABEL[a.mode]}</span>
                    {a.due && <span style={{ fontSize: 12, color: T.meta }}>· Due {a.due}</span>}
                  </div>
                </div>
              </div>
              <Icon name="chevron-right" size={16} style={{ color: T.faint }} />
            </div>)}
          </div>
        </Card>

        {/* students */}
        <Card style={{ marginTop: 16, padding: 0 }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '16px 24px', borderBottom: `1px solid ${T.line}` }}>
            <h3 style={{ margin: 0, fontSize: 15, fontWeight: 600, color: T.ink }}>Students <span style={{ fontWeight: 400, color: T.meta }}>(8)</span></h3>
            <div style={{ display: 'flex', gap: 8 }}>
              <AButton variant="ghost" size="sm" iconLeft="printer">Print QR</AButton>
              <AButton variant="ghost" size="sm" iconLeft="upload">Bulk import</AButton>
              <AButton variant="brand" size="sm">+ Add Student</AButton>
            </div>
          </div>
          <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 14 }}>
            <thead><tr style={{ background: T.s50, textAlign: 'left', color: T.meta, fontSize: 11, textTransform: 'uppercase', letterSpacing: '.06em' }}>
              <th style={{ padding: '10px 24px', fontWeight: 500 }}>No.</th><th style={{ padding: '10px 24px', fontWeight: 500 }}>Name</th><th style={{ padding: '10px 24px', fontWeight: 500 }}>Code (barcode)</th><th style={{ padding: '10px 24px', fontWeight: 500, textAlign: 'right' }}>Actions</th>
            </tr></thead>
            <tbody>{STUDENTS.map((s, i) => <tr key={s.code} style={{ borderTop: `1px solid ${T.line2}` }}>
              <td style={{ padding: '11px 24px', color: T.body, fontVariantNumeric: 'tabular-nums' }}>{s.n}</td>
              <td style={{ padding: '11px 24px', fontWeight: 500, color: T.ink }}>{s.name}</td>
              <td style={{ padding: '11px 24px' }}><span style={{ fontFamily: T.mono, fontSize: 12, background: T.s100, color: '#334155', padding: '2px 8px', borderRadius: 4 }}>{s.code}</span></td>
              <td style={{ padding: '11px 24px', textAlign: 'right', fontSize: 13 }}>
                <span style={{ color: T.meta, cursor: 'pointer' }}>QR</span> <span style={{ color: '#cbd5e1' }}>·</span> <span style={{ color: T.meta, cursor: 'pointer' }}>View</span> <span style={{ color: '#cbd5e1' }}>·</span> <span style={{ color: T.brand700, cursor: 'pointer' }}>Edit</span>
              </td></tr>)}</tbody>
          </table>
        </Card>
      </div>
    </div>;
  }

  // ====================== ASSIGNMENT ======================
  function AssignmentScreen({ onBack, onScan }) {
    const a = ASSIGNMENTS[1]; // Reading Quiz (custom/graded)
    const total = STUDENTS.length;
    const submittedSet = { 1: 85, 2: 72, 4: 90, 5: 68 }; // student.n -> score
    const submittedCount = Object.keys(submittedSet).length;
    const pct = Math.round(submittedCount / total * 100);
    return <div style={{ minHeight: '100vh', background: T.bg }}>
      <TopNav user={USER} onHome={onBack} />
      <PageHeader>
        <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12 }}>
          <div>
            <span onClick={onBack} style={{ fontSize: 12, color: T.meta, cursor: 'pointer' }}>← Grade 5/2 English</span>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginTop: 6 }}>
              <h1 style={{ fontSize: 22, fontWeight: 600, color: T.ink, margin: 0 }}>{a.name}</h1>
              <CategoryBadge category={a.category} />
            </div>
            <div style={{ fontSize: 12, color: T.meta, marginTop: 4 }}>Due: {a.due}</div>
          </div>
          <div style={{ display: 'flex', gap: 8 }}><AButton variant="ghost" size="sm">Export CSV</AButton><AButton variant="ghost" size="sm">Edit</AButton></div>
        </div>
      </PageHeader>
      <div style={{ ...wrap, maxWidth: 820 }}>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 16 }}>
          <Card style={{ padding: 20 }}>
            <div style={{ fontSize: 11, textTransform: 'uppercase', letterSpacing: '.08em', color: T.meta }}>Submitted</div>
            <div style={{ fontSize: 28, fontWeight: 700, color: T.ink, marginTop: 6 }}>{submittedCount} <span style={{ fontSize: 15, fontWeight: 400, color: T.meta }}>/ {total}</span></div>
            <div style={{ marginTop: 12, height: 6, borderRadius: 9999, background: T.s100, overflow: 'hidden' }}><div style={{ height: '100%', width: pct + '%', background: T.brand600 }} /></div>
          </Card>
          <Card style={{ padding: 20 }}>
            <div style={{ fontSize: 11, textTransform: 'uppercase', letterSpacing: '.08em', color: T.meta }}>Mode</div>
            <div style={{ fontSize: 16, fontWeight: 600, color: T.ink, marginTop: 8 }}>{MODE_LABEL[a.mode]}</div>
            <div style={{ fontSize: 12, color: T.meta, marginTop: 8 }}>Average: <b style={{ color: '#334155' }}>78.8</b></div>
          </Card>
          <div onClick={onScan} style={{ display: 'flex', flexDirection: 'column', justifyContent: 'center', borderRadius: 16, padding: 20, color: '#fff', background: T.brand600, boxShadow: '0 10px 20px -6px rgba(31,71,230,.35)', cursor: 'pointer' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 11, textTransform: 'uppercase', letterSpacing: '.08em', color: T.brand100 }}><Icon name="scan" size={15} /> Ready to scan</div>
            <div style={{ fontSize: 18, fontWeight: 700, marginTop: 4 }}>Start scanning →</div>
          </div>
        </div>

        <Card style={{ marginTop: 20, padding: 0 }}>
          <div style={{ padding: '16px 24px', borderBottom: `1px solid ${T.line}` }}><h3 style={{ margin: 0, fontSize: 15, fontWeight: 600, color: T.ink }}>Students</h3></div>
          <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 14 }}>
            <thead><tr style={{ background: T.s50, textAlign: 'left', color: T.meta, fontSize: 11, textTransform: 'uppercase', letterSpacing: '.06em' }}>
              {['No.', 'Name', 'Status', 'Score', 'Time', ''].map((h, i) => <th key={i} style={{ padding: '10px 24px', fontWeight: 500, textAlign: i === 5 ? 'right' : 'left' }}>{h}</th>)}
            </tr></thead>
            <tbody>{STUDENTS.map(s => { const score = submittedSet[s.n]; const done = score !== undefined; return <tr key={s.code} style={{ borderTop: `1px solid ${T.line2}` }}>
              <td style={{ padding: '11px 24px', color: T.body }}>{s.n}</td>
              <td style={{ padding: '11px 24px', fontWeight: 500, color: T.ink }}>{s.name}</td>
              <td style={{ padding: '11px 24px' }}>{done
                ? <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, background: '#ecfdf5', color: '#047857', fontSize: 11, fontWeight: 600, padding: '2px 9px', borderRadius: 9999 }}><Icon name="check" size={11} stroke={3} /> submitted</span>
                : <span style={{ fontSize: 12, color: T.faint }}>pending</span>}</td>
              <td style={{ padding: '11px 24px', fontWeight: 600, color: done ? T.ink : '#cbd5e1' }}>{done ? score : '—'}</td>
              <td style={{ padding: '11px 24px', fontSize: 12, color: T.meta }}>{done ? '09:' + (10 + s.n) : '—'}</td>
              <td style={{ padding: '11px 24px', textAlign: 'right', fontSize: 13 }}>{done ? <span style={{ color: '#e11d48', cursor: 'pointer' }}>Undo</span> : <span style={{ color: T.brand700, cursor: 'pointer' }}>Mark submitted</span>}</td>
            </tr>; })}</tbody>
          </table>
        </Card>
      </div>
    </div>;
  }

  // ====================== SCAN ======================
  function ScanScreen({ onBack }) {
    const total = STUDENTS.length;
    const [done, setDone] = useState([1, 2, 4]); // student numbers submitted
    const [code, setCode] = useState('');
    const [toast, setToast] = useState(null);
    const submit = (val) => {
      const s = STUDENTS.find(x => x.code.toLowerCase() === val.trim().toLowerCase());
      if (!s) { setToast({ kind: 'error', msg: `Code [${val}] not found in this class.` }); }
      else if (done.includes(s.n)) { setToast({ kind: 'error', msg: `${s.name} — already submitted` }); }
      else { setDone(d => [s.n, ...d]); setToast({ kind: 'success', msg: `Marked submitted — ${s.name}` }); }
      setCode('');
      setTimeout(() => setToast(null), 2200);
    };
    const recent = done.map(n => STUDENTS.find(s => s.n === n)).filter(Boolean);
    return <div style={{ minHeight: '100vh', background: T.s900, color: '#fff', fontFamily: T.font }}>
      <header style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 20px', borderBottom: '1px solid rgba(255,255,255,.1)' }}>
        <button onClick={onBack} style={{ display: 'inline-flex', alignItems: 'center', gap: 6, background: 'rgba(255,255,255,.08)', color: '#fff', border: 0, borderRadius: 9999, padding: '7px 14px', fontSize: 13, fontWeight: 500, cursor: 'pointer', fontFamily: T.font }}><Icon name="chevron-left" size={14} stroke={2.5} /> Done</button>
        <div style={{ textAlign: 'center' }}><div style={{ fontSize: 14, fontWeight: 600 }}>Reading Quiz — Unit 3</div><div style={{ fontSize: 12, color: '#94a3b8' }}>Grade 5/2 English</div></div>
        <div style={{ width: 78 }} />
      </header>

      <div style={{ maxWidth: 480, margin: '0 auto', padding: '24px 20px 48px' }}>
        {/* live count */}
        <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'center', gap: 8, marginBottom: 20 }}>
          <span style={{ fontSize: 44, fontWeight: 800, fontVariantNumeric: 'tabular-nums', lineHeight: 1 }}>{done.length}</span>
          <span style={{ fontSize: 18, color: '#94a3b8' }}>/ {total} submitted</span>
        </div>

        {/* viewfinder */}
        <div style={{ position: 'relative', aspectRatio: '4/3', borderRadius: 20, overflow: 'hidden', background: '#000', display: 'grid', placeItems: 'center', border: '1px solid rgba(255,255,255,.1)' }}>
          <div style={{ position: 'absolute', inset: 0, background: 'radial-gradient(circle at 50% 45%, #1e293b, #020617)' }} />
          {/* corner frame */}
          <div style={{ position: 'relative', width: 200, height: 200 }}>
            {[[0,0,1,1,0,0],[0,1,0,1,0,0],[1,0,1,0,0,0],[1,1,0,0,0,0]].map((p,i)=>(
              <span key={i} style={{ position:'absolute', width:36, height:36,
                borderTop: i<2?'3px solid #3366ff':'none', borderBottom: i>=2?'3px solid #3366ff':'none',
                borderLeft: i%2===0?'3px solid #3366ff':'none', borderRight: i%2===1?'3px solid #3366ff':'none',
                borderTopLeftRadius:i===0?12:0, borderTopRightRadius:i===1?12:0, borderBottomLeftRadius:i===2?12:0, borderBottomRightRadius:i===3?12:0,
                top:i<2?0:'auto', bottom:i>=2?0:'auto', left:i%2===0?0:'auto', right:i%2===1?0:'auto' }} />
            ))}
            <Icon name="scan" size={48} stroke={1.5} style={{ position: 'absolute', inset: 0, margin: 'auto', color: 'rgba(255,255,255,.25)' }} />
          </div>
          <div style={{ position: 'absolute', bottom: 16, left: 0, right: 0, textAlign: 'center', fontSize: 13, color: '#94a3b8' }}>Point the camera at a student's QR / barcode</div>
        </div>

        {/* toast */}
        {toast && <div style={{ marginTop: 16 }}><Toast kind={toast.kind}>{toast.msg}</Toast></div>}

        {/* manual entry */}
        <div style={{ marginTop: 16 }}>
          <div style={{ fontSize: 12, color: '#94a3b8', marginBottom: 8 }}>Or type the code / use a hardware scanner</div>
          <form onSubmit={e => { e.preventDefault(); if (code.trim()) submit(code); }} style={{ display: 'flex', gap: 8 }}>
            <input value={code} onChange={e => setCode(e.target.value)} placeholder="Type the 8-char code" style={{ flex: 1, background: 'rgba(255,255,255,.06)', border: '1px solid rgba(255,255,255,.15)', borderRadius: 8, padding: '11px 14px', fontSize: 15, color: '#fff', fontFamily: T.mono, letterSpacing: '.05em', boxSizing: 'border-box' }} />
            <button type="submit" style={{ background: T.brand600, color: '#fff', border: 0, borderRadius: 8, padding: '0 22px', fontSize: 14, fontWeight: 600, cursor: 'pointer', fontFamily: T.font }}>OK</button>
          </form>
          <div style={{ fontSize: 11, color: '#64748b', marginTop: 8 }}>Try a real code: <span style={{ fontFamily: T.mono, color: '#cbd5e1' }}>P5K2C9</span> (Chai) · <span style={{ fontFamily: T.mono, color: '#cbd5e1' }}>T9L4E2</span> (Fern)</div>
        </div>

        {/* recently submitted */}
        <div style={{ marginTop: 28 }}>
          <div style={{ fontSize: 12, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.08em', color: '#94a3b8' }}>Recently submitted</div>
          <div style={{ marginTop: 10, display: 'flex', flexDirection: 'column', gap: 8 }}>
            {recent.length === 0 && <div style={{ fontSize: 13, color: '#64748b' }}>Nothing yet — scan a code.</div>}
            {recent.slice(0, 6).map(s => <div key={s.code} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: 'rgba(255,255,255,.05)', borderRadius: 10, padding: '10px 14px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                <span style={{ display: 'grid', placeItems: 'center', width: 26, height: 26, borderRadius: '50%', background: 'rgba(16,185,129,.2)', color: '#34d399' }}><Icon name="check" size={14} stroke={3} /></span>
                <span style={{ fontSize: 14 }}>{s.name}</span>
              </div>
              <span style={{ fontFamily: T.mono, fontSize: 12, color: '#64748b' }}>{s.code}</span>
            </div>)}
          </div>
        </div>
      </div>
    </div>;
  }

  Object.assign(window, { LoginScreen, Dashboard, ClassroomScreen, AssignmentScreen, ScanScreen });
})();
