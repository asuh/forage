<?php

namespace FM\Comments;

class Comments extends \Walker_Comment
{
    /**
     * Start the list of child comments.
     *
     * @param string    $output  Used to append additional content.
     * @param int       $depth   Optional. Depth of the current comment. Default 0.
     * @param array     $args    Optional. Uses 'style' argument for type of HTML list. Default empty array.
     */
    public function start_lvl(&$output, $depth = 0, $args = [])
    {
        $output .= '<ol class="children comments-list">';
    }

    /**
     * End the list of child comments.
     *
     * @param string    $output  Used to append additional content.
     * @param int       $depth   Optional. Depth of the current comment. Default 0.
     * @param array     $args    Optional. Uses 'style' argument for type of HTML list. Default empty array.
     */
    public function end_lvl(&$output, $depth = 0, $args = [])
    {
        $output .= '</ol><!-- .children -->';
    }

    /**
     * Outputs a comment in the HTML5 format.
     *
     * @param WP_Comment $comment Comment to display.
     * @param int        $depth   Depth of the current comment.
     * @param array      $args    An array of arguments.
     * @param int        $id      Optional. ID of the comment.
     */
    public function start_el(&$output, $comment, $depth = 0, $args = [], $id = 0)
    {
        $depth++;
        $GLOBALS['comment_depth'] = $depth;
        $GLOBALS['comment'] = $comment;

        // Set up variables for WordPress functions
        $addBelow = 'comment';
        $commentClass = comment_class(empty($args['has_children']) ? '' : 'parent', null, null, false);
        $commentId = get_comment_ID();
        $avatar = get_avatar($comment, 65, '', 'Author\'s gravatar');
        $authorUrl = get_comment_author_url();
        $author = get_comment_author();
        $commentDate = get_comment_date('F jS Y');
        $isoDate = get_comment_date('Y-m-d');
        $commentTime = get_comment_time('H:iP');
        $editLink = get_edit_comment_link();
        $commentText = get_comment_text();
        
        ob_start();
        ?>
        <li <?php echo $commentClass; ?> id="comment-<?php echo $commentId; ?>">
            <article class="comment-body">
                <footer class="comment-meta post-meta" role="complementary">
                    <div class="comment-author vcard">
                        <?php echo $avatar; ?>
                        <b class="fn"><a class="comment-author-link" href="<?php echo esc_url($authorUrl); ?>">
                            <?php echo esc_html($author); ?>
                        </a></b>
                    </div>

                    <a href="#comment-<?php echo $commentId; ?>">#</a>
                    
                    <time class="comment-meta-item" datetime="<?php echo $isoDate; ?>T<?php echo $commentTime; ?>">
                        <?php echo $commentDate; ?>
                    </time>

                    <?php if ($editLink): ?>
                        <a href="<?php echo esc_url($editLink); ?>" class="comment-meta-item">Edit this comment</a>
                    <?php endif; ?>

                    <?php if ($comment->comment_approved == '0') : ?>
                        <p class="comment-meta-item">Your comment is awaiting moderation.</p>
                    <?php endif; ?>
                </footer>

                <div class="comment-content post-content">
                    <?php echo $commentText; ?>
                    <?php
                        comment_reply_link([
                            'add_below' => $addBelow,
                            'depth' => $depth,
                            'max_depth' => $args['max_depth'] ?? 5,
                        ]);
                    ?>
                </div>
            </article>
        </li><?php
        $output .= ob_get_clean();
    }
}
