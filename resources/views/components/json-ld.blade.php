{{-- JSON-LD Structured Data --}}
@if(isset($type) && $type === 'website')
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'Wolffiles.eu',
    'url' => 'https://wolffiles.eu',
    'description' => 'The largest collection of maps, mods, skins and tools for Wolfenstein: Enemy Territory',
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => 'https://wolffiles.eu/search?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
@endphp
</script>
@elseif(isset($type) && $type === 'file' && isset($file))
<script type="application/ld+json">
@php
$data = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $file->title,
    'description' => \Illuminate\Support\Str::limit(strip_tags($file->description ?? ''), 200),
    'url' => route('files.show', $file),
    'applicationCategory' => 'GameMod',
    'operatingSystem' => 'Windows, Linux, macOS',
    'softwareVersion' => $file->version ?? '1.0',
    'datePublished' => $file->created_at->toIso8601String(),
    'dateModified' => $file->updated_at->toIso8601String(),
    'downloadUrl' => route('files.download', $file),
    'author' => [
        '@type' => 'Person',
        'name' => $file->original_author ?? $file->user?->name ?? 'Unknown',
    ],
    'interactionStatistic' => [
        '@type' => 'InteractionCounter',
        'interactionType' => 'https://schema.org/DownloadAction',
        'userInteractionCount' => $file->download_count,
    ],
];
if ($file->screenshots->first()) {
    $data['image'] = $file->screenshots->first()->url;
}
if ($file->file_size) {
    $data['fileSize'] = number_format($file->file_size / 1024 / 1024, 1) . ' MB';
}
if ($file->rating_count > 0) {
    $data['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $file->average_rating,
        'bestRating' => '5',
        'worstRating' => '1',
        'ratingCount' => $file->rating_count,
    ];
}
echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
@endphp
</script>
@elseif(isset($type) && $type === 'post' && isset($post))
<script type="application/ld+json">
@php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $post->title,
    'description' => \Illuminate\Support\Str::limit(strip_tags($post->content), 200),
    'url' => route('posts.show', $post),
    'datePublished' => $post->created_at->toIso8601String(),
    'dateModified' => $post->updated_at->toIso8601String(),
    'author' => [
        '@type' => 'Person',
        'name' => $post->user?->name ?? 'Wolffiles.eu',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Wolffiles.eu',
        'url' => 'https://wolffiles.eu',
    ],
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
@endphp
</script>
@endif
