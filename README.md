# 3DLens

## WordPress-promotiethema

`wordpress/3dlens/` bevat een zelfstandig klassiek thema voor de openbare promotiewebsite. De privéapp is een aparte installatie. Het thema bevat uitleg over functies, eigen hosting, opslag en herstelmail, download- en handleidinglinks, uitklapvragen en templates voor nieuws en gewone pagina's. Onder Weergave > Customizer > 3DLens promotie zijn 24 velden aanpasbaar. Er zijn geen pagebuilder, externe fonts of scripts nodig.

Installeer het thema als ZIP via Weergave > Thema's > Nieuw thema > Thema uploaden. Lees `wordpress/3dlens/README.txt` voor het overschakelen vanaf de bestaande openbare `index.html`, backups en terugdraaien. Laat de privéapp, downloads en handleiding staan.

Het ZIP wordt gebouwd met `python tools/build-wordpress-theme.py --output PAD_NAAR_ZIP`. De generator gebruikt de bestaande openbare vormgeving en neemt alleen de themamap mee. De eigen illustratie, GPL-licentie en WordPress-themascreenshot zijn inbegrepen.

Controle: alle negen PHP-bestanden zijn syntactisch gecontroleerd met PHP 8.3. `php tools/preview-wordpress-theme.php PAD_NAAR_VOORBEELDMAP` controleert 24 Customizer-instellingen, sanitizers, escaping, de stylesheetversie en de gerenderde homepage met een geïsoleerde WordPress-functiesimulatie. Browsercontrole van de gerenderde pagina: brede en 390px-smalle weergave, sectienavigatie en een uitklapvraag geslaagd. Het thema is inmiddels door de gebruiker geactiveerd op de hosting.

Thema 1.0.1 herstelt een cacheconflict: het echte stylesheet op de server kwam overeen met het installatiepakket, maar de browser kreeg op het adres met `ver=1.0.0` nog CSS van een andere indeling. De stylesheet-URL gebruikt nu een SHA-256 van de inhoud, zodat een gewijzigde stylesheet een eigen cacheversie krijgt. `functions.php` en de stylesheetheader zijn na lokale controle live bijgewerkt; de vorige bestanden zijn in de werkmap geback-upt. De live WordPress-homepage laadt de nieuwe versie en toont de bedoelde indeling zonder horizontale overflow. Geen WordPress-inhoud, thema-instellingen of privéappgegevens gewijzigd.

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

Versie 1.4.0 (26 oktober 2026) gebruikt `3dlens-private/release.php` als centrale bron voor het versienummer, de releasedatum en de releasegeschiedenis. De kop van de app en About tonen dezelfde versie. De link Wijzigingen per versie in About opent `changelog.php` in een nieuw tabblad. Deze route vereist de bestaande login, sluit de sessielock na controle en rendert de privéweergave `changelog-view.php`. De geschiedenis onderscheidt de huidige online uitgave van basisversie 1.3.0; tussenliggende, niet genummerde hostingupdates krijgen geen achteraf verzonnen releaseversies. Het About-venster is begrensd tot het scherm en verticaal scrollbaar.

Bij een volgende release wijzig je de centrale versiegegevens en voeg je een geschiedenisblok toe in `release.php`. Installatiepakketten nemen de twee privébestanden en de beveiligde publieke route mee. De vier gewijzigde/nieuwe bestanden zijn op Hostinger geplaatst; opgeslagen inhoud is opnieuw ingelezen en vergeleken. PHP-lint, JavaScript-controle, pakketcontrole, lokale browsercontrole van About en de geschiedenislink zijn geslaagd. Live gecontroleerd dat de wijzigingspagina zonder sessie naar de login verwijst. Het openbare installatiepakket 1.0.0 en het WordPress-thema zijn niet gewijzigd.

Modellen kun je met de muis vanuit hun kaart (ook vanaf de miniatuur) naar een collectie in de linkerzijbalk slepen. De doelcollectie krijgt een stippelrand. Loslaten op Niet-gecategoriseerd verwijdert alleen de collectietoewijzing. Alle bestanden is een overzicht en geen verplaatsdoel. Bestanden vanaf je computer en mappen kun je ook rechtstreeks op een collectie loslaten; ondersteunde bestanden worden dan in die collectie geïmporteerd. Loslaten elders behoudt de bestaande importwerkwijze. Het selectiemenu in het detailpaneel blijft beschikbaar, onder meer voor apparaten zonder muis.

Vinkjes op de modelkaarten en Ctrl+klik (Mac: Cmd+klik) ondersteunen meervoudige selectie. De actiebalk toont het aantal geselecteerde modellen, Alles in beeld, een doelcollectie, Verplaatsen, Verwijderen en Deselecteren. Een geselecteerde groep kan ook samen worden versleept. Bij wisselen van collectie of zoekopdracht vervalt de selectie, zodat verborgen modellen niet onbedoeld worden verwerkt. Groepsacties gebruiken opeenvolgende geauthenticeerde API-verzoeken; ze vormen geen gezamenlijke database-transactie. Bij een fout stopt de actie, toont het aantal bevestigde successen en bewaart de resterende selectie. Verwijderen vraagt één bevestiging met het aantal en is definitief. Alleen geslaagde verwijderingen verdwijnen uit de interface en previewcache.

De groepsselectie staat op Hostinger; de opgeslagen bron is na herladen vergeleken met de lokale versie. Acht aanvullende tests dekken selectie, groepsverplaatsing, annuleren, geslaagde en gedeeltelijk mislukte verwijderingen, verouderde doelen en gelijktijdige acties. Alle 24 Node-tests, de JavaScript-syntaxiscontrole en PHP-lint slagen. In een lokale browserkopie met uitsluitend voorbeeldgegevens zijn vinkjes, Ctrl+klik, Alles in beeld, groepsverplaatsing via de knop, groepsslepen en behoud na herladen gecontroleerd. Er zijn geen productiemodellen verwijderd of verplaatst voor deze controle. De openbare appdownload 1.0.0 is niet vervangen; de werkmap bevat een apart updatepakket.

Verplaatsen gebruikt dezelfde geauthenticeerde API met CSRF en wijzigt alleen metadata; er worden geen modelbytes opnieuw geüpload of naar een andere opslag verplaatst. De lokale collectie wordt pas na een geslaagde opslag bijgewerkt. De wijziging staat op Hostinger; het opgeslagen archive.php is na herladen vergeleken met de lokale bron. Zes nieuwe regressietests controleren echte drop-handlers en opgeslagen collectiegegevens, inclusief opslagfouten en externe bestanden; de bestaande tien opslagtests, JavaScript-controle en PHP-lint slagen. In een lokale browserkopie met uitsluitend voorbeeldgegevens zijn een STL en 3MF door een echte sleepbeweging verplaatst en na herladen teruggevonden. Live controle achter de eigenaarslogin vereist opnieuw inloggen; het productiedata-archief is niet gebruikt voor testmutaties.

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

## Toegang herstellen via de hosting-eigenaar

`recover.php` is standaard afgesloten (404). Alleen de hosting-eigenaar kan buiten public_html een tijdelijke `recovery.local.json` plaatsen met een SHA-256 van een cryptografisch willekeurige sleutel en een vervaltijd binnen 30 minuten. Eenmalig herstel vereist die sleutel, CSRF en tweemaal een nieuw wachtwoord van 14 tot 72 bytes. De eigenaar vult het wachtwoord zelf in; de sleutel wordt voor de databasewijziging verbruikt. Een onzekere databasecommit vereist een nieuwe herstelsleutel. Herstel wijzigt alleen de wachtwoordhash van eigenaarsaccount 1 en behoudt alle archiefgegevens. Sessies zijn aan de wachtwoordhash gekoppeld en worden na een wachtwoordwijziging ongeldig. Eenmalig aanmelden is ook nodig voor sessies die nog van voor deze controle dateren.

De herstelbestanden en eventuele sleutelbackups zijn geheim en blijven buiten Git en distributiepakketten. Verlopen sleutels werken niet meer; ruim tijdelijke herstelarchieven en uitgepakte sleutelbestanden na afloop op via Hostinger.

## Versie 1.0.0 verspreiden en herstelmail

Het algemene installatiepakket wordt gemaakt met python tools/package-hostinger.py --output PAD_NAAR_ZIP. Voor de bestaande diepere documentroot gebruik je aanvullend --subdomain koomen. De pakketcontrole weigert echte configuratie, modellen en bekende installatiegegevens. distribution/INSTALLEREN.html en LEESMIJ.txt vormen de algemene handleiding; distribution/site/index.html is de openbare downloadpagina. Elke gebruiker installeert een eigen archief met een eigenaarsaccount, eigen app_url, database, mailbox en eventueel Microsoft-appregistratie.

Account biedt hersteladres en beveiligde SMTP-instellingen, met bevestiging van het huidige 3DLens-wachtwoord. De twee nieuwe tabellen worden alleen bij installatie of na ingelogd openen van Account aangemaakt. Herstel gebruikt een willekeurige selector en geheime code; alleen de hash van de code wordt opgeslagen. Links werken 30 minuten en eenmaal. Een nieuwe aanvraag vervangt de vorige. Wachtwoordwijziging en verbruik van de code vinden in dezelfde database-transactie plaats. Bestaande sessies worden door de wachtwoordvingerafdruk ongeldig; modellen blijven behouden. Publieke aanvragen geven dezelfde tekst voor onbekende adressen, ontbrekende mailconfiguratie en mailfouten. Aanvragen zijn begrensd per IP en adres.

PHPMailer 7.1.1 is ongewijzigd gebundeld onder LGPL 2.1, inclusief LICENSE. SMTP vereist TLS, een eigen mailbox en een toegestane afzender. Bij PHP-FPM/LiteSpeed wordt SMTP na het publieke antwoord uitgevoerd waar de hosting dit ondersteunt; andere hosts kunnen wachten op de mailserver. Er is geen garantie dat een door SMTP aangenomen bericht in de inbox aankomt: test aflevering, spam en SPF/DKIM per installatie.

De geïsoleerde resettests draaien met php -d extension=pdo_sqlite tests/email-reset.test.php. De SQLite-adapter laat uitsluitend in de test FOR UPDATE weg; deze test vervangt geen MySQL-concurrentietest op hosting. Geslaagd: PHP-lint, 22 resetcontroles, bestaande opslagtests en OneDrive-tests. De gebruiker heeft inmiddels bevestigd dat OneDrive werkt. Werkelijke SMTP-aflevering blijft afhankelijk van de eigen ingestelde mailbox.
