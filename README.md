# 3DLens

Privéarchief voor STL-, 3MF- en resin/SLA-printbestanden, gebaseerd op de bestaande `stl-organizer14.html`. Repository: https://github.com/jeroenkoomen1975/3dlens.

Hosting: Hostinger Business Web Hosting. Website: **https://koomen.3dlens.cloud/**. Het subdomein, de bestanden, database, privéconfiguratie en het eigenaarsaccount zijn ingericht. Database en gebruiker heten `u179310343_3dlens`. De gebruiker heeft de echte credentials rechtstreeks op Hostinger ingevuld; die staan niet in deze repository.

De bestaande Three.js-viewer, collecties, tags, beschrijvingen, miniaturen, zoeken en modelanalyse zijn behouden. Het archief staat centraal op de server. Je moet inloggen om de website, gegevens en originele modellen te openen. Er is één eigenaar en geen openbare registratie.

## Hostinger installeren

Volg **[HOSTINGER.md](HOSTINGER.md)**. Deze versie is bedoeld voor PHP-webhosting met MySQL/MariaDB, niet voor een website die uitsluitend binnen Hostinger AI Builder draait.

- PHP 8.2 of hoger, `pdo_mysql`, sessies en `getimagesizefromstring`.
- Een eigen MySQL/MariaDB-database met InnoDB en utf8mb4.
- Een domein of subdomein met HTTPS.
- Schrijfrechten voor `3dlens-private/storage`, buiten de documentroot.
- Geen Node.js-server, Composer, framework of buildstap nodig voor hosting.

```text
3dlens-private/        Buiten de publiek bereikbare map
  archive.php         Bestaande viewer met serveropslag
  bootstrap.php       Sessies, authenticatie, CSRF, databaseverbinding
  repository.php      Validatie en opslag
  auth-view.php       Inlog- en installatieformulier
  schema.sql          Databasetabellen
  config.example.php  Kopiëren naar config.local.php, alleen op de server
  storage/            Privébestanden, automatisch aangemaakt en niet in Git
public_html/          Documentroot van je domein of subdomein
  index.php           Privéarchief achter login
  login.php
  logout.php
  setup.php           Eenmalig eigenaarsaccount, daarna geblokkeerd
  api.php             Geauthenticeerde gegevens en downloads
  storage.js          Browserkoppeling met de API
```

## Opslag en toegang

MySQL bewaart gebruikersnaam, wachtwoordhash, collecties, tags, omschrijvingen, miniaturen, bestandsnamen en modelstatistieken. De originele 3D-bestanden staan als willekeurig benoemde `.bin`-bestanden buiten `public_html`. Downloads lopen via de geauthenticeerde API. De server berekent zelf de SHA-256 en bestandsgrootte; dubbele inhoud wordt geweigerd.

Schrijven en uitloggen vereisen een sessie en CSRF-token. Cookies zijn HttpOnly en SameSite=Lax (voor de terugkeer vanaf Microsoft; schrijfacties blijven met CSRF beveiligd), en Secure op HTTPS. Sessies verlopen standaard na twee uur inactiviteit en uiterlijk na twaalf uur. Inlogpogingen worden per IP en gebruikersnaam begrensd in de database, ook over meerdere browsersessies.

Grote modellen worden pas geladen wanneer een preview of backup ze nodig heeft. Extra geladen modeldata wordt gecachet tot 128 MiB; net geïmporteerde bestanden en de 3D-viewer gebruiken daarnaast geheugen. Een volledige `.forge`-backup wordt nog in de browser opgebouwd en kan voor heel grote archieven veel geheugen vragen.

## Bestaande gegevens overzetten

De oude HTML bevat niet je modellen: die staan in de lokale browserdatabase. Open de originele HTML in de browser waarin je het archief gebruikt en maak daar eerst een **Backup**. Log na installatie in op je online website en zet het `.forge`-bestand terug via **Restore**. De online versie voegt samen; bestaande gegevens worden niet eerst gewist. Bij een onderbroken import blijven reeds opgeslagen bestanden behouden; opnieuw samenvoegen slaat dubbele inhoud over.

De originele HTML op OneDrive is ongewijzigd. De historische taalteksten over lokale opslag worden bij het laden in alle vijf talen vervangen door de instructies voor serveropslag.

## Ontwikkelen en controleren

Node.js is alleen nodig voor de ontwikkelcontroles; er zijn geen npm-afhankelijkheden.

```sh
npm test
npm run check
```

Met PHP beschikbaar kun je alle PHP-bestanden controleren met `php -l bestandsnaam.php`. Maak voor lokale tests een testconfiguratie met een aparte MySQL-database, zet uitsluitend daarin `require_https` op `false`, en start vanuit de repository:

```sh
php -S 127.0.0.1:8080 -t public_html
```

Open `http://127.0.0.1:8080/setup.php` en volg de installatiehandleiding. Gebruik de ingebouwde PHP-server alleen lokaal. Voor de serverintegratietest stel je `LENS_TEST_URL`, `LENS_TEST_USERNAME`, `LENS_TEST_PASSWORD` en `LENS_TEST_CONFIRM=disposable` tijdelijk in je shellomgeving in en voer je `npm run test:server` uit. Gebruik een aparte testwebsite: de test maakt en verwijdert eigen testcollecties en modellen. Hij controleert onder meer sessierotatie, CSRF, privédownloads, uploadvalidatie, dubbele inhoud en uitloggen.

Controleer in een echte browser ook STL/3MF/SLA-import, previews, tags, omschrijving, herladen op een tweede apparaat, backup en samenvoegen. De viewer gebruikt vastgepinde modules van jsDelivr en fonts van Google; de browser heeft internettoegang nodig. Er worden geen modellen naar die diensten geüpload.

## Verificatiestatus van deze oplevering

- Tien Node-tests voor de opslagkoppeling geslaagd.
- JavaScript-syntaxis van de viewer en opslagmodule gecontroleerd.
- Op Hostinger gecontroleerd: databaseverbinding, aanmaken eigenaarsaccount, inloggen en openen van het lege archief zonder browserfouten. De installatiepagina is na het aanmaken van het account geblokkeerd.
- Na het verlopen van de sessie gecontroleerd: archief verwijst naar login; bestandenlijst, collectielijst en modeldownload geven uitsluitend de melding om opnieuw in te loggen.
- Uploaden, 3D-preview, bewerken en backup/restore zijn **nog niet met een model op de hosting getest**. Alle PHP-bestanden zijn nu syntactisch gecontroleerd met PHP 8.3. De geautomatiseerde serverintegratietest is nog niet uitgevoerd.
- De serverintegratietest is voorbereid en wordt zonder expliciete testomgeving overgeslagen.
- De website is online. Bij de eerste oplevering was het archief leeg; de gebruiker heeft inmiddels gemeld dat de applicatie werkt. Nog niet naar GitHub gepusht.

Rond de server- en browsercontroles op een testsubdomein af voordat je je echte archief importeert.

## OneDrive (persoonlijk Microsoft-account)

De optionele koppeling gebruikt Microsoft Graph met `Files.ReadWrite.AppFolder` en `offline_access`. Alleen de eigen appmap wordt toegankelijk. MySQL bewaart metadata; modelbytes gaan na koppeling naar OneDrive. Er ontstaan geen openbare deellinks. Downloads blijven achter de bestaande 3DLens-login en worden via een tijdelijk serverbestand volledig op grootte en SHA-256 gecontroleerd.

De OneDrive-code is op Hostinger geplaatst. Zonder Microsoft-toestemming is de koppeling nog niet actief; de instellingen achter login en een echte cloud-upload moeten nog worden gecontroleerd. Zie [ONEDRIVE.md](ONEDRIVE.md) voor installatie, appregistratie en migratie. Geen schemawijziging nodig. Zonder koppeling blijft lokale opslag werken. PHP cURL en tijdelijke schijfruimte voor één model zijn vereist. De bestaande app-limiet van 200 MiB blijft gelden.

`3dlens-private/connections/` bevat geheime appinstellingen en vernieuwbare tokens; deze map wordt uitgesloten van Git en installatiepakketten. De bestaande `storage/` bevat ook kleine OneDrive-verwijzingen: neem die samen met database en verbindingen mee in een beveiligde hostingbackup. Een database-export alleen is onvoldoende.

Controleer de offline foutafhandeling met `php tests/onedrive.test.php`. Deze test gebruikt tijdelijke voorbeeldgegevens zonder Microsoft-verzoeken. PHP-lint, deze offline tests, tien Node-opslagtests en JavaScript-syntaxiscontroles zijn geslaagd. Een volledige Microsoft-koppeling en cloudmigratie zijn pas te testen na toestemming van de eigenaar.
