<div>
<x-filament-panels::page>
@php
    $stats = $this->getLanguageStats();
    $rows  = $this->getTranslationRows();
    $langNames = [
        'de'=>'🇩🇪 Deutsch','fr'=>'🇫🇷 Français','nl'=>'🇳🇱 Nederlands',
        'pl'=>'🇵🇱 Polski','tr'=>'🇹🇷 Türkçe','es'=>'🇪🇸 Español',
        'it'=>'🇮🇹 Italiano','pt'=>'🇵🇹 Português','ru'=>'🇷🇺 Русский',
    ];
@endphp

<style>
.tm-wrap{display:flex;gap:0;height:calc(100vh - 160px);overflow:hidden;border-radius:12px;border:1px solid rgb(55,65,81)}
.tm-sidebar{width:220px;flex-shrink:0;background:rgb(17,24,39);border-right:1px solid rgb(55,65,81);display:flex;flex-direction:column;overflow:hidden}
.tm-sidebar-head{padding:14px 16px 10px;border-bottom:1px solid rgb(55,65,81)}
.tm-sidebar-title{font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:rgb(107,114,128);margin-bottom:8px}
.tm-overall-bar{height:4px;background:rgb(55,65,81);border-radius:2px;overflow:hidden;margin-bottom:4px}
.tm-overall-fill{height:100%;background:linear-gradient(90deg,#f59e0b,#fcd34d);border-radius:2px;transition:width .4s}
.tm-overall-stats{display:flex;justify-content:space-between;font-size:10px;color:rgb(107,114,128);font-family:monospace}
.tm-lang-list{flex:1;overflow-y:auto;padding:4px 0}
.tm-lang-item{display:flex;align-items:center;gap:8px;padding:9px 14px;cursor:pointer;border-left:3px solid transparent;transition:all .15s}
.tm-lang-item:hover{background:rgb(31,41,55)}
.tm-lang-item.active{background:rgb(31,41,55);border-left-color:#f59e0b}
.tm-lang-flag{font-size:18px}
.tm-lang-info{flex:1;min-width:0}
.tm-lang-name{font-size:12px;font-weight:600;color:rgb(229,231,235)}
.tm-lang-sub{display:flex;align-items:center;gap:5px;margin-top:2px}
.tm-lang-pct{font-size:10px;font-family:monospace;color:rgb(107,114,128)}
.tm-lang-mini{flex:1;height:3px;background:rgb(55,65,81);border-radius:2px;overflow:hidden}
.tm-lang-mini-fill{height:100%;border-radius:2px;transition:width .3s}
.tm-sidebar-footer{padding:10px 12px;border-top:1px solid rgb(55,65,81);display:flex;flex-direction:column;gap:6px}
.tm-editor{flex:1;display:flex;flex-direction:column;overflow:hidden;background:rgb(11,13,17)}
.tm-toolbar{display:flex;align-items:center;gap:8px;padding:10px 14px;background:rgb(17,24,39);border-bottom:1px solid rgb(55,65,81);flex-wrap:wrap}
.tm-lang-badge{display:flex;align-items:center;gap:6px;padding:5px 12px;background:rgb(31,41,55);border:1px solid rgb(55,65,81);border-radius:8px;font-size:13px;font-weight:600;color:#f59e0b;white-space:nowrap}
.tm-search{flex:1;min-width:120px;background:rgb(31,41,55);border:1px solid rgb(55,65,81);border-radius:8px;padding:6px 12px;color:rgb(229,231,235);font-size:13px;outline:none}
.tm-search:focus{border-color:#f59e0b}
.tm-filter{background:rgb(31,41,55);border:1px solid rgb(55,65,81);border-radius:8px;padding:6px 10px;color:rgb(229,231,235);font-size:12px;outline:none}
.tm-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:8px;border:1px solid rgb(55,65,81);background:rgb(31,41,55);color:rgb(229,231,235);font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap;text-decoration:none}
.tm-btn:hover{background:rgb(55,65,81)}.tm-btn.ai{border-color:rgba(59,130,246,.4);color:rgb(96,165,250)}.tm-btn.ai:hover{background:rgba(59,130,246,.1)}
.tm-table-wrap{flex:1;overflow-y:auto}
.tm-table{width:100%;border-collapse:collapse;table-layout:fixed}
.tm-thead{position:sticky;top:0;z-index:10;background:rgb(17,24,39);border-bottom:2px solid rgb(55,65,81)}
.tm-table th{text-align:left;padding:8px 12px;font-size:10px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:rgb(107,114,128)}
.tm-table td{padding:0;border-bottom:1px solid rgb(31,41,55);vertical-align:top}
.tm-table tr:hover td{background:rgba(255,255,255,.015)}
.tm-key-cell{padding:10px 12px;font-family:monospace;font-size:11px;color:#f59e0b;word-break:break-all;display:flex;align-items:flex-start;gap:6px}
.tm-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;margin-top:2px}
.tm-dot.ok{background:#22c55e}.tm-dot.todo{background:#f59e0b}.tm-dot.missing{background:#ef4444}
.tm-ta{width:100%;background:transparent;border:1px solid transparent;border-radius:5px;color:rgb(229,231,235);font-family:inherit;font-size:13px;padding:8px 10px;outline:none;resize:none;line-height:1.5;transition:all .15s;display:block;min-height:38px}
.tm-ta:focus{background:rgb(31,41,55);border-color:rgb(59,130,246)}
.tm-ta.en-field{color:rgb(156,163,175)}
.tm-ta.en-field:focus{border-color:#22c55e}
.tm-ai-btn{width:26px;height:26px;border-radius:5px;border:1px solid rgb(55,65,81);background:rgb(31,41,55);color:rgb(107,114,128);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;transition:all .15s;margin:8px auto}
.tm-ai-btn:hover{color:rgb(96,165,250);border-color:rgba(59,130,246,.4);background:rgba(59,130,246,.1)}
.tm-statsbar{display:flex;align-items:center;gap:14px;padding:7px 14px;background:rgb(17,24,39);border-top:1px solid rgb(55,65,81);font-size:11px;color:rgb(107,114,128);font-family:monospace;flex-shrink:0}
.tm-statdot{width:6px;height:6px;border-radius:50%;display:inline-block;margin-right:4px}
.tm-add-lang{padding:8px 12px;border-top:1px solid rgb(55,65,81)}
.tm-add-lang input{width:100%;background:rgb(31,41,55);border:1px solid rgb(55,65,81);border-radius:6px;padding:5px 8px;color:rgb(229,231,235);font-size:12px;outline:none;margin-bottom:5px}
</style>

<div class="tm-wrap">
  {{-- SIDEBAR --}}
  <div class="tm-sidebar">
    <div class="tm-sidebar-head">
      <div class="tm-sidebar-title">Sprachen</div>
      @php
        $totalDone = array_sum(array_column($stats, 'translated'));
        $totalAll  = array_sum(array_column($stats, 'total'));
        $overallPct = $totalAll ? round($totalDone/$totalAll*100) : 0;
      @endphp
      <div class="tm-overall-bar"><div class="tm-overall-fill" style="width:{{$overallPct}}%"></div></div>
      <div class="tm-overall-stats"><span>{{$totalDone}}/{{$totalAll}}</span><span>{{$overallPct}}%</span></div>
    </div>

    <div class="tm-lang-list">
      @foreach($stats as $lang => $s)
      @php $cls = $s['percent']>=80?'#22c55e':($s['percent']>=50?'#f59e0b':'#ef4444'); @endphp
      <div class="tm-lang-item {{ $selectedLang===$lang?'active':'' }}" wire:click="$set('selectedLang','{{$lang}}')">
        <span class="tm-lang-flag">{{ $langNames[$lang] ? mb_substr($langNames[$lang],0,2) : '🌐' }}</span>
        <div class="tm-lang-info">
          <div class="tm-lang-name">{{ $langNames[$lang] ?? strtoupper($lang) }}</div>
          <div class="tm-lang-sub">
            <span class="tm-lang-pct">{{$s['done'] ?? $s['translated']}}/{{$s['total']}}</span>
            <div class="tm-lang-mini"><div class="tm-lang-mini-fill" style="width:{{$s['percent']}}%;background:{{$cls}}"></div></div>
            <span class="tm-lang-pct">{{$s['percent']}}%</span>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    <div class="tm-add-lang">
      <div style="font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:rgb(107,114,128);margin-bottom:6px">+ Sprache</div>
      <input type="text" wire:model="newLangCode" placeholder="z.B. it" maxlength="3">
      <button wire:click="addLanguage" class="tm-btn" style="width:100%;justify-content:center">Hinzufügen</button>
    </div>

    <div class="tm-sidebar-footer">
      <button wire:click="syncAll" class="tm-btn" style="font-size:11px;justify-content:center">🔄 Sync alle</button>
    </div>
  </div>

  {{-- EDITOR --}}
  <div class="tm-editor">
    <div class="tm-toolbar">
      <div class="tm-lang-badge">{{ $langNames[$selectedLang] ?? strtoupper($selectedLang) }}</div>
      <input class="tm-search" wire:model.live.debounce.300ms="search" placeholder="🔍 Key oder Text suchen...">
      <select class="tm-filter" wire:model.live="filter">
        <option value="all">Alle Keys</option>
        <option value="missing">Nur TODO</option>
        <option value="translated">Fertig</option>
      </select>
      <button wire:click="aiTranslateAll" class="tm-btn ai">✦ AI: alle TODO</button>
      <a href="#" wire:click.prevent="exportJson('{{$selectedLang}}')" class="tm-btn">⬇ JSON</a>
      <a href="#" wire:click.prevent="exportCsv('{{$selectedLang}}')" class="tm-btn">⬇ CSV</a>
      <label class="tm-btn" style="cursor:pointer">📤 Import JSON<input type="file" wire:model="importJsonFile" accept=".json" class="hidden"></label>
      @if($importJsonFile)<button wire:click="importJson" class="tm-btn ai">✓ Import</button>@endif
    </div>

    <div class="tm-table-wrap">
      <table class="tm-table">
        <thead class="tm-thead">
          <tr>
            <th style="width:170px">Key</th>
            <th>🇬🇧 English <span style="color:#22c55e;font-size:9px">● editierbar</span></th>
            <th>{{ $langNames[$selectedLang] ?? strtoupper($selectedLang) }}</th>
            <th style="width:44px">AI</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $key => $row)
          @php
            $dotCls = $row['status']==='translated'?'ok':($row['status']==='todo'?'todo':'missing');
            $trVal  = ($row['status']==='todo') ? '' : ($row['value'] ?? '');
          @endphp
          <tr>
            <td>
              <div class="tm-key-cell">
                <span class="tm-dot {{$dotCls}}"></span>
                {{ $row['key'] }}
              </div>
            </td>
            <td>
              <textarea class="tm-ta en-field"
                wire:blur="saveEnglish('{{ $row['key'] }}', $event.target.value)"
                rows="1"
                oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
              >{{ $row['en'] }}</textarea>
            </td>
            <td>
              <textarea class="tm-ta"
                wire:blur="saveTranslation('{{ $row['key'] }}', $event.target.value)"
                rows="1"
                placeholder="Übersetzung..."
                oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
              >{{ $trVal }}</textarea>
            </td>
            <td>
              <button class="tm-ai-btn" wire:click="aiTranslate('{{ $row['key'] }}')" title="AI übersetzen">✦</button>
            </td>
          </tr>
          @empty
          <tr><td colspan="4" style="padding:32px;text-align:center;color:rgb(107,114,128)">Keine Keys gefunden.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="tm-statsbar">
      <span><span class="tm-statdot" style="background:#22c55e"></span>{{ collect($rows)->where('status','translated')->count() }} fertig</span>
      <span><span class="tm-statdot" style="background:#ef4444"></span>{{ collect($rows)->whereIn('status',['todo','missing'])->count() }} TODO</span>
      <span>{{ count($rows) }} angezeigt</span>
      <span style="margin-left:auto">Auto-Save aktiv ✓</span>
    </div>
  </div>
</div>

</x-filament-panels::page>
</div>
