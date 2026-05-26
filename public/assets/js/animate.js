document.addEventListener('DOMContentLoaded', () => {
    // ── Animate on scroll ────────────────────────────────────────────────────
    const animated = document.querySelectorAll('[data-animate]');
    if ('IntersectionObserver' in window) {
        const obs = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    const delay = parseInt(e.target.getAttribute('data-animate-delay') || '0', 10);
                    setTimeout(() => e.target.classList.add('animate-visible'), delay);
                    obs.unobserve(e.target);
                }
            });
        }, { threshold: 0.08 });
        animated.forEach(el => obs.observe(el));
    } else {
        animated.forEach(el => el.classList.add('animate-visible'));
    }

    // ── Mobile nav toggle ────────────────────────────────────────────────────
    const mobileBtn = document.querySelector('[data-toggle-mobile-nav]');
    const mobileNav = document.querySelector('[data-mobile-nav]');
    if (mobileBtn && mobileNav) {
        mobileBtn.addEventListener('click', () => mobileNav.classList.toggle('hidden'));
    }
    document.addEventListener('click', e => {
        if (mobileNav && !mobileNav.classList.contains('hidden')) {
            if (!mobileBtn?.contains(e.target) && !mobileNav.contains(e.target)) {
                mobileNav.classList.add('hidden');
            }
        }
    });

    // ── Dropdown ────────────────────────────────────────────────────────────
    document.querySelectorAll('[data-dropdown]').forEach(dd => {
        const toggle = dd.querySelector('[data-dropdown-toggle]');
        const panel  = dd.querySelector('[data-dropdown-panel]');
        if (!toggle || !panel) return;
        toggle.addEventListener('click', e => { e.stopPropagation(); panel.classList.toggle('hidden'); });
        panel.addEventListener('click', e => e.stopPropagation());
        document.addEventListener('click', () => panel.classList.add('hidden'));
    });

    // ── Theme toggle ─────────────────────────────────────────────────────────
    function applyThemeIcons(theme) {
        document.querySelectorAll('#icon-sun').forEach(el => el.classList.toggle('hidden', theme !== 'dark'));
        document.querySelectorAll('#icon-moon').forEach(el => el.classList.toggle('hidden', theme === 'dark'));
    }

    document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            const cur  = document.documentElement.dataset.theme;
            const next = cur === 'light' ? 'dark' : 'light';
            document.documentElement.dataset.theme = next;
            localStorage.setItem('bisped-theme', next);
            applyThemeIcons(next);
        });
    });

    // ── Clipboard copy ───────────────────────────────────────────────────────
    document.querySelectorAll('[data-copy-text]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const text = btn.getAttribute('data-copy-text');
            if (!text) return;
            try {
                await (navigator.clipboard?.writeText
                    ? navigator.clipboard.writeText(text)
                    : Promise.resolve(document.execCommand('copy')));
                const orig = btn.innerHTML;
                btn.innerHTML = '✓ Copiato';
                setTimeout(() => { btn.innerHTML = orig; }, 2000);
            } catch (err) { console.error('Copy failed', err); }
        });
    });

    // ── Category pill scroll spy ─────────────────────────────────────────────
    const catPills = document.querySelectorAll('.cat-pill[href^="#"]');
    if (catPills.length > 0) {
        catPills.forEach(pill => {
            pill.addEventListener('click', () => {
                catPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
            });
        });
    }
});
