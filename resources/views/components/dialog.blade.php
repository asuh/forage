@props([
    'content' => '', // The content to render inside the dialog
    'id' => 'dialog', // The dialog ID
    'title' => __('Dialog', 'forage'), // The accessible dialog title
])

@php
    $toggleId = "{$id}-toggle";
    $titleId = "{$id}-title";
    $closeId = "{$id}-close";
@endphp

<button id="{{ $toggleId }}" class="dialog-toggle" type="button" command="show-modal" commandfor="{{ $id }}">
    <span class="visuallyhidden">{{ sprintf(__('Open %s', 'forage'), $title) }}</span>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" aria-hidden="true" focusable="false">
        <path fill="currentColor" d="M509 479L360 330a203 203 0 10-30 30l149 149c4 4 11 4 15 0l15-15c4-4 4-11 0-15zM203 363a160 160 0 110-321 160 160 0 010 321z"></path>
    </svg>
</button>

<dialog id="{{ $id }}" class="dialog" aria-labelledby="{{ $titleId }}" closedby="any">
    <h2 id="{{ $titleId }}" class="dialog-title">{{ $title }}</h2>

    {!! $content !!}

    <button id="{{ $closeId }}" type="button" class="dialog-close" commandfor="{{ $id }}" command="close">
        <span class="visuallyhidden">{{ sprintf(__('Close %s', 'forage'), $title) }}</span>
        <svg width="64" height="64" viewBox="0 0 40 40" focusable="false" aria-hidden="true">
            <path d="M25.6 14.3a1 1 0 0 1 0 1.4l-4.24 4.25 4.25 4.24a1 1 0 1 1-1.42 1.42l-4.24-4.25-4.24 4.25a1 1 0 0 1-1.42-1.42l4.25-4.24-4.25-4.24a1 1 0 0 1 1.42-1.42l4.24 4.25 4.24-4.25a1 1 0 0 1 1.42 0z" fill="currentColor" fill-rule="evenodd"></path>
        </svg>
    </button>
</dialog>
