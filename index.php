<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReClaimQR — Scan. Find. Claim.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary:       #7c83fd;
            --primary-dark:  #5a61e8;
            --primary-light: #a5a9fe;
            --accent:        #ff6b6b;
            --dark:          #1e1e2e;
            --text:          #3d3d5c;
            --muted:         #888;
            --bg:            #f0f2ff;
            --white:         #ffffff;
            --card-shadow:   0 8px 32px rgba(124,131,253,0.13);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* ─── NAVBAR ────────────────────────────── */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 60px;
            background: rgba(240,242,255,0.88);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(124,131,253,0.1);
            transition: box-shadow 0.3s;
        }
        .navbar.scrolled { box-shadow: 0 4px 24px rgba(124,131,253,0.13); }

        .nav-logo {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }
        .nav-logo-icon {
            width: 38px; height: 38px;
            background: var(--primary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .nav-logo-text {
            font-family: 'Poppins', sans-serif;
            font-size: 20px; font-weight: 900;
            color: var(--dark); letter-spacing: -0.5px;
        }
        .nav-logo-text span { color: var(--primary); }

        .nav-btns { display: flex; gap: 12px; align-items: center; }

        .btn-outline {
            padding: 10px 26px;
            border: 2px solid var(--primary);
            border-radius: 25px;
            color: var(--primary);
            font-size: 14px; font-weight: 800;
            font-family: 'Nunito', sans-serif;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-outline:hover {
            background: var(--primary); color: white;
            transform: translateY(-1px);
        }
        .btn-solid {
            padding: 10px 26px;
            border: 2px solid var(--primary);
            border-radius: 25px;
            background: var(--primary); color: white;
            font-size: 14px; font-weight: 800;
            font-family: 'Nunito', sans-serif;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(124,131,253,0.35);
        }
        .btn-solid:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(124,131,253,0.45);
        }

        /* ─── HERO ──────────────────────────────── */
        .hero {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            text-align: center;
            padding: 120px 24px 100px;
            position: relative; overflow: hidden;
        }

        .blob {
            position: absolute; border-radius: 50%;
            filter: blur(72px); opacity: 0.32;
            animation: blobFloat 9s ease-in-out infinite;
            pointer-events: none;
        }
        .blob-1 { width: 520px; height: 520px; background: var(--primary-light); top: -120px; left: -160px; animation-delay: 0s; }
        .blob-2 { width: 420px; height: 420px; background: #ff6b6b;              bottom: -100px; right: -120px; animation-delay: -3.5s; }
        .blob-3 { width: 320px; height: 320px; background: #7c83fd;              top: 45%; right: 8%; animation-delay: -6s; }

        @keyframes blobFloat {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(18px,-18px) scale(1.05); }
            66%      { transform: translate(-14px,14px) scale(0.96); }
        }

        .dots-grid {
            position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(124,131,253,0.16) 1.5px, transparent 1.5px);
            background-size: 36px 36px;
            pointer-events: none;
        }

        .hero-content { position: relative; z-index: 2; max-width: 800px; }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: white;
            border: 1.5px solid rgba(124,131,253,0.22);
            border-radius: 30px;
            padding: 7px 18px;
            font-size: 13px; font-weight: 700;
            color: var(--primary);
            margin-bottom: 28px;
            box-shadow: 0 2px 12px rgba(124,131,253,0.10);
            animation: fadeUp 0.7s ease both;
        }
        .badge-dot {
            width: 8px; height: 8px;
            background: var(--primary); border-radius: 50%;
            animation: pulseDot 2s infinite;
        }
        @keyframes pulseDot {
            0%,100% { opacity:1; transform:scale(1); }
            50%      { opacity:0.45; transform:scale(1.5); }
        }

        .hero-title {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(42px, 7.5vw, 78px);
            font-weight: 900;
            line-height: 1.07;
            color: var(--dark);
            letter-spacing: -2.5px;
            margin-bottom: 22px;
            animation: fadeUp 0.7s 0.1s ease both;
        }
        .hero-title .hl {
            color: var(--primary);
            position: relative; display: inline-block;
        }
        .hero-title .hl::after {
            content: '';
            position: absolute;
            bottom: 6px; left: 0; right: 0;
            height: 7px;
            background: linear-gradient(90deg, var(--primary-light), var(--primary));
            border-radius: 4px; opacity: 0.3;
        }

        .hero-sub {
            font-size: 18px; color: var(--muted);
            line-height: 1.75; max-width: 560px;
            margin: 0 auto 40px; font-weight: 600;
            animation: fadeUp 0.7s 0.2s ease both;
        }

        .hero-btns {
            display: flex; gap: 14px;
            justify-content: center; flex-wrap: wrap;
            animation: fadeUp 0.7s 0.3s ease both;
        }
        .hero-btn-main {
            padding: 16px 40px;
            background: var(--primary); color: white;
            border-radius: 30px;
            font-size: 16px; font-weight: 800;
            font-family: 'Nunito', sans-serif;
            text-decoration: none;
            box-shadow: 0 6px 24px rgba(124,131,253,0.42);
            transition: all 0.25s;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .hero-btn-main:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(124,131,253,0.52);
        }
        .hero-btn-sec {
            padding: 16px 40px;
            background: white; color: var(--primary);
            border-radius: 30px;
            font-size: 16px; font-weight: 800;
            font-family: 'Nunito', sans-serif;
            text-decoration: none;
            border: 2px solid rgba(124,131,253,0.18);
            transition: all 0.25s;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }
        .hero-btn-sec:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .scroll-hint {
            position: absolute; bottom: 32px; left: 50%;
            transform: translateX(-50%);
            display: flex; flex-direction: column;
            align-items: center; gap: 6px;
            color: var(--muted);
            font-size: 12px; font-weight: 700;
            animation: fadeUp 0.7s 0.7s ease both;
        }
        .scroll-line {
            width: 2px; height: 38px;
            background: linear-gradient(to bottom, var(--primary), transparent);
            border-radius: 2px;
            animation: scrollAnim 1.9s ease-in-out infinite;
        }
        @keyframes scrollAnim {
            0%,100% { opacity:1; transform:scaleY(1); }
            50%      { opacity:0.25; transform:scaleY(0.4); }
        }

        /* ─── STATS ─────────────────────────────── */
        .stats-strip {
            background: white;
            border-top: 1px solid rgba(124,131,253,0.09);
            border-bottom: 1px solid rgba(124,131,253,0.09);
            padding: 36px 60px;
            display: flex; justify-content: center;
        }
        .stat-item {
            flex: 1; max-width: 220px;
            text-align: center;
            padding: 0 28px;
            border-right: 1px solid rgba(124,131,253,0.1);
        }
        .stat-item:last-child { border-right: none; }
        .stat-num {
            font-family: 'Poppins', sans-serif;
            font-size: 38px; font-weight: 900;
            color: var(--primary); line-height: 1;
        }
        .stat-label {
            font-size: 13px; color: var(--muted);
            font-weight: 700; margin-top: 5px;
        }

        /* ─── SHARED SECTION STYLES ─────────────── */
        section { padding: 96px 60px; }

        .section-tag {
            display: inline-block;
            background: rgba(124,131,253,0.1);
            color: var(--primary);
            font-size: 11.5px; font-weight: 800;
            padding: 5px 14px; border-radius: 20px;
            letter-spacing: 1.2px; text-transform: uppercase;
            margin-bottom: 14px;
        }
        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(28px, 4vw, 44px);
            font-weight: 900; color: var(--dark);
            line-height: 1.12; letter-spacing: -1.2px;
            margin-bottom: 14px;
        }
        .section-sub {
            font-size: 16px; color: var(--muted);
            font-weight: 600; line-height: 1.75;
            max-width: 520px;
        }

        /* ─── HOW IT WORKS ──────────────────────── */
        .how-section { background: white; }
        .how-header  { text-align: center; }
        .how-header .section-sub { margin: 0 auto; }

        .how-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px; margin-top: 56px;
        }
        .how-card {
            background: var(--bg);
            border-radius: 24px; padding: 36px 28px;
            position: relative; overflow: hidden;
            border: 1.5px solid rgba(124,131,253,0.09);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .how-card:hover {
            transform: translateY(-7px);
            box-shadow: var(--card-shadow);
        }
        .how-num {
            position: absolute; top: 18px; right: 22px;
            font-family: 'Poppins', sans-serif;
            font-size: 68px; font-weight: 900;
            color: rgba(124,131,253,0.07); line-height: 1;
            pointer-events: none;
        }
        .how-icon {
            width: 62px; height: 62px;
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            color: white; margin-bottom: 22px;
        }
        .hi-blue   { background: var(--primary);  box-shadow: 0 6px 18px rgba(124,131,253,0.38); }
        .hi-red    { background: var(--accent);    box-shadow: 0 6px 18px rgba(255,107,107,0.38); }
        .hi-green  { background: #27ae60;          box-shadow: 0 6px 18px rgba(39,174,96,0.38); }

        .how-card h4 {
            font-family: 'Poppins', sans-serif;
            font-size: 18px; font-weight: 700;
            color: var(--dark); margin-bottom: 10px;
        }
        .how-card p {
            font-size: 14px; color: var(--muted);
            line-height: 1.72; font-weight: 600;
        }

        /* ─── FEATURES ──────────────────────────── */
        .features-section { background: var(--bg); }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px; margin-top: 52px;
        }
        .feature-card {
            background: white; border-radius: 20px;
            padding: 30px; display: flex;
            gap: 20px; align-items: flex-start;
            border: 1.5px solid rgba(124,131,253,0.07);
            transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow);
            border-color: rgba(124,131,253,0.22);
        }
        .feat-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .fi-p { background: rgba(124,131,253,0.11); color: var(--primary); }
        .fi-r { background: rgba(255,107,107,0.11); color: var(--accent); }
        .fi-g { background: rgba(39,174,96,0.11);   color: #27ae60; }
        .fi-o { background: rgba(255,159,67,0.11);  color: #ff9f43; }

        .feature-card h4 {
            font-family: 'Poppins', sans-serif;
            font-size: 16px; font-weight: 700;
            color: var(--dark); margin-bottom: 7px;
        }
        .feature-card p {
            font-size: 13.5px; color: var(--muted);
            line-height: 1.72; font-weight: 600;
        }

        /* ─── QR SPOTLIGHT ──────────────────────── */
        .qr-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex; align-items: center; gap: 80px;
            position: relative; overflow: hidden;
        }
        .qr-section::before {
            content: ''; position: absolute;
            width: 520px; height: 520px;
            background: rgba(255,255,255,0.05); border-radius: 50%;
            top: -220px; right: -80px; pointer-events: none;
        }
        .qr-section::after {
            content: ''; position: absolute;
            width: 320px; height: 320px;
            background: rgba(255,255,255,0.05); border-radius: 50%;
            bottom: -160px; left: 180px; pointer-events: none;
        }

        .qr-text { flex: 1; position: relative; z-index: 2; }
        .qr-text .section-tag  { background: rgba(255,255,255,0.15); color: white; }
        .qr-text .section-title { color: white; }
        .qr-text .section-sub   { color: rgba(255,255,255,0.78); max-width: 440px; }

        .qr-list {
            list-style: none; margin: 28px 0 36px;
            display: flex; flex-direction: column; gap: 12px;
        }
        .qr-list li {
            display: flex; align-items: center; gap: 12px;
            font-size: 15px; font-weight: 700;
            color: rgba(255,255,255,0.92);
        }
        .qr-list li .check {
            width: 24px; height: 24px; flex-shrink: 0;
            background: rgba(255,255,255,0.18); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

        .qr-cta {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 14px 32px;
            background: white; color: var(--primary);
            border-radius: 30px;
            font-size: 15px; font-weight: 800;
            font-family: 'Nunito', sans-serif;
            text-decoration: none;
            transition: all 0.25s;
            box-shadow: 0 6px 24px rgba(0,0,0,0.16);
        }
        .qr-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.22);
        }

        .qr-visual { flex-shrink: 0; position: relative; z-index: 2; }
        .qr-box {
            width: 240px; height: 240px;
            background: white; border-radius: 28px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 24px 64px rgba(0,0,0,0.22);
            animation: qrFloat 4s ease-in-out infinite;
        }
        @keyframes qrFloat {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-14px); }
        }

        /* ─── CTA SECTION ───────────────────────── */
        .cta-section { background: white; text-align: center; }
        .cta-section .section-title { margin: 0 auto 12px; }
        .cta-section .section-sub   { margin: 0 auto 40px; }

        .cta-btns {
            display: flex; gap: 14px;
            justify-content: center; flex-wrap: wrap;
        }
        .cta-main {
            padding: 16px 48px;
            background: var(--primary); color: white;
            border-radius: 30px;
            font-size: 16px; font-weight: 800;
            font-family: 'Nunito', sans-serif;
            text-decoration: none;
            box-shadow: 0 6px 24px rgba(124,131,253,0.42);
            transition: all 0.25s;
        }
        .cta-main:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(124,131,253,0.52);
        }
        .cta-sec {
            padding: 16px 48px;
            background: var(--bg); color: var(--primary);
            border-radius: 30px;
            font-size: 16px; font-weight: 800;
            font-family: 'Nunito', sans-serif;
            text-decoration: none;
            border: 2px solid rgba(124,131,253,0.18);
            transition: all 0.25s;
        }
        .cta-sec:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
        }

        /* ─── FOOTER ────────────────────────────── */
        footer {
            background: var(--dark);
            color: rgba(255,255,255,0.45);
            text-align: center;
            padding: 30px 24px;
            font-size: 13px; font-weight: 600;
        }
        footer span { color: var(--primary-light); }

        /* ─── ANIMATIONS ────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(26px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .reveal {
            opacity: 0; transform: translateY(34px);
            transition: opacity 0.65s ease, transform 0.65s ease;
        }
        .reveal.visible { opacity: 1; transform: translateY(0); }
        .rd1 { transition-delay: 0.1s; }
        .rd2 { transition-delay: 0.2s; }
        .rd3 { transition-delay: 0.3s; }
        .rd4 { transition-delay: 0.4s; }

        /* ─── RESPONSIVE ────────────────────────── */
        @media (max-width: 960px) {
            .navbar { padding: 16px 24px; }
            section { padding: 72px 24px; }
            .how-grid      { grid-template-columns: 1fr; }
            .features-grid { grid-template-columns: 1fr; }
            .qr-section    { flex-direction: column; gap: 40px; padding: 72px 24px; text-align: center; }
            .qr-text .section-sub { margin: 0 auto; }
            .qr-list       { align-items: center; }
            .stats-strip   { flex-wrap: wrap; padding: 36px 24px; gap: 24px; }
            .stat-item     { border-right: none; border-bottom: 1px solid rgba(124,131,253,0.1); padding-bottom: 20px; }
            .stat-item:last-child { border-bottom: none; padding-bottom: 0; }
        }
    </style>
</head>
<body>

<!-- ══ NAVBAR ══════════════════════════════════════ -->
<nav class="navbar" id="navbar">
    <a href="index.php" class="nav-logo">
        <div class="nav-logo-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/><circle cx="17.5" cy="17.5" r="2.5"/>
            </svg>
        </div>
        <span class="nav-logo-text">Re<span>Claim</span>QR</span>
    </a>
    <div class="nav-btns">
        <a href="login.php"    class="btn-outline">Log In</a>
        <a href="register.php" class="btn-solid">Sign Up</a>
    </div>
</nav>

<!-- ══ HERO ════════════════════════════════════════ -->
<section class="hero">
    <div class="dots-grid"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="hero-content">
        <div class="hero-badge">
            <span class="badge-dot"></span>
            Smart Campus Lost &amp; Found System
        </div>

        <h1 class="hero-title">
            Lost something?<br>
            <span class="hl">We'll help</span> you find it.
        </h1>

        <p class="hero-sub">
            ReClaimQR connects people who lose things with people who find them —
            powered by QR codes, real-time messaging, and a simple claim process.
        </p>

        <div class="hero-btns">
            <a href="register.php" class="hero-btn-main">
                Get Started Free
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
            <a href="login.php" class="hero-btn-sec">Log In to My Account</a>
        </div>
    </div>

    <div class="scroll-hint">
        <span>Scroll</span>
        <div class="scroll-line"></div>
    </div>
</section>

<!-- ══ STATS STRIP ══════════════════════════════════ -->
<div class="stats-strip">
    <div class="stat-item reveal">
        <div class="stat-num" data-target="500">0</div>
        <div class="stat-label">Items Reported</div>
    </div>
    <div class="stat-item reveal rd1">
        <div class="stat-num" data-target="320">0</div>
        <div class="stat-label">Items Returned</div>
    </div>
    <div class="stat-item reveal rd2">
        <div class="stat-num" data-target="200">0</div>
        <div class="stat-label">Happy Users</div>
    </div>
    <div class="stat-item reveal rd3">
        <div class="stat-num" data-target="98" data-suffix="%">0</div>
        <div class="stat-label">Satisfaction Rate</div>
    </div>
</div>

<!-- ══ HOW IT WORKS ══════════════════════════════════ -->
<section class="how-section">
    <div class="how-header">
        <div class="section-tag reveal">How It Works</div>
        <h2 class="section-title reveal rd1">Three simple steps<br>to recover your item</h2>
        <p class="section-sub reveal rd2">No complicated process — just report, connect, and claim your item back.</p>
    </div>

    <div class="how-grid">
        <div class="how-card reveal">
            <div class="how-num">01</div>
            <div class="how-icon hi-blue">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </div>
            <h4>Report Lost or Found</h4>
            <p>Describe the item, upload a photo, set the location and date. Takes less than 2 minutes and it's live on the dashboard instantly.</p>
        </div>

        <div class="how-card reveal rd1">
            <div class="how-num">02</div>
            <div class="how-icon hi-red">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <h4>Connect &amp; Chat</h4>
            <p>Owner and finder connect through the in-app messaging system — linked to the specific item — to arrange a meetup safely.</p>
        </div>

        <div class="how-card reveal rd2">
            <div class="how-num">03</div>
            <div class="how-icon hi-green">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <h4>Claim &amp; Close</h4>
            <p>Claimant submits proof of ownership, the reporter confirms, and the item is marked as returned. Clean and done!</p>
        </div>
    </div>
</section>

<!-- ══ FEATURES ══════════════════════════════════════ -->
<section class="features-section">
    <div class="section-tag reveal">Features</div>
    <h2 class="section-title reveal rd1">Everything you need<br>to reclaim what's yours</h2>
    <p class="section-sub reveal rd2">Built for students and campus communities to make lost &amp; found fast and trustworthy.</p>

    <div class="features-grid">
        <div class="feature-card reveal">
            <div class="feat-icon fi-p">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/><circle cx="17.5" cy="17.5" r="2.5"/>
                </svg>
            </div>
            <div>
                <h4>QR Code Generation</h4>
                <p>Generate a unique QR code for your valuables. Anyone who scans it gets directed straight to your contact info — no app needed on their end.</p>
            </div>
        </div>

        <div class="feature-card reveal rd1">
            <div class="feat-icon fi-r">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <div>
                <h4>In-App Messaging</h4>
                <p>Chat directly with the finder or owner in real time. Every conversation is permanently linked to the specific item it's about.</p>
            </div>
        </div>

        <div class="feature-card reveal rd2">
            <div class="feat-icon fi-g">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <div>
                <h4>Claim with Proof</h4>
                <p>Claimants upload proof of ownership before the item is handed over — keeping the process fair and secure for everyone.</p>
            </div>
        </div>

        <div class="feature-card reveal rd3">
            <div class="feat-icon fi-o">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 17H2a3 3 0 0 0 3-3V9a7 7 0 0 1 14 0v5a3 3 0 0 0 3 3zm-8.27 4a2 2 0 0 1-3.46 0"/>
                </svg>
            </div>
            <div>
                <h4>Email Notifications</h4>
                <p>Get notified by email the moment someone claims your reported item — so you never miss an update even when you're offline.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══ QR SPOTLIGHT ══════════════════════════════════ -->
<section class="qr-section">
    <div class="qr-text">
        <div class="section-tag reveal">QR Technology</div>
        <h2 class="section-title reveal rd1">Tag it once.<br>Never lose it again.</h2>
        <p class="section-sub reveal rd2">
            Generate a personal QR code for your laptop, bag, water bottle — anything valuable.
            Stick it on, and if it's ever found, the finder scans and reaches you instantly.
        </p>
        <ul class="qr-list reveal rd3">
            <li>
                <span class="check">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                Unique QR code per item
            </li>
            <li>
                <span class="check">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                Instant contact page when scanned
            </li>
            <li>
                <span class="check">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                No account needed to scan
            </li>
            <li>
                <span class="check">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                Download &amp; print anytime
            </li>
        </ul>
        <a href="register.php" class="qr-cta reveal rd4">
            Generate My QR Code
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
            </svg>
        </a>
    </div>

    <div class="qr-visual">
        <div class="qr-box">
            <!-- QR Code style SVG illustration -->
            <svg width="170" height="170" viewBox="0 0 170 170" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Top-left finder pattern -->
                <rect x="10" y="10" width="54" height="54" rx="7" fill="#7c83fd" opacity="0.13"/>
                <rect x="17" y="17" width="40" height="40" rx="5" fill="#7c83fd" opacity="0.28"/>
                <rect x="26" y="26" width="22" height="22" rx="3" fill="#7c83fd"/>
                <!-- Top-right finder pattern -->
                <rect x="106" y="10" width="54" height="54" rx="7" fill="#7c83fd" opacity="0.13"/>
                <rect x="113" y="17" width="40" height="40" rx="5" fill="#7c83fd" opacity="0.28"/>
                <rect x="122" y="26" width="22" height="22" rx="3" fill="#7c83fd"/>
                <!-- Bottom-left finder pattern -->
                <rect x="10" y="106" width="54" height="54" rx="7" fill="#7c83fd" opacity="0.13"/>
                <rect x="17" y="113" width="40" height="40" rx="5" fill="#7c83fd" opacity="0.28"/>
                <rect x="26" y="122" width="22" height="22" rx="3" fill="#7c83fd"/>
                <!-- Data modules -->
                <rect x="76" y="10"  width="18" height="9"  rx="2" fill="#7c83fd" opacity="0.55"/>
                <rect x="76" y="26"  width="9"  height="18" rx="2" fill="#7c83fd" opacity="0.4"/>
                <rect x="89" y="26"  width="9"  height="9"  rx="2" fill="#7c83fd" opacity="0.7"/>
                <rect x="76" y="48"  width="18" height="9"  rx="2" fill="#7c83fd" opacity="0.5"/>
                <rect x="106" y="76" width="9"  height="18" rx="2" fill="#7c83fd" opacity="0.6"/>
                <rect x="120" y="76" width="9"  height="9"  rx="2" fill="#7c83fd" opacity="0.4"/>
                <rect x="134" y="76" width="26" height="9"  rx="2" fill="#7c83fd" opacity="0.7"/>
                <rect x="106" y="90" width="18" height="9"  rx="2" fill="#7c83fd" opacity="0.5"/>
                <rect x="129" y="90" width="9"  height="9"  rx="2" fill="#7c83fd" opacity="0.65"/>
                <rect x="76" y="76"  width="18" height="9"  rx="2" fill="#7c83fd" opacity="0.5"/>
                <rect x="76" y="90"  width="9"  height="9"  rx="2" fill="#7c83fd" opacity="0.6"/>
                <rect x="89" y="90"  width="9"  height="18" rx="2" fill="#7c83fd" opacity="0.4"/>
                <rect x="10" y="76"  width="54" height="9"  rx="2" fill="#7c83fd" opacity="0.38"/>
                <rect x="10" y="90"  width="9"  height="9"  rx="2" fill="#7c83fd" opacity="0.6"/>
                <rect x="26" y="90"  width="24" height="9"  rx="2" fill="#7c83fd" opacity="0.5"/>
                <rect x="54" y="90"  width="9"  height="26" rx="2" fill="#7c83fd" opacity="0.42"/>
                <rect x="106" y="120" width="9" height="9"  rx="2" fill="#7c83fd" opacity="0.7"/>
                <rect x="120" y="106" width="9" height="24" rx="2" fill="#7c83fd" opacity="0.52"/>
                <rect x="134" y="106" width="26" height="9" rx="2" fill="#7c83fd" opacity="0.6"/>
                <rect x="134" y="120" width="9" height="9"  rx="2" fill="#7c83fd" opacity="0.42"/>
                <rect x="151" y="120" width="9" height="40" rx="2" fill="#7c83fd" opacity="0.5"/>
                <rect x="106" y="134" width="40" height="9" rx="2" fill="#7c83fd" opacity="0.6"/>
                <rect x="10" y="120"  width="9" height="40" rx="2" fill="#7c83fd" opacity="0.5"/>
                <rect x="26" y="134"  width="24" height="9" rx="2" fill="#7c83fd" opacity="0.42"/>
                <rect x="54" y="120"  width="9" height="9"  rx="2" fill="#7c83fd" opacity="0.7"/>
            </svg>
        </div>
    </div>
</section>

<!-- ══ CTA ══════════════════════════════════════════ -->
<section class="cta-section">
    <div class="section-tag reveal" style="display:inline-block;">Ready to start?</div>
    <h2 class="section-title reveal rd1">Join ReClaimQR today</h2>
    <p class="section-sub reveal rd2">
        Report a lost item, help return a found one, or tag your valuables with a QR code —
        all in one place, completely free.
    </p>
    <div class="cta-btns reveal rd3">
        <a href="register.php" class="cta-main">Create Free Account</a>
        <a href="login.php"    class="cta-sec">Log In</a>
    </div>
</section>

<!-- ══ FOOTER ════════════════════════════════════════ -->
<footer>
    <p>© <?php echo date('Y'); ?> <span>ReClaimQR</span> — Scan. Find. Claim.</p>
</footer>

<script>
// Navbar shadow on scroll
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
});

// Scroll reveal
const revealEls = document.querySelectorAll('.reveal');
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.classList.add('visible');
            revealObs.unobserve(e.target);
        }
    });
}, { threshold: 0.12 });
revealEls.forEach(el => revealObs.observe(el));

// Animated counters
const counters = document.querySelectorAll('.stat-num[data-target]');
const countObs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (!e.isIntersecting) return;
        const el     = e.target;
        const target = parseInt(el.dataset.target);
        const suffix = el.dataset.suffix || '+';
        const dur    = 1600;
        const step   = 16;
        const inc    = target / (dur / step);
        let cur = 0;
        const timer = setInterval(() => {
            cur += inc;
            if (cur >= target) { cur = target; clearInterval(timer); }
            el.textContent = Math.floor(cur) + suffix;
        }, step);
        countObs.unobserve(el);
    });
}, { threshold: 0.5 });
counters.forEach(c => countObs.observe(c));
</script>

</body>
</html>