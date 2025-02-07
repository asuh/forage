<?php

namespace FM\Comments;

class Comments extends \Walker_Comment
{

    /**
     * Outputs a comment in the HTML5 format.
     *
	 * @param string     $output  Used to append additional content. Passed by reference.
     * @param WP_Comment $comment Comment to display.
     * @param int        $depth   Optional. Depth of the current comment.
     * @param array      $args    Optional. An array of arguments.
     * @param int        $id      Optional. ID of the current comment.
     */
    public function start_el(&$output, $comment, $depth = 0, $args = [], $id = 0)
    {
        $depth++;
        $GLOBALS['comment_depth'] = $depth;
        $GLOBALS['comment']       = $comment;

        // Set up variables for WordPress functions
        $addBelow = 'comment';
        $commentClass = comment_class($args['has_children'] ? 'parent u-comment h-cite' : 'u-comment h-cite', null, null, false);
        $commentId = get_comment_ID();
        $avatar = get_avatar($comment, 65, '', 'Author\'s gravatar');
        $authorUrl = get_comment_author_url();
        $author = get_comment_author();
        $commentDate = get_comment_date('F jS, Y');
        $isoDate = get_comment_date('Y-m-d');
        $commentTime = get_comment_time('H:iP');
        $editLink = get_edit_comment_link();
        
        ob_start();
        ?>
        <li id="comment-<?php echo $commentId; ?>" <?php comment_class($args['has_children'] ? 'parent u-comment h-cite' : 'u-comment h-cite', $comment); ?>>
            <article class="comment-body">
                <footer class="comment-meta meta">
                    <div class="comment-author vcard">
                        <b class="u-author h-card fn">
                        <?php if (!empty($authorUrl)): ?>
                            <a class="u-url comment-author-link" href="<?php echo esc_url($authorUrl); ?>">
                                <?php echo esc_html($author); ?>
                            </a>
                        <?php else: ?>
                            <?php echo esc_html($author); ?>
                        <?php endif; ?>
                        </b>
                        ·
                        <a class="comment-permalink" href="#comment-<?php echo $commentId; ?>">
                            <time class="comment-meta-item dt-published" datetime="<?php echo $isoDate; ?>T<?php echo $commentTime; ?>" title="<?php echo $commentDate . " at " . $commentTime; ?>">
                                <?php echo $this->timeAgo($isoDate . 'T' . $commentTime); ?>
                            </time>
                        </a>
                    </div>

                    <?php if ($editLink): ?>
                        <a href="<?php echo esc_url($editLink); ?>" class="comment-meta-item">Edit this comment</a>
                    <?php endif; ?>

                    <?php if ($comment->comment_approved == '0') : ?>
                        <p class="comment-meta-item">Your comment is awaiting moderation.</p>
                    <?php endif; ?>
                </footer>

                <div class="comment-content p-content p-name">
                    <?php  
                        comment_text();                    
                    ?>
                    <?php
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
                    ?>
                </div>
                <div class="avatar">
                    <?php echo $avatar; ?>
                </div>
            </article><!-- .comment-body -->
        <?php
        $output .= ob_get_clean();
    }

    public function timeAgo($timestamp) {
        $currentTime = time();
        $timestampDate = strtotime($timestamp);
        $timeDiff = $currentTime - $timestampDate;
        
        // Define time intervals in seconds
        $intervals = [
            'mo'  => 2592000,    // 30 days
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
}
