<?php

namespace FM\Comments;

class Comments extends \Walker_Comment {
	// start_lvl – Wrapper for child comments list, starts the list before the elements are added.
	public function start_lvl( &$output, $depth = 0, $args = [] ) {
		$GLOBALS['comment_depth'] = $depth + 2; ?>

		<ol class="children comments-list">

	<?php }

	// end_lvl – closing wrapper for child comments list
	public function end_lvl( &$output, $depth = 0, $args = [] ) {
		$GLOBALS['comment_depth'] = $depth + 2; ?>

		</ol><!-- .children -->

	<?php }

	/**
	 * Outputs a comment in the HTML5 format.
	 *
	 * @see wp_list_comments()
	 *
	 * @param WP_Comment $comment Comment to display.
	 * @param int        $depth   Depth of the current comment.
	 * @param array      $args    An array of arguments.
	 */
	public function start_el( &$output, $comment, $depth = 0, $args = [], $current_object_id = 0) {
		$depth++;
		$GLOBALS['comment_depth'] = $depth;
		$GLOBALS['comment'] = $comment;

		if ( 'article' == $args['style'] ) {
			$tag = 'article';
			$add_below = 'comment';
		} else {
			$tag = 'article';
			$add_below = 'comment';
		} ?>

		<li <?php comment_class(empty( $args['has_children'] ) ? '' :'parent') ?> id="comment-<?php comment_ID() ?>" itemprop="comment" itemscope itemtype="http://schema.org/Comment">
            <article class="comment-body">
                <footer class="comment-meta post-meta" role="complementary">
                    <h2 class="comment-author">
                        <figure class="gravatar"><?php echo get_avatar( $comment, 65, '', 'Author’s gravatar' ); ?></figure>
                        <a class="comment-author-link" href="<?php comment_author_url(); ?>" itemprop="author"><?php comment_author(); ?></a>
                    </h2>
                    <a href="#comment-<?php comment_ID() ?>">#</a> <time class="comment-meta-item" datetime="<?php comment_date('Y-m-d') ?>T<?php comment_time('H:iP') ?>" itemprop="datePublished"><?php comment_date('F jS Y') ?></time>
                    <?php edit_comment_link('<p class="comment-meta-item">Edit this comment</p>','',''); ?>
                    <?php if ($comment->comment_approved == '0') : ?>
                    <p class="comment-meta-item">Your comment is awaiting moderation.</p>
                    <?php endif; ?>
                </footer>
                <div class="comment-content post-content" itemprop="text">
                    <?php comment_text() ?>
                    <?php comment_reply_link(array_merge( $args, array('add_below' => $add_below, 'depth' => $depth, 'max_depth' => $args['max_depth']))) ?>
                </div>
            </article>

	<?php }

	// end_el – closing HTML for comment template
	public function end_el( &$output, $comment, $depth = 0, $args = array() ) { ?>

        </li>

	<?php }

}