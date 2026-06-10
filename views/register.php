<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — Plant Sphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

        html, body { font-family: 'Inter', sans-serif; }

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

        /* Video */
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
            width: 100%; max-width: 740px;
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
            width: 36%;
            flex-shrink: 0;
            background: linear-gradient(160deg, #0d2b0a 0%, #1a3d16 50%, #2d5a27 100%);
            border-right: 1px solid rgba(255,255,255,0.07);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 32px 24px;
            position: relative;
            overflow: hidden;
        }
        .auth-left::before {
            content: '';
            position: absolute;
            width: 180px; height: 180px;
            background: radial-gradient(circle, rgba(168,213,162,0.1), transparent 70%);
            bottom: -30px; right: -30px; border-radius: 50%;
        }
        .auth-left::after {
            content: '';
            position: absolute;
            width: 130px; height: 130px;
            background: radial-gradient(circle, rgba(74,124,68,0.12), transparent 70%);
            top: -20px; left: -20px; border-radius: 50%;
        }

        .brand-mark {
            display: flex; align-items: center; gap: 9px;
            margin-bottom: 22px; position: relative; z-index: 1;
        }
        .brand-icon {
            width: 52px; height: 52px;
            border-radius: 50%;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            background: transparent;
        }
        .brand-icon img {
            width: 100%; height: 100%;
            object-fit: cover;
            object-position: center;
            transform: scale(1.3);
        }
        .brand-name { font-size: 1.15rem; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
        .brand-name span { color: var(--ga); }

        .panel-headline {
            font-size: 1.2rem; font-weight: 800; color: #fff;
            line-height: 1.25; margin-bottom: 8px; letter-spacing: -0.3px;
            position: relative; z-index: 1;
        }
        .panel-headline em { font-style: normal; color: var(--ga); }

        .panel-sub {
            font-size: 0.72rem; color: rgba(255,255,255,0.52);
            line-height: 1.6; margin-bottom: 20px;
            position: relative; z-index: 1;
        }

        .role-cards { display: flex; flex-direction: column; gap: 7px; position: relative; z-index: 1; }
        .role-card {
            display: flex; align-items: center; gap: 9px;
            background: rgba(168,213,162,0.08);
            border: 1px solid rgba(168,213,162,0.15);
            border-radius: 9px; padding: 8px 11px;
            transition: background 0.2s, border-color 0.2s;
        }
        .role-card:hover {
            background: rgba(168,213,162,0.1);
            border-color: rgba(168,213,162,0.25);
        }
        .role-card-icon {
            width: 28px; height: 28px;
            background: rgba(168,213,162,0.14);
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .role-card-icon i { color: var(--ga); font-size: 0.8rem; }
        .role-card-text strong { display: block; font-size: 0.71rem; font-weight: 600; color: #fff; line-height: 1.2; }
        .role-card-text span   { font-size: 0.64rem; color: rgba(255,255,255,0.42); }

        /* ── Right panel ── */
        .auth-right {
            width: 64%;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 28px 30px;
            position: relative;
            overflow: hidden;
        }
        .auth-right::before {
            content: none;
        }

        .form-eyebrow {
            font-size: 0.63rem; font-weight: 700; letter-spacing: 2px;
            text-transform: uppercase; color: var(--g3); margin-bottom: 3px;
        }
        .form-title {
            font-size: 1.25rem; font-weight: 800; color: #1a2e1a;
            letter-spacing: -0.3px; margin-bottom: 2px;
        }
        .form-subtitle { font-size: 0.73rem; color: #6c757d; margin-bottom: 14px; }

        /* Section label */
        .section-label {
            font-size: 0.6rem; font-weight: 700; letter-spacing: 1.2px;
            text-transform: uppercase; color: var(--g3);
            margin: 12px 0 8px;
            display: flex; align-items: center; gap: 7px;
        }
        .section-label::after {
            content: ''; flex: 1; height: 1px;
            background: #d8e8d5;
        }

        /* Alert */
        .ps-alert {
            display: flex; align-items: flex-start; gap: 8px;
            padding: 9px 13px; border-radius: 9px;
            font-size: 0.76rem; margin-bottom: 12px;
        }
        .ps-alert-danger { background: #fdf2f2; border: 1px solid #f5c6cb; color: #842029; }
        .ps-alert i { margin-top: 1px; flex-shrink: 0; }

        /* Form */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .form-group { margin-bottom: 9px; }

        .form-label {
            font-size: 0.7rem; font-weight: 600;
            color: #1a2e1a; margin-bottom: 4px; display: block;
        }

        .input-wrap { position: relative; }
        .input-wrap .ps-input,
        .input-wrap .ps-select { padding-left: 32px; }
        .input-wrap .i-icon {
            position: absolute; left: 10px; top: 50%;
            transform: translateY(-50%);
            color: #b0bec5; font-size: 0.8rem;
            pointer-events: none; transition: color 0.2s;
        }
        .input-wrap:focus-within .i-icon { color: var(--g3); }
        .input-wrap .toggle-pw {
            position: absolute; right: 9px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; color: #b0bec5;
            cursor: pointer; font-size: 0.8rem; padding: 2px;
            transition: color 0.2s;
        }
        .input-wrap .toggle-pw:hover { color: var(--g3); }
        .input-wrap.select-wrap::after {
            content: '\F282'; font-family: 'bootstrap-icons';
            position: absolute; right: 10px; top: 50%;
            transform: translateY(-50%);
            color: #b0bec5; pointer-events: none; font-size: 0.72rem;
        }

        .ps-input, .ps-select {
            width: 100%; padding: 8px 11px;
            background: #fff;
            border: 1.5px solid #d8e8d5;
            border-radius: 9px;
            font-size: 0.78rem; font-family: 'Inter', sans-serif;
            color: #1a2e1a; outline: none; appearance: none;
            transition: border-color 0.25s, box-shadow 0.25s;
        }
        .ps-input::placeholder { color: #b0bec5; }
        .ps-input:focus, .ps-select:focus {
            border-color: var(--g3);
            box-shadow: 0 0 0 3px rgba(45,90,39,0.1);
        }
        /* Fix browser autofill */
        .ps-input:-webkit-autofill,
        .ps-input:-webkit-autofill:hover,
        .ps-input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 60px #fff inset !important;
            -webkit-text-fill-color: #1a2e1a !important;
            caret-color: #1a2e1a;
        }
        .ps-select option { background: #fff; color: #1a2e1a; }

        /* Password strength */
        .pw-strength { margin-top: 4px; }
        .pw-bar {
            height: 3px; border-radius: 3px;
            background: rgba(255,255,255,0.1);
            overflow: hidden; margin-bottom: 3px;
        }
        .pw-fill { height: 100%; border-radius: 3px; width: 0%; transition: width 0.3s, background 0.3s; }
        .pw-label { font-size: 0.63rem; color: #6c757d; }

        .form-hint { font-size: 0.62rem; color: #6c757d; margin-top: 2px; }

        /* Button */
        .btn-auth {
            width: 100%; padding: 10px;
            background: linear-gradient(135deg, var(--g3) 0%, var(--g4) 100%);
            color: #fff; border: none; border-radius: 9px;
            font-size: 0.82rem; font-weight: 700;
            font-family: 'Inter', sans-serif; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 7px;
            margin-top: 12px; position: relative; overflow: hidden;
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
            text-align: center; margin-top: 12px;
            font-size: 0.72rem; color: #6c757d;
        }
        .auth-switch a {
            color: var(--g3); font-weight: 700; text-decoration: none;
        }
        .auth-switch a:hover { text-decoration: underline; }

        @media (max-width: 620px) {
            .auth-card { flex-direction: column; }
            .auth-left, .auth-right { width: 100%; }
            .form-row { grid-template-columns: 1fr; }
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

            <h1 class="panel-headline">Join the <em>Movement</em><br>for Change</h1>
            <p class="panel-sub">Create your account and start contributing to tree planting activities and environmental programs.</p>

            <div class="role-cards">
                <div class="role-card">
                    <div class="role-card-icon"><i class="bi bi-person-raised-hand"></i></div>
                    <div class="role-card-text">
                        <strong>Community Organizer</strong>
                        <span>Submit & track requests</span>
                    </div>
                </div>
                <div class="role-card">
                    <div class="role-card-icon"><i class="bi bi-people"></i></div>
                    <div class="role-card-text">
                        <strong>Affairs Worker</strong>
                        <span>Review and refer requests</span>
                    </div>
                </div>
                <div class="role-card">
                    <div class="role-card-icon"><i class="bi bi-clipboard2-pulse"></i></div>
                    <div class="role-card-text">
                        <strong>Agri Technologist</strong>
                        <span>Validate sites & prepare slips</span>
                    </div>
                </div>
                <div class="role-card">
                    <div class="role-card-icon"><i class="bi bi-building"></i></div>
                    <div class="role-card-text">
                        <strong>MAO</strong>
                        <span>Approve and route requests</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="auth-right">
            <div class="form-eyebrow">Get started</div>
            <h2 class="form-title">Create your account</h2>
            <p class="form-subtitle">Fill in the details below to join Plant Sphere</p>

            <?php if (isset($error)): ?>
                <div class="ps-alert ps-alert-danger">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="index.php?action=register" method="POST" autocomplete="on">

                <div class="section-label">Personal Information</div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="firstname">First Name</label>
                        <div class="input-wrap">
                            <i class="bi bi-person i-icon"></i>
                            <input type="text" id="firstname" name="firstname" class="ps-input"
                                   placeholder="Juan" required autofocus autocomplete="given-name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="lastname">Last Name</label>
                        <div class="input-wrap">
                            <i class="bi bi-person i-icon"></i>
                            <input type="text" id="lastname" name="lastname" class="ps-input"
                                   placeholder="Dela Cruz" required autocomplete="family-name">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope i-icon"></i>
                        <input type="email" id="email" name="email" class="ps-input"
                               placeholder="name@example.com" required autocomplete="email">
                    </div>
                </div>

                <div class="section-label">Security</div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock i-icon"></i>
                        <input type="password" id="password" name="password" class="ps-input"
                               placeholder="Min. 8 characters" minlength="8" required
                               autocomplete="new-password" style="padding-right: 32px;"
                               oninput="checkStrength(this.value)">
                        <button type="button" class="toggle-pw"
                                onclick="togglePassword('password', this)"
                                tabindex="-1" aria-label="Toggle password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="pw-strength">
                        <div class="pw-bar"><div class="pw-fill" id="pwFill"></div></div>
                        <span class="pw-label" id="pwLabel">Enter a password</span>
                    </div>
                    <p class="form-hint"><i class="bi bi-shield-check me-1"></i>Uppercase, lowercase, number &amp; special character required.</p>
                </div>

                <div class="section-label">Role & Access</div>

                <div class="form-group">
                    <label class="form-label" for="role">Role / Position</label>
                    <div class="input-wrap select-wrap">
                        <i class="bi bi-shield-check i-icon"></i>
                        <select id="role" name="role" class="ps-select" required>
                            <option value="" disabled selected>Select your role</option>
                            <option value="community_organizer">Community Organizer</option>
                            <option value="community_affairs_worker">Community Affairs Worker</option>
                            <option value="agricultural_technologist">Agricultural Technologist</option>
                            <option value="mao">Municipal Agriculture Office (MAO)</option>
                            <option value="barangay_captain">Barangay Captain</option>
                            <option value="department_head">Department Head</option>
                            <option value="nursery">Nursery Staff</option>
                        </select>
                    </div>
                    <p class="form-hint"><i class="bi bi-info-circle me-1"></i>Your role determines what features you can access.</p>
                </div>

                <button type="submit" class="btn-auth" id="registerBtn">
                    <i class="bi bi-person-plus-fill"></i> Create Account
                </button>
            </form>

            <div class="auth-switch">
                Already have an account? <a href="index.php?action=login">Sign in here</a>
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

function checkStrength(val) {
    const fill  = document.getElementById('pwFill');
    const label = document.getElementById('pwLabel');
    let score = 0;
    if (val.length >= 8)          score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct: '0%',   color: 'rgba(255,255,255,0.1)', text: 'Enter a password' },
        { pct: '25%',  color: '#dc3545', text: 'Weak' },
        { pct: '50%',  color: '#fd7e14', text: 'Fair' },
        { pct: '75%',  color: '#ffc107', text: 'Good' },
        { pct: '100%', color: '#a8d5a2', text: 'Strong' },
    ];
    const lvl = val.length === 0 ? levels[0] : levels[score];
    fill.style.width      = lvl.pct;
    fill.style.background = lvl.color;
    label.textContent     = lvl.text;
    label.style.color     = score === 0 ? 'rgba(255,255,255,0.3)' : lvl.color;
}

// Ripple
document.getElementById('registerBtn').addEventListener('click', function(e) {
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
