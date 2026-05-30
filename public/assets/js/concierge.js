(function () {
    'use strict';
    var config = window.BISPED_CONCIERGE || {};
    var root = document.querySelector('[data-concierge]');
    if (!root) return;
    var panel = root.querySelector('[data-concierge-panel]');
    var messages = root.querySelector('[data-concierge-messages]');
    var form = root.querySelector('form');
    var input = root.querySelector('input');
    var handoff = root.querySelector('[data-concierge-handoff]');
    var history = [];

    function add(role, text) {
        var item = document.createElement('p');
        item.className = 'concierge-message concierge-message--' + role;
        item.textContent = text;
        messages.appendChild(item);
        messages.scrollTop = messages.scrollHeight;
        history.push({ role: role === 'bot' ? 'assistant' : 'user', text: text });
    }
    function whatsappUrl() {
        var lines = ['Ciao bisp&d, ho raccolto queste informazioni con il concierge del sito:'];
        history.forEach(function (item) { lines.push((item.role === 'user' ? 'Cliente: ' : 'Concierge: ') + item.text); });
        return 'https://wa.me/' + config.phone + '?text=' + encodeURIComponent(lines.join('\n'));
    }
    root.querySelector('[data-concierge-open]').addEventListener('click', function () {
        panel.hidden = !panel.hidden;
        if (!panel.hidden && history.length === 0) add('bot', config.greeting);
    });
    root.querySelector('[data-concierge-close]').addEventListener('click', function () { panel.hidden = true; });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var text = input.value.trim();
        if (!text) return;
        add('user', text);
        input.value = '';
        input.disabled = true;
        fetch('/api/concierge', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf: config.csrf, locale: config.locale, messages: history })
        }).then(function (response) { return response.json(); }).then(function (data) {
            if (data.csrf) config.csrf = data.csrf;
            add('bot', data.reply || data.error || config.fallback);
            if (data.ready) {
                handoff.href = whatsappUrl();
                handoff.hidden = false;
            }
        }).catch(function () {
            add('bot', config.fallback);
            handoff.href = whatsappUrl();
            handoff.hidden = false;
        }).finally(function () {
            input.disabled = false;
            input.focus();
        });
    });
})();
