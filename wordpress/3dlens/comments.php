<?php defined('ABSPATH') || exit; if(post_password_required()) return; ?>
<section aria-label="Reacties"><?php if(have_comments()): ?><h2>Reacties</h2><ol class="comment-list"><?php wp_list_comments(['style'=>'ol','short_ping'=>true]); ?></ol><?php the_comments_pagination(); endif; comment_form(); ?></section>
