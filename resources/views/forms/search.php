<form role="search" method="get" class="search-form" action="<?php echo home_url('/'); ?>">
  <label class="search-label">
    <span class="visuallyhidden">
      <?php echo _x('Search for:', 'label'); ?>
    </span>

    <input
      type="search"
      class="search-field"
      placeholder="<?php echo _x('Search&hellip;', 'placeholder'); ?>"
      value="<?php if (is_search()) { get_search_query(); } ?>"
      name="s"
    >
  </label>

  <button id="button-submit" class="button-submit" type="submit" aria-label="Submit search request">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" focusable="false" aria-labelledby="button-submit">
      <path fill="currentColor" d="M509 479L360 330a203 203 0 10-30 30l149 149c4 4 11 4 15 0l15-15c4-4 4-11 0-15zM203 363a160 160 0 110-321 160 160 0 010 321z"/>
    </svg>
  </button>

  <button id="search-close" type="button" class="search-close" aria-label="Close mobile navigation">
    <svg width="60" height="60" viewBox="0 0 40 40" focusable="false" aria-labelledby="search-close">
      <path d="M25.6 14.3a1 1 0 0 1 0 1.4l-4.24 4.25 4.25 4.24a1 1 0 1 1-1.42 1.42l-4.24-4.25-4.24 4.25a1 1 0 0 1-1.42-1.42l4.25-4.24-4.25-4.24a1 1 0 0 1 1.42-1.42l4.24 4.25 4.24-4.25a1 1 0 0 1 1.42 0z" fill="#8898AA" fill-rule="evenodd"></path>
    </svg>
  </button>
</form>
