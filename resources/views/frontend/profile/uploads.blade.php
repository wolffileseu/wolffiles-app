<x-layouts.app title="My Uploads">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">My Uploads</h1>
            <a href="{{ route('files.upload') }}" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                Upload New File
            </a>
        </div>

        @if($files->isEmpty())
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-12 text-center">
                <p class="text-gray-400 text-lg mb-4">You haven't uploaded any files yet.</p>
                <a href="{{ route('files.upload') }}" class="text-amber-400 hover:text-amber-300">Upload your first file →</a>
            </div>
        @else
            <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-750 border-b border-gray-700">
                        <tr>
                            <th class="text-left text-gray-400 text-sm font-medium px-6 py-3">File</th>
                            <th class="text-left text-gray-400 text-sm font-medium px-6 py-3">Category</th>
                            <th class="text-left text-gray-400 text-sm font-medium px-6 py-3">Status</th>
                            <th class="text-left text-gray-400 text-sm font-medium px-6 py-3">Downloads</th>
                            <th class="text-left text-gray-400 text-sm font-medium px-6 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($files as $file)
                            <tr class="hover:bg-gray-750 transition-colors">
                                <td class="px-6 py-4">
                                    <a href="{{ $file->isApproved() ? route('files.show', $file) : '#' }}" class="text-white hover:text-amber-400 font-medium">
                                        {{ $file->title }}
                                    </a>
                                    <div class="text-gray-500 text-xs mt-1">{{ $file->file_size_formatted }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-400 text-sm">{{ $file->category?->name }}</td>
                                <td class="px-6 py-4">
                                    @switch($file->status)
                                        @case('approved')
                                            <span class="bg-green-900/50 text-green-400 px-2 py-1 rounded text-xs font-medium">Approved</span>
                                            @break
                                        @case('pending')
                                            <span class="bg-yellow-900/50 text-yellow-400 px-2 py-1 rounded text-xs font-medium">Pending Review</span>
                                            @break
                                        @case('rejected')
                                            <span class="bg-red-900/50 text-red-400 px-2 py-1 rounded text-xs font-medium" title="{{ $file->rejection_reason }}">Rejected</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="px-6 py-4 text-gray-400 text-sm">{{ number_format($file->download_count) }}</td>
                                <td class="px-6 py-4 text-gray-500 text-sm">{{ $file->created_at->format('d.m.Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $files->links() }}</div>
        @endif
    </div>
</x-layouts.app>
