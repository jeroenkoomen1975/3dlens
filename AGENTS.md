# Projectinstructies voor Codex

- Dit project gebruikt de bestaande GitHub-repository `jeroenkoomen1975/3dlens`.
- Communiceer met de gebruiker in het Nederlands.
- 3DLens is een privéarchief voor STL-, 3MF- en SLA-bestanden op Hostinger PHP-webhosting met MySQL/MariaDB. Gebruik PHP 8.2 of hoger, PDO en de bestaande Three.js-viewer; er is geen framework of buildstap nodig.
- De hele website en alle modellen vereisen inloggen. Er is één eigenaarsaccount en geen openbare registratie.
- Alleen `public_html/` is publiek. `3dlens-private/`, databasegegevens en uploads blijven buiten de documentroot. Modeldownloads moeten altijd via de geauthenticeerde API lopen.
- Alle schrijfacties vereisen een geldige sessie en CSRF-token. Sla wachtwoorden uitsluitend gehasht op.
- Houd wijzigingen gericht op de gevraagde opdracht en behoud bestaand werk.
- Sla geen wachtwoorden, tokens of lokale omgevingsbestanden op in Git. Voeg een `.env.example` met uitsluitend voorbeeldwaarden toe als configuratie nodig is.
- Documenteer installatie-, start- en teststappen in `README.md` zodra ze bestaan.
- Voer bij codewijzigingen de passende beschikbare controles uit en meld welke controles zijn uitgevoerd of nog ontbreken.
