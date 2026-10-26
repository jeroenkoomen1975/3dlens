<?php
declare(strict_types=1);
if (!defined('THREEDLENS_BOOTSTRAPPED')) { http_response_code(404); exit; }

function app_release(): array
{
    return ['version' => '1.5.0', 'date' => '2026-10-26', 'date_label' => '26 oktober 2026'];
}

function app_changelog(): array
{
    return [
        [
            ...app_release(),
            'title' => 'Modelcontrole en projecten met meerdere onderdelen',
            'changes' => [
                'Een importrapport toont welke identieke bestanden zijn overgeslagen en verwijst naar het bestaande model, ook bij een andere bestandsnaam.',
                'Afmetingen en een basiscontrole op open randen, niet-manifold randen, vlakrichting en ongeldige, dubbele of vlakke driehoeken staan onder Info.',
                'STL-afmetingen worden als millimeters geïnterpreteerd, met Z als printhoogte. Bij vereenvoudigd gelezen 3MF-bestanden wordt gewaarschuwd voor onbetrouwbare schaal en plaatsing.',
                'Maak projecten met modellen uit meerdere collecties, aantallen per onderdeel en montage- en onderdeelnotities. Hetzelfde model kan in meerdere projecten staan.',
                'Backups bevatten projecten en modelcontroles. Bij terugzetten blijven projectonderdelen gekoppeld, ook wanneer een identiek model al bestaat.',
            ],
            'notes' => [
                'Dubbelherkenning vergelijkt identieke bestandsinhoud; opnieuw geëxporteerde of geschaalde modellen worden niet geometrisch vergeleken.',
                'Modelcontrole repareert niets en controleert geen zelfdoorsnijdingen of wanddikte. Boven 200.000 driehoeken wordt de randcontrole expliciet overgeslagen. Geslicete resinbestanden bevatten geen controleerbare mesh.',
            ],
        ],
        [
            'version' => '1.4.0', 'date' => '2026-10-26', 'date_label' => '26 oktober 2026',
            'title' => 'Meerdere modellen selecteren en organiseren',
            'changes' => [
                'Selecteer meerdere modellen met vinkjes of Ctrl+klik (Mac: Cmd+klik). Met Alles in beeld selecteer je alle zichtbare modellen.',
                'Verplaats geselecteerde modellen samen via de actiebalk of sleep de hele selectie naar een collectie in de zijbalk.',
                'Verwijder meerdere modellen met één bevestiging waarin het aantal bestanden staat. Verwijderen is definitief.',
                'Sleep STL- en 3MF-modellen vanuit het archief naar een collectie. Sleep bestanden of mappen vanaf je computer direct naar de gewenste collectie om ze daar te importeren.',
                'Bij een fout stopt een groepsactie. Je ziet hoeveel modellen verwerkt zijn; de resterende selectie kun je opnieuw proberen. Bij wisselen van collectie of zoekopdracht wordt de selectie gewist.',
                'About toont het actuele versienummer, de releasedatum en een link naar deze wijzigingsgeschiedenis.',
            ],
            'notes' => [
                'De online uitgave gebruikt een eigen login, een MySQL-database en privéopslag op de server. OneDrive-opslag is optioneel.',
                'Wachtwoordherstel per e-mail is beschikbaar na het instellen en testen van een hersteladres en een verzendmailbox.',
                'De tussenliggende hosting- en OneDrive-updates hadden nog geen afzonderlijk versienummer in About. Deze geschiedenis vat ze daarom samen bij de huidige online uitgave.',
            ],
        ],
        [
            'version' => '1.3.0', 'title' => 'Oorspronkelijke basis van het archief',
            'changes' => [
                'Archief voor STL-, 3MF- en ondersteunde resin/SLA-bestanden met previews, collecties, tags en omschrijvingen.',
                'Importeren van bestanden en mappen, zoeken en backup/restore van het archief.',
                'De oorspronkelijke lokale HTML-uitgave gebruikte browseropslag. De huidige online uitgave slaat het archief op de server op.',
            ],
            'notes' => ['Dit was het versienummer dat eerder in About stond. Er zijn geen afzonderlijke versies of releasedatums achteraf toegevoegd.'],
        ],
    ];
}
