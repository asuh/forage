<?php

namespace FM\Comments;

class Comments extends \Walker_Comment
{
    // Wrapper for child comments list, starts the list before the elements are added.
    public function start_lvl(&$output, $depth = 0, $args = [])
    { ?>

        <ol class="children comments-list">

        <?php
    }

    // Closing wrapper for child comments list.
    public function end_lvl(&$output, $depth = 0, $args = [])
    { ?>

        </ol><!-- .children -->

        <?php
    }

    /**
     * Outputs a comment in the HTML5 format.
     *
     * @see wp_list_comments()
     *
     * @param WP_Comment $comment Comment to display.
     * @param int        $depth   Depth of the current comment.
     * @param array      $args    An array of arguments.
     */
    public function start_el(&$output, $comment, $depth = 0, $args = [], $id = 0)
    {
        $depth++;
        $GLOBALS['comment_depth'] = $depth;
        $GLOBALS['comment'] = $comment;

        if ('article' === $args['style']) {
            $tag = 'article';
            $addBelow = 'comment';
        } else {
            $tag = 'article';
            $addBelow = 'comment';
        }

        ob_start();
        ?>
        <li <?php comment_class(empty($args['has_children']) ? '' : 'parent'); ?> id="comment-<?php comment_ID(); ?>" itemprop="comment" itemscope itemtype="http://schema.org/Comment">
            <article class="comment-body">
                <footer class="comment-meta post-meta" role="complementary">
                    <h2 class="comment-author">
                        <figure class="gravatar"><?php echo get_avatar($comment, 65, '', 'Author\'s gravatar'); ?></figure>
                        <a class="comment-author-link" href="<?php comment_author_url(); ?>" itemprop="author"><?php comment_author(); ?></a>
                    </h2>
                    <a href="#comment-<?php comment_ID(); ?>">#</a> <time class="comment-meta-item" datetime="<?php comment_date('Y-m-d'); ?>T<?php comment_time('H:iP'); ?>" itemprop="datePublished"><?php comment_date('F jS Y'); ?></time>
                    <?php edit_comment_link('<p class="comment-meta-item">Edit this comment</p>', '', ''); ?>
                    <?php if ($comment->comment_approved == '0') : ?>
                    <p class="comment-meta-item">Your comment is awaiting moderation.</p>
                    <?php endif; ?>
                </footer>
                <div class="comment-content post-content" itemprop="text">
                    <?php comment_text(); ?>
                    <?php
                        comment_reply_link(
                            array_merge(
                                $args,
                                [
                                    'add_below' => $addBelow,
                                    'depth' => $depth,
                                    'max_depth' => $args['max_depth'],
                                ]
                            )
                        );
                    ?>
                </div>
            </article>
        </li>
        <?php
        $output .= ob_get_clean();
    }
}
