@if ( in_array( get_post_type(), array( 'post', 'attachment' ) ) )
  @if ( is_singular() )
    <span class="posted-on posted-on-single">
  @else
    <span class="posted-on">
  @endif
  <a class="u-url" href="{{ get_permalink() }}">@section('link')<svg xmlns="http://www.w3.org/2000/svg" class="hyperlink" width="12" height="12" viewBox="0 0 32 32"><title>hyperlink</title><path fill="#ccc" d="M13.757 19.868a1.62 1.62 0 0 1-1.149-.476c-2.973-2.973-2.973-7.81 0-10.783l6-6C20.048 1.169 21.963.376 24 .376s3.951.793 5.392 2.233c2.973 2.973 2.973 7.81 0 10.783l-2.743 2.743a1.624 1.624 0 1 1-2.298-2.298l2.743-2.743a4.38 4.38 0 0 0 0-6.187c-.826-.826-1.925-1.281-3.094-1.281s-2.267.455-3.094 1.281l-6 6a4.38 4.38 0 0 0 0 6.187 1.624 1.624 0 0 1-1.149 2.774z"/><path fill="#ccc" d="M8 31.625a7.575 7.575 0 0 1-5.392-2.233c-2.973-2.973-2.973-7.81 0-10.783l2.743-2.743a1.624 1.624 0 1 1 2.298 2.298l-2.743 2.743a4.38 4.38 0 0 0 0 6.187c.826.826 1.925 1.281 3.094 1.281s2.267-.455 3.094-1.281l6-6a4.38 4.38 0 0 0 0-6.187 1.624 1.624 0 1 1 2.298-2.298c2.973 2.973 2.973 7.81 0 10.783l-6 6A7.575 7.575 0 0 1 8 31.625z"/></svg>@endsection
    {{-- "mins/hrs ago if < 24hrs --}}
    @if ( current_time( 'timestamp' ) - get_the_time( 'U' ) > 0 && current_time( 'timestamp' ) - get_the_time( 'U' ) < 24*60*60 )
      @yield('link')<time class="entry-date published updated dt-published dt-updated" datetime="{{ get_post_time( DATE_W3C ) }}">{!! human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ).' '.__( 'ago' ); !!}</time>
    @else
      @yield('link')<time class="entry-date published updated dt-published dt-updated" datetime="{{ get_post_time( DATE_W3C ) }}"><span class="date-month-day">{{ get_the_date('F jS') }}</span> <span class="date-year">{{ get_the_date('Y') }}</span></time>
    @endif
  </a>
</span>
@endif

@if ( ! is_singular() && ! post_password_required() && ( comments_open() || get_comments_number() ) )
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
  @if ( taxonomy_exists( 'series' ) && get_the_term_list( get_the_ID(), 'series', '', _x( ', ', 'Used between list items, there is a space after the comma.') ) )
    <span class="series-links">
      <span class="screen-reader-text">
        {{ _x( 'Series', 'Used before series names.') }}
      </span>
      {!! get_the_term_list( get_the_ID(), 'series', '', _x( ', ', 'Used between list items, there is a space after the comma.') ) !!}
    </span>
  @endif

  @if ( is_singular() && get_the_tag_list( '', _x( ', ', 'Used between list items, there is a space after the comma.') ) )
    <div class="tags-links">
      {{ _x( 'Tags:', 'Used before tag names.') }}
      {!! get_the_tag_list( '', _x( ', ', 'Used between list items, there is a space after the comma.') ) !!}
    </div>
  @endif
@endif

@if ( class_exists( 'WP_Geo_Data' ) )
    {!! Loc_View::get_location() !!}
@endif
