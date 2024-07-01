<?
if (post_password_required()) {
  return;
}
?>

<? if (get_comments_number() != '0') { ?>
<h2 class="comments-title">
    Responses
</h2>

<section id="comments" class="comments">
    <? if (comments_open()) { ?>
    <div class="button">
        <a href="#respond">Write a new comment below</a>
    </div>
    <? } ?>

    <ol class="comment-list">
        <?
        wp_list_comments(
          [
            'style' => 'ol',
            'short_ping' => true,
            'avatar_size' => 64,
            'walker' => new FM\Comments\Comments()
          ]
        );
      ?>
    </ol><!-- .comment-list -->

    <? if (get_comment_pages_count() > 1 && get_option('page_comments')) { ?>
    <nav aria-label="Comment">
        <ul class="pager">
            <? if (get_previous_comments_link()) { ?>
            <li class="previous"><? previous_comments_link(__('&larr; Older comments')) ?></li>
            <? } ?>
            <? if (get_next_comments_link()) { ?>
            <li class="next"><? next_comments_link(__('Newer comments &rarr;')) ?></li>
            <? } ?>
        </ul>
    </nav>
    <? } ?>

    <? comment_form() ?>
</section>
<? } ?>
