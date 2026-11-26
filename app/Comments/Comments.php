<?php

namespace Forage\Comments;

require_once __DIR__ . '/helpers.php';

if (class_exists('\Webmention\Comment_Walker')) {
    class Comments extends \Webmention\Comment_Walker
    {
        protected function html5_comment($comment, $depth, $args)
        {
            // Only call this local version for comments that are webmention based.
            if (
                'webmention' ===
                get_comment_meta($comment->comment_ID, 'protocol', true)
            ) {
                parent::html5_comment($comment, $depth, $args);
                return;
            }

            $tag = 'div' === $args['style'] ? 'div' : 'li';

            $type = get_webmention_comment_type_attr(
                $comment->comment_type,
                'class',
            );
            if ('comment' === $comment->comment_type) {
                $type = 'p-comment';
            }

            $commenter = wp_get_current_commenter();
            $show_pending_links = ! empty($commenter['comment_author']);

            if ($commenter['comment_author_email']) {
                $moderation_note = __(
                    'Your comment is awaiting moderation.',
                    'forage',
                );
            } else {
                $moderation_note = __(
                    'Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.',
                    'forage',
                );
            }
            ?>
            <<?php echo tag_escape($tag); ?> id="comment-<?php comment_ID(); ?>" <?php comment_class($this->has_children ? 'parent p-comment' : 'p-comment', $comment); ?>>
                <article id="div-comment-<?php comment_ID(); ?>" class="comment-body h-cite <?php echo esc_attr($type); ?>">
                    <footer class="comment-meta">
                        <div class="comment-author vcard h-card u-author">
                            <?php
                            if (0 !== $args['avatar_size']) {
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() returns WordPress-generated markup.
                                echo get_avatar($comment, $args['avatar_size']);
                            }
                            ?>
                            <?php
                            $comment_author = get_comment_author_link($comment);

                            if (
                                '0' === $comment->comment_approved &&
                                ! $show_pending_links
                            ) {
                                $comment_author = get_comment_author($comment);
                            }

                            echo '<b class="fn">' . wp_kses_post($comment_author) . '</b>';
                            ?>
                        </div><!-- .comment-author -->

                        <div class="comment-metadata">
                            <?php
                            // Allow arbitrary additions to comment metadata.
                            do_action('webmention_comment_metadata', $comment);
                            printf(
                                '<a class="u-url comment-permalink" href="%s"><time class="dt-published" datetime="%s">%s</time></a>',
                                esc_url(get_comment_link($comment, $args)),
                                esc_attr(get_comment_time(DATE_W3C)),
                                esc_html(timeAgo(get_comment_time(DATE_W3C))),
                            );
                            ?>
                        </div><!-- .comment-metadata -->

                        <?php if ('0' === $comment->comment_approved) : ?>
                        <em class="comment-awaiting-moderation"><?php echo esc_html($moderation_note); ?></em>
                        <?php endif; ?>
                    </footer><!-- .comment-meta -->

                    <div class="comment-content e-content p-name">
                        <?php comment_text(); ?>
                    </div><!-- .comment-content -->

                    <div class="comment-interact">
                        <?php
                        edit_comment_link(
                            __('Edit', 'forage'),
                            ' <span class="edit-link">',
                            '</span>',
                        );

                        if (
                            '1' === $comment->comment_approved ||
                            $show_pending_links
                        ) {
                            comment_reply_link(
                                array_merge(
                                    $args,
                                    [
                                        'add_below' => 'div-comment',
                                        'depth' => $depth,
                                        'max_depth' => $args['max_depth'],
                                        'before' => '<div class="reply">',
                                        'after' => '</div>',
                                    ]
                                ),
                            );
                        }
                        ?>
                    </div><!-- .comment-interact -->
                </article><!-- .comment-body -->
            <?php
        }
    }
} else {
    class Comments extends \Walker_Comment
    {
    }
}
