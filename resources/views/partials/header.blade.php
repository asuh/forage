<header class="banner">
    @if (is_front_page() && is_home())
        <h1 class="site-title"><a class="brand u-url" href="{{ home_url('/') }}">{{ get_bloginfo('name', 'display') }}</a>
        </h1>
    @else
        <site-title><a class="brand" href="{{ home_url('/') }}">{{ get_bloginfo('name', 'display') }}</a></site-title>
    @endif

    @if (has_nav_menu('primary_navigation'))
        <nav class="nav-primary" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
            <button class="toggle-button" type="button" aria-expanded="false" aria-controls="nav-primary"><span class="visuallyhidden">Toggle Navigation</span></button>
            {!! wp_nav_menu([
                'theme_location' => 'primary_navigation',
                'menu_class' => 'nav-list',
                'menu_id' => 'nav-primary',
                'container' => false,
            ]) !!}
        </nav>
    @endif

    <header-action>
        <button id="search-toggle" class="search-toggle" href="#" aria-label="Search form toggle button">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" focusable="false" aria-labelledby="search-toggle">
                <path fill="currentColor" d="M509 479L360 330a203 203 0 10-30 30l149 149c4 4 11 4 15 0l15-15c4-4 4-11 0-15zM203 363a160 160 0 110-321 160 160 0 010 321z"></path>
            </svg>
        </button>
    </header-action>

    {!! get_search_form(false) !!}
</header>
