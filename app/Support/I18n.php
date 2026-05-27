<?php
declare(strict_types=1);

namespace App\Support;

final class I18n
{
    public static function currentLocale(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return str_starts_with($path, '/en') ? 'en' : 'it';
    }

    public static function translateHtml(string $html): string
    {
        if (self::currentLocale() !== 'en' || str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/admin')) {
            return $html;
        }

        return strtr($html, self::dictionary());
    }

    /**
     * Static-page dictionary. It intentionally targets visible CMS copy rather
     * than admin/forms internals, so untranslated dynamic content remains safe.
     *
     * @return array<string,string>
     */
    private static function dictionary(): array
    {
        return [
            '<html lang="it">' => '<html lang="en">',
            'Home | Bisped Piombino | Computer, smartphone, gaming e assistenza tecnica' => 'Home | Bisped Piombino | Computers, smartphones, gaming and technical support',
            'Bisped Piombino | Computer, smartphone, gaming e assistenza tecnica' => 'Bisped Piombino | Computers, smartphones, gaming and technical support',
            'Sostenibilita' => 'Sustainability',
            'Computer, smartphone, gaming e assistenza tecnica a Piombino (LI).' => 'Computers, smartphones, gaming and technical support in Piombino (LI).',
            'Area riservata' => 'Reserved area',
            'Accedi al tuo account' => 'Sign in to your account',
            'Clienti, commessi e amministratori: un unico accesso per seguire richieste, gestire catalogo e storico ordini.' => 'Customers, shop staff and administrators: one access point for requests, catalog management and order history.',
            'Accedi con Google' => 'Sign in with Google',
            'Accedi' => 'Sign in',
            'Registrati' => 'Register',
            'Registrazione' => 'Registration',
            'Crea account' => 'Create account',
            'Nuovo account' => 'New account',
            'Crea il tuo account.' => 'Create your account.',
            'Registrati per seguire richieste, appuntamenti e comunicazioni con bisp&amp;d.' => 'Register to follow requests, appointments and communications with bisp&amp;d.',
            'Registrati con Google' => 'Register with Google',
            'Hai gia un account?' => 'Already have an account?',
            'Non hai un account?' => 'No account yet?',
            'Nome' => 'Name',
            'Email' => 'Email',
            'Password' => 'Password',
            'oppure' => 'or',
            'Area personale' => 'Personal area',
            'Nuova richiesta' => 'New request',
            'Catalogo prodotti' => 'Product catalog',
            'Dove siamo' => 'Find us',
            'Esci dall\'account' => 'Sign out',
            'Contatti' => 'Contact',
            'Raccontaci cosa ti serve.' => 'Tell us what you need.',
            'Invia una richiesta' => 'Send a request',
            'Nome e cognome' => 'Full name',
            'Telefono' => 'Phone',
            'Tipo richiesta' => 'Request type',
            'Messaggio' => 'Message',
            'Invia richiesta' => 'Send request',
            'Cosa succede dopo' => 'What happens next',
            'Prenota appuntamento' => 'Book an appointment',
            'Prenota una visita o una call.' => 'Book an in-store visit or a call.',
            'Agenda bisp&amp;d' => 'bisp&amp;d calendar',
            'Richiedi appuntamento' => 'Request appointment',
            'Telefono / WhatsApp' => 'Phone / WhatsApp',
            'Motivo' => 'Reason',
            'Modalita' => 'Mode',
            'In negozio' => 'In store',
            'Data' => 'Date',
            'Ora' => 'Time',
            'Note utili' => 'Useful notes',
            'Invia richiesta appuntamento' => 'Send appointment request',
            'Scegli una fascia indicativa: il team controlla l’agenda Google condivisa e conferma l’appuntamento in negozio, su Google Meet o via WhatsApp Business.' => 'Choose an indicative slot: the team checks the shared Google Calendar and confirms the appointment in store, on Google Meet or via WhatsApp Business.',
            'Come funziona' => 'How it works',
            'L’agente del sito raccoglie la richiesta, la mette in coda per il negozio e, appena il calendario e configurato con accesso offline, puo creare direttamente l’evento nel Google Calendar usato dagli umani.' => 'The website agent collects the request, queues it for the store team and, once offline calendar access is configured, can create the event directly in the Google Calendar used by the human staff.',
            'Shop' => 'Shop',
            'Servizi' => 'Services',
            'Chi siamo' => 'About',
            'Blog' => 'Blog',
            'Altro' => 'More',
            'Teleassistenza' => 'Remote support',
            'Sostenibilità' => 'Sustainability',
            'Prodotti' => 'Products',
            'Tutto il catalogo' => 'Full catalog',
            'Assistenza PC' => 'PC support',
            'Connettività' => 'Connectivity',
            'Fonia' => 'Voice services',
            'Energia' => 'Energy',
            'Azienda' => 'Company',
            'News & Blog' => 'News & Blog',
            'Appuntamenti' => 'Appointments',
            'Area legale' => 'Legal area',
            'Trasparenza' => 'Transparency',
            'Privacy, cookie e condizioni di utilizzo: informazioni chiare per usare il sito e contattare bisp&amp;d con consapevolezza.' => 'Privacy, cookies and terms of use: clear information to use the website and contact bisp&amp;d with confidence.',
            'Informazioni societarie' => 'Company information',
            'Privacy e contatti' => 'Privacy and contact',
            'Cookie e navigazione' => 'Cookies and browsing',
            'Prodotti, prezzi e disponibilita' => 'Products, prices and availability',
            'Appuntamenti e calendario' => 'Appointments and calendar',
            'Per richieste relative a privacy, dati personali, ordini, appuntamenti o assistenza puoi scrivere a' => 'For privacy, personal data, orders, appointments or support requests you can write to',
            'I dati inviati tramite form vengono usati esclusivamente per gestire la richiesta e ricontattarti.' => 'Data submitted through forms is used only to manage the request and contact you back.',
            'Il sito usa solo strumenti tecnici necessari al funzionamento, alla sicurezza e alla gestione delle sessioni.' => 'The website only uses technical tools required for operation, security and session management.',
            'Eventuali strumenti statistici o marketing dovranno essere attivati solo dopo configurazione di consenso e cookie policy definitiva.' => 'Analytics or marketing tools must only be activated after consent management and the final cookie policy are configured.',
            'Prezzi, disponibilita, campagne e promozioni possono variare.' => 'Prices, availability, campaigns and promotions may vary.',
            'Le schede online aiutano a orientarsi, ma la conferma finale avviene sempre tramite negozio, preventivo o contatto diretto con bisp&amp;d.' => 'Online product pages help orientation, but final confirmation always happens through the store, a quote or direct contact with bisp&amp;d.',
            'Le richieste di appuntamento inviate dal sito vengono validate dal team bisp&amp;d e possono essere integrate nel Google Calendar aziendale per visite in negozio, call Google Meet o contatti WhatsApp Business.' => 'Appointment requests submitted from the website are validated by the bisp&amp;d team and can be integrated into the company Google Calendar for in-store visits, Google Meet calls or WhatsApp Business contacts.',
            'Codice SDI' => 'SDI code',
            'Capitale sociale' => 'Share capital',
            'interamente versato' => 'fully paid up',
            'Per l’invio delle fatture elettroniche preferiamo il canale SDI.' => 'For electronic invoices we prefer the SDI channel.',
            'Il punto di riferimento tech a Piombino: negozio, laboratorio e consulenza digitale per privati, professionisti e aziende.' => 'The tech reference point in Piombino: store, workshop and digital consulting for private customers, professionals and companies.',
            'Tutti i diritti riservati.' => 'All rights reserved.',
            'Usiamo cookie tecnici necessari al funzionamento. Nessun tracciamento marketing senza il tuo consenso.' => 'We use technical cookies required for the website to work. No marketing tracking without your consent.',
            'Cookie policy' => 'Cookie policy',
            'Accetta' => 'Accept',
            'Solo necessari' => 'Necessary only',
            'Contattaci su WhatsApp' => 'Contact us on WhatsApp',
            'Raggiungi il negozio con Google Maps' => 'Get directions with Google Maps',
            'Le nostre recensioni Google' => 'Our Google reviews',
            'su Google Reviews' => 'on Google Reviews',
            'Informatica' => 'Computing',
            'Smartphone' => 'Smartphones',
            'Gaming' => 'Gaming',
            'Assistenza tecnica' => 'Technical support',
            'Consulenza acquisto' => 'Buying advice',
            'Telefonia' => 'Mobile services',
            'Soluzioni aziendali' => 'Business solutions',
            'Soluzioni business' => 'Business solutions',
            'Preventivo prodotto' => 'Product quote',
            'Altro' => 'Other',
        ];
    }
}
