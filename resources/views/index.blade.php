@extends('base')

@section('content')
    @include('partials.page-header')

    @if (!have_posts())
        <div class="alert alert-warning">
            {{ __('Sorry, no results were found.', 'forage') }}
        </div>

        {!! get_search_form(false) !!}
    @endif

    @while (have_posts())
        @php(the_post())

        @if (class_exists('Kind_Taxonomy'))
            @includeFirst(['partials.content-' . get_post_kind(), 'partials.content'])
        @else
            @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
        @endif
    @endwhile

    {!! get_the_posts_navigation(['prev_text' => esc_html__('Older', 'forage'), 'next_text' => esc_html__('Newer', 'forage')]) !!}
@endsection
