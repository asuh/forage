<header class="banner u-container">
    @if (is_front_page() && is_home())
        <h1 class="site-title"><a class="brand u-url" href="{{ home_url('/') }}">{{ get_bloginfo('name', 'display') }}</a>
        </h1>
    @else
        <site-title><a class="brand" href="{{ home_url('/') }}">{{ get_bloginfo('name', 'display') }}</a></site-title>
    @endif

    @if (has_nav_menu('primary_navigation'))
        <nav class="nav-primary">
            <button class="toggle-button" id="toggle-button" type="button" aria-expanded="false" aria-controls="nav-primary" aria-pressed="false">
                <span class="visuallyhidden">Toggle Navigation</span>
                <svg class="toggle-menu-open" width="16" height="10" viewBox="0 0 16 10" focusable="false" aria-hidden="true" aria-labelledby="toggle-button">
                    <g fill="currentColor" fill-rule="evenodd">
                        <rect y="8" width="16" height="2" rx="1"></rect>
                        <rect y="4" width="16" height="2" rx="1"></rect>
                        <rect width="16" height="2" rx="1"></rect>
                    </g>
                </svg>
                <span class="toggle-menu-close">❌</span>
            </button>
            {!! wp_nav_menu([
                'theme_location' => 'primary_navigation',
                'menu_class' => 'nav-list',
                'menu_id' => 'nav-primary',
                'container' => false,
            ]) !!}
        </nav>
    @endif

    {{-- Use this section for actions such as sign in, sign out, and sign up, toggle buttons to open modals, or links that a user needs for quick access. By default, the search form is added here and invoked using a dialog component --}}
    @section('header-actions')
        @include('components.dialog', [
            'content' => get_search_form(false),
            'id' => 'search-dialog'
        ])
    @endsection

    @include('components.header-actions')
</header>
