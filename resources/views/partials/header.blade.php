<header class="banner">
    @if (is_front_page() && is_home())
        <h1 class="site-title"><a class="brand u-url" href="{{ home_url('/') }}">{{ get_bloginfo('name', 'display') }}</a>
        </h1>
    @else
        <p class="site-title"><a class="brand" href="{{ home_url('/') }}">{{ get_bloginfo('name', 'display') }}</a></p>
    @endif

    @if (has_nav_menu('primary_navigation'))
        <nav class="nav-primary" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
            <button class="toggle-button" type="button" aria-expanded="false" aria-controls="nav-primary">Menu</button>
            {!! wp_nav_menu([
                'theme_location' => 'primary_navigation',
                'menu_class' => 'nav-list',
                'menu_id' => 'nav-primary',
                'container' => false,
            ]) !!}
        </nav>
    @endif
</header>
