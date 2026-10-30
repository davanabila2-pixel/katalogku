@props([
    'name' => 'q',
    'id' => null,
    'value' => null,
    'placeholder' => 'Cari…',
    'action' => null,
    'method' => 'GET',
    'width' => null,
    'buttonText' => null,
    'buttonClass' => 'btn btn-primary btn-sm',
    'clearable' => true,
])

<form
    method="{{ strtoupper($method) === 'POST' ? 'POST' : 'GET' }}"
    @if($action) action="{{ $action }}" @endif
    @if($id) id="{{ $id }}" @endif
    class="search-form {{ $attributes->get('class') }}"
    role="search"
    {{ $attributes->except(['class', 'id', 'action', 'method']) }}
>
    @if(strtoupper($method) === 'POST')
        @csrf
    @endif

    <x-search-input
        :name="$name"
        :value="$value"
        :placeholder="$placeholder"
        :width="$width"
        :clearable="$clearable"
    />

    @if($buttonText)
        <button type="submit" class="{{ $buttonClass }}">{{ $buttonText }}</button>
    @endif

    {{ $slot ?? '' }}
</form>
