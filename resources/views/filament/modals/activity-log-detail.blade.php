<div class="space-y-4 text-sm">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="text-gray-500 block">Time</span>
            <span class="text-white">{{ $log->created_at->format('d.m.Y H:i:s') }}</span>
        </div>
        <div>
            <span class="text-gray-500 block">User</span>
            <span class="text-white">{{ $log->user?->name ?? 'System / Guest' }}</span>
        </div>
        <div>
            <span class="text-gray-500 block">Action</span>
            <span class="text-amber-400 font-semibold">{{ $log->action }}</span>
        </div>
        <div>
            <span class="text-gray-500 block">IP Address</span>
            <span class="text-white font-mono text-xs">{{ $log->ip_address }}</span>
        </div>
        <div>
            <span class="text-gray-500 block">Subject Type</span>
            <span class="text-white">{{ $log->subject_type ? class_basename($log->subject_type) : '-' }}</span>
        </div>
        <div>
            <span class="text-gray-500 block">Subject ID</span>
            <span class="text-white">{{ $log->subject_id ?? '-' }}</span>
        </div>
    </div>

    <div>
        <span class="text-gray-500 block mb-1">Description</span>
        <div class="bg-gray-900 rounded-lg p-3 text-gray-300">{{ $log->description }}</div>
    </div>

    @if($log->properties)
    <div>
        <span class="text-gray-500 block mb-1">Properties</span>
        <pre class="bg-gray-900 rounded-lg p-3 text-xs text-gray-300 overflow-x-auto">{{ json_encode(is_array($log->properties) ? $log->properties : json_decode($log->properties), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @endif
</div>
