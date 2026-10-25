# OneDrive voor 3DLens

Doel: persoonlijk Microsoft-account, website https://koomen.3dlens.cloud. De updatecode is op Hostinger geplaatst. Microsoft is nog niet gekoppeld en er zijn geen modellen via deze koppeling overgezet. Een backup van de vorige applicatie staat buiten public_html in 3dlens-backup-voor-onedrive. De nieuwe instellingenpagina verwijst zonder login naar het inlogscherm; de verdere controle wacht op opnieuw inloggen door de eigenaar.

## Update plaatsen

Maak eerst een veilige kopie van de huidige applicatiecode. Pak `3dlens-onedrive-voorbereid.zip` buiten `public_html` uit in een nieuwe tijdelijke map. Het pakket bevat uitsluitend applicatiecode en voorbeeldconfiguratie: geen echte configuratie, tokens, database of modellen.

Plaats eerst de nieuwe private bestanden `onedrive.php` en `model-storage.php` in `3dlens-private/`. Werk daarna de overige meegeleverde private code bij en plaats de publieke code in `public_html/koomen/`. Bewaar `config.local.php`, `storage/` en eventuele `connections/` ongewijzigd. Gebruik PHP 8.2+ met cURL, PDO MySQL en HTTPS. Er is geen databasewijziging nodig.

## Microsoft eenmalig instellen

1. Open Microsoft Entra, Appregistraties. Microsoft vereist een directory waarin je apps mag registreren. Een persoonlijk OneDrive-account gebruiken en een app mogen registreren zijn verschillende zaken; bij een directoryfout moet eerst de beschikbare Microsoft-directory worden vastgesteld.
2. Registreer `3DLens` met ondersteuning voor persoonlijke Microsoft-accounts (de optie met organisatieaccounts én persoonlijke accounts werkt met het gebruikte common-eindpunt).
3. Voeg een Web-omleidingsadres toe, exact: `https://koomen.3dlens.cloud/onedrive-callback.php`.
4. Gebruik de gedelegeerde Graph-machtiging `Files.ReadWrite.AppFolder`. De koppeling vraagt tevens `offline_access` voor vernieuwbare toegang. Andere bestandsmachtigingen zijn niet nodig.
5. Maak zelf een client secret aan onder Certificates & secrets. Bewaar de Value en vervaldatum in je wachtwoordmanager. Secret ID is niet de juiste waarde. Deel de secret nooit in chat.
6. Log in bij 3DLens en open OneDrive. Vul de Application (client) ID en secret rechtstreeks in. Sla op en kies OneDrive koppelen. Kies je persoonlijke account en controleer de machtiging op het Microsoft-scherm voordat je toestemming geeft.

De app houdt eigen login en Microsoft-toestemming gescheiden. OAuth gebruikt een eenmalige sessiestaat en PKCE. De sessiecookie is SameSite=Lax voor de terugkeer van Microsoft; alle schrijfacties behouden CSRF-controle. Microsoft-appinstellingen en tokens worden uitsluitend buiten de documentroot opgeslagen met beperkte bestandsrechten.

## Bestaande bestanden overzetten

Nieuwe uploads gaan na geslaagde koppeling naar de eigen appmap in OneDrive (onder Apps). Bestaande modellen blijven lokaal totdat je in de OneDrive-pagina Overzetten kiest. Dit werkt één bestand per verzoek en controleert elke cloudkopie door opnieuw te downloaden en de volledige SHA-256 te vergelijken. Lokale kopieën blijven dan behouden. Bij onderbreking kun je opnieuw starten; reeds overgezette modellen worden overgeslagen.

Controleer eerst één klein model: upload, preview, download, metadata en backup. Pas na controle kun je de afzonderlijke opruimactie kiezen. Die downloadt en verifieert ieder cloudmodel nogmaals voordat de lokale kopie wordt verwijderd. Database-ID's, tags, collecties en miniaturen blijven gelijk.

De webserver gebruikt nog tijdelijke schijfruimte tijdens upload en download. OneDrive-opslag telt mee voor je Microsoft-quotum. De app-limiet blijft 200 MiB per model; de gebruikte Microsoft-uploadmethode ondersteunt maximaal 250 MB.

Verwijder of verplaats de appmap en modelbestanden niet handmatig in OneDrive. De applicatie gebruikt OneDrive-bestands-ID's en private verwijzingsbestanden. Na het verwijderen van een model via 3DLens gaat de cloudkopie naar de OneDrive-prullenbak. Mislukt dat, dan blijft een private herhaalopdracht bewaard en toont de instellingenpagina de mogelijkheid opnieuw te proberen.

## Backup en herstel

Bewaar database, private storage (inclusief `.onedrive.json`-verwijzingen), verbindingen en modelinhoud samen. De modellen zijn na opruimen alleen nog in OneDrive; een hostingbackup alleen bevat ze dan niet. Maak tevens een .forge-backup vanuit 3DLens en bewaar die onafhankelijk van OneDrive. Behandel de verbindingenbackup als een wachtwoord: deze bevat vernieuwbare toegang.

Verloopt de client secret, vernieuw die rechtstreeks bij Microsoft en vul de nieuwe Value in 3DLens in. Een leeg secretveld bij opslaan behoudt de bestaande secret. Verander niet van appregistratie of OneDrive-account zolang modellen ermee verbonden zijn. Bij cloudstoring werkt een nog aanwezige, gecontroleerde lokale kopie als terugval; modellen zonder lokale kopie zijn tijdens de storing niet beschikbaar.

## Controles

Geslaagd: PHP-syntaxis op alle bestanden, offline OAuth- en opslagfouttests, tien Node-tests, JavaScript-syntaxis en controle van pakketinhoud zonder credentials. Nog nodig: echte Microsoft-toestemming, cloud-upload/download en migratie op de hosting. De serverintegratietest vereist een aparte wegwerpdatabase en is niet op het productiearchief uitgevoerd.

Microsoft-documentatie: https://learn.microsoft.com/en-us/graph/onedrive-sharepoint-appfolder en https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-auth-code-flow.

## Microsoft weigert de tokenuitwisseling

De foutmelding toont uitsluitend een gecontroleerde AADSTS-code en eigen uitleg; nooit de externe fouttekst, tokens of secret. Start na het wijzigen van appinstellingen opnieuw via OneDrive koppelen. Vernieuw de callback niet: de Microsoft-code is eenmalig. AADSTS7000215 duidt op een onjuiste secret of een client-ID/secret van verschillende apps; 7000222 op een verlopen secret. De offline tests controleren ook dat geheime externe foutvelden niet worden getoond.
