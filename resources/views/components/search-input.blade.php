@props([
    'name' => 'q',
    'id' => null,
    'value' => null,
    'placeholder' => 'Cari…',
    'width' => null,
    'autocomplete' => 'off',
    'autofocus' => false,
    'clearable' => true,
    'asForm' => false,
    'action' => null,
    'method' => 'GET',
    'formId' => null,
    'formClass' => '',
])

@php
    $inputId = $id ?? ($attributes->get('id') ?? null);
    $currentVal = $value !== null ? $value : request($name, '');
    $customStyle = $attributes->get('style', '');
    $containerStyle = $width ? 'width:' . $width . ';' . $customStyle : $customStyle;
    $hasValue = filled($currentVal);
    $shouldRenderForm = $asForm || !empty($action) || !empty($formId);
@endphp

@if($shouldRenderForm)
<form
    method="{{ strtoupper($method) === 'POST' ? 'POST' : 'GET' }}"
    @if($action) action="{{ $action }}" @endif
    @if($formId) id="{{ $formId }}" @endif
    class="search-form {{ $formClass }}"
    role="search"
>
    @if(strtoupper($method) === 'POST')
        @csrf
    @endif
@endif

<div
    class="search-box {{ $hasValue ? 'has-value' : '' }} {{ $attributes->get('class') }}"
    @if(!empty(trim($containerStyle))) style="{{ trim($containerStyle) }}" @endif
    data-search-box
>
    <span class="search-box-icon" aria-hidden="true">
        <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="9" r="6"></circle>
            <path d="M19 19l-4.5-4.5"></path>
        </svg>
    </span>

    <input
        type="search"
        name="{{ $name }}"
        @if($inputId) id="{{ $inputId }}" @endif
        value="{{ $currentVal }}"
        placeholder="{{ $placeholder }}"
        autocomplete="{{ $autocomplete }}"
        @if($autofocus) autofocus @endif
        class="search-box-input"
        {{ $attributes->except(['class', 'style', 'id', 'width']) }}
    >

    @if($clearable)
    <button
        type="button"
        class="search-box-clear"
        aria-label="Hapus pencarian"
        title="Hapus pencarian"
        tabindex="-1"
    >
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>
    @endif
</div>

@if($shouldRenderForm)
    {{ $slot ?? '' }}
</form>
@endif

@once
<script>
(function() {
    if (window.__searchBoxInitialized) return;
    window.__searchBoxInitialized = true;

    // Sync has-value class dynamically on input
    document.addEventListener('input', function(e) {
        var input = e.target;
        if (!input || !input.classList.contains('search-box-input')) return;
        var box = input.closest('[data-search-box]');
        if (!box) return;
        if (input.value && input.value.trim().length > 0) {
            box.classList.add('has-value');
        } else {
            box.classList.remove('has-value');
        }
    });

    // Clear button functionality
    document.addEventListener('click', function(e) {
        var clearBtn = e.target.closest('.search-box-clear');
        if (!clearBtn) return;
        var box = clearBtn.closest('[data-search-box]');
        if (!box) return;
        var input = box.querySelector('.search-box-input');
        if (!input) return;

        input.value = '';
        box.classList.remove('has-value');
        input.focus();

        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // Keyboard navigation: Escape key clears input when focused
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        var input = e.target;
        if (!input || !input.classList.contains('search-box-input')) return;
        if (input.value && input.value.length > 0) {
            e.preventDefault();
            var box = input.closest('[data-search-box]');
            input.value = '';
            if (box) box.classList.remove('has-value');
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
})();
</script>
@endonce
