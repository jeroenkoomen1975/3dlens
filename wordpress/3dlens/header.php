<?php defined('ABSPATH') || exit; ?><!doctype html>
<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1">
<?php if (is_front_page()): ?><meta name="description" content="<?php echo esc_attr(dlens_value('hero_intro')); ?>"><?php endif; ?>
<?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip-link" href="#inhoud">Ga naar de inhoud</a>
<header class="site-header"><nav aria-label="Hoofdnavigatie"><div><?php if (has_custom_logo()) { the_custom_logo(); } else { ?><a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="3DLens home"><span aria-hidden="true">◉</span> 3DLens</a><?php } ?></div><div class="navlinks"><?php wp_nav_menu(['theme_location'=>'primary','container'=>false,'depth'=>1,'fallback_cb'=>'dlens_fallback_menu']); ?></div></nav></header>
