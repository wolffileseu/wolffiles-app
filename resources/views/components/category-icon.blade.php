@props([
    'category',
    'textSize' => 'text-3xl',
    'imgClass' => 'w-12 h-12 object-cover rounded',
])

@if($category->image)
    <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($category->image) }}"
         alt="{{ $category->name }}"
         class="{{ $imgClass }}">
@elseif($category->icon)
    <span class="{{ $textSize }}">{{ $category->icon }}</span>
@else
    {{ $slot }}
@endif
