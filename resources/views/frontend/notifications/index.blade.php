<x-layouts.app :title="__('messages.notifications')">
<style>
    .notif-row{transition:background .15s,border-color .15s}.notif-row:hover{background:rgba(255,255,255,.04)}
    .notif-avatar{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0}
    .notif-dot{width:8px;height:8px;border-radius:50%;background:#f59e0b;flex-shrink:0;box-shadow:0 0 6px rgba(245,158,11,.6)}
    .badge-t{font-size:10px;font-weight:600;letter-spacing:.6px;padding:2px 7px;border-radius:20px;text-transform:uppercase}
    .bc{background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.25)}
    .bu{background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.25)}
    .bo{background:rgba(156,163,175,.12);color:#9ca3af;border:1px solid rgba(156,163,175,.2)}
    .preview{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    @keyframes fiu{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    .ni{animation:fiu .25s ease both}
</style>
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">{{ __('messages.notifications') }}</h1>
            @php $uc = auth()->user()->unreadNotifications->count(); @endphp
            @if($uc > 0)
                <p class="text-sm text-gray-400 mt-0.5"><span class="text-amber-400 font-semibold">{{ $uc }}</span> ungelesen</p>
            @else
                <p class="text-sm text-gray-500 mt-0.5">Alles gelesen ✓</p>
            @endif
        </div>
        @if($uc > 0)
        <form action="{{ route('notifications.markAllRead') }}" method="POST">
            @csrf
            <button type="submit" class="flex items-center gap-1.5 text-sm text-amber-400 hover:text-amber-300 border border-amber-400/30 hover:border-amber-400/60 px-3 py-1.5 rounded-lg transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ __('messages.mark_all_read') }}
            </button>
        </form>
        @endif
    </div>

    <div class="space-y-1.5">
    @forelse($notifications as $i => $notification)
        @php
            $data=$notification->data; $isUnread=is_null($notification->read_at);
            $type=class_basename($notification->type);
            $isC=$type==='NewComment'; $isU=$type==='NewFileUploaded';
            if($isC){$actor=$data['commenter_name']??'?';$subject=$data['commentable_title']??'';$preview=$data['body']??'';}
            elseif($isU){$actor=$data['uploader_name']??'?';$subject=$data['file_title']??'';$preview='';}
            else{$actor=$data['actor_name']??$data['uploader_name']??$data['commenter_name']??'?';$subject=$data['file_title']??$data['commentable_title']??'';$preview=$data['body']??$data['message']??'';}
            $ini=strtoupper(mb_substr(strip_tags($actor),0,2));
            $cls=['bg-blue-600','bg-emerald-600','bg-violet-600','bg-rose-600','bg-amber-600','bg-cyan-600','bg-pink-600'];
            $ac=$cls[abs(crc32($actor))%count($cls)];
        @endphp
        <a href="{{ route('notifications.read', $notification->id) }}"
           class="ni notif-row flex items-start gap-3.5 px-4 py-3.5 rounded-xl border {{ $isUnread ? 'bg-gray-800/80 border-gray-700/70' : 'bg-gray-800/30 border-gray-700/30 opacity-70 hover:opacity-100' }}"
           style="animation-delay:{{ $i*.04 }}s">
            <div class="notif-avatar {{ $ac }} text-white mt-0.5">{{ $ini }}</div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    @if($isC)<span class="badge-t bc">💬 Comment</span>
                    @elseif($isU)<span class="badge-t bu">⬆ New Map</span>
                    @else<span class="badge-t bo">Notification</span>@endif
                    <span class="text-sm font-semibold text-white">{{ $actor }}</span>
                    <span class="text-sm text-gray-400">{{ $isC ? 'commented on' : ($isU ? 'uploaded' : '') }}</span>
                    @if($subject)<span class="text-sm font-medium text-amber-400/90 truncate max-w-xs">{{ $subject }}</span>@endif
                </div>
                @if($preview)
                    <p class="text-xs text-gray-400 preview leading-relaxed mt-0.5 border-l-2 border-gray-600 pl-2">{{ $preview }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-1.5">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
            <div class="pt-1.5">
                @if($isUnread)<div class="notif-dot"></div>@else<div style="width:8px;height:8px"></div>@endif
            </div>
        </a>
    @empty
        <div class="text-center py-20">
            <svg class="mx-auto w-12 h-12 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <p class="text-gray-400 font-medium">{{ __('messages.no_notifications') }}</p>
            <p class="text-gray-500 text-sm mt-1">New activity will appear here</p>
        </div>
    @endforelse
    </div>
    @if($notifications->hasPages())<div class="mt-8">{{ $notifications->links() }}</div>@endif
</div>
</x-layouts.app>
