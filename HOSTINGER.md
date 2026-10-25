# 3DLens op Hostinger installeren

Installatie: **Hostinger Business Web Hosting**, op **https://koomen.3dlens.cloud/**. Subdomein, bestanden, database, privéconfiguratie en eigenaarsaccount zijn ingericht. Database en gebruiker heten `u179310343_3dlens`. De databaseverbinding en login werken; het lege archief is geopend. De gebruiker heeft alle echte toegangscodes rechtstreeks op Hostinger ingevuld.

### Vastgestelde mapindeling in hPanel

Het subdomein is aangemaakt met documentroot `/home/u179310343/domains/3dlens.cloud/public_html/koomen`. Het doelpakket `3dlens-install-koomen.zip` is daarvoor aangepast. Pak dit pakket uit op het niveau waar `public_html` staat. Het bevat `public_html/koomen/` en daarnaast `3dlens-private/`; de PHP-ingangspunten verwijzen twee niveaus omhoog naar de privémap. Hierdoor blijven configuratie en modellen ook buiten de publieke map van het hoofddomein.

Het algemene pakket `3dlens-hostinger-voorbereid.zip` is voor een zelfstandige documentroot; gebruik op deze hosting het specifieke `koomen`-pakket. In de repository wordt dit pakket gemaakt met `python tools/package-hostinger.py --subdomain koomen --output PAD_NAAR_ZIP`.

Controleer in hPanel of `koomen.3dlens.cloud` als eigen PHP-website/subdomein aan dit pakket is gekoppeld en naar de juiste hosting verwijst. Activeer daarna het SSL-certificaat. Gebruik de documentroot die hPanel voor dit subdomein aanwijst; dit kan een andere map zijn dan de `public_html` van je hoofddomein. De privémap moet direct naast die documentroot staan en buiten alle publiek bereikbare mappen blijven. Als de documentroot van het subdomein zelf onder de publieke map van een andere website staat, moet de mapindeling eerst worden aangepast om privébestanden buiten beide websites te houden.

## 1. Controleer je pakket en kies een plek

Log in op [Hostinger hPanel](https://hpanel.hostinger.com/). Ga naar **Websites → Dashboard** bij je website en daarna naar **Hostingplan → Plangegevens** (*Hosting plan → Plan Details*). De pakketnaam en domeinnaam staan daar. [Hostinger: pakketgegevens vinden](https://www.hostinger.com/support/1583276-how-to-find-the-details-of-your-hosting-plan-in-hostinger/).

De app gebruikt standaard PHP-webhosting met een MySQL-database. Als je alleen Hostinger AI Builder ziet en geen PHP-/databasebeheer hebt, is die omgeving niet geschikt voor deze app. Kies een eigen PHP-website. Een los subdomein zoals `3dlens.jouwdomein.nl` is handig als op je hoofddomein al een andere website staat. Vervang die bestaande website niet.

Deze mapindeling verwacht de documentroot van een eigen website of subdomein. Voor installatie in een submap moeten de paden naar de privémap in de PHP-ingangspunten eerst worden aangepast.

## 2. Stel PHP en HTTPS in

Kies een ondersteunde PHP-versie van minimaal **8.2**, bij voorkeur de standaard ondersteunde versie van je hosting. Controleer dat `pdo_mysql` actief is. [Hostinger: PHP-versie instellen](https://www.hostinger.com/support/1575755-how-to-change-the-php-version-of-your-hostinger-hosting-plan/).

Activeer het SSL-certificaat en open de website via `https://`. De app weigert standaard onbeveiligde HTTP-aanvragen. Gebruik ook de HTTPS-instellingen in hPanel en laat `require_https` in de privéconfiguratie aan staan.

De standaardlimiet in de app is **200 MiB per model**. Stel `upload_max_filesize` minstens zo hoog in en `post_max_size` iets hoger, bijvoorbeeld 220 MiB, voor de upload plus miniatuur en metadata. Een lager pakketlimiet blijft van toepassing. Houd rekening met schijfruimte en verwerkingstijd voor grote resinbestanden.

## 3. Maak een lege database

Ga in het dashboard van de gekozen website naar **Databases → Beheer** (*Management*). Maak een nieuwe database met een eigen databasegebruiker en een sterk wachtwoord. Noteer de volledige namen, inclusief de Hostinger-prefix zoals `u123456789_`.

Gebruik een aparte lege database voor 3DLens. De installatie maakt tabellen met het voorvoegsel `lens_` aan; er is geen handmatige SQL-import nodig. De databasegebruiker moet tijdens installatie tabellen kunnen aanmaken. [Hostinger: database aanmaken](https://www.hostinger.com/support/1583542-how-to-create-a-new-mysql-database-in-hostinger/).

## 4. Zet de bestanden op de juiste plaats

Open Bestandsbeheer op het niveau waar je **public_html** kunt zien, dus één niveau erboven. Plaats de map **3dlens-private** naast **public_html**. Plaats uitsluitend de inhoud van de meegeleverde **public_html**-map in de documentroot van je gekozen website.

```text
map-van-je-website/
├── 3dlens-private/
│   ├── bootstrap.php
│   ├── repository.php
│   ├── archive.php
│   ├── auth-view.php
│   ├── schema.sql
│   ├── config.example.php
│   └── .htaccess
└── public_html/
    ├── index.php
    ├── login.php
    ├── logout.php
    ├── setup.php
    ├── api.php
    ├── storage.js
    └── .htaccess
```

De PHP-bestanden zoeken de privémap direct naast de documentroot. Upload geen `.git`, tests, lokale configuratie of bestaande modellen los naar de publieke map. Neem de verborgen `.htaccess`-bestanden mee. Verwijder na uitpakken het eventuele uploadarchief van de hosting.

Als Bestandsbeheer je niet boven `public_html` laat komen, controleer dan of SFTP of het volledige bestandsbeheer toegang geeft tot de bovenliggende map. Verplaats de privémap niet naar de publiek bereikbare map.

## 5. Vul de privéconfiguratie in

Maak binnen **3dlens-private** een kopie van `config.example.php` met de naam **config.local.php**. Vul in de editor van Bestandsbeheer je databasehost, volledige databasenaam, databasegebruiker en databasewachtwoord in. De host is vaak `localhost`; gebruik de waarde die hPanel voor jouw database toont.

Vul bij `setup_key` een unieke, willekeurige installatiesleutel van minimaal **32 tekens** in. Genereer die bijvoorbeeld met je wachtwoordmanager en gebruik letters en cijfers. De sleutel is tijdelijk en is iets anders dan je toekomstige inlogwachtwoord.

Behoud de PHP-aanhalingstekens en komma's. Een enkel aanhalingsteken of backslash in een waarde moet in een PHP-string correct worden ge-escaped. Zet echte waarden alleen in `config.local.php` op de server en deel ze niet via chat of GitHub. De voorbeeldconfiguratie bevat geen echte toegangscodes.

Laat `storage_path` op de standaardwaarde staan. Tijdens installatie maakt de app **3dlens-private/storage** aan. Die map moet schrijfbaar zijn voor PHP en buiten `public_html` blijven. Gebruik geen algemeen schrijfbare bestandsrechten zoals 777.

## 6. Maak je eigen account aan

Open **https://koomen.3dlens.cloud/setup.php**. Vul de installatiesleutel in en kies je gebruikersnaam en een uniek wachtwoord van minimaal **14 tekens**. De server accepteert maximaal 72 bytes voor het wachtwoord; gewone letters, cijfers en leestekens tellen elk als één byte.

De installatie maakt de tabellen en precies één eigenaarsaccount aan en verwijst je naar de loginpagina. Er is geen openbare registratie. Als het account bestaat, blokkeert de server de setup, ook als iemand de URL weet.

Maak daarna `setup_key` in de privéconfiguratie leeg (`'setup_key' => ''`). Je kunt ook `setup.php` van de server verwijderen als extra afsluiting. Het normale inloggen gebruikt de installatiesleutel niet.

## 7. Controleer eerst een testmodel

1. Open de website in een privévenster: je moet naar de loginpagina gaan.
2. Open zonder login `api.php?store=files`: er mogen geen gegevens verschijnen.
3. Log in en importeer één klein STL-bestand. Open de 3D-preview.
4. Maak een collectie, verplaats het bestand, voeg een tag en omschrijving toe.
5. Vernieuw de pagina en log op een tweede apparaat in. De gegevens moeten behouden zijn.
6. Download het model, maak een backup en test samenvoegen zonder duplicaten.
7. Log uit en gebruik de terugknop. De website moet opnieuw om inloggen vragen. Een model-URL moet zonder sessie geblokkeerd zijn.
8. Controleer dat de privémap niet publiek bereikbaar is. Alleen de documentroot mag worden gepubliceerd.

PHP-syntaxis is inmiddels gecontroleerd. De volledige MySQL-integratie en modelpreviews moeten nog op een testomgeving worden gecontroleerd. De repository bevat hiervoor een serverintegratietest; de README beschrijft hoe je die draait.

## 8. Zet je bestaande archief over

Open de originele HTML op OneDrive met de browser waarin je modellen staan. Maak daar een **Backup**. Log in op de online versie en kies **Restore** om het `.forge`-bestand samen te voegen.

De modellen in je oude browser worden niet automatisch meegestuurd met de HTML. Bewaar de originele backup tot je hebt gecontroleerd dat alle collecties, omschrijvingen en modellen online aanwezig zijn. Een grote restore uploadt modellen afzonderlijk; bij een onderbreking kun je dezelfde backup opnieuw samenvoegen.

## Backups en herstel

Bewaar periodiek zowel een **database-export** als een kopie van **3dlens-private/storage** van hetzelfde moment. Maak die hostingbackup terwijl je geen wijzigingen uitvoert. Alleen de database exporteren bewaart niet de originele modellen. De ingebouwde `.forge`-backup bevat modellen en archiefgegevens maar geen gebruikersaccount of serverconfiguratie.

Er is geen e-mailfunctie voor wachtwoordherstel. De README beschrijft inmiddels een tijdelijke, eenmalige herstelpagina die alleen de hosting-eigenaar kan activeren. Bewaar je wachtwoord in een wachtwoordmanager. Als je het kwijt bent, laat je de bestaande wachtwoordhash via beveiligd databasebeheer vervangen door een nieuw gegenereerde PHP-wachtwoordhash. Wis je database of modellen niet om je account te herstellen.

## Als iets niet werkt

- **Nog niet ingesteld:** controleer de bestandsnaam en plaats van `config.local.php`.
- **Tijdelijk niet beschikbaar:** controleer PHP-versie, PDO MySQL, databasegegevens en de PHP-foutenlog in hPanel. Deel nooit wachtwoorden of je volledige configuratie.
- **Opslag niet beschikbaar:** controleer locatie en schrijfrechten van de privémap. De app weigert opslag in de documentroot.
- **Upload te groot:** controleer de app-limiet, `upload_max_filesize` en `post_max_size`.
- **Preview werkt niet:** controleer de browserconsole en toegang tot jsDelivr. Ondersteuning voor sommige 3MF- en resinvarianten blijft beperkt tot wat de originele viewer kon lezen.
- **Te veel inlogpogingen:** wacht 15 minuten. Een nieuw cookie omzeilt de limiet niet.

Uitgevoerd: subdomein `koomen` aangemaakt, doelpakket geüpload, privébestanden buiten `public_html` geplaatst en webbestanden in `public_html/koomen` geplaatst. Database en gebruiker `u179310343_3dlens`, privéconfiguratie en eigenaarsaccount zijn ingericht. Inloggen en het lege archief zijn gecontroleerd; de browser meldde geen fouten. De installatiepagina is geblokkeerd. Na sessieverloop verwijzen pagina's naar de login en weigeren de bestandenlijst, collectielijst en modeldownload gegevens te tonen.

Het archief is nog leeg. Test eerst één klein model voordat je je oude backup importeert: upload, preview, metadata bewerken, herladen, downloaden en backup/restore moeten nog met een model worden getest. De PHP-lint is inmiddels geslaagd; de geautomatiseerde serverintegratietest is nog niet uitgevoerd.

De tijdelijke uitgepakte map en het installatiearchief staan buiten `public_html` en bevatten geen echte credentials of modellen. Een aanvullende controlepagina kon wegens verlopen Bestandsbeheer-toegang niet worden geplaatst en maakt geen deel uit van de oplevering. De toegangscontrole is vervolgens rechtstreeks via de browser uitgevoerd.

## Optionele OneDrive-opslag

Zie [ONEDRIVE.md](ONEDRIVE.md). De OneDrive-update behoudt de database, lokale modellen en `config.local.php`. Publiceer beide nieuwe privémodules voordat je `repository.php` bijwerkt. De koppeling wordt pas actief na de Microsoft-toestemming van de eigenaar.

## Publieke distributie en herstelmail 1.0.0

De openbare downloadpagina staat op https://3dlens.cloud/ met handleiding en versie-ZIP. De aanwezige WordPress-installatie is behouden; een DirectoryIndex-regel buiten de WordPress-markeringen toont index.html als startpagina. Na publicatie is de hostingcache gewist. Het gepubliceerde ZIP-bestand is opnieuw gedownload en op SHA-256 vergeleken met het geteste lokale origineel: identiek.

Voor de update zijn 3dlens-private, de publieke map koomen en de oude WordPress-.htaccess gekopieerd naar 3dlens-backup-voor-publicatie, buiten public_html. De oorspronkelijke config.local.php, modelopslag en OneDrive-verbindingen zijn behouden. De twee aanvullende hersteltabellen zijn op MySQL succesvol aangemaakt met migrations/002-email-reset.sql. Er zijn geen hersteladressen of SMTP-geheimen door de agent opgeslagen.

De bestaande eigenaar moet na inloggen op account.php zelf het hersteladres en een verzendmailbox instellen en aflevering testen. De gebruiker heeft jeroen@jroen.nl als gewenst hersteladres opgegeven; dit is nog niet geactiveerd. De private connections-map bevat nog geen mail-settings.json. Herstel via e-mail is daarom nog niet actief. Er is geen echte SMTP-mail verstuurd. Anonieme herstelaanvragen zijn op de hosting getest met een niet-bestaand testadres; de pagina gaf de generieke bevestiging zonder accountinformatie.
