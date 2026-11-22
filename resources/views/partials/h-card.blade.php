<div class="representative h-card vcard">
    @php
        $post = is_singular() ? get_queried_object() : null;
        $postAuthorId = $post instanceof \WP_Post ? (int) $post->post_author : 0;
        $author = get_userdata($postAuthorId) ?: get_userdata((int) get_option('iw_default_author', 0));
        $organization = $author ? get_the_author_meta('organization', $author->ID) : '';
    @endphp
    <a class="u-url u-uid" rel="author" href="{{ home_url('/') }}">
        @if ($author && forage()->integrations()->gravatar()->hasAvatar($author->ID))
            {!! get_avatar($author->ID, 96, '404', 'Profile image for ' . get_the_author_meta('display_name', $author->ID), ['class' => 'u-photo']) !!}
        @else
            <svg class="avatar avatar-placeholder" width="96" height="96" viewBox="0 0 256 256" aria-hidden="true" focusable="false">
                <rect width="256" height="256" fill="#c5c5c5" />
                <ellipse cx="128" cy="130" rx="55" ry="60" fill="#fff" />
                <path d="M107 187c-3 3-8 7-20 10-33 7-50 27-50 59h182c0-32-17-52-50-59-12-3-17-7-20-10h-42Z" fill="#fff" />
            </svg>
        @endif
        <span class="p-name fn">
            @if (! $author)
                {{ get_bloginfo('name') }}
            @elseif ($organization !== '')
                <span class="fn org">{{ $organization }}</span>
            @else
                <span class="p-given-name">{{ get_the_author_meta('first_name', $author->ID) }}</span>
                <span class="screen-reader-text p-family-name">{{ get_the_author_meta('last_name', $author->ID) }}</span>
            @endif
        </span>
        {{-- Many h-cards contain a notes section where you can add a biography or short description of the author. Feel free to uncomment the line below, add a bio to the default profile (found in the Users profile section of WordPress), and then delete this line as well.

        You can validate this h-card setup at https://indiewebify.me/validate-h-card/ --}}
        {{-- <div class="p-note">{{ $author ? get_the_author_meta('description', $author->ID) : '' }}</div> --}}
    </a>
</div>
