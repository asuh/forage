<div class="h-card vcard">
    <a class="u-url u-uid" rel="author" href="{{ home_url('/') }}">
        {!! get_avatar(
                get_the_author_meta('ID'),
                96,
                '',
                '',
                array('class' => 'u-photo')
            ) !!}
        <span class="p-name fn">
            <span class="p-given-name">{{ get_the_author_meta('first_name') }}</span>
            <span class="screen-reader-text p-family-name">{{ get_the_author_meta('last_name') }}</span>
        </span>
    </a>
</div>
