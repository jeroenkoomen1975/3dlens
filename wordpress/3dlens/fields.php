<?php
defined('ABSPATH') || exit;
function dlens_fields(): array {
 return [
  'hero_title' => ['Je modellen.
Je overzicht.
Jouw plek.', 'Hoofdtitel (iedere regel apart)', 'textarea'],
  'hero_intro' => ['Geef je 3D-printbestanden een eigen thuis. Bekijk, organiseer en vind je modellen terug in een privéarchief op je eigen hosting.', 'Introductie', 'textarea'],
  'version' => ['1.0.0', 'Versie van de app', 'text'],
  'download_url' => ['https://3dlens.cloud/downloads/3dlens-1.0.0.zip', 'Downloadadres van de app (ZIP)', 'url'],
  'guide_url' => ['https://3dlens.cloud/handleiding.html', 'Installatiehandleiding (URL)', 'url'],
  'feature_heading' => ['Minder zoeken. Meer maken.', 'Titel boven de functies', 'text'],
  'feature_1_title' => ['Van map naar collectie', 'Functie 1: titel', 'text'],
  'feature_1_text' => ['Organiseer je modellen met collecties, tags, omschrijvingen en miniaturen. Zo vind je het juiste bestand terug zonder eindeloos door mappen te bladeren.', 'Functie 1: uitleg', 'textarea'],
  'feature_2_title' => ['Je model in beeld', 'Functie 2: titel', 'text'],
  'feature_2_text' => ['Bekijk ondersteunde modellen direct in je browser. Werk met STL, 3MF en ondersteunde resin/SLA-varianten en download je originele bestanden wanneer je ze nodig hebt.', 'Functie 2: uitleg', 'textarea'],
  'feature_3_title' => ['Een privéarchief voor jou', 'Functie 3: titel', 'text'],
  'feature_3_text' => ['Je modellen en bestanden zijn alleen na inloggen toegankelijk. Iedere installatie heeft één eigenaar, een eigen database en een eigen account.', 'Functie 3: uitleg', 'textarea'],
  'storage_heading' => ['Jouw bestanden.
Jouw keuze.', 'Titel over opslag', 'textarea'],
  'storage_text' => ['Bewaar je modellen privé op je webserver of verbind je persoonlijke OneDrive. Bij de optionele OneDrive-koppeling staan de modelbestanden in jouw appmap; je collecties en beschrijvingen blijven in je eigen database.', 'Uitleg over opslag', 'textarea'],
  'faq_1_q' => ['Maak ik hier een account aan?', 'Vraag 1', 'text'],
  'faq_1_a' => ['Je downloadt 3DLens en installeert het op je eigen hosting. Daar maak je jouw eigenaarsaccount aan. Er is geen openbare registratie op 3dlens.cloud en andere bezoekers krijgen geen toegang tot jouw archief.', 'Antwoord 1', 'textarea'],
  'faq_2_q' => ['Wat heb ik nodig om te beginnen?', 'Vraag 2', 'text'],
  'faq_2_a' => ['Een domein met HTTPS, PHP 8.2 of hoger en een MySQL- of MariaDB-database. Voor herstelmails heb je een bestaande mailbox met SMTP nodig. De installatiehandleiding beschrijft alle stappen.', 'Antwoord 2', 'textarea'],
  'faq_3_q' => ['Moet ik OneDrive gebruiken?', 'Vraag 3', 'text'],
  'faq_3_a' => ['Nee. Je kunt de bestanden op je eigen webserver bewaren. OneDrive is optioneel en vraagt een eigen Microsoft-appregistratie en toestemming voor je persoonlijke Microsoft-account. De handleiding legt de koppeling uit.', 'Antwoord 3', 'textarea'],
  'faq_4_q' => ['Kan ik mijn wachtwoord herstellen?', 'Vraag 4', 'text'],
  'faq_4_a' => ['Ja, nadat je jouw hersteladres en verzendmailbox hebt ingesteld en getest. Via Wachtwoord vergeten ontvang je dan een eenmalige herstelkoppeling per e-mail. Zonder ingestelde mailbox kan de app geen herstelmail versturen.', 'Antwoord 4', 'textarea'],
  'faq_5_q' => ['Kan ik mijn archief meenemen?', 'Vraag 5', 'text'],
  'faq_5_a' => ['De app heeft backup- en restorefuncties voor je archief. Maak daarnaast hostingbackups van je database, privébestanden en eventuele OneDrive-verbindingsgegevens. Controleer altijd of je een backup kunt terugzetten.', 'Antwoord 5', 'textarea'],
 ];
}
