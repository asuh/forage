@if (!is_front_page() && !is_home())
    <div class="page-header">
        <h1 class="page-title p-name">{!! get_the_title() !!}</h1>
    </div>
@endif
