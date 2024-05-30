<article @php post_class() @endphp>
  <header class="post-header">
    <h2 class="entry-title"><a class="entry-title-link" href="{{ get_permalink() }}">{!! get_the_title() !!}</a></h2>
  </header>
  <div class="main-content">
    <div class="p-summary entry-summary">
    <p>@php the_excerpt() @endphp</p>
    </div>
  </div><!-- .main-content -->
  @if (get_post_type() === 'post')
  <div class="entry-meta">
    @include('partials/entry-meta')
  </div>
  @endif
</article>
