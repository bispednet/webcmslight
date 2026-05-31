(function () {
    'use strict';

    var root = document.querySelector('[data-ai-concierge]');
    if (!root) return;

    var panel         = root.querySelector('[data-ai-panel]');
    var messages      = root.querySelector('[data-ai-messages]');
    var form          = root.querySelector('[data-ai-form]');
    var input         = form ? form.querySelector('input') : null;
    var handoffBox    = root.querySelector('[data-ai-handoff-container]');
    var handoffLink   = root.querySelector('[data-ai-handoff]');
    var subtitle      = root.querySelector('[data-ai-agent-subtitle]');

    var csrf           = '';
    var conversationId = '';
    var busy           = false;
    var handoffDone    = false;

    // ── Sector labels (shown in header, not agent name) ──────────────────────
    var sectorLabels = {
        serenai: 'Linea e telefonia',
        andreai: 'Assistenza tecnica',
        sarai:   'Energia e pratiche',
        router:  'WhatsApp guidato'
    };

    function setSubtitle(agentKey) {
        if (!subtitle) return;
        subtitle.textContent = sectorLabels[agentKey] || 'WhatsApp guidato';
    }

    // ── Add a message bubble ─────────────────────────────────────────────────
    function addMsg(role, text) {
        var p = document.createElement('p');
        p.className = 'ai-concierge__message ai-concierge__message--' + role;
        p.textContent = text;
        messages.appendChild(p);
        messages.scrollTop = messages.scrollHeight;
    }

    // ── WhatsApp redirect ────────────────────────────────────────────────────
    function showFallback(url) {
        if (!handoffBox || !handoffLink) return;
        handoffLink.href = url;
        handoffBox.hidden = false;
    }

    function openWhatsApp(url) {
        // Disable input after handoff
        if (form) form.hidden = true;
        handoffDone = true;

        if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            window.location.href = url;
            return;
        }

        var popup = null;
        try { popup = window.open(url, '_blank', 'noopener,noreferrer'); } catch (e) {}

        // Always show fallback after ~600ms (popup blockers are unreliable)
        setTimeout(function () { showFallback(url); }, 600);
        if (!popup) showFallback(url);
    }

    // ── Apply API response ───────────────────────────────────────────────────
    function apply(data) {
        if (data.csrf)            csrf = data.csrf;
        if (data.conversation_id) conversationId = data.conversation_id;
        if (data.agent && data.agent.key) setSubtitle(data.agent.key);

        addMsg(data.error ? 'error' : 'bot', data.reply || data.error || 'Riprova tra poco.');

        if (data.action === 'redirect_whatsapp' && data.handoff && data.handoff.url) {
            openWhatsApp(data.handoff.url);
        }
    }

    // ── Send a message ───────────────────────────────────────────────────────
    function send(payload, visibleText) {
        if (busy || handoffDone) return;
        busy = true;
        if (visibleText) addMsg('user', visibleText);

        fetch('/ai/concierge/message', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(Object.assign({ csrf: csrf, conversation_id: conversationId }, payload))
        })
        .then(function (r) { return r.json(); })
        .then(apply)
        .catch(function () { addMsg('error', 'La connessione ha avuto un intoppo. Riprova tra poco.'); })
        .finally(function () { busy = false; if (!handoffDone && input) input.focus(); });
    }

    // ── Bootstrap ────────────────────────────────────────────────────────────
    function bootstrap() {
        if (conversationId || busy) return;
        busy = true;

        fetch('/ai/concierge/bootstrap')
        .then(function (r) { return r.json(); })
        .then(apply)
        .catch(function () { addMsg('error', 'Il servizio non è disponibile in questo momento.'); })
        .finally(function () { busy = false; });
    }

    // ── Event listeners ──────────────────────────────────────────────────────
    var openBtn = root.querySelector('[data-ai-open]');
    var closeBtn = root.querySelector('[data-ai-close]');

    if (openBtn) {
        openBtn.addEventListener('click', function () {
            panel.hidden = false;
            bootstrap();
            if (input) input.focus();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function () { panel.hidden = true; });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!input) return;
            var text = input.value.trim();
            if (!text || handoffDone) return;
            input.value = '';
            send({ message: text }, text);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel && !panel.hidden) panel.hidden = true;
    });

})();
