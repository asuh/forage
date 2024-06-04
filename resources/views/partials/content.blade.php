<article @php post_class() @endphp>
    @if (has_post_thumbnail())
        <figure class="full">
            <a class="p-name u-url" href="{{ get_permalink() }}">@php the_post_thumbnail() @endphp</a>
        </figure>
    @endif
    <header class="post-header">
        @if (get_the_title())
            <h2 class="entry-title"><a class="p-name u-url" href="{{ get_permalink() }}">{!! get_the_title() !!}</a></h2>
        @endif
    </header>
    <div class="main-content e-content">
        @php the_content() @endphp
    </div><!-- .main-content -->
    <div class="entry-meta">
        @include('partials/entry-meta')
    </div><!-- .entry-meta -->
</article>
