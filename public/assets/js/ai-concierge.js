(function () {
    'use strict';
    var root = document.querySelector('[data-ai-concierge]');
    if (!root) return;
    var panel = root.querySelector('[data-ai-panel]');
    var messages = root.querySelector('[data-ai-messages]');
    var form = root.querySelector('[data-ai-form]');
    var input = form.querySelector('input');
    var handoff = root.querySelector('[data-ai-handoff]');
    var agentSubtitle = root.querySelector('[data-ai-agent-subtitle]');
    var agentBadge = root.querySelector('[data-ai-agent-badge]');
    var csrf = '';
    var conversationId = '';
    var busy = false;

    function add(role, text) {
        var item = document.createElement('p');
        item.className = 'ai-concierge__message ai-concierge__message--' + role;
        item.textContent = text;
        messages.appendChild(item);
        messages.scrollTop = messages.scrollHeight;
    }
    function renderAgent(agent, transition) {
        if (!agent) return;
        if (transition) add('transition', transition);
        agentSubtitle.textContent = 'Ti segue ' + agent.name;
        agentBadge.textContent = 'Assistente digitale';
    }
    function showWhatsAppFallback(url) {
        handoff.href = url;
        handoff.hidden = false;
    }
    function openWhatsApp(url) {
        if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            window.location.href = url;
            return;
        }
        var popup = window.open(url, '_blank', 'noopener');
        if (!popup) showWhatsAppFallback(url);
        else setTimeout(function () { showWhatsAppFallback(url); }, 800);
    }
    function apply(data) {
        if (data.csrf) csrf = data.csrf;
        if (data.conversation_id) conversationId = data.conversation_id;
        renderAgent(data.agent, data.transition);
        add(data.error ? 'error' : 'bot', data.reply || data.error || 'Riprova tra poco.');
        if (data.action === 'redirect_whatsapp' && data.handoff && data.handoff.url) {
            openWhatsApp(data.handoff.url);
        }
    }
    function send(payload, visibleText) {
        if (busy) return;
        busy = true;
        if (visibleText) add('user', visibleText);
        fetch('/ai/concierge/message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.assign({ csrf: csrf, conversation_id: conversationId }, payload))
        }).then(function (response) { return response.json(); }).then(apply).catch(function () {
            add('error', 'La connessione ha avuto un intoppo. Riprova tra poco.');
        }).finally(function () { busy = false; input.focus(); });
    }
    function bootstrap() {
        if (conversationId || busy) return;
        busy = true;
        fetch('/ai/concierge/bootstrap').then(function (response) { return response.json(); }).then(apply).catch(function () {
            add('error', 'Il servizio non è disponibile in questo momento.');
        }).finally(function () { busy = false; });
    }
    root.querySelector('[data-ai-open]').addEventListener('click', function () { panel.hidden = false; bootstrap(); input.focus(); });
    root.querySelector('[data-ai-close]').addEventListener('click', function () { panel.hidden = true; });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var text = input.value.trim();
        if (!text) return;
        input.value = '';
        send({ message: text }, text);
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') panel.hidden = true; });
})();
