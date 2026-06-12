@php($items = $getRecord()->evidence)
@if($items->isEmpty())
    <p class="text-gray-500 text-sm">No screenshots attached.</p>
@else
    <div style="display:flex;flex-wrap:wrap;gap:12px;">
        @foreach($items as $ev)
            @php($url = $ev->url(60))
            @if($url)
                <a href="{{ $url }}" target="_blank" rel="noopener">
                    <img src="{{ $url }}" style="width:200px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #374151;">
                </a>
            @endif
        @endforeach
    </div>
@endif
