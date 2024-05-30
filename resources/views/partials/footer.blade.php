<footer class="content-info">
  <section class="container">
    @php dynamic_sidebar('sidebar-footer') @endphp
    <div class="copyright">
      <p>&copy; <a href="{{ home_url('/') }}" rel="me">{{ get_bloginfo('name', 'display') }}</a></p>
    </div>
    <script>
      window.onload = function(){
        setTimeout(function(){
          window.performance = window.performance || window.mozPerformance || window.msPerformance || window.webkitPerformance || {};

          const t = performance.timing || {};
          if (!t) {
            return;
          }
          const loadTime = (t.loadEventEnd - t.navigationStart) / 1000;
          const markup = `This page loaded in <b>${loadTime}</b> seconds`;

          const copyright = document.querySelector('.copyright');
          const p = document.createElement('small');
          p.innerHTML = markup;
          copyright.appendChild(p);
        }, 0);
      };
    </script>
    <div>
      <a href="https://xn--sr8hvo.ws/%E2%AC%86%EF%B8%8F%F0%9F%8D%AF%F0%9F%8F%8E/previous">←</a>
      An IndieWeb Webring 🕸💍
      <a href="https://xn--sr8hvo.ws/%E2%AC%86%EF%B8%8F%F0%9F%8D%AF%F0%9F%8F%8E/next">→</a>
    </div>
  </section>
</footer>
