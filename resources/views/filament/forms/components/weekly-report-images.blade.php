@php
    $record = $getRecord() ?? ($record ?? null);
    $images = $record->images ?? [];
@endphp

@if(!empty($images))
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        @foreach($images as $image)
            <div class="relative">
                <img src="{{ Storage::url($image) }}" alt="Report Image" class="w-full h-48 object-cover rounded-lg border">
            </div>
        @endforeach
    </div>
@else
    <p class="text-gray-500 dark:text-gray-400">No images uploaded</p>
@endif

