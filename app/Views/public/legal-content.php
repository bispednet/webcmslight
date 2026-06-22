<?php
/** @var array $settings */

use App\Core\Container;

$config  = Container::get('config', []);
$company = $config['company'] ?? [];

$legalName = $company['legal_name'] ?? 'bisp&d s.r.l.';
$address   = $company['address']    ?? 'Piazza della Costituzione, 68 - 57025 Piombino (LI) Italia';
$vat       = $company['vat_id']     ?? 'IT0156025048';
$rea       = $company['rea']        ?? 'LI-138175';
$pec       = $company['pec']        ?? 'bisped@pec.it';
$email     = $settings['contact_email'] ?? 'negozio@bisped.net';
$phone     = $company['phone']      ?? '[NUMERO DA INSERIRE]';
$updated   = '22 giugno 2026';

function legalSection(string $title, string $body, string $id = ''): void
{
    ?>
    <details class="info-card legal-acc"<?= $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
        <summary class="cursor-pointer list-none flex items-center justify-between gap-4">
            <h2 class="font-display text-xl font-black" style="color:var(--c-acc)"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
            <svg class="legal-acc__chevron w-5 h-5 flex-shrink-0" style="color:var(--bisped-red)" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </summary>
        <div class="legal-body mt-5 space-y-4 text-sm leading-7" style="color:var(--c-muted)">
            <?= $body ?>
        </div>
    </details>
    <?php
}

$titolare = '<strong style="color:var(--c-txt)">' . htmlspecialchars($legalName, ENT_QUOTES, 'UTF-8') . '</strong>, '
    . htmlspecialchars($address, ENT_QUOTES, 'UTF-8') . ' — P.IVA/C.F. ' . htmlspecialchars($vat, ENT_QUOTES, 'UTF-8')
    . ', REA ' . htmlspecialchars($rea, ENT_QUOTES, 'UTF-8') . ', PEC <a href="mailto:' . htmlspecialchars($pec) . '" style="color:var(--bisped-red)">' . htmlspecialchars($pec) . '</a>';
?>

<div class="space-y-10">

    <div data-animate>
        <p class="section-label mb-5">Trasparenza</p>
        <h1 class="font-display text-4xl font-black md:text-5xl" style="color:var(--c-acc)">Area legale</h1>
        <p class="mt-4 max-w-2xl text-lg" style="color:var(--c-muted)">
            Privacy Policy, Cookie Policy e Condizioni di vendita di <?= htmlspecialchars($legalName, ENT_QUOTES, 'UTF-8') ?>.
            Documenti redatti ai sensi del Regolamento UE 2016/679 (GDPR), del D.Lgs. 196/2003 e 101/2018,
            del Codice del Consumo (D.Lgs. 206/2005), come modificato anche dal D.Lgs. 31 dicembre 2025, n. 209,
            e della normativa vigente. Ultimo aggiornamento: <?= $updated ?>.
        </p>
    </div>

    <div class="info-card max-w-4xl" id="recesso-online" data-animate>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-xl font-black" style="color:var(--c-acc)">Diritto di recesso online</h2>
                <p class="mt-2 text-sm leading-6" style="color:var(--c-muted)">
                    Per i contratti conclusi a distanza puoi usare la funzione online dedicata. La ricevuta viene inviata via email con contenuto, data e ora della trasmissione.
                </p>
            </div>
            <a href="/recesso" class="btn-primary flex-shrink-0">Recedere dal contratto qui</a>
        </div>
    </div>

    <section class="space-y-4 max-w-4xl">

        <?php
        // ── PRIVACY POLICY ──────────────────────────────────────────────────
        legalSection('Privacy Policy', <<<HTML
<p>Ai sensi degli artt. 13 e 14 del Regolamento UE 2016/679 ("GDPR"), {$titolare} ("Titolare") informa gli utenti del sito <strong style="color:var(--c-txt)">bisped.net</strong> circa il trattamento dei dati personali raccolti.</p>

<h3 style="color:var(--c-txt);font-weight:800;margin-top:.5rem">1. Titolare del trattamento</h3>
<p>{$titolare}. Per qualsiasi richiesta in materia di protezione dei dati è possibile scrivere a <a href="mailto:{$pec}" style="color:var(--bisped-red)">{$pec}</a> oppure a <a href="mailto:{$email}" style="color:var(--bisped-red)">{$email}</a>. Il Titolare non ha nominato un Responsabile della Protezione dei Dati (DPO), non sussistendone l'obbligo ai sensi dell'art. 37 GDPR.</p>

<h3 style="color:var(--c-txt);font-weight:800">2. Tipologie di dati trattati</h3>
<p>A seconda dell'interazione, il Titolare può trattare: <em>dati anagrafici e di contatto</em> (nome, cognome, email, telefono, indirizzo) forniti tramite moduli, registrazione, ordini, recesso o assistenza; <em>dati di navigazione</em> (indirizzo IP, tipo di browser/dispositivo, pagine visitate, orari di accesso) raccolti automaticamente; <em>dati di acquisto, fatturazione, garanzia e recesso</em>; <em>contenuti delle comunicazioni</em> inviate tramite moduli, chat, WhatsApp o funzione di recesso online; <em>cookie e identificatori</em> (v. Cookie Policy).</p>

<h3 style="color:var(--c-txt);font-weight:800">3. Finalità e basi giuridiche</h3>
<ul style="list-style:disc;padding-left:1.25rem">
  <li><strong>Evasione di ordini, vendita e assistenza</strong> — base giuridica: esecuzione di un contratto o di misure precontrattuali (art. 6.1.b GDPR).</li>
  <li><strong>Adempimenti fiscali, contabili e di legge</strong> — obbligo legale (art. 6.1.c GDPR).</li>
  <li><strong>Gestione del diritto di recesso, garanzie e reclami</strong> — obbligo legale / esecuzione del contratto (art. 6.1.c/b GDPR).</li>
  <li><strong>Risposta a richieste tramite moduli, chat o telefono</strong> — esecuzione di misure precontrattuali / legittimo interesse (art. 6.1.b/f GDPR).</li>
  <li><strong>Marketing diretto e newsletter</strong> — consenso dell'interessato (art. 6.1.a GDPR), revocabile in ogni momento.</li>
  <li><strong>Statistiche e profilazione tramite cookie analitici/di marketing</strong> — consenso (art. 6.1.a GDPR), prestato tramite il banner cookie.</li>
  <li><strong>Sicurezza del sito e prevenzione abusi/frodi</strong> — legittimo interesse (art. 6.1.f GDPR).</li>
</ul>

<h3 style="color:var(--c-txt);font-weight:800">4. Modalità del trattamento</h3>
<p>I dati sono trattati con strumenti elettronici e cartacei, adottando misure tecniche e organizzative adeguate (art. 32 GDPR) a garantirne riservatezza, integrità e disponibilità. Il sito è ospitato su server situati nell'Unione Europea (Italia).</p>

<h3 style="color:var(--c-txt);font-weight:800">5. Destinatari e categorie di responsabili</h3>
<p>I dati possono essere comunicati a: fornitori di servizi IT e hosting; corrieri e spedizionieri; commercialisti, consulenti e istituti di pagamento; fornitori di servizi di statistica e marketing (Google, Meta); autorità competenti ove previsto dalla legge. Tali soggetti operano quali Responsabili del trattamento ex art. 28 GDPR o titolari autonomi. L'elenco aggiornato è disponibile su richiesta.</p>

<h3 style="color:var(--c-txt);font-weight:800">6. Trasferimento extra-UE</h3>
<p>Alcuni fornitori (es. Google LLC, Meta Platforms Inc.) possono trattare dati in Paesi extra-SEE. Il trasferimento avviene nel rispetto degli artt. 44 e ss. GDPR, mediante <em>Clausole Contrattuali Standard</em> approvate dalla Commissione UE e/o adesione al <em>EU-US Data Privacy Framework</em>.</p>

<h3 style="color:var(--c-txt);font-weight:800">7. Periodo di conservazione</h3>
<p>I dati sono conservati per il tempo strettamente necessario alle finalità: dati contrattuali e di fatturazione per 10 anni (obblighi civilistici/fiscali); dati per assistenza, garanzie, reclami e recesso per il tempo necessario alla gestione della pratica e alla tutela dei diritti delle parti; dati di marketing fino a revoca del consenso e comunque non oltre 24 mesi dall'ultimo contatto; dati di navigazione e cookie secondo le durate indicate nella Cookie Policy.</p>

<h3 style="color:var(--c-txt);font-weight:800">8. Diritti dell'interessato</h3>
<p>L'interessato può esercitare in ogni momento i diritti di cui agli artt. 15-22 GDPR: <em>accesso, rettifica, cancellazione ("diritto all'oblio"), limitazione, portabilità, opposizione</em> al trattamento, nonché <em>revoca del consenso</em> senza pregiudizio per la liceità del trattamento precedente. Le richieste vanno inviate a <a href="mailto:{$pec}" style="color:var(--bisped-red)">{$pec}</a> e sono evase entro 30 giorni.</p>

<h3 style="color:var(--c-txt);font-weight:800">9. Reclamo all'Autorità di controllo</h3>
<p>L'interessato ha diritto di proporre reclamo al <strong style="color:var(--c-txt)">Garante per la protezione dei dati personali</strong> (<a href="https://www.garanteprivacy.it" target="_blank" rel="noopener" style="color:var(--bisped-red)">garanteprivacy.it</a>) qualora ritenga che il trattamento violi il GDPR.</p>

<h3 style="color:var(--c-txt);font-weight:800">10. Modifiche</h3>
<p>Il Titolare si riserva di aggiornare la presente informativa. Le modifiche sono pubblicate su questa pagina con indicazione della data di ultimo aggiornamento.</p>
HTML, 'privacy-policy');

        // ── COOKIE POLICY ───────────────────────────────────────────────────
        legalSection('Cookie Policy', <<<HTML
<p>La presente Cookie Policy è redatta in conformità al GDPR e al Provvedimento del Garante Privacy del 10 giugno 2021 ("Linee guida cookie"). Spiega quali cookie utilizza <strong style="color:var(--c-txt)">bisped.net</strong> e come gestire le preferenze.</p>

<h3 style="color:var(--c-txt);font-weight:800;margin-top:.5rem">1. Cosa sono i cookie</h3>
<p>I cookie sono piccoli file di testo che i siti salvano sul dispositivo dell'utente per memorizzare informazioni. Possono essere "tecnici" (necessari al funzionamento) o "di profilazione/marketing" (per analisi e pubblicità). Si distinguono inoltre in cookie di prima parte (impostati dal sito) e di terza parte.</p>

<h3 style="color:var(--c-txt);font-weight:800">2. Cookie tecnici necessari (nessun consenso richiesto)</h3>
<p>Indispensabili per la navigazione e i servizi richiesti. Senza di essi il sito non funziona correttamente.</p>
<table style="width:100%;font-size:.8rem;border-collapse:collapse">
  <tr style="border-bottom:1px solid var(--c-border)"><td style="padding:.4rem 0"><code>bisped_session</code></td><td>Gestione sessione utente</td><td>Sessione</td></tr>
  <tr style="border-bottom:1px solid var(--c-border)"><td style="padding:.4rem 0"><code>bisped_locale</code></td><td>Lingua preferita (IT/EN)</td><td>1 anno</td></tr>
  <tr><td style="padding:.4rem 0"><code>csrf_token</code></td><td>Protezione anti-frode dei moduli</td><td>Sessione</td></tr>
</table>

<h3 style="color:var(--c-txt);font-weight:800">3. Cookie analitici (previo consenso)</h3>
<p><strong>Google Analytics 4</strong> (Google Ireland Ltd.) — raccoglie dati aggregati e pseudonimizzati sulle visite per misurare l'utilizzo del sito. L'indirizzo IP è anonimizzato. I cookie (<code>_ga</code>, <code>_ga_*</code>) hanno durata fino a 24 mesi. Privacy policy: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" style="color:var(--bisped-red)">policies.google.com/privacy</a>.</p>

<h3 style="color:var(--c-txt);font-weight:800">4. Cookie di marketing e profilazione (previo consenso)</h3>
<p><strong>Meta Pixel</strong> (Meta Platforms Ireland Ltd.) — consente di misurare l'efficacia delle campagne e mostrare annunci personalizzati su Facebook/Instagram. Cookie (<code>_fbp</code>, <code>fr</code>) con durata fino a 3 mesi. Privacy policy: <a href="https://www.facebook.com/privacy/policy" target="_blank" rel="noopener" style="color:var(--bisped-red)">facebook.com/privacy/policy</a>.</p>

<h3 style="color:var(--c-txt);font-weight:800">5. Servizi di terze parti</h3>
<p>Il sito carica risorse da <strong>Google Fonts</strong> e CDN per font e librerie di stile: ciò comporta la comunicazione dell'indirizzo IP ai relativi provider, che agiscono come titolari autonomi. Eventuali funzioni di <strong>Google OAuth</strong> (accesso) e <strong>Google Calendar</strong> (appuntamenti) sono attivate solo su azione dell'utente.</p>

<h3 style="color:var(--c-txt);font-weight:800">6. Gestione del consenso</h3>
<p>Al primo accesso un banner consente di <em>accettare</em>, <em>rifiutare</em> o <em>personalizzare</em> i cookie non necessari. I cookie analitici e di marketing sono installati solo dopo consenso. È possibile modificare o revocare le scelte in qualsiasi momento dalle impostazioni del banner o cancellando i cookie dal browser. La revoca non pregiudica la liceità del trattamento basato sul consenso prestato prima della revoca.</p>

<h3 style="color:var(--c-txt);font-weight:800">7. Disattivazione dal browser</h3>
<p>È possibile gestire o disabilitare i cookie dalle impostazioni del proprio browser (Chrome, Firefox, Safari, Edge). La disattivazione dei cookie tecnici può compromettere il funzionamento del sito.</p>
HTML, 'cookie-policy');

        // ── CONDIZIONI DI VENDITA ───────────────────────────────────────────
        legalSection('Condizioni generali di vendita', <<<HTML
<p>Le presenti Condizioni Generali di Vendita ("Condizioni") disciplinano l'offerta e la vendita dei prodotti tramite il sito <strong style="color:var(--c-txt)">bisped.net</strong>, ai sensi del Codice del Consumo (D.Lgs. 206/2005) e del D.Lgs. 70/2003 sul commercio elettronico.</p>

<h3 style="color:var(--c-txt);font-weight:800;margin-top:.5rem">1. Venditore</h3>
<p>Il venditore è {$titolare}. Per informazioni e assistenza: <a href="mailto:{$email}" style="color:var(--bisped-red)">{$email}</a> — tel. {$phone}.</p>

<h3 style="color:var(--c-txt);font-weight:800">2. Prodotti e prezzi</h3>
<p>Le caratteristiche e i prezzi dei prodotti sono indicati nelle relative schede. Tutti i prezzi sono espressi in Euro e <strong>comprensivi di IVA</strong>. Le immagini hanno valore indicativo. La disponibilità indicata ("pz online") riflette la giacenza presso il fornitore al momento della consultazione e può variare; il venditore si riserva di confermare la disponibilità effettiva prima dell'evasione.</p>

<h3 style="color:var(--c-txt);font-weight:800">3. Conclusione del contratto</h3>
<p>L'ordine inoltrato dal cliente costituisce proposta d'acquisto. Il contratto si intende concluso con la conferma d'ordine da parte del venditore, inviata via email. Il venditore si riserva di non accettare ordini incompleti, sospetti di frode o per indisponibilità del prodotto, dandone tempestiva comunicazione.</p>

<h3 style="color:var(--c-txt);font-weight:800">4. Pagamenti</h3>
<p>I metodi di pagamento accettati sono indicati in fase di ordine (bonifico bancario, carte di credito/debito, Satispay, pagamento presso il punto vendita). I dati di pagamento sono gestiti da istituti e gateway certificati; il venditore non conserva i dati delle carte.</p>

<h3 style="color:var(--c-txt);font-weight:800">5. Spedizione e consegna</h3>
<p>La spedizione avviene tramite corriere sul territorio nazionale, salvo diverso accordo. I tempi di consegna sono indicativi e decorrono dalla conferma d'ordine e, ove previsto, dal buon esito del pagamento. È possibile concordare il ritiro presso il punto vendita di Piombino. Le spese di spedizione, se applicabili, sono indicate prima della conferma dell'ordine.</p>

<h3 style="color:var(--c-txt);font-weight:800" id="diritto-recesso">6. Diritto di recesso (consumatori)</h3>
<p>Il cliente che agisce come <strong>consumatore</strong> ha diritto di recedere dal contratto entro <strong>14 giorni</strong> dalla ricezione del bene, senza obbligo di motivazione (artt. 52 e ss. Codice del Consumo). Per esercitarlo può utilizzare la funzione online <a href="/recesso" style="color:var(--bisped-red)">Recedere dal contratto qui</a>, oppure inviare comunicazione a <a href="mailto:{$pec}" style="color:var(--bisped-red)">{$pec}</a> o <a href="mailto:{$email}" style="color:var(--bisped-red)">{$email}</a>. La funzione online richiede nome, identificativo del contratto/ordine/prodotto ed email per la conferma; dopo l'invio viene trasmessa una ricevuta con contenuto, data e ora. Il bene va restituito integro, nella confezione originale, entro 14 giorni; le spese di restituzione sono a carico del cliente salvo diversa indicazione. Il rimborso è effettuato entro 14 giorni dal ricevimento del reso o della prova di spedizione. Il recesso è escluso nei casi previsti dall'art. 59 Codice del Consumo (es. beni sigillati non più restituibili per motivi igienici, software/licenze attivati, beni personalizzati).</p>

<h3 style="color:var(--c-txt);font-weight:800">6-bis. Recesso online e servizi finanziari a distanza</h3>
<p>Il D.Lgs. 31 dicembre 2025, n. 209 introduce nel Codice del Consumo specifiche disposizioni sui contratti di servizi finanziari conclusi a distanza e sulla funzione online di recesso. Tali modifiche si applicano ai contratti conclusi successivamente al 19 giugno 2026. bisp&amp;d non opera come banca, assicurazione o intermediario finanziario: eventuali servizi di telefonia, energia, pagamento, credito, assicurazione o finanziamento sono regolati dalle condizioni del relativo fornitore o intermediario, che resta responsabile della propria informativa precontrattuale e delle modalità di recesso di settore.</p>

<h3 style="color:var(--c-txt);font-weight:800">7. Garanzia legale di conformità</h3>
<p>Tutti i prodotti godono della <strong>garanzia legale di conformità di 24 mesi</strong> per i consumatori (artt. 128 e ss. Codice del Consumo). In caso di difetto di conformità il cliente ha diritto al ripristino della conformità (riparazione o sostituzione) o, nei casi previsti, alla riduzione del prezzo o alla risoluzione del contratto. Per i clienti professionali si applica la garanzia di legge ex art. 1490 c.c. (12 mesi).</p>

<h3 style="color:var(--c-txt);font-weight:800">8. Reclami e risoluzione delle controversie</h3>
<p>Eventuali reclami possono essere inviati a <a href="mailto:{$pec}" style="color:var(--bisped-red)">{$pec}</a>. Ai sensi del Reg. UE 524/2013, il consumatore può ricorrere alla piattaforma europea di risoluzione online delle controversie (ODR): <a href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener" style="color:var(--bisped-red)">ec.europa.eu/consumers/odr</a>.</p>

<h3 style="color:var(--c-txt);font-weight:800">9. Legge applicabile e foro</h3>
<p>Le presenti Condizioni sono regolate dalla legge italiana. Per il consumatore è competente il foro del luogo di residenza o domicilio. Per i rapporti con clienti professionali è competente in via esclusiva il Foro di Livorno.</p>
HTML, 'condizioni-vendita');
        ?>

        <p class="text-xs mt-6" style="color:var(--c-muted)">
            Per qualsiasi chiarimento scrivi a <a href="mailto:<?= htmlspecialchars($pec, ENT_QUOTES, 'UTF-8') ?>" style="color:var(--bisped-red)"><?= htmlspecialchars($pec, ENT_QUOTES, 'UTF-8') ?></a> o chiama lo <?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>. Si consiglia comunque una revisione da parte di un legale prima dell'attivazione delle vendite online.
        </p>
    </section>
</div>
