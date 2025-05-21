@if (isset($post) && $post->password_required)
    @return
@endif

<section id="comments" class="comments">
    <h2 class="comments-title">
        Responses
    </h2>

    @if (isset($comments_open) && $comments_open)
    <div class="respond-link">
        <a href="#respond">Write a new comment below</a>
    </div>
    @endif

    @if (have_comments())
        <ol class="comment-list">
            {!! wp_list_comments([
                'style' => 'ol',
                'short_ping' => true,
                'avatar_size' => 64,
                'walker' => new FM\Comments\Comments(),
            ]) !!}
        </ol><!-- .comment-list -->
    @endif

    @if (get_comment_pages_count() > 1 && get_option('page_comments'))
        <nav>
            <ul class="pager">
                @if (isset($previous_page_url))
                    <li class="previous">
                        <a href="{{ $previous_page_url }}">&larr; Older comments</a>
                    </li>
                @endif
                @if (isset($next_page_url))
                    <li class="next">
                        <a href="{{ $next_page_url }}">Newer comments &rarr;</a>
                    </li>
                @endif
            </ul>
        </nav>
    @endif

    @if (! $comments_open)
    <p class="warning">
        {!! __('Comments are closed.', 'forage') !!}
    </p>
    @endif

    @php(comment_form())
</section>
