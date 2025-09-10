<article @php(post_class('h-entry'))>
    {{-- @if (has_post_thumbnail())
        <figure class="post-thumbnail full">@php(the_post_thumbnail())</figure>
    @endif --}}

    @if (get_the_title())
        <header class="post-header">
            <h1 class="entry-title p-name">{!! get_the_title() !!}</h1>
        </header>
    @endif

    @if (get_the_title())
    <div class="main-content e-content">
    @else
    <div class="main-content e-content p-name">
    @endif
        @php(the_content())
    </div><!-- .main-content -->

    <footer>
        @include('partials/entry-meta')

        @if (wp_link_pages())
            {!! wp_link_pages([
                'echo' => 0,
                'before' => '<nav class="page-nav"><p>' . __('Pages:'),
                'after' => '</p></nav>',
            ]) !!}
        @endif
    </footer>

    {!! get_the_post_navigation(['prev_text' => 'Older', 'next_text' => 'Newer']) !!}
    {!! comments_template() !!}
</article>

@section('sidebar')
  @include('partials.sidebar')
@endsection
