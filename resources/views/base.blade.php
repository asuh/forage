<!DOCTYPE html>
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
        @php(do_action('wp_body_open'))
        @php(do_action('get_header'))

        @include('partials.header')

        <main class="main" id="main">
            @yield('content')
        </main>

        @hasSection('sidebar')
            <aside class="sidebar">
                @yield('sidebar')
            </aside>
        @endif

        @include('partials.footer')

        @php(do_action('get_footer'))
        @php(wp_footer())
    </body>
</html>
