@props(['title', 'subtitle' => null])

<div class="mb-6">
    <div class="flex justify-between items-end gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-semibold leading-tight text-white">
                {{ $title }}
            </h1>
            @if ($subtitle)
                <p class="band-sub text-sm mt-1">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
        @if ($slot->isNotEmpty())
            <div class="flex items-center gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
