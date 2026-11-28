@props(['type' => 'success'])

@if ($type === 'success')
    <div class="p-4 rounded-[var(--radius-card)] border-2 border-[var(--color-success)]" style="background-color: color-mix(in srgb, var(--color-success) 10%, white);">
        {{ $slot }}
    </div>
@else
    <div class="p-4 rounded-[var(--radius-card)] border-2 border-[var(--color-danger)]" style="background-color: color-mix(in srgb, var(--color-danger) 10%, white);">
        {{ $slot }}
    </div>
@endif
