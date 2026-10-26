<?php
// Isolated rendering check. This is NOT a WordPress substitute or production route.
define('ABSPATH', __DIR__);
const DLENS_PREVIEW_THEME = __DIR__ . '/../wordpress/3dlens';
$GLOBALS['dlens_test_mods'] = [];
function get_template_directory() { return DLENS_PREVIEW_THEME; }
function get_stylesheet_directory() { return DLENS_PREVIEW_THEME; }
function get_stylesheet_uri() { return '/style.css'; }
function wp_enqueue_style($handle,$url,$dependencies,$version) { $GLOBALS['dlens_test_asset_version']=$version; }
function is_singular() { return false; }
function add_action(...$args) {}
function get_theme_mod($key,$default='') { return $GLOBALS['dlens_test_mods'][$key] ?? $default; }
function esc_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_attr($s) { return esc_html($s); }
function esc_url($s) { return preg_match('~^(https?://|/|#)~i', $s) ? esc_attr($s) : ''; }
function home_url($path='/') { return $path; }
function language_attributes() { echo 'lang="nl-NL"'; }
function bloginfo($key) { if($key==='charset') echo 'UTF-8'; }
function is_front_page() { return true; }
function wp_head() { echo '<title>3DLens — Je 3D-archief, op jouw plek</title><link rel="stylesheet" href="style.css">'; }
function wp_footer() {}
function body_class() { echo 'class="home"'; }
function wp_body_open() {}
function has_custom_logo() { return false; }
function wp_nav_menu($args) { call_user_func($args['fallback_cb']); }
function get_option($key) { return 'posts'; }
function get_header() { require DLENS_PREVIEW_THEME.'/header.php'; }
function get_footer() { require DLENS_PREVIEW_THEME.'/footer.php'; }
require DLENS_PREVIEW_THEME.'/functions.php';
dlens_assets();
if(strlen($GLOBALS['dlens_test_asset_version'] ?? '') !== 16 || $GLOBALS['dlens_test_asset_version'] === '1.0.0') throw new RuntimeException('Missing content version for stylesheet cache');

// Check every Customizer control is registered with sanitization and no key is lost.
$manager = new class {
    public array $settings = [];
    public array $controls = [];
    public function add_section(...$args) {}
    public function add_setting($key,$value) { $this->settings[$key]=$value; }
    public function add_control($key,$value) { $this->controls[$key]=$value; }
};
dlens_customize($manager);
foreach (dlens_fields() as $key=>[$default,$label,$type]) {
    $entry=$manager->settings['dlens_'.$key] ?? [];
    if(!isset($entry['sanitize_callback']) || !isset($manager->controls['dlens_'.$key])) throw new RuntimeException('Missing sanitization/control: '.$key);
    if(dlens_value($key)!==$default) throw new RuntimeException('Incorrect default: '.$key);
}
$GLOBALS['dlens_test_mods']['dlens_hero_intro']='<script>alert("example")</script>';
ob_start(); dlens_text('hero_intro'); $escaped=ob_get_clean();
if(str_contains($escaped,'<script>') || !str_contains($escaped,'&lt;script&gt;')) throw new RuntimeException('Unescaped text');
$GLOBALS['dlens_test_mods']=[];
ob_start(); require DLENS_PREVIEW_THEME.'/front-page.php'; $html=ob_get_clean();
foreach(['id="mogelijkheden"','id="vragen"','id="installeren"','<em>Jouw plek.</em>','https://3dlens.cloud/downloads/3dlens-1.0.0.zip','https://3dlens.cloud/handleiding.html'] as $marker) {
    if(!str_contains($html,$marker)) throw new RuntimeException('Missing landing content: '.$marker);
}
if(substr_count($html,'<details>')!==5) throw new RuntimeException('Missing FAQ');
if(PHP_SAPI==='cli' && isset($argv[1])) {
    if(!is_dir($argv[1])) mkdir($argv[1],0777,true);
    file_put_contents($argv[1].'/index.html',$html);
    copy(DLENS_PREVIEW_THEME.'/style.css',$argv[1].'/style.css');
    echo count(dlens_fields())." Customizer defaults and sanitizers checked; escaped rendering and front page passed.\n";
} else { echo $html; }
