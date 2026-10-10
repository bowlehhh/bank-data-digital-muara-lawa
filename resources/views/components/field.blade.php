@props(['name', 'label', 'help' => null])
<div {{ $attributes }}>
    <label for="{{ $name }}" class="mb-2 block text-sm font-semibold">{{ $label }}</label>
    {{ $slot }}
    @if($help)<p id="{{ $name }}-help" class="mt-2 text-xs leading-relaxed text-muted">{{ $help }}</p>@endif
    @error($name)<p id="{{ $name }}-error" role="alert" class="mt-2 text-sm text-primary">{{ $message }}</p>@enderror
</div>
