<form role="search" method="get" class="search-form" action="{{ home_url('/') }}">
  <label class="search-label">
    <span class="visuallyhidden">
      {{ _x('Search for:', 'label') }}
    </span>

    <input
      type="search"
      class="search-field"
      placeholder="{{ esc_attr_x('Search...', 'placeholder') }}"
      value="{{ get_search_query() }}"
      name="s"
    >
  </label>

  <button id="button-submit" class="button-submit" type="submit">
    <span class="visuallyhidden">{{ _x('Submit search request', 'label') }}</span>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" focusable="false" aria-labelledby="button-submit">
      <path fill="currentColor" d="M509 479L360 330a203 203 0 10-30 30l149 149c4 4 11 4 15 0l15-15c4-4 4-11 0-15zM203 363a160 160 0 110-321 160 160 0 010 321z"/>
    </svg>
  </button>
</form>
