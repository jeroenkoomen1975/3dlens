"""Build a standalone classic WordPress promotion theme (no private app data)."""
from pathlib import Path
import re
import json
import zipfile
import argparse

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / 'wordpress' / '3dlens'
THEME.mkdir(parents=True, exist_ok=True)

def put(name, source):
    (THEME / name).write_text(source.strip() + '\n', encoding='utf-8')

landing = (ROOT / 'distribution/site/index.html').read_text(encoding='utf-8')
css = re.search(r'<style>(.*?)</style>', landing, re.S).group(1)
for old, new in [('ol{','.install ol{'), ('ol li{','.install ol li{'), ('ol li::before{','.install ol li::before{'), ('li strong{','.install li strong{'), ('li span{','.install li span{')]:
    css = css.replace(old, new)
put('style.css', '''/*
Theme Name: 3DLens
Theme URI: https://3dlens.cloud/
Author: 3DLens
Description: Nederlandstalig promotiethema voor het 3DLens privéarchief. Met aanpasbare introductie, functies, downloadlinks en veelgestelde vragen.
Version: 1.0.1
Requires at least: 6.6
Requires PHP: 8.2
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: dlens
Tags: custom-logo, custom-menu, featured-images, blog
*/
''' + css + '''
.skip-link{position:absolute;left:20px;top:-100px;z-index:10000;background:var(--lime);color:#111;padding:12px}.skip-link:focus{top:20px}.site-header nav{height:auto;min-height:96px;gap:20px;padding-top:20px;padding-bottom:20px}.site-header .navlinks{flex-wrap:wrap;justify-content:flex-end}.navlinks ul{display:flex;flex-wrap:wrap;list-style:none;gap:20px;margin:0;padding:0}.custom-logo{width:auto;max-height:48px}.brand{display:flex;align-items:center;gap:10px}.hero .preview{transform:rotate(1.5deg)}.preview-label{font-size:11px;color:var(--muted);text-align:center;margin:14px 0 0}.storage-section{border-top:1px solid var(--line);display:grid;grid-template-columns:1fr 1fr;gap:60px}.storage-section p{color:var(--muted)}.storage-map{display:grid;gap:12px;align-content:center}.storage-node{border:1px solid var(--line);border-radius:9px;padding:22px;background:var(--card);display:flex;gap:18px;align-items:center}.storage-node b{font-size:20px;color:var(--lime);font-family:ui-monospace,monospace}.storage-node strong{display:block}.storage-node small{color:var(--muted)}.storage-note{margin:0;text-align:center;font-size:12px}.faq{border-top:1px solid var(--line)}details{border-bottom:1px solid var(--line);padding:22px 0}summary{cursor:pointer;font-weight:650;font-size:18px}details p{color:var(--muted);max-width:820px;margin-bottom:0}details[open] summary{color:var(--lime)}.downloadbox{margin-bottom:0}.entry-content{overflow-wrap:anywhere}.entry-content img{max-width:100%;height:auto}.entry-content a,.post-list a{color:var(--lime)}.entry-content h1,.post-title{font-size:clamp(32px,4vw,55px);letter-spacing:-1.5px}.entry-content h2{margin-top:45px}.content-wrap{max-width:860px;padding-top:60px;padding-bottom:90px;min-height:65vh}.post-list article{padding:25px 0;border-bottom:1px solid var(--line)}.post-list h2{font-size:28px;line-height:1.2}.post-meta{font-size:13px;color:var(--muted)}.pagination{margin-top:30px}.screen-reader-text{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}.alignwide{max-width:1100px}.wp-caption{max-width:100%}.wp-caption-text,.gallery-caption{color:var(--muted);font-size:13px}.alignleft{float:left;margin:0 25px 20px 0}.alignright{float:right;margin:0 0 20px 25px}.aligncenter{display:block;margin:auto}.sticky{border-left:2px solid var(--lime);padding-left:20px!important}.bypostauthor{border-left:2px solid var(--line);padding-left:20px}.comment-list{padding-left:22px}.comment-list li{margin-bottom:20px}.comment-form input:not([type=checkbox]),.comment-form textarea{display:block;width:100%;background:var(--card);color:var(--text);border:1px solid #526046;padding:12px;border-radius:5px}.comment-form input[type=submit]{width:auto;background:var(--lime);color:#151b0d;cursor:pointer}.comment-form label{display:block;margin:12px 0 6px}.comment-form-cookies-consent label{display:inline}.page-links{clear:both;padding-top:20px}section[id]{scroll-margin-top:25px}@media(max-width:800px){.storage-section{grid-template-columns:1fr;gap:25px}.hero .preview{transform:none}.site-header nav{min-height:85px}.navlinks{font-size:12px}.navlinks ul{gap:12px}}@media(max-width:480px){nav,.wrap,footer{padding-left:20px;padding-right:20px}.site-header nav{align-items:flex-start}.navlinks{max-width:200px}.hero h1{font-size:49px}.hero{gap:30px}.preview{padding:12px}.preview-grid{grid-template-columns:68px 1fr;gap:8px}.model-art{height:190px}.model-caption{flex-wrap:wrap;gap:6px}.buttons .button{width:100%}.storage-node{padding:18px}.downloadbox h2{font-size:26px}}''')

defaults = {
 'hero_title': ('Je modellen.\nJe overzicht.\nJouw plek.', 'Hoofdtitel (iedere regel apart)', 'textarea'),
 'hero_intro': ('Geef je 3D-printbestanden een eigen thuis. Bekijk, organiseer en vind je modellen terug in een privéarchief op je eigen hosting.', 'Introductie', 'textarea'),
 'version': ('1.0.0', 'Versie van de app', 'text'),
 'download_url': ('https://3dlens.cloud/downloads/3dlens-1.0.0.zip', 'Downloadadres van de app (ZIP)', 'url'),
 'guide_url': ('https://3dlens.cloud/handleiding.html', 'Installatiehandleiding (URL)', 'url'),
 'feature_heading': ('Minder zoeken. Meer maken.', 'Titel boven de functies', 'text'),
 'feature_1_title': ('Van map naar collectie', 'Functie 1: titel', 'text'),
 'feature_1_text': ('Organiseer je modellen met collecties, tags, omschrijvingen en miniaturen. Zo vind je het juiste bestand terug zonder eindeloos door mappen te bladeren.', 'Functie 1: uitleg', 'textarea'),
 'feature_2_title': ('Je model in beeld', 'Functie 2: titel', 'text'),
 'feature_2_text': ('Bekijk ondersteunde modellen direct in je browser. Werk met STL, 3MF en ondersteunde resin/SLA-varianten en download je originele bestanden wanneer je ze nodig hebt.', 'Functie 2: uitleg', 'textarea'),
 'feature_3_title': ('Een privéarchief voor jou', 'Functie 3: titel', 'text'),
 'feature_3_text': ('Je modellen en bestanden zijn alleen na inloggen toegankelijk. Iedere installatie heeft één eigenaar, een eigen database en een eigen account.', 'Functie 3: uitleg', 'textarea'),
 'storage_heading': ('Jouw bestanden.\nJouw keuze.', 'Titel over opslag', 'textarea'),
 'storage_text': ('Bewaar je modellen privé op je webserver of verbind je persoonlijke OneDrive. Bij de optionele OneDrive-koppeling staan de modelbestanden in jouw appmap; je collecties en beschrijvingen blijven in je eigen database.', 'Uitleg over opslag', 'textarea'),
 'faq_1_q': ('Maak ik hier een account aan?', 'Vraag 1', 'text'),
 'faq_1_a': ('Je downloadt 3DLens en installeert het op je eigen hosting. Daar maak je jouw eigenaarsaccount aan. Er is geen openbare registratie op 3dlens.cloud en andere bezoekers krijgen geen toegang tot jouw archief.', 'Antwoord 1', 'textarea'),
 'faq_2_q': ('Wat heb ik nodig om te beginnen?', 'Vraag 2', 'text'),
 'faq_2_a': ('Een domein met HTTPS, PHP 8.2 of hoger en een MySQL- of MariaDB-database. Voor herstelmails heb je een bestaande mailbox met SMTP nodig. De installatiehandleiding beschrijft alle stappen.', 'Antwoord 2', 'textarea'),
 'faq_3_q': ('Moet ik OneDrive gebruiken?', 'Vraag 3', 'text'),
 'faq_3_a': ('Nee. Je kunt de bestanden op je eigen webserver bewaren. OneDrive is optioneel en vraagt een eigen Microsoft-appregistratie en toestemming voor je persoonlijke Microsoft-account. De handleiding legt de koppeling uit.', 'Antwoord 3', 'textarea'),
 'faq_4_q': ('Kan ik mijn wachtwoord herstellen?', 'Vraag 4', 'text'),
 'faq_4_a': ('Ja, nadat je jouw hersteladres en verzendmailbox hebt ingesteld en getest. Via Wachtwoord vergeten ontvang je dan een eenmalige herstelkoppeling per e-mail. Zonder ingestelde mailbox kan de app geen herstelmail versturen.', 'Antwoord 4', 'textarea'),
 'faq_5_q': ('Kan ik mijn archief meenemen?', 'Vraag 5', 'text'),
 'faq_5_a': ('De app heeft backup- en restorefuncties voor je archief. Maak daarnaast hostingbackups van je database, privébestanden en eventuele OneDrive-verbindingsgegevens. Controleer altijd of je een backup kunt terugzetten.', 'Antwoord 5', 'textarea'),
}
php_defaults = '<?php\ndefined(\'ABSPATH\') || exit;\nfunction dlens_fields(): array {\n return ' + '[' + '\n'
def phpstr(s): return "'" + s.replace('\\','\\\\').replace("'", "\\'") + "'"
for key,(value,label,kind) in defaults.items():
    php_defaults += f'  {phpstr(key)} => [{phpstr(value)}, {phpstr(label)}, {phpstr(kind)}],\n'
php_defaults += ' ];\n}\n'
put('fields.php', php_defaults)
put('functions.php', '''<?php
defined('ABSPATH') || exit;
require_once get_template_directory() . '/fields.php';
function dlens_setup(): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    add_theme_support('custom-logo', ['height'=>60,'width'=>240,'flex-width'=>true,'flex-height'=>true]);
    register_nav_menus(['primary'=>'Hoofdnavigatie']);
}
add_action('after_setup_theme', 'dlens_setup');
function dlens_assets(): void {
    // The URL must change when CSS changes, even when replacing an older theme
    // with the same release number: browsers and hosting caches may retain it.
    $path = get_stylesheet_directory() . '/style.css';
    $digest = is_readable($path) ? hash_file('sha256', $path) : false;
    $version = $digest ? substr($digest, 0, 16) : wp_get_theme()->get('Version');
    wp_enqueue_style('dlens', get_stylesheet_uri(), [], $version);
    if (is_singular() && comments_open() && get_option('thread_comments')) wp_enqueue_script('comment-reply');
}
add_action('wp_enqueue_scripts', 'dlens_assets');
function dlens_value(string $key): string {
    $fields = dlens_fields();
    return (string) get_theme_mod('dlens_' . $key, $fields[$key][0] ?? '');
}
function dlens_text(string $key): void { echo esc_html(dlens_value($key)); }
function dlens_lines(string $key): void { echo nl2br(esc_html(dlens_value($key))); }
function dlens_customize($manager): void {
    $manager->add_section('dlens_promotion', ['title'=>'3DLens promotie','priority'=>30,'description'=>'Pas de homepage en de download- en handleidinglinks aan. Wijzigingen zie je direct in het voorbeeld.']);
    foreach (dlens_fields() as $key => [$default,$label,$type]) {
        $sanitize = $type === 'url' ? 'esc_url_raw' : ($type === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field');
        $manager->add_setting('dlens_' . $key, ['default'=>$default,'sanitize_callback'=>$sanitize,'transport'=>'refresh','capability'=>'edit_theme_options']);
        $manager->add_control('dlens_' . $key, ['label'=>$label,'section'=>'dlens_promotion','type'=>$type]);
    }
}
add_action('customize_register', 'dlens_customize');
function dlens_fallback_menu(): void { ?>
<a href="<?php echo esc_url(home_url('/#mogelijkheden')); ?>">Mogelijkheden</a>
<a href="<?php echo esc_url(home_url('/#installeren')); ?>">Installeren</a>
<a href="<?php echo esc_url(home_url('/#vragen')); ?>">Vragen</a>
<a href="<?php echo esc_url(dlens_value('guide_url')); ?>">Handleiding ↗</a>
<?php }
''')
put('header.php', '''<?php defined('ABSPATH') || exit; ?><!doctype html>
<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1">
<?php if (is_front_page()): ?><meta name="description" content="<?php echo esc_attr(dlens_value('hero_intro')); ?>"><?php endif; ?>
<?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip-link" href="#inhoud">Ga naar de inhoud</a>
<header class="site-header"><nav aria-label="Hoofdnavigatie"><div><?php if (has_custom_logo()) { the_custom_logo(); } else { ?><a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="3DLens home"><span aria-hidden="true">◉</span> 3DLens</a><?php } ?></div><div class="navlinks"><?php wp_nav_menu(['theme_location'=>'primary','container'=>false,'depth'=>1,'fallback_cb'=>'dlens_fallback_menu']); ?></div></nav></header>
''')
put('footer.php', '''<?php defined('ABSPATH') || exit; ?>
<footer><span>◉ 3DLens · Jouw 3D-archief, op jouw plek.</span><span><a href="<?php echo esc_url(dlens_value('guide_url')); ?>">Installatiehandleiding</a> · <a href="<?php echo esc_url(dlens_value('download_url')); ?>">Download de app</a></span></footer>
<?php wp_footer(); ?></body></html>
''')
preview = re.search(r'<div class="preview".*?</div></section>', landing, re.S).group(0).removesuffix('</section>')
preview = preview.replace('<div class="preview"', '<div><div class="preview"', 1) + '<p class="preview-label">Illustratie van een georganiseerd 3D-archief.</p></div>'
front = '''<?php defined('ABSPATH') || exit; get_header(); ?>
<main id="inhoud" class="wrap">
<section class="hero"><div><div class="eyebrow"><span class="dot"></span> Voor jouw volgende print</div><h1><?php $lines = explode("\n", dlens_value('hero_title')); foreach($lines as $n=>$line) { if($n) echo '<br>'; if($n === count($lines)-1) echo '<em>'; echo esc_html($line); if($n === count($lines)-1) echo '</em>'; } ?></h1><p class="lead"><?php dlens_text('hero_intro'); ?></p><div class="buttons"><a class="button" href="<?php echo esc_url(dlens_value('download_url')); ?>">Download 3DLens <span aria-hidden="true">↓</span></a><a class="button secondary" href="#mogelijkheden">Ontdek de mogelijkheden</a></div><p class="fine">Versie <?php dlens_text('version'); ?> · PHP + MySQL · Op je eigen hosting</p></div>
''' + preview + '''</section>
<div class="band"><span><b>3D-preview</b> in je browser</span><span><b>Collecties & tags</b> voor overzicht</span><span><b>Privé</b> achter jouw login</span><span><b>OneDrive</b> optioneel</span></div>
<section class="section" id="mogelijkheden"><div class="eyebrow">Van bestand naar overzicht</div><h2><?php dlens_text('feature_heading'); ?></h2><div class="cards">
<?php for ($i=1;$i<=3;$i++): ?><article class="card"><span class="number">0<?php echo (int) $i; ?> / <?php echo esc_html(['','ORGANISEREN','BEKIJKEN','BEHEREN'][$i]); ?></span><h3><?php dlens_text('feature_'.$i.'_title'); ?></h3><p><?php dlens_text('feature_'.$i.'_text'); ?></p></article><?php endfor; ?>
</div></section>
<section class="section storage-section"><div><div class="eyebrow">Ruimte voor je verzameling</div><h2><?php dlens_lines('storage_heading'); ?></h2><p><?php dlens_text('storage_text'); ?></p><p>De toegang tot je modellen blijft via jouw 3DLens-login lopen. Je hoeft geen openbare deellinks te maken.</p></div><div class="storage-map" aria-label="Opbouw van jouw installatie"><div class="storage-node"><b>01</b><div><strong>Jouw 3DLens</strong><small>Inloggen, bekijken en organiseren</small></div></div><div class="storage-node"><b>02</b><div><strong>Jouw database</strong><small>Collecties, tags en beschrijvingen</small></div></div><div class="storage-node"><b>03</b><div><strong>Jouw opslag</strong><small>Privé op de webserver óf in OneDrive</small></div></div><p class="storage-note">Eén eigen installatie. Jouw eigen archief.</p></div></section>
<section class="section install" id="installeren"><div><div class="eyebrow">Van download naar eerste model</div><h2>Een eigen archief,<br>in vier stappen.</h2><p>Je installeert de app op je eigen PHP-webhosting. De Nederlandstalige handleiding helpt je met de database, je account en de optionele koppelingen.</p><a class="button secondary" href="<?php echo esc_url(dlens_value('guide_url')); ?>">Open de installatiehandleiding ↗</a></div><ol><li><div><strong>Download en plaats de bestanden</strong><span>Webbestanden in je website; privébestanden veilig erbuiten.</span></div></li><li><div><strong>Verbind je eigen database</strong><span>Gebruik PHP 8.2+, MySQL of MariaDB en een website met HTTPS.</span></div></li><li><div><strong>Maak je eigenaarsaccount</strong><span>Stel ook je hersteladres en verzendmailbox in en test de herstelmail.</span></div></li><li><div><strong>Voeg je eerste model toe</strong><span>Bekijk de preview, maak een collectie en test je backup.</span></div></li></ol></section>
<p class="notice"><strong>Goed om te weten:</strong> 3DLens is een app die je zelf host. De download geeft je geen account op deze website. Iedere installatie heeft één eigenaar; openbare registratie is niet inbegrepen.</p>
<section class="section faq" id="vragen"><div class="eyebrow">Voor je begint</div><h2>Veelgestelde vragen.</h2><?php for ($i=1;$i<=5;$i++): ?><details><summary><?php dlens_text('faq_'.$i.'_q'); ?></summary><p><?php dlens_text('faq_'.$i.'_a'); ?></p></details><?php endfor; ?></section>
<?php if (get_option('show_on_front') === 'page' && have_posts()): while(have_posts()): the_post(); if(trim((string)get_the_content()) !== ''): ?><section class="section entry-content" aria-label="Meer over 3DLens"><?php the_content(); wp_link_pages(); ?></section><?php endif; endwhile; endif; ?>
<section class="section" id="download"><div class="downloadbox"><div><h2>Klaar voor meer overzicht?</h2><p>Begin met je eigen 3D-archief. De handleiding zit in het pakket.</p><p class="fine">Maak eerst een backup als je een bestaande installatie bijwerkt.</p></div><a class="button" href="<?php echo esc_url(dlens_value('download_url')); ?>">Download versie <?php dlens_text('version'); ?> ↓</a></div></section>
</main><?php get_footer(); ?>
'''
put('front-page.php', front)
put('index.php', '''<?php defined('ABSPATH') || exit; get_header(); ?>
<main id="inhoud" class="wrap content-wrap post-list"><div class="eyebrow">Van 3DLens</div><h1 class="post-title"><?php if (is_home()) echo 'Nieuws & updates'; elseif (is_search()) echo 'Zoekresultaten'; else the_archive_title(); ?></h1>
<?php if(have_posts()): while(have_posts()): the_post(); ?><article <?php post_class(); ?>><p class="post-meta"><?php echo esc_html(get_the_date()); ?></p><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></article><?php endwhile; ?><div class="pagination"><?php the_posts_pagination(); ?></div><?php else: ?><p>Er zijn nog geen berichten om te tonen.</p><?php endif; ?></main><?php get_footer(); ?>
''')
put('singular.php', '''<?php defined('ABSPATH') || exit; get_header(); ?>
<main id="inhoud" class="wrap content-wrap"><?php while(have_posts()): the_post(); ?><article <?php post_class('entry-content'); ?>><h1><?php the_title(); ?></h1><?php if(has_post_thumbnail()) the_post_thumbnail('large'); the_content(); wp_link_pages(); ?></article><?php if(comments_open() || get_comments_number()) comments_template(); endwhile; ?></main><?php get_footer(); ?>
''')
put('404.php', '''<?php defined('ABSPATH') || exit; get_header(); ?><main id="inhoud" class="wrap content-wrap"><div class="eyebrow">404</div><h1 class="post-title">Deze pagina is er niet.</h1><p>Ga terug naar het overzicht van 3DLens.</p><a class="button" href="<?php echo esc_url(home_url('/')); ?>">Naar de homepage</a></main><?php get_footer(); ?>''')
put('comments.php', '''<?php defined('ABSPATH') || exit; if(post_password_required()) return; ?>
<section aria-label="Reacties"><?php if(have_comments()): ?><h2>Reacties</h2><ol class="comment-list"><?php wp_list_comments(['style'=>'ol','short_ping'=>true]); ?></ol><?php the_comments_pagination(); endif; comment_form(); ?></section>
''')
put('theme.json', json.dumps({'version':3,'settings':{'color':{'palette':[{'slug':'background','name':'Donker','color':'#0b0e0b'},{'slug':'lime','name':'3DLens lime','color':'#d4ff3a'},{'slug':'text','name':'Licht','color':'#f0f3e9'}]},'layout':{'contentSize':'804px','wideSize':'1100px'},'typography':{'fontFamilies':[{'fontFamily':'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif','slug':'system','name':'Systeemlettertype'}]}},'styles':{'color':{'background':'#0b0e0b','text':'#f0f3e9'},'typography':{'fontFamily':'var:preset|font-family|system'}}}, ensure_ascii=False, indent=2))
put('README.txt', '''3DLens WordPress-thema 1.0.1
===========================
Dit is een zelfstandig klassiek thema. Geen pagebuilder of betaalde plugin nodig.
WordPress 6.6+ en PHP 8.2+. Het thema promoot de app; het bevat niet de app zelf.

INSTALLATIE
1. Maak een backup van de WordPress-database en webbestanden.
2. WordPress > Weergave > Thema's > Nieuw thema > Thema uploaden.
3. Upload 3dlens-wordpress-thema-1.0.1.zip en installeer het thema.
   Lees bij 3dlens.cloud eerst de opmerking over index.html hieronder.
4. Activeer 3DLens. De homepage toont meteen de promotiepagina.
5. Weergave > Customizer > 3DLens promotie: pas teksten, versie, downloadadres,
   handleiding, functies en veelgestelde vragen aan. Klik Publiceren om op te slaan.
6. Gebruik Site-identiteit voor je eigen logo en Menu's voor de hoofdnavigatie.
   Zonder eigen menu zijn de sectielinks al aanwezig. Een menu toont één niveau.
7. Optioneel: maak een pagina Home en selecteer die bij Instellingen > Lezen.
   De inhoud van die pagina verschijnt onder de vragen op de promotiehomepage.
   Voor nieuws maak je een aparte pagina Nieuws en stel je deze in als berichtpagina.

BELANGRIJK OP 3DLENS.CLOUD
De bestaande openbare index.html heeft voorrang op WordPress index.php.
Zolang die index.html er staat, zie je mogelijk ook in het WordPress-livevoorbeeld
nog de oude downloadpagina. Verplaats uitsluitend die index.html naar een
backupmap BUITEN public_html als je klaar bent om over te schakelen.
Bewaar ook een kopie van .htaccess. Bekijk dan het thema in WordPress en activeer
3DLens. Er is kort een overgang met het huidige WordPress-thema zichtbaar.
Laat index.php, WordPress-bestanden, downloads/ en handleiding.html staan.
Laat public_html/koomen en 3dlens-private volledig ongemoeid.
DirectoryIndex index.html index.php default.php mag blijven: zonder index.html
kiest de webserver index.php. Wis daarna de Hostinger-cache en browsercache.
Als de oude pagina terug moet: zet de bewaarde index.html terug.

CONTROLE NA ACTIVEREN
Controleer homepage op telefoon en computer, alle navigatielinks, download,
handleiding, uitklapvragen, extra pagina's en de bestaande privé-app.
Controleer dat het ZIP-downloadadres de app bevat en niet dit thema.
De standaardlinks wijzen naar de al gepubliceerde app 1.0.0 op 3dlens.cloud.
Bij een nieuwe appversie wijzig je zowel versie als downloadadres in de Customizer.

GEGEVENS EN BEHEER
Het thema maakt geen accounts, databasewijzigingen, cookies of tracking aan.
WordPress en eventuele plugins kunnen eigen gegevensverwerking hebben.
Er zijn geen externe fonts, scripts of illustratiediensten nodig voor het thema.
Het archiefvoorbeeld is een gelabelde illustratie, geen interactieve appdemo.
Eigen teksten worden gesaneerd en bij uitvoer ge-escaped; URLs gebruiken esc_url.
Wachtwoordherstel en SMTP horen bij de aparte 3DLens-app, niet bij dit thema.

LICENTIE
Themacode en eigen illustratie: GNU GPL versie 2 of later.
Zie LICENSE.txt. Geen afbeeldingen of fonts van derden opgenomen.
''')

parser = argparse.ArgumentParser()
parser.add_argument('--output', type=Path)
args = parser.parse_args()
if args.output:
    args.output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(args.output, 'w', zipfile.ZIP_DEFLATED) as archive:
        for path in sorted(THEME.rglob('*')):
            if path.is_file(): archive.write(path, Path('3dlens') / path.relative_to(THEME))
    print(f'Thema: {args.output} ({args.output.stat().st_size} bytes)')
else:
    print(f'Themabestanden: {THEME}')
