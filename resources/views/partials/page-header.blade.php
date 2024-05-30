<div class="page-header">
  @if (!is_front_page() && !is_home())
    <h1 class="page-title p-name">{!! get_the_title() !!}</h1>
  @endif
</div>
