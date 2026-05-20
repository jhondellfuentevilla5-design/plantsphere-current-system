    </div><!-- /.ps-content -->
    <footer class="ps-footer">
        &copy; <?= date('Y') ?> Plant Sphere. All rights reserved.
    </footer>
</div><!-- /.ps-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Skeleton dismiss ───────────────────────────────────────
(function() {
    const overlay = document.getElementById('ps-skeleton-overlay');
    if (!overlay) return;

    function dismiss() {
        overlay.classList.add('fade-out');
        setTimeout(() => overlay.remove(), 320);
    }

    // Dismiss when DOM + all resources are ready
    if (document.readyState === 'complete') {
        dismiss();
    } else {
        window.addEventListener('load', dismiss);
        // Fallback: never show skeleton longer than 3s
        setTimeout(dismiss, 3000);
    }
})();
// ── Session timeout countdown ──────────────────────────────
(function() {
    const remaining = <?= SessionGuard::remainingSeconds() ?>;
    let secs = remaining;
    const bar = document.getElementById('sessionBar');
    const txt = document.getElementById('sessionCountdown');
    const warn = document.getElementById('sessionWarning');

    function tick() {
        secs--;
        if (secs <= 0) {
            window.location.href = 'index.php?action=login&expired=1';
            return;
        }
        const m = Math.floor(secs / 60);
        const s = secs % 60;
        if (txt) txt.textContent = m + ':' + String(s).padStart(2,'0');
        if (bar) bar.style.width = ((secs / <?= SessionGuard::TIMEOUT_SECONDS ?>) * 100) + '%';
        if (warn) warn.classList.toggle('d-none', secs > 120);
        setTimeout(tick, 1000);
    }
    setTimeout(tick, 1000);

    // Ping server on user activity to reset idle timer
    let pingTimer;
    ['mousemove','keydown','click','scroll'].forEach(ev => {
        document.addEventListener(ev, () => {
            clearTimeout(pingTimer);
            pingTimer = setTimeout(() => {
                fetch('api/ping_session.php', {credentials:'same-origin'});
                secs = <?= SessionGuard::TIMEOUT_SECONDS ?>;
            }, 2000);
        });
    });
})();

// ── DLP: Data Loss Prevention ─────────────────────────────

// 1. Block right-click
document.addEventListener('contextmenu', e => e.preventDefault());

// 2. DLP overlay element — covers entire screen when triggered
const dlpOverlay = document.createElement('div');
dlpOverlay.id = 'dlp-overlay';
dlpOverlay.innerHTML = `
    <div class="dlp-box">
        <div class="dlp-icon">🔒</div>
        <div class="dlp-title">Screenshot Blocked</div>
        <div class="dlp-msg">This action is not permitted.<br>This attempt has been logged.</div>
    </div>`;
dlpOverlay.style.cssText = `
    display:none;
    position:fixed;inset:0;
    background:rgba(0,0,0,0.97);
    z-index:999999;
    align-items:center;justify-content:center;
    flex-direction:column;
`;
document.body.appendChild(dlpOverlay);

// Overlay styles
const dlpStyle = document.createElement('style');
dlpStyle.textContent = `
    #dlp-overlay { display:none; }
    #dlp-overlay.show {
        display:flex !important;
        animation: dlpFadeIn 0.15s ease;
    }
    @keyframes dlpFadeIn { from{opacity:0} to{opacity:1} }
    .dlp-box {
        text-align:center;
        color:#fff;
        padding:40px;
        border:1px solid rgba(255,255,255,0.15);
        border-radius:16px;
        background:rgba(255,255,255,0.05);
        backdrop-filter:blur(10px);
        max-width:340px;
    }
    .dlp-icon  { font-size:3rem; margin-bottom:16px; }
    .dlp-title { font-size:1.3rem; font-weight:800; margin-bottom:8px; letter-spacing:-0.3px; }
    .dlp-msg   { font-size:0.9rem; color:rgba(255,255,255,0.6); line-height:1.6; }

    /* Prevent text selection across the app */
    body.ps-app * {
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
    }
    /* Allow text selection only inside form inputs */
    body.ps-app input,
    body.ps-app textarea,
    body.ps-app select {
        -webkit-user-select: text;
        -moz-user-select: text;
        user-select: text;
    }

    /* CSS-only print block — hides all content when printing */
    @media print {
        body * { visibility: hidden !important; }
        body::after {
            visibility: visible !important;
            content: "Printing is not allowed for this application.";
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.5rem;
            font-weight: bold;
            color: #000;
        }
    }
`;
document.head.appendChild(dlpStyle);

// Show overlay for N milliseconds then hide
function showDlpOverlay(ms = 3000) {
    dlpOverlay.classList.add('show');
    setTimeout(() => dlpOverlay.classList.remove('show'), ms);
}

// Log to server
function logDlp(what) {
    <?php if (isset($_SESSION['user'])): ?>
    fetch('api/log_dlp_block.php?what=' + encodeURIComponent(what), {credentials:'same-origin'});
    <?php endif; ?>
}

// 3. Keyboard DLP
document.addEventListener('keydown', e => {
    const blocked =
        e.key === 'PrintScreen' ||
        e.key === 'F12' ||
        (e.ctrlKey && ['p','P','s','S','u','U'].includes(e.key)) ||
        (e.metaKey && ['p','P','s','S'].includes(e.key));   // Mac

    if (blocked) {
        e.preventDefault();
        e.stopImmediatePropagation();
        showDlpOverlay(3000);
        logDlp(e.key);
    }
}, true); // capture phase — fires before any other handler

// 4. Detect PrintScreen via clipboard (fires after OS captures)
document.addEventListener('keyup', e => {
    if (e.key === 'PrintScreen') {
        // Clear clipboard to remove the screenshot
        navigator.clipboard?.writeText('').catch(() => {});
        showDlpOverlay(3000);
        logDlp('PrintScreen');
    }
});

// 5. Visibility change — detect when user Alt+Tabs (possible screenshot tool)
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        // Page hidden — could be screenshot tool opening
        // We don't block this but we log it
        logDlp('visibility_hidden');
    }
});

// 6. Block window.print() calls
window.print = function() {
    showDlpOverlay(3000);
    logDlp('window.print()');
    return false;
};

function openSidebar() {
    document.getElementById('psSidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.remove('d-none');
}
function closeSidebar() {
    document.getElementById('psSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.add('d-none');
}
function toggleSidebarNotif() {
    document.getElementById('sidebarNotifDropdown').classList.toggle('d-none');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('#sidebarNotifBtn') && !e.target.closest('#sidebarNotifDropdown')) {
        document.getElementById('sidebarNotifDropdown')?.classList.add('d-none');
    }
});
</script>
</body>
</html>
