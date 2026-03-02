<x-layouts.app :title="'World Map'">
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-wrap items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-amber-500">World Map</h1>
            <p class="text-gray-400 mt-1">Live server locations around the world</p>
        </div>
        <a href="{{ route('tracker.index') }}" class="text-amber-400 hover:text-amber-300">&larr; Back to Tracker</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-400">{{ $stats['total_servers'] }}</div>
            <div class="text-gray-400 text-sm">Servers Online</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-amber-400">{{ $stats['total_players'] }}</div>
            <div class="text-gray-400 text-sm">Players Online</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-400">{{ count($stats['countries']) }}</div>
            <div class="text-gray-400 text-sm">Countries</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-400">{{ $stats['countries']->first()?->country ?? '-' }}</div>
            <div class="text-gray-400 text-sm">Most Active</div>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg overflow-hidden mb-6" style="height: 500px;">
        <div id="tracker-world-map" class="w-full h-full"></div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-white mb-4">Players by Country</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($stats['countries'] as $country)
            <div class="flex items-center gap-2 bg-gray-700/50 rounded-lg px-3 py-2">
                @if($country->country_code)
                <img src="https://flagcdn.com/{{ strtolower($country->country_code) }}.svg" alt="" class="w-5 h-3 rounded-sm object-cover">
                @endif
                <div class="flex-1 min-w-0">
                    <div class="text-white text-sm font-medium truncate">{{ $country->country ?? strtoupper($country->country_code) }}</div>
                    <div class="text-gray-400 text-xs">{{ $country->count }} srv &middot; {{ $country->players }} players</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<style>
.leaflet-container{background:#111827}
.server-popup .name{color:#f59e0b;font-weight:bold;font-size:14px}
.server-popup .players{color:#34d399;font-weight:600}
.server-popup .connect{display:inline-block;margin-top:6px;padding:2px 10px;background:#f59e0b;color:#111;border-radius:4px;text-decoration:none;font-size:12px;font-weight:bold}
.server-marker{width:12px;height:12px;border-radius:50%;border:2px solid #f59e0b;background:rgba(245,158,11,0.25)}
.server-marker.has-players{background:rgba(34,197,94,0.4);border-color:#22c55e;animation:pulse-g 2s infinite}
@keyframes pulse-g{0%,100%{box-shadow:0 0 8px rgba(34,197,94,0.4)}50%{box-shadow:0 0 20px rgba(34,197,94,0.6)}}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var map=L.map('tracker-world-map',{center:[30,10],zoom:3,minZoom:2,maxZoom:12});
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{
        attribution:'&copy; OSM &copy; CARTO',subdomains:'abcd',maxZoom:19
    }).addTo(map);
    var markers=L.markerClusterGroup({maxClusterRadius:50,showCoverageOnHover:false,
        iconCreateFunction:function(c){var n=c.getChildCount();return L.divIcon({
            html:'<div style="background:#f59e0b;color:#111;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px;width:100%;height:100%">'+n+'</div>',
            className:'marker-cluster',iconSize:L.point(40,40)});}});
    var servers=@json($servers);
    servers.forEach(function(s){
        if(!s.latitude||!s.longitude)return;
        var hp=s.current_players>0;
        var icon=L.divIcon({className:'server-marker'+(hp?' has-players':''),iconSize:[12,12]});
        var m=L.marker([s.latitude,s.longitude],{icon:icon});
        m.bindPopup('<div class="server-popup"><div class="name">'+(s.hostname_clean||s.ip)+'</div><div>'+
            (s.current_map||'-')+'</div><div class="players">'+s.current_players+'/'+s.max_players+'</div>'+
            '<a href="/servers/'+s.id+'" class="connect">Details</a> <a href="et://'+s.ip+':'+s.port+'" class="connect" style="background:#22c55e">Connect</a></div>');
        markers.addLayer(m);
    });
    map.addLayer(markers);
});
</script>
</x-layouts.app>
