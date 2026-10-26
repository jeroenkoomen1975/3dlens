<?php
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
