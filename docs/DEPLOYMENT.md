# Deployment locale webcmslight

## Servizi persistenti

Su questo server la preview usa `systemd --user`, con linger attivo per `funboy`.

Servizi installati:

```bash
~/.config/systemd/user/bisped-mariadb.service
~/.config/systemd/user/bisped-frankenphp.service
```

Template tracciati nel repo:

```bash
deploy/systemd/bisped-mariadb.service
deploy/systemd/bisped-frankenphp.service
```

Installazione/aggiornamento:

```bash
cp deploy/systemd/bisped-mariadb.service ~/.config/systemd/user/bisped-mariadb.service
cp deploy/systemd/bisped-frankenphp.service ~/.config/systemd/user/bisped-frankenphp.service
systemctl --user daemon-reload
systemctl --user enable --now bisped-mariadb.service bisped-frankenphp.service
```

Verifica:

```bash
systemctl --user is-active bisped-mariadb.service bisped-frankenphp.service
curl -s http://127.0.0.1:4000/ping.php
curl -s http://127.0.0.1:4000/health/db
curl -I https://bisped.net/
```

Restart:

```bash
systemctl --user restart bisped-mariadb.service bisped-frankenphp.service
```

Log:

```bash
journalctl --user -u bisped-mariadb.service -n 100 --no-pager
journalctl --user -u bisped-frankenphp.service -n 100 --no-pager
tail -n 100 storage/logs/mariadb.err
tail -n 100 storage/logs/app.log
```

## Health endpoint

`/ping.php` verifica PHP.

`/health/db` verifica connessione database e restituisce `503` se il DB non e disponibile.

## Sicurezza deploy

- `.env.php` deve restare fuori da Git e leggibile solo dall'utente runtime.
- `public/install.php` e disabilitato: abilitarlo solo temporaneamente con `BISPED_ALLOW_WEB_INSTALL=1`.
- Le credenziali FTP per recupero asset non sono nel repo: usare `BISPED_FTP_USER`, `BISPED_FTP_PASS`, `BISPED_FTP_UPLOADS_URL`.
- Per Google OAuth configurare redirect separati per preview e produzione.
- Per Calendar sync impostare `calendar.google_refresh_token` solo in `.env.php`.
- Prima del go-live verificare:

```bash
curl -I https://bisped.net/
curl -s https://bisped.net/health/db
curl -s -o /tmp/install -w '%{http_code}\n' https://bisped.net/install.php
```

## Nota

Non avviare MariaDB e FrankenPHP a mano dentro una shell SSH per la preview pubblica: se la sessione termina, il sito rischia `Connection refused`.
