<div class="p-author vcard h-card">
    {!! get_avatar( get_the_author_meta( 'ID' ), apply_filters( 'fm_author_avatar_size', 49 ) ) !!}
    <span class="screen-reader-text">{{ _x( 'Author', 'Used before post author name.') }} </span>
    <a class="u-uid" href="{{ home_url('/') }}">
      <span class="p-name">
          <span class="p-given-name">{{ get_the_author_meta('first_name') }}</span>
          <span class="p-family-name">{{ get_the_author_meta('last_name') }}</span>
      </span>
    </a>
  </div>
