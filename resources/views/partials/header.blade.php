<header class="banner">
    @if (is_front_page() && is_home())
        <h1 class="site-title"><a class="brand u-url" href="{{ home_url('/') }}">{{ get_bloginfo('name', 'display') }}</a>
        </h1>
    @else
        <site-title><a class="brand" href="{{ home_url('/') }}">{{ get_bloginfo('name', 'display') }}</a></site-title>
    @endif

    @if (has_nav_menu('primary_navigation'))
        <nav class="nav-primary">
            <button class="toggle-button" type="button" aria-expanded="false" aria-controls="nav-primary"><span class="visuallyhidden">Toggle Navigation</span></button>
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
