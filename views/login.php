<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Plant Sphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --g1: #0d2b0a;
            --g2: #1a3d16;
            --g3: #2d5a27;
            --g4: #4a7c44;
            --g5: #8fb986;
            --ga: #a8d5a2;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            font-family: 'Inter', sans-serif;
            height: 100%;
        }

        body {
            min-height: 100vh;
            background: var(--g1);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 16px;
            position: relative;
            overflow-x: hidden;
        }

        /* Video background */
        .video-bg {
            position: fixed; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover; z-index: 0; opacity: 1;
        }
        .scene-overlay {
            position: fixed; inset: 0;
            background: linear-gradient(135deg, rgba(13,43,10,0.42) 0%, rgba(26,61,22,0.35) 50%, rgba(45,90,39,0.28) 100%);
            z-index: 1;
        }

        /* Orbs */
        .orbs { position: fixed; inset: 0; z-index: 2; pointer-events: none; overflow: hidden; }
        .orb {
            position: absolute; border-radius: 50%;
            filter: blur(90px); opacity: 0.18;
            animation: orbFloat linear infinite;
        }
        .orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, #3a7a34, transparent 70%); top: -150px; left: -120px; animation-duration: 20s; }
        .orb-2 { width: 350px; height: 350px; background: radial-gradient(circle, #6dbf67, transparent 70%); bottom: -100px; right: -100px; animation-duration: 24s; animation-delay: -9s; }
        .orb-3 { width: 260px; height: 260px; background: radial-gradient(circle, #a8d5a2, transparent 70%); top: 35%; left: 60%; animation-duration: 17s; animation-delay: -5s; opacity: 0.12; }

        @keyframes orbFloat {
            0%   { transform: translate(0,0) scale(1); }
            25%  { transform: translate(25px,-35px) scale(1.04); }
            50%  { transform: translate(-18px,28px) scale(0.96); }
            75%  { transform: translate(35px,18px) scale(1.06); }
            100% { transform: translate(0,0) scale(1); }
        }

        #particles { position: fixed; inset: 0; z-index: 2; pointer-events: none; }

        /* ── Card ── */
        .auth-wrap {
            position: relative; z-index: 10;
            width: 100%; max-width: 680px;
        }

        .auth-card {
            display: flex;
            width: 100%;
            border-radius: 24px;
            overflow: hidden;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.1),
                0 32px 80px rgba(0,0,0,0.55),
                0 0 60px rgba(168,213,162,0.06);
            transition: transform 0.1s ease;
        }

        /* ── Left panel ── */
        .auth-left {
            width: 38%;
            flex-shrink: 0;
            background: linear-gradient(160deg, #0d2b0a 0%, #1a3d16 50%, #2d5a27 100%);
            border-right: 1px solid rgba(255,255,255,0.07);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 36px 26px;
            position: relative;
            overflow: hidden;
        }
        .auth-left::before {
            content: '';
            position: absolute;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(168,213,162,0.1), transparent 70%);
            bottom: -40px; right: -40px; border-radius: 50%;
        }
        .auth-left::after {
            content: '';
            position: absolute;
            width: 140px; height: 140px;
            background: radial-gradient(circle, rgba(74,124,68,0.12), transparent 70%);
            top: -20px; left: -20px; border-radius: 50%;
        }

        .brand-mark {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 28px; position: relative; z-index: 1;
        }
        .brand-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .brand-icon img {
            width: 100%; height: 100%;
            object-fit: cover; object-position: center;
            transform: scale(1.3);
        }
        .brand-name { font-size: 1.2rem; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
        .brand-name span { color: var(--ga); }

        .panel-headline {
            font-size: 1.3rem; font-weight: 800; color: #fff;
            line-height: 1.25; margin-bottom: 10px; letter-spacing: -0.3px;
            position: relative; z-index: 1;
        }
        .panel-headline em { font-style: normal; color: var(--ga); }

        .panel-sub {
            font-size: 0.73rem; color: rgba(255,255,255,0.52);
            line-height: 1.65; margin-bottom: 24px;
            position: relative; z-index: 1;
        }

        .feature-pills {
            display: flex; flex-wrap: wrap; gap: 7px;
            position: relative; z-index: 1;
        }
        .feature-pill {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(168,213,162,0.07);
            border: 1px solid rgba(168,213,162,0.14);
            border-radius: 50px; padding: 5px 11px;
            font-size: 0.68rem; color: rgba(255,255,255,0.75);
        }
        .feature-pill i { color: var(--ga); font-size: 0.68rem; }

        /* ── Right panel ── */
        .auth-right {
            width: 62%;
            background: #1a3d16;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 36px 32px;
            position: relative;
            overflow: hidden;
        }

        .form-eyebrow {
            font-size: 0.63rem; font-weight: 700; letter-spacing: 2px;
            text-transform: uppercase; color: var(--ga); margin-bottom: 3px;
        }
        .form-title {
            font-size: 1.4rem; font-weight: 800; color: #fff;
            letter-spacing: -0.3px; margin-bottom: 3px;
        }
        .form-subtitle { font-size: 0.75rem; color: rgba(255,255,255,0.45); margin-bottom: 18px; }

        /* Alerts */
        .ps-alert {
            display: flex; align-items: flex-start; gap: 8px;
            padding: 9px 13px; border-radius: 9px;
            font-size: 0.76rem; margin-bottom: 14px;
        }
        .ps-alert-danger {
            background: rgba(220,53,69,0.14);
            border: 1px solid rgba(220,53,69,0.28);
            color: #ff8a8a;
        }
        .ps-alert-success {
            background: rgba(40,167,69,0.14);
            border: 1px solid rgba(40,167,69,0.28);
            color: #a8d5a2;
        }
        .ps-alert i { margin-top: 1px; flex-shrink: 0; }

        /* Form */
        .form-group { margin-bottom: 13px; }
        .form-label {
            font-size: 0.72rem; font-weight: 600;
            color: rgba(255,255,255,0.7);
            margin-bottom: 5px; display: block;
        }

        .input-wrap { position: relative; }
        .input-wrap .ps-input { padding-left: 34px; }
        .input-wrap .i-icon {
            position: absolute; left: 10px; top: 50%;
            transform: translateY(-50%);
            color: rgba(168,213,162,0.55); font-size: 0.82rem;
            pointer-events: none; transition: color 0.2s;
        }
        .input-wrap:focus-within .i-icon { color: var(--ga); }
        .input-wrap .toggle-pw {
            position: absolute; right: 9px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: rgba(255,255,255,0.3);
            cursor: pointer; font-size: 0.82rem; padding: 2px;
            transition: color 0.2s;
        }
        .input-wrap .toggle-pw:hover { color: var(--ga); }

        .ps-input {
            width: 100%; padding: 9px 11px;
            background: #0d2b0a;
            border: 1px solid rgba(168,213,162,0.25);
            border-radius: 9px;
            font-size: 0.82rem; font-family: 'Inter', sans-serif;
            color: #fff; outline: none;
            transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
        }
        .ps-input::placeholder { color: rgba(255,255,255,0.28); }
        .ps-input:focus {
            border-color: rgba(168,213,162,0.55);
            background: #122b10;
            box-shadow: 0 0 0 3px rgba(168,213,162,0.12),
                        0 0 18px rgba(168,213,162,0.08);
        }

        .btn-auth {
            width: 100%; padding: 10px;
            background: linear-gradient(135deg, var(--g3) 0%, var(--g4) 100%);
            color: #fff; border: none; border-radius: 9px;
            font-size: 0.85rem; font-weight: 700;
            font-family: 'Inter', sans-serif; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 7px;
            margin-top: 6px; position: relative; overflow: hidden;
            transition: transform 0.15s, box-shadow 0.25s;
            box-shadow: 0 4px 20px rgba(45,90,39,0.4);
        }
        .btn-auth::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent);
            opacity: 0; transition: opacity 0.25s;
        }
        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(45,90,39,0.55), 0 0 18px rgba(168,213,162,0.18);
        }
        .btn-auth:hover::before { opacity: 1; }
        .btn-auth:active { transform: translateY(0); }

        .ripple {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.22);
            transform: scale(0);
            animation: rippleAnim 0.6s linear;
            pointer-events: none;
        }
        @keyframes rippleAnim { to { transform: scale(4); opacity: 0; } }

        .auth-switch {
            text-align: center; margin-top: 14px;
            font-size: 0.73rem; color: rgba(255,255,255,0.4);
        }
        .auth-switch a {
            color: var(--ga); font-weight: 700; text-decoration: none;
            transition: color 0.2s;
        }
        .auth-switch a:hover { color: #fff; }

        @media (max-width: 600px) {
            .auth-card { flex-direction: column; }
            .auth-left, .auth-right { width: 100%; }
            body { overflow-y: auto; }
        }
    </style>
</head>
<body>

<video class="video-bg" autoplay muted loop playsinline>
    <source src="assets/videos/background.mp4" type="video/mp4">
</video>
<div class="scene-overlay"></div>

<div class="orbs">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
</div>

<canvas id="particles"></canvas>

<div class="auth-wrap">
    <div class="auth-card" id="authCard">

        <!-- LEFT -->
        <div class="auth-left">
            <div class="brand-mark">
                <div class="brand-icon">
                    <img src="assets/img/logo.png" alt="PlantSphere Logo">
                </div>
                <div class="brand-name">Plant<span>Sphere</span></div>
            </div>

            <h1 class="panel-headline">Growing a <em>Greener</em><br>Tomorrow</h1>
            <p class="panel-sub">
                A unified platform for managing tree planting activities,
                seedling requests, and environmental programs across communities.
            </p>

            <div class="feature-pills">
                <span class="feature-pill"><i class="bi bi-geo-alt-fill"></i> Site Validation</span>
                <span class="feature-pill"><i class="bi bi-clipboard2-check-fill"></i> Request Tracking</span>
                <span class="feature-pill"><i class="bi bi-people-fill"></i> Multi-Role Access</span>
                <span class="feature-pill"><i class="bi bi-bar-chart-fill"></i> Reports</span>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="auth-right">
            <div class="form-eyebrow">Welcome back</div>
            <h2 class="form-title">Sign in</h2>
            <p class="form-subtitle">Enter your credentials to access your dashboard</p>

            <?php if (isset($error)): ?>
                <div class="ps-alert ps-alert-danger">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="ps-alert ps-alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <form action="index.php?action=login" method="POST" autocomplete="on">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope i-icon"></i>
                        <input type="email" id="email" name="email" class="ps-input"
                               placeholder="name@example.com" required autofocus autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock i-icon"></i>
                        <input type="password" id="password" name="password" class="ps-input"
                               placeholder="••••••••" required autocomplete="current-password"
                               style="padding-right: 34px;">
                        <button type="button" class="toggle-pw"
                                onclick="togglePassword('password', this)"
                                tabindex="-1" aria-label="Toggle password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </button>
            </form>

            <div class="auth-switch">
                Don't have an account? <a href="index.php?action=register">Register here</a>
            </div>
        </div>

    </div>
</div>

<script>
function togglePassword(id, btn) {
    const inp  = document.getElementById(id);
    const icon = btn.querySelector('i');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    icon.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

// Ripple
document.getElementById('loginBtn').addEventListener('click', function(e) {
    const btn  = this;
    const rect = btn.getBoundingClientRect();
    const r    = document.createElement('span');
    const size = Math.max(btn.offsetWidth, btn.offsetHeight);
    r.className = 'ripple';
    r.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px`;
    btn.appendChild(r);
    setTimeout(() => r.remove(), 700);
});

// Mouse parallax
const card = document.getElementById('authCard');
document.addEventListener('mousemove', e => {
    const cx = window.innerWidth  / 2;
    const cy = window.innerHeight / 2;
    const dx = (e.clientX - cx) / cx;
    const dy = (e.clientY - cy) / cy;
    card.style.transform = `perspective(1200px) rotateY(${dx * 2.5}deg) rotateX(${-dy * 1.5}deg)`;
});
document.addEventListener('mouseleave', () => {
    card.style.transform = 'perspective(1200px) rotateY(0deg) rotateX(0deg)';
});

// Particles
(function() {
    const canvas = document.getElementById('particles');
    const ctx    = canvas.getContext('2d');
    let W, H, particles = [];

    function resize() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    function rand(a, b) { return Math.random() * (b - a) + a; }

    class Particle {
        constructor() { this.reset(); }
        reset() {
            this.x    = rand(0, W);
            this.y    = rand(0, H);
            this.r    = rand(0.8, 2.2);
            this.vx   = rand(-0.25, 0.25);
            this.vy   = rand(-0.55, -0.12);
            this.life = rand(0.3, 1);
            this.fade = rand(0.002, 0.005);
        }
        update() {
            this.x += this.vx; this.y += this.vy; this.life -= this.fade;
            if (this.life <= 0 || this.y < -10) this.reset();
        }
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(168,213,162,${this.life * 0.55})`;
            ctx.fill();
        }
    }

    for (let i = 0; i < 90; i++) particles.push(new Particle());

    (function loop() {
        ctx.clearRect(0, 0, W, H);
        particles.forEach(p => { p.update(); p.draw(); });
        requestAnimationFrame(loop);
    })();
})();
</script>
</body>
</html>
