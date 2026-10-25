<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require dirname(__DIR__) . '/3dlens-private/repository.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = post_string('action');
    if ($action === 'connect') od_begin_connect();
    if ($action === 'save') {
        try {
            write_lock();
            od_save_settings(post_string('client_id'), post_string('client_secret'));
            db()->commit();
        } catch (Throwable $error) {
            if (db()->inTransaction()) db()->rollBack();
            throw $error;
        }
    } elseif ($action === 'retry_deletes') {
        model_retry_deletions();
    } else {
        throw new HttpError(422, 'Ongeldige opslagactie.');
    }
    header('Location: onedrive.php?saved=1', true, 303);
    exit;
}
require_method('GET');
$settings = od_settings();
$counts = model_storage_counts();
$ready = od_ready();
$active = od_active();
$csrf = csrf_token();
session_write_close();
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="csrf-token" content="<?= h($csrf) ?>">
<title>OneDrive · 3DLens</title>
<style>
:root{color-scheme:dark;font:15px/1.6 system-ui,sans-serif;color:#e8e6df;background:#0d0e0c}*{box-sizing:border-box}body{margin:0;padding:28px 20px 60px}main{max-width:880px;margin:auto}.brand{font-size:25px;font-weight:800;color:#d4ff3a}a{color:#d4ff3a}h1{font-size:32px;line-height:1.2;margin-bottom:12px}h2{font-size:20px;margin-top:0}p,small{color:#b4b6ac}section{padding:24px;border:1px solid #3d3f38;background:#1a1c19;border-radius:12px;margin:22px 0}.status{padding:12px 16px;background:#d4ff3a10;border-left:3px solid #d4ff3a}.stats{display:flex;gap:24px;flex-wrap:wrap}.stat strong{display:block;font-size:28px;color:#d4ff3a}.stat span{font-size:13px;color:#b4b6ac}label{display:block;margin:16px 0 6px}input{width:100%;font:inherit;color:inherit;background:#0d0e0c;border:1px solid #55594e;border-radius:5px;padding:11px}input:focus,button:focus-visible{outline:2px solid #d4ff3a;outline-offset:2px}input[readonly]{color:#b4b6ac}button{font:inherit;font-weight:700;border:1px solid #d4ff3a;background:#d4ff3a;color:#171a10;padding:11px 17px;border-radius:5px;cursor:pointer;margin:16px 8px 0 0}button:disabled{opacity:.45;cursor:wait}.secondary{background:transparent;color:#e8e6df;border-color:#6b705e}.danger{border-color:#ff9978;color:#ffb89f}code{word-break:break-all;background:#0d0e0c;padding:3px 5px}li{margin:9px 0}.small{font-size:13px}.hidden{display:none}[role=status]{white-space:pre-line}.notice{color:#d4ff3a}
</style>
</head>
<body><main>
<a href="index.php">← Terug naar mijn archief</a>
<div class="brand">◉ 3DLens</div>
<h1>Je modellen in OneDrive</h1>
<p>Bewaar je 3D-bestanden in je eigen OneDrive. Je collecties, tags en omschrijvingen blijven in de database; je bestaande 3DLens-login blijft vereist.</p>
<p class="status"><?= $active ? 'OneDrive is gekoppeld. Nieuwe modellen worden daar opgeslagen.' : 'OneDrive is nog niet gekoppeld. Bestaande en nieuwe modellen staan voorlopig op de webserver.' ?></p>
<?php if (isset($_GET['saved'])): ?><p class="notice" role="status">De instellingen zijn opgeslagen.</p><?php endif; ?>
<section>
<h2>Waar staan je modellen?</h2>
<div class="stats">
<div class="stat"><strong><?= $counts['local'] ?></strong><span>alleen op de webserver</span></div>
<div class="stat"><strong><?= $counts['onedrive'] ?></strong><span>in OneDrive</span></div>
<div class="stat"><strong><?= $counts['local_copies'] ?></strong><span>gecontroleerde lokale kopieën</span></div>
</div>
</section>
<section>
<h2>1. Microsoft-app instellen</h2>
<p>Maak eenmalig een appregistratie aan voor jouw website. Microsoft vraagt dit voordat 3DLens toegang krijgt tot je appmap. Een werk- of schoolaccount kan hiervoor toestemming van je beheerder nodig hebben.</p>
<details <?= !$ready ? 'open' : '' ?>><summary>Stappen en verbindingsinstellingen</summary>
<ol>
<li>Open <a href="https://entra.microsoft.com/#view/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank" rel="noopener noreferrer">Microsoft Entra — Appregistraties</a> en registreer een app met de naam <strong>3DLens</strong>.</li>
<li>Kies het accounttype dat zowel accounts uit organisatiemappen als persoonlijke Microsoft-accounts ondersteunt.</li>
<li>Voeg een omleidingsadres van type <strong>Web</strong> toe: <code><?= h(od_redirect_uri()) ?></code>. Kies hiervoor geen SPA- of mobiele app.</li>
<li>Gebruik de gedelegeerde Microsoft Graph-machtiging <code>Files.ReadWrite.AppFolder</code>. Voor blijvende toegang vraagt de koppeling ook <code>offline_access</code>. Er is geen toegang tot je hele OneDrive nodig.</li>
<li>Maak onder <strong>Certificates &amp; secrets</strong> een client secret aan. Kopieer de <strong>Value</strong>, niet de Secret ID. Bewaar ook de vervaldatum: voor die datum moet je deze secret vernieuwen.</li>
<li>Vul de Application (client) ID en de secret hieronder rechtstreeks in. Deel de secret niet via chat.</li>
</ol>
<form method="post" autocomplete="off">
<input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="save">
<label for="client_id">Application (client) ID</label>
<input id="client_id" name="client_id" value="<?= h($settings['client_id'] ?? '') ?>" maxlength="36" required spellcheck="false">
<label for="client_secret">Client secret — Value</label>
<input type="password" id="client_secret" name="client_secret" autocomplete="new-password" <?= $ready ? '' : 'required' ?> maxlength="4096">
<?php if ($ready): ?><small>Er is al een secret opgeslagen. Laat dit veld leeg om die te behouden. De opgeslagen waarde wordt nooit teruggestuurd naar de browser.</small><?php endif; ?>
<button type="submit">Instellingen opslaan</button>
</form>
</details>
</section>
<section>
<h2>2. Je OneDrive koppelen</h2>
<p>Log bij Microsoft in en geef 3DLens toegang tot zijn eigen map onder <strong>Apps/3DLens</strong>. Na het koppelen gaan nieuwe uploads naar OneDrive. Bestaande modellen worden pas overgezet als je stap 3 start.</p>
<form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="connect"><button type="submit" <?= $ready ? '' : 'disabled' ?>><?= $active ? 'Dezelfde OneDrive opnieuw koppelen' : 'OneDrive koppelen' ?></button></form>
<p class="small">Als er al modellen zijn overgezet, moet je hetzelfde Microsoft-account en dezelfde appregistratie blijven gebruiken. 3DLens maakt geen openbare deellinks.</p>
</section>
<section>
<h2>3. Bestaande modellen overzetten</h2>
<p>De app zet één model tegelijk over en controleert de kopie door het hele bestand terug te lezen. Lokale bestanden blijven daarbij behouden. Je kunt na een onderbreking opnieuw starten.</p>
<button id="copy" type="button" <?= $active && $counts['local'] > 0 ? '' : 'disabled' ?>>Bestaande modellen naar OneDrive kopiëren</button>
<p class="small">Nieuwe uploads worden na het koppelen direct in OneDrive opgeslagen. Tijdens uploaden en downloaden kan de server tijdelijke ruimte gebruiken. Een bestand mag in deze versie maximaal 250 MB zijn; je lagere hosting- of app-limiet blijft gelden.</p>
<p role="status" id="migration-status"></p>
<hr>
<p>Pas nadat je je modellen hebt gecontroleerd kun je de lokale kopieën opruimen. Iedere OneDrive-kopie wordt vóór het opruimen opnieuw volledig gecontroleerd.</p>
<button id="cleanup" class="secondary danger" type="button" <?= $active && $counts['local_copies'] > 0 ? '' : 'disabled' ?>>Gecontroleerde lokale kopieën opruimen</button>
<p class="small">De database en de privéverwijzingen naar OneDrive blijven op Hostinger staan. Bewaar daarvan een backup en maak daarnaast een onafhankelijke backup van je OneDrive-modellen.</p>
</section>
<?php if ($counts['pending_delete'] > 0): ?>
<section><h2>OneDrive-prullenbak bijwerken</h2>
<p><?= $counts['pending_delete'] ?> verwijderde modellen wachten nog op verplaatsing naar de OneDrive-prullenbak. Dit kan gebeuren wanneer OneDrive tijdelijk niet bereikbaar is.</p>
<form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="retry_deletes"><button class="secondary" type="submit">Opnieuw naar prullenbak verplaatsen</button></form></section>
<?php endif; ?>
</main>
<script type="module">
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const status = document.getElementById('migration-status');
async function migrate(mode) {
  if (mode === 'cleanup' && !confirm('Alleen de volledig gecontroleerde kopieën op de webserver opruimen? Je modellen blijven in OneDrive staan.')) return;
  document.querySelectorAll('button').forEach(button => { button.disabled = true; });
  status.textContent = mode === 'copy' ? 'Modellen overzetten en controleren…' : 'OneDrive-kopieën controleren en lokale ruimte vrijmaken…';
  let completed = 0;
  try {
    for (;;) {
      const body = new URLSearchParams({ mode });
      if (mode === 'cleanup') body.set('confirm', 'remove-verified-local-copies');
      const response = await fetch('onedrive-migrate.php', { method: 'POST', credentials: 'same-origin', cache: 'no-store', headers: { 'X-CSRF-Token': csrf }, body });
      if (response.status === 401) { location.assign('login.php'); return; }
      let result;
      try { result = await response.json(); }
      catch { throw new Error('De hosting heeft de bewerking onderbroken. Vernieuw de pagina en hervat; niet-gecontroleerde lokale bestanden blijven behouden.'); }
      if (!response.ok) throw new Error(result.error || 'De bewerking is gestopt. Probeer opnieuw.');
      if (result.done) { location.assign('onedrive.php'); return; }
      completed++;
      status.textContent = completed + ' model(len) gecontroleerd. Laatste: ' + result.name;
    }
  } catch (error) {
    status.textContent = error.message + '\nVernieuw deze pagina om te hervatten.';
  }
}
document.getElementById('copy').addEventListener('click', () => migrate('copy'));
document.getElementById('cleanup').addEventListener('click', () => migrate('cleanup'));
</script>
</body></html>
