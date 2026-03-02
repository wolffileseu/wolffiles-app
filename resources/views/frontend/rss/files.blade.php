{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>Wolffiles.eu - Latest Files</title>
        <link>{{ url('/') }}</link>
        <description>Latest file uploads on Wolffiles.eu</description>
        <language>{{ app()->getLocale() }}</language>
        <lastBuildDate>{{ now()->toRssString() }}</lastBuildDate>
        <atom:link href="{{ route('rss.files') }}" rel="self" type="application/rss+xml"/>

        @foreach($files as $file)
        <item>
            <title>{{ htmlspecialchars($file->title, ENT_XML1) }}</title>
            <link>{{ route('files.show', $file) }}</link>
            <description><![CDATA[{!! Str::limit(strip_tags($file->description), 500) !!}]]></description>
            <category>{{ $file->category?->name ?? 'Uncategorized' }}</category>
            <pubDate>{{ ($file->published_at ?? $file->created_at)->toRssString() }}</pubDate>
            <guid isPermaLink="true">{{ route('files.show', $file) }}</guid>
        </item>
        @endforeach

    </channel>
</rss>
