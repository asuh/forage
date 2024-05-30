<article @php post_class() @endphp>
  @if (has_post_thumbnail())
    <figure class="full">@php the_post_thumbnail() @endphp</figure>
  @endif
  @if (get_the_title())
  <header class="post-header">
    <h1 class="entry-title p-name">{!! get_the_title() !!}</h1>
  </header>
  @endif
  @if (get_the_title())
  <div class="main-content">
  @else
  <div class="main-content p-name">
  @endif
    @php the_content() @endphp
  </div><!-- .main-content -->
  @include('partials/entry-meta')
  <footer>
    {!! wp_link_pages(['echo' => 0, 'before' => '<nav class="page-nav"><p>' . __('Pages:'), 'after' => '</p></nav>']) !!}
  </footer>
</article>
{!! get_the_post_navigation(['prev_text' => 'Older', 'next_text' => 'Newer']) !!}
{!! comments_template('/views/partials/comments.blade.php') !!}
