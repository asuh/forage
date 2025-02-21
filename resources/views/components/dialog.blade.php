@props([
    'content' => '', // The content to render inside the dialog
    'id' => 'dialog', // The dialog ID
])

<button id="dialog-toggle" class="dialog-toggle" type="button" onclick="dialog.showModal()">
    <span class="visuallyhidden">{{ _x('Toggle dialog to show', 'label') }}</span>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16" aria-hidden="true" focusable="false" aria-labelledby="dialog-toggle">
        <path fill="currentColor" d="M509 479L360 330a203 203 0 10-30 30l149 149c4 4 11 4 15 0l15-15c4-4 4-11 0-15zM203 363a160 160 0 110-321 160 160 0 010 321z"></path>
    </svg>
</button>

<dialog id="{{ $id }}" class="dialog" aria-labelledby="{{ $id }}-title">
    {!! $content !!}

    <button id="dialog-close" type="button" class="dialog-close" onclick="this.closest('dialog').close('close')">
        <span class="visuallyhidden">{{ _x('Close dialog', 'label') }}</span>
        <svg width="16" height="16" viewBox="0 0 40 40" focusable="false" aria-hidden="true" aria-labelledby="dialog-close">
            <path d="M25.6 14.3a1 1 0 0 1 0 1.4l-4.24 4.25 4.25 4.24a1 1 0 1 1-1.42 1.42l-4.24-4.25-4.24 4.25a1 1 0 0 1-1.42-1.42l4.25-4.24-4.25-4.24a1 1 0 0 1 1.42-1.42l4.24 4.25 4.24-4.25a1 1 0 0 1 1.42 0z" fill="currentColor" fill-rule="evenodd"></path>
        </svg>
    </button>
</dialog>

<script>
  const dialog = document.querySelector('.dialog')

  dialog.addEventListener('click', ({target:dialog}) => {
    if (dialog.nodeName === 'DIALOG')
      dialog.close('dismiss')
  })
</script>
