@extends('base')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    @includeFirst(['partials.content-single-' . get_post_type(), 'partials.content-single'])
  @endwhile
@endsection