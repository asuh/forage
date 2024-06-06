<!doctype html>
<html @php(language_attributes())>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php(wp_head())
    </head>

    <body @php(body_class())>
        <a class="visuallyhidden" href="#main">
            {{ __('Skip to content') }}
        </a>
        {!! do_action('wp_body_open') !!}
        {!! do_action('get_header') !!}

        @include('partials.header')
        @include('partials/h-card')

        <main class="main" id="main">
            @yield('content')
        </main>

        @hasSection('sidebar')
            <aside class="sidebar">
                @yield('sidebar')
            </aside>
        @endif

        @php('get_footer')
        @include('partials.footer')
        @php('wp_footer')
    </body>
</html>
