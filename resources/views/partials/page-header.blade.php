@if (is_archive())
    <div class="page-header">
        <h1 class="page-title p-name">{!! get_the_archive_title() !!}</h1>
    </div>
@elseif (is_search())
    <div class="page-header">
        <h1 class="page-title p-name">{!! sprintf(
            /* translators: %s is replaced with the search query */
            __('Search Results for %s'),
            get_search_query()
        ) !!}</h1>
    </div>
@elseif (is_404())
    <div class="page-header">
        <h1 class="page-title p-name">{!! __('Not Found') !!}</h1>
    </div>
@elseif (!is_front_page() && !is_home())
    <div class="page-header">
        <h1 class="page-title p-name">{!! get_the_title() !!}</h1>
    </div>
@endif
