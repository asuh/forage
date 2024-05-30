<div class="p-author author vcard h-card">
  @if ( class_exists( 'IndieWeb_Plugin' ) )
    {!! get_avatar( get_option( 'iw_default_author', get_the_author_meta( 'ID' ) ), apply_filters( 'fm_author_avatar_size', 49 ) ) !!}
  @else
    {!! get_avatar( get_the_author_meta( 'ID' ), apply_filters( 'fm_author_avatar_size', 49 ) ) !!}
  @endif
  <span class="screen-reader-text">{{ _x( 'Author', 'Used before post author name.') }} </span>
  @if ( class_exists( 'IndieWeb_Plugin' ) && is_multi_author())
    <a class="url fn n u-url" href="{{ esc_url( get_author_posts_url( get_option( 'iw_default_author' ) ) ) }}">{{ get_the_author() }}</a>
  @elseif (is_multi_author())
    <a class="url fn n u-url" href="{{ esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) }}">{{ get_the_author() }}</a>
  @else
    <a class="url fn n u-url" href="{{ home_url('/') }}"><span class="p-name">{{ get_the_author_meta('first_name') }} <span class="visuallyhidden">{{ get_the_author_meta('last_name') }}</span></span></a>
  @endif
</div>
