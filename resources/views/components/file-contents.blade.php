{{-- File Contents & Virus Scan Info --}}
@php
    $metadata = $file->extracted_metadata;
    $fileTypes = $metadata['file_types'] ?? [];
    $fileList = $metadata['file_list'] ?? [];
    $totalFiles = $metadata['total_files'] ?? 0;
    $totalSize = $metadata['total_uncompressed_size'] ?? 0;
    $bspFiles = $metadata['bsp_files'] ?? [];
    $arena = $metadata['arena'] ?? [];
    $hasBots = $metadata['has_bots'] ?? false;
    $pk3Contents = $metadata['pk3_contents'] ?? [];
    $containedPk3s = $metadata['contained_pk3s'] ?? [];
@endphp

<div class="space-y-6">
    {{-- Virus Scan Status --}}
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4 flex items-center space-x-2">
            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span>Virus Scan</span>
        </h3>
        @if($file->virus_scanned && $file->virus_clean === true)
            {{-- Clean --}}
            <div class="flex items-center space-x-3 bg-green-900/20 border border-green-700/30 rounded-lg px-4 py-3">
                <div class="w-8 h-8 bg-green-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <div class="text-green-400 font-medium text-sm">Clean — No threats detected</div>
                    <div class="text-gray-500 text-xs mt-0.5">Scanned with ClamAV</div>
                </div>
            </div>
        @elseif($file->virus_scanned && $file->virus_clean === false)
            {{-- Infected --}}
            <div class="flex items-center space-x-3 bg-red-900/20 border border-red-700/30 rounded-lg px-4 py-3">
                <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <div class="text-red-400 font-medium text-sm">Warning — Threat detected</div>
                    <div class="text-gray-500 text-xs mt-0.5">{{ $file->virus_scan_result }}</div>
                </div>
            </div>
        @elseif($file->virus_scanned && $file->virus_clean === null)
            {{-- Scan Error --}}
            <div class="flex items-center space-x-3 bg-yellow-900/20 border border-yellow-700/30 rounded-lg px-4 py-3">
                <div class="w-8 h-8 bg-yellow-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <div class="text-yellow-400 font-medium text-sm">Scan Error — Could not be fully scanned</div>
                    <div class="text-gray-500 text-xs mt-0.5">{{ $file->virus_scan_result }}</div>
                </div>
            </div>
        @else
            {{-- Not yet scanned --}}
            <div class="flex items-center space-x-3 bg-gray-700/30 border border-gray-600/30 rounded-lg px-4 py-3">
                <div class="w-8 h-8 bg-gray-600/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-gray-400 font-medium text-sm">Pending — Not yet scanned</div>
                </div>
            </div>
        @endif
    </div>

    {{-- File Contents --}}
    @if(!empty($fileList) || !empty($fileTypes))
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6" x-data="{ showAll: false }">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center space-x-2">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                <span>File Contents</span>
            </h3>

            {{-- Quick Stats --}}
            <div class="flex flex-wrap gap-3 mb-4">
                @if($totalFiles > 0)
                    <span class="bg-gray-700/50 text-gray-300 px-3 py-1 rounded-full text-xs">
                        {{ $totalFiles }} {{ $totalFiles === 1 ? 'file' : 'files' }}
                    </span>
                @endif
                @if($totalSize > 0)
                    <span class="bg-gray-700/50 text-gray-300 px-3 py-1 rounded-full text-xs">
                        {{ number_format($totalSize / 1048576, 1) }} MB uncompressed
                    </span>
                @endif
                @if(!empty($bspFiles))
                    <span class="bg-blue-900/30 text-blue-400 px-3 py-1 rounded-full text-xs">
                        {{ count($bspFiles) }} BSP {{ count($bspFiles) === 1 ? 'map' : 'maps' }}: {{ implode(', ', $bspFiles) }}
                    </span>
                @endif
                @if($hasBots)
                    <span class="bg-green-900/30 text-green-400 px-3 py-1 rounded-full text-xs">Bot Support</span>
                @endif
                @if(!empty($arena['type']))
                    <span class="bg-amber-900/30 text-amber-400 px-3 py-1 rounded-full text-xs">Type: {{ $arena['type'] }}</span>
                @endif
                @if(!empty($containedPk3s))
                    <span class="bg-purple-900/30 text-purple-400 px-3 py-1 rounded-full text-xs">
                        {{ count($containedPk3s) }} PK3 {{ count($containedPk3s) === 1 ? 'file' : 'files' }}
                    </span>
                @endif
            </div>

            {{-- File Types Summary --}}
            @if(!empty($fileTypes))
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach(array_slice($fileTypes, 0, 12) as $ext => $count)
                        <span class="bg-gray-700/30 text-gray-400 px-2 py-0.5 rounded text-xs font-mono">.{{ $ext }} <span class="text-gray-500">({{ $count }})</span></span>
                    @endforeach
                    @if(count($fileTypes) > 12)
                        <span class="text-gray-500 text-xs py-0.5">+{{ count($fileTypes) - 12 }} more</span>
                    @endif
                </div>
            @endif

            {{-- File Listing --}}
            @if(!empty($fileList))
                <div class="border-t border-gray-700 pt-4">
                    <button @click="showAll = !showAll"
                            class="text-sm text-amber-400 hover:text-amber-300 transition-colors flex items-center space-x-1 mb-3">
                        <svg class="w-4 h-4 transition-transform" :class="showAll ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span x-text="showAll ? 'Hide file list' : 'Show all {{ count($fileList) }} files'"></span>
                    </button>

                    <div x-show="showAll" x-collapse>
                        <div class="max-h-80 overflow-y-auto rounded-lg bg-gray-900/50 border border-gray-700/50">
                            <table class="w-full text-xs">
                                <thead class="sticky top-0 bg-gray-800">
                                    <tr class="text-gray-500 uppercase tracking-wider">
                                        <th class="text-left px-3 py-2 font-medium">File</th>
                                        <th class="text-right px-3 py-2 font-medium w-24">Size</th>
                                    </tr>
                                </thead>
                                <tbody class="font-mono text-gray-400">
                                    @foreach($fileList as $item)
                                        @php
                                            $itemExt = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
                                            $colorClass = match(true) {
                                                in_array($itemExt, ['bsp']) => 'text-blue-400',
                                                in_array($itemExt, ['tga', 'jpg', 'png']) => 'text-green-400',
                                                in_array($itemExt, ['wav', 'mp3', 'ogg']) => 'text-purple-400',
                                                in_array($itemExt, ['shader', 'cfg', 'arena']) => 'text-amber-400',
                                                in_array($itemExt, ['md3', 'mdc', 'mdm']) => 'text-pink-400',
                                                in_array($itemExt, ['nav', 'way']) => 'text-cyan-400',
                                                default => 'text-gray-500',
                                            };
                                        @endphp
                                        <tr class="border-t border-gray-800/50 hover:bg-gray-800/50">
                                            <td class="px-3 py-1.5 truncate {{ $colorClass }}" title="{{ $item['name'] }}">{{ $item['name'] }}</td>
                                            <td class="px-3 py-1.5 text-right text-gray-500 whitespace-nowrap">
                                                @if($item['size'] > 0)
                                                    @if($item['size'] >= 1048576)
                                                        {{ number_format($item['size'] / 1048576, 1) }}M
                                                    @elseif($item['size'] >= 1024)
                                                        {{ number_format($item['size'] / 1024, 0) }}K
                                                    @else
                                                        {{ $item['size'] }}B
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>