<footer class="content-info">
    @include('partials/h-card')

    @php(dynamic_sidebar('sidebar-footer'))
    <div class="copyright">
        <p>&copy; <a href="{{ home_url('/') }}" rel="me">{{ get_bloginfo('name', 'display') }}</a></p>
    </div>
    <div>
        <a href="https://xn--sr8hvo.ws/%E2%AC%86%EF%B8%8F%F0%9F%8D%AF%F0%9F%8F%8E/previous">←</a>
        An IndieWeb Webring 🕸💍
        <a href="https://xn--sr8hvo.ws/%E2%AC%86%EF%B8%8F%F0%9F%8D%AF%F0%9F%8F%8E/next">→</a>
    </div>
</footer>
