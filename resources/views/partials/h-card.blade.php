<div class="h-card vcard">
    @php
        $id = get_the_author_meta('ID');

        if (class_exists('HCard_User')) {
            $id = get_option('iw_default_author', 0);

            if (is_singular()) {
                $id = get_the_author_meta('ID');
            }
        }
    @endphp
    <a class="u-url u-uid" rel="author" href="{{ home_url('/') }}">
        {!! get_avatar($id, 96, '', '', ['class' => 'u-photo']) !!}
        <span class="p-name fn">
            @if (get_the_author_meta('organization') !== '')
                <span class="fn org">{{ get_the_author_meta('organization', $id) }}</span>
            @else
                <span class="p-given-name">{{ get_the_author_meta('first_name', $id) }}</span>
                <span class="screen-reader-text p-family-name">{{ get_the_author_meta('last_name', $id) }}</span>
            @endif
        </span>
        {{-- Many h-cards contain a notes section where you can add a biography or short description of the author. Feel free to uncomment the line below, add a bio to the default profile (found in the Users profile section of WordPress), and then delete this line as well.

        You can validate this h-card setup at https://indiewebify.me/validate-h-card/ --}}
        {{-- <div class="p-note">{{ the_author_meta('description', $id) }}</div> --}}
    </a>
</div>
