@if ( in_array( get_post_type(), array( 'post', 'attachment' ) ) )
  @if ( is_singular() )
    <span class="posted-on posted-on-single">
  @else
    <span class="posted-on">
  @endif
  <a class="u-url" href="{{ get_permalink() }}">@section('link')<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" class="hyperlink" viewBox="0 0 32 32"><path fill="currentColor" d="M13.8 19.9a1.6 1.6 0 0 1-1.2-.5c-3-3-3-7.8 0-10.8l6-6a7.6 7.6 0 0 1 10.8 0c3 3 3 7.8 0 10.8L26.6 16a1.6 1.6 0 1 1-2.2-2.3l2.7-2.7a4.4 4.4 0 0 0 0-6.2 4.3 4.3 0 0 0-6.2 0l-6 6a4.4 4.4 0 0 0 0 6.2 1.6 1.6 0 0 1-1.1 2.8z"/><path fill="currentColor" d="M8 31.6a7.6 7.6 0 0 1-5.4-2.2c-3-3-3-7.8 0-10.8L5.4 16a1.6 1.6 0 1 1 2.2 2.3L5 20.9a4.4 4.4 0 0 0 0 6.2 4.3 4.3 0 0 0 6.2 0l6-6a4.4 4.4 0 0 0 0-6.2 1.6 1.6 0 1 1 2.3-2.3c3 3 3 7.8 0 10.8l-6 6A7.6 7.6 0 0 1 8 31.6z"/></svg>@endsection
    {{-- "mins/hrs ago if < 24hrs --}}
    @if ( current_time( 'timestamp' ) - get_the_time( 'U' ) > 0 && current_time( 'timestamp' ) - get_the_time( 'U' ) < 24*60*60 )
      @yield('link')<time class="entry-date published updated dt-published dt-updated" datetime="{{ get_post_time( DATE_W3C ) }}">{!! human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ).' '.__( 'ago' ); !!}</time>
    @else
      @yield('link')<time class="entry-date published updated dt-published dt-updated" datetime="{{ get_post_time( DATE_W3C ) }}"><span class="date-month-day">{{ get_the_date('F jS') }}</span> <span class="date-year">{{ get_the_date('Y') }}</span></time>
    @endif
  </a>
</span>
@endif

@if ( ! is_singular() )
<span class="comments-link">
  {!! comments_number('', '1 reply', '% replies') !!}
</span>
@endif

@if ( function_exists( 'get_syndication_links' ) )
  @php
    $args = array(
      'text'             => false,
      'icons'            => true,
      'show_text_before' => false
    )
  @endphp
  {!! get_syndication_links( get_the_ID(), $args ) !!}
@endif

@if ( 'post' === get_post_type() )
  @if ( taxonomy_exists( 'series' ) && get_the_term_list( get_the_ID(), 'series', '', ', ') )
    <span class="series-links">
      <span class="screen-reader-text">
        {{ _x( 'Series', '') }}
      </span>
      {!! get_the_term_list( get_the_ID(), 'series', '', ', ') !!}
    </span>
  @endif

  @if ( is_singular() && get_the_tag_list( '', ', ') )
    <div class="tags-links">
      {{ _x( 'Tags:', '') }}
      {!! get_the_tag_list( '', ', ') !!}
    </div>
  @endif
@endif

@if ( class_exists( 'WP_Geo_Data' ) )
    {!! Loc_View::get_location() !!}
@endif
