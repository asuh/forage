<div class="main-content e-content">
    @if (has_post_thumbnail())
        <figure class="full">@php(the_post_thumbnail())</figure>
    @endif
    @php(the_content())

    {!! wp_link_pages([
        'echo' => 0,
        'before' => '<nav class="page-nav"><p>' . __('Pages:'),
        'after' => '</p></nav>',
    ]) !!}
</div><!-- .main-content -->
