(function () {
    'use strict';
    var root = document.querySelector('[data-ai-concierge]');
    if (!root) return;
    var panel = root.querySelector('[data-ai-panel]');
    var messages = root.querySelector('[data-ai-messages]');
    var choices = root.querySelector('[data-ai-choices]');
    var quoteList = root.querySelector('[data-ai-quotes]');
    var form = root.querySelector('[data-ai-form]');
    var input = form.querySelector('input');
    var handoff = root.querySelector('[data-ai-handoff]');
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
    function renderChoices(items) {
        choices.replaceChildren();
        (items || []).forEach(function (item) {
            var button = document.createElement('button');
            button.type = 'button';
            button.textContent = item.label;
            button.addEventListener('click', function () { send('/ai/concierge/choice', { choice: item.value }, item.label); });
            choices.appendChild(button);
        });
    }
    function renderQuotes(items) {
        quoteList.replaceChildren();
        (items || []).forEach(function (quote) {
            var card = document.createElement('article');
            var title = document.createElement('strong');
            var text = document.createElement('span');
            title.textContent = quote.title;
            text.textContent = quote.summary;
            card.append(title, text);
            quoteList.appendChild(card);
        });
    }
    function apply(data) {
        if (data.csrf) csrf = data.csrf;
        if (data.conversation_id) conversationId = data.conversation_id;
        add(data.error ? 'error' : 'bot', data.reply || data.error || 'Riprova tra poco.');
        renderChoices(data.choices);
        renderQuotes(data.quotes);
        handoff.hidden = !data.ready;
    }
    function send(url, payload, visibleText) {
        if (busy) return;
        busy = true;
        if (visibleText) add('user', visibleText);
        renderChoices([]);
        fetch(url, {
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
            add('error', 'Il concierge non e disponibile in questo momento.');
        }).finally(function () { busy = false; });
    }
    root.querySelector('[data-ai-open]').addEventListener('click', function () { panel.hidden = false; bootstrap(); input.focus(); });
    root.querySelector('[data-ai-close]').addEventListener('click', function () { panel.hidden = true; });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var text = input.value.trim();
        if (!text) return;
        input.value = '';
        send('/ai/concierge/message', { message: text }, text);
    });
    handoff.addEventListener('click', function () {
        if (busy) return;
        var target = window.open('about:blank', '_blank');
        busy = true;
        fetch('/ai/concierge/handoff/whatsapp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf: csrf, conversation_id: conversationId })
        }).then(function (response) { return response.json(); }).then(function (data) {
            if (data.csrf) csrf = data.csrf;
            if (!data.url) throw new Error(data.error || 'handoff unavailable');
            if (target) target.location.href = data.url;
            else window.location.href = data.url;
        }).catch(function () {
            if (target) target.close();
            add('error', 'Non riesco ad aprire WhatsApp. Riprova tra poco.');
        }).finally(function () { busy = false; });
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') panel.hidden = true; });
})();
