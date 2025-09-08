<?php

namespace FM\Comments;

if ( class_exists( '\Webmention\Comment_Walker' ) ) {
    class Comments extends \Webmention\Comment_Walker {
        protected function html5_comment( $comment, $depth, $args ) {
            // Only call this local version for comments that are webmention based.
            if ( 'webmention' === get_comment_meta( $comment->comment_ID, 'protocol', true ) ) {
                parent::html5_comment( $comment, $depth, $args );
                return; 
            }

            $tag = ( 'div' === $args['style'] ) ? 'div' : 'li';

            $cite = apply_filters( 'webmention_cite', '<small>&nbsp;@&nbsp;<cite><a href="%1s">%2s</a></cite></small>' );
            $url  = get_url_from_webmention( $comment );
            $host = wp_parse_url( $url, PHP_URL_HOST );
            $host = preg_replace( '/^www\./', '', $host );
            $type = get_webmention_comment_type_attr( $comment->comment_type, 'class' );
            if ( 'comment' === $comment->comment_type ) {
                $type = 'p-comment';
            }

            $commenter          = wp_get_current_commenter();
            $show_pending_links = ! empty( $commenter['comment_author'] );

            if ( $commenter['comment_author_email'] ) {
                $moderation_note = __( 'Your comment is awaiting moderation.', 'default' );
            } else {
                $moderation_note = __( 'Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.', 'default' );
            }  
            
            ?>
            <<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( $this->has_children ? 'parent p-comment' : 'p-comment', $comment ); ?>>
                <article id="div-comment-<?php comment_ID(); ?>" class="comment-body h-cite <?php echo $type; ?>">
                    <footer class="comment-meta">
                        <div class="comment-author vcard h-card u-author">
                            <?php
                            if ( 0 !== $args['avatar_size'] ) {
                                echo get_avatar( $comment, $args['avatar_size'] );
                            }
                            ?>
                            <?php
                            $comment_author = get_comment_author_link( $comment );

                            if ( '0' === $comment->comment_approved && ! $show_pending_links ) {
                                $comment_author = get_comment_author( $comment );
                            }

                            printf(
                                /* translators: %s: Comment author link. */
                                __( '%s', 'default' ),
                                sprintf( '<b class="fn">%s</b>', $comment_author )
                            );
                            if ( ! empty( $cite ) && 'webmention' === get_comment_meta( $comment->comment_ID, 'protocol', true ) ) {
                                printf( $cite, $url, $host );
                            }

                            ?>
                        </div><!-- .comment-author -->

                        <div class="comment-metadata">
                            <?php
                            // Allow arbitrary additions to comment metadata.
                            do_action( 'webmention_comment_metadata', $comment );
                            printf(
                                '<a class="u-url comment-permalink" href="%s"><time class="dt-published" datetime="%s">%s</time></a>',
                                esc_url( get_comment_link( $comment, $args ) ),
                                get_comment_time( DATE_W3C ),
                                timeAgo(get_comment_time( DATE_W3C )),
                            );
                            ?>
                        </div><!-- .comment-metadata -->

                        <?php if ( '0' === $comment->comment_approved ) : ?>
                        <em class="comment-awaiting-moderation"><?php echo $moderation_note; ?></em>
                        <?php endif; ?>
                    </footer><!-- .comment-meta -->

                    <div class="comment-content e-content p-name">
                        <?php comment_text(); ?>
                    </div><!-- .comment-content -->

                    <div class="comment-interact">
                        <?php
                        edit_comment_link( __( 'Edit', 'default' ), ' <span class="edit-link">', '</span>' );

                        if ( '1' == $comment->comment_approved || $show_pending_links ) {
                            comment_reply_link(
                                array_merge(
                                    $args,
                                    array(
                                        'add_below' => 'div-comment',
                                        'depth'     => $depth,
                                        'max_depth' => $args['max_depth'],
                                        'before'    => '<div class="reply">',
                                        'after'     => '</div>',
                                    )
                                )
                            );
                        }
                        ?>
                    </div><!-- .comment-interact -->
                </article><!-- .comment-body -->
            <?php
        }
    }
} else {
    class Comments extends \Walker_Comment {
        protected function html5_comment( $comment, $depth, $args ) {
            parent::html5_comment( $comment, $depth, $args );
        }
    }
}

/**
 * Converts date and time to be relative to current time
 *
 * @param string $timestamp
 * @return string
 *
 */
function timeAgo($timestamp) {
    $currentTime = time();
    $timestampDate = strtotime($timestamp);
    $timeDiff = $currentTime - $timestampDate;

    // Define time intervals in seconds
    $intervals = [
        'mo'  => 2592000,
        'd'   => 86400,
        'h'   => 3600,
        'm'   => 60
    ];

    // If 12 months or more, return formatted date
    if ($timeDiff >= (31536000)) { // 365 days
        return date('m/d/Y', $timestampDate);
    }

    if ($timeDiff < 60) {
        return 'now';
    }

    foreach ($intervals as $interval => $seconds) {
        $diff = floor($timeDiff / $seconds);

        if ($diff >= 1) {
            // If it's at least a month, calculate total months
            if ($interval === 'mo') {
                $totalMonths = floor($timeDiff / $intervals['mo']);
                return $totalMonths . 'mo';
            }
            return $diff . $interval;
        }
    }
}
