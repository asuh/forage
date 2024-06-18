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
        {{-- Many h-cards contain a notes section where you can add a biography or short description of the author. Feel free to uncomment the line below and add a bio to the profile, then delete this line.

        You can validate this h-card setup at https://indiewebify.me/validate-h-card/ --}}
        {{-- <div class="p-note">{{ get_the_author_meta('description') }}</div> --}}
    </a>
</div>
