<article @php(post_class())>
    @if (has_post_thumbnail())
        <figure class="post-thumbnail full">
            <a class="p-name u-url" href="{{ get_permalink() }}">@php(the_post_thumbnail())</a>
        </figure>
    @endif

    <header class="post-header">
        @if (get_the_title())
            <h2 class="entry-title"><a class="p-name u-url" href="{{ get_permalink() }}">{!! get_the_title() !!}</a></h2>
        @endif
    </header>

    <div class="main-content e-content">
        @php(the_content())
    </div><!-- .main-content -->

    <div class="entry-meta">
        @include('partials/entry-meta')
    </div><!-- .entry-meta -->
</article>
