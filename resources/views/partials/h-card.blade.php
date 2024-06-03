<div class="p-author vcard h-card">
  {!! get_avatar( get_the_author_meta( 'ID' ), apply_filters( 'fm_author_avatar_size', 49 ) ) !!}
  <span class="screen-reader-text">{{ _x( 'Author', 'Used before post author name.') }} </span>
  <a class="url fn n u-url" href="{{ home_url('/') }}">
    <span class="p-name">{{ get_the_author_meta('first_name') }} {{ get_the_author_meta('last_name') }}</span>
  </a>
</div>
