<x-layouts.app>

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .et-0{color:#000}.et-1{color:#F00}.et-2{color:#0F0}.et-3{color:#FF0}.et-4{color:#00F}
    .et-5{color:#0FF}.et-6{color:#F0F}.et-7{color:#FFF}.et-8{color:#FF7F00}.et-9{color:#808080}
    .et-preview{font-family:'Courier New',monospace;font-size:1.6rem;font-weight:bold;letter-spacing:.5px;text-shadow:1px 1px 3px rgba(0,0,0,.8);line-height:2;min-height:3rem}
    .color-btn{width:32px;height:32px;border-radius:6px;border:2px solid transparent;cursor:pointer;transition:all .15s;font-size:10px;font-weight:bold;font-family:monospace;display:flex;align-items:center;justify-content:center}
    .color-btn:hover{transform:scale(1.15);border-color:rgba(255,255,255,.5);z-index:1}
    .preset-card{transition:all .2s;cursor:pointer}.preset-card:hover{border-color:rgba(217,119,6,.4);transform:translateY(-1px)}
    .char-block{display:inline-block}
</style>
@endpush

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="nicknameGenerator()" x-cloak>

    {{-- HEADER --}}
    <div class="text-center mb-8">
        <p class="text-xs font-mono tracking-[0.25em] uppercase text-amber-500 mb-1">{{ __('messages.ng_tools_label') }}</p>
        <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-gray-200 via-amber-500 to-amber-400 bg-clip-text text-transparent">
            {{ __('messages.ng_title') }}
        </h1>
        <p class="text-gray-400 text-sm mt-2">{{ __('messages.ng_subtitle') }}</p>
        <div class="w-24 h-0.5 bg-gradient-to-r from-transparent via-amber-600 to-transparent mx-auto mt-4"></div>
    </div>

    {{-- LIVE PREVIEW --}}
    <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 mb-6 relative overflow-hidden">
        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-600/40 to-transparent"></div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-200 flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs">👁</span>
                {{ __('messages.ng_live_preview') }}
            </h2>
            <span class="text-[10px] font-mono text-gray-500" x-text="nickname.length + '/36 chars'"></span>
        </div>
        <div class="bg-gray-950 border border-gray-700 rounded-lg p-5 flex items-center justify-center min-h-[80px]">
            <div class="et-preview" x-html="renderedPreview"></div>
        </div>
        <div class="mt-3 flex items-center gap-2">
            <code class="flex-1 px-3 py-1.5 bg-gray-900 border border-gray-700 rounded-lg text-xs font-mono text-amber-400 overflow-x-auto whitespace-nowrap"
                  x-text="nickname || '{{ __('messages.ng_type_something') }}'"></code>
            <button @click="copyNickname()"
                    class="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-black text-xs font-semibold rounded-lg transition flex items-center gap-1 flex-shrink-0">
                <span x-text="copied ? '✓' : '📋'"></span>
                <span x-text="copied ? '{{ __('messages.ng_copied') }}' : '{{ __('messages.ng_copy') }}'"></span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-5">

            {{-- INPUT --}}
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-5 relative overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-600/40 to-transparent"></div>
                <h3 class="text-sm font-semibold text-gray-200 flex items-center gap-2 mb-3">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs">✏</span>
                    {{ __('messages.ng_your_nickname') }}
                </h3>
                <input type="text" x-model="nickname" @input="updatePreview()" maxlength="128"
                       class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-amber-300 text-lg font-mono focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 outline-none transition"
                       placeholder="{{ __('messages.ng_placeholder') }}">
                <p class="text-xs text-gray-500 mt-2 italic">{{ __('messages.ng_input_hint') }}</p>
            </div>

            {{-- COLOR PALETTE --}}
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-5 relative overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-600/40 to-transparent"></div>
                <h3 class="text-sm font-semibold text-gray-200 flex items-center gap-2 mb-1">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs">🎨</span>
                    {{ __('messages.ng_color_palette') }}
                </h3>
                <p class="text-xs text-gray-500 mb-3">{{ __('messages.ng_click_color') }}</p>

                <div class="mb-3">
                    <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1.5">{{ __('messages.ng_standard_colors') }} (^0–^9)</div>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="c in standardColors" :key="c.code">
                            <button @click="insertColor(c.code)" class="color-btn"
                                    :style="'background:'+c.hex+';color:'+(c.dark?'#fff':'#000')"
                                    :title="'^'+c.code+' — '+c.name" x-text="'^'+c.code"></button>
                        </template>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1.5">{{ __('messages.ng_extended_colors') }} (^a–^z)</div>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="c in extendedColors" :key="c.code">
                            <button @click="insertColor(c.code)" class="color-btn"
                                    :style="'background:'+c.hex+';color:'+(c.dark?'#fff':'#000')"
                                    :title="'^'+c.code+' — '+c.name" x-text="'^'+c.code"></button>
                        </template>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 pt-3 border-t border-gray-700">
                    <button @click="applyRainbow()" class="px-3 py-1.5 text-xs font-medium border border-gray-700 rounded-lg text-gray-400 hover:text-gray-200 hover:border-gray-600 transition">
                        🌈 {{ __('messages.ng_rainbow') }}
                    </button>
                    <button @click="applyGradient()" class="px-3 py-1.5 text-xs font-medium border border-gray-700 rounded-lg text-gray-400 hover:text-gray-200 hover:border-gray-600 transition">
                        🎨 {{ __('messages.ng_gradient') }}
                    </button>
                    <button @click="applyRandom()" class="px-3 py-1.5 text-xs font-medium border border-gray-700 rounded-lg text-gray-400 hover:text-gray-200 hover:border-gray-600 transition">
                        🎲 {{ __('messages.ng_random') }}
                    </button>
                    <button @click="applyAlternate()" class="px-3 py-1.5 text-xs font-medium border border-gray-700 rounded-lg text-gray-400 hover:text-gray-200 hover:border-gray-600 transition">
                        🔀 {{ __('messages.ng_alternate') }}
                    </button>
                    <button @click="nickname='';updatePreview()" class="px-3 py-1.5 text-xs font-medium border border-red-900/30 rounded-lg text-red-500 hover:bg-red-900/10 transition">
                        ✕ {{ __('messages.ng_clear') }}
                    </button>
                </div>
            </div>

            {{-- GRADIENT BUILDER --}}
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-5 relative overflow-hidden" x-show="showGradient" x-transition>
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-600/40 to-transparent"></div>
                <h3 class="text-sm font-semibold text-gray-200 flex items-center gap-2 mb-3">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs">🎨</span>
                    {{ __('messages.ng_gradient_builder') }}
                </h3>
                <div class="grid grid-cols-2 gap-4 mb-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-1">{{ __('messages.ng_start_color') }}</label>
                        <select x-model="gradientStart" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 text-sm focus:border-amber-500 outline-none appearance-none">
                            <template x-for="c in allColors" :key="'gs'+c.code"><option :value="c.code" x-text="'^'+c.code+' — '+c.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-400 mb-1">{{ __('messages.ng_end_color') }}</label>
                        <select x-model="gradientEnd" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 text-sm focus:border-amber-500 outline-none appearance-none">
                            <template x-for="c in allColors" :key="'ge'+c.code"><option :value="c.code" x-text="'^'+c.code+' — '+c.name"></option></template>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2">
                    <input type="text" x-model="gradientText" placeholder="{{ __('messages.ng_gradient_text_placeholder') }}"
                           class="flex-1 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-200 text-sm font-mono focus:border-amber-500 outline-none">
                    <button @click="buildGradient()" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-black font-semibold text-sm rounded-lg transition">
                        {{ __('messages.ng_apply') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- SIDEBAR --}}
        <div class="space-y-5">
            {{-- PRESETS --}}
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-5 relative overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-600/40 to-transparent"></div>
                <h3 class="text-sm font-semibold text-gray-200 flex items-center gap-2 mb-3">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs">⚡</span>
                    {{ __('messages.ng_presets') }}
                </h3>
                <div class="space-y-2">
                    <template x-for="(preset,pi) in presets" :key="pi">
                        <div @click="nickname=preset.code;updatePreview()" class="preset-card bg-gray-900 border border-gray-700 rounded-lg p-3">
                            <div class="et-preview text-sm mb-1" x-html="renderCode(preset.code)" style="font-size:.9rem"></div>
                            <div class="text-[10px] font-mono text-gray-600 truncate" x-text="preset.code"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- COLOR REFERENCE --}}
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-5 relative overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-600/40 to-transparent"></div>
                <h3 class="text-sm font-semibold text-gray-200 flex items-center gap-2 mb-3">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs">📖</span>
                    {{ __('messages.ng_color_reference') }}
                </h3>
                <div class="space-y-1 max-h-64 overflow-y-auto pr-1">
                    <template x-for="c in allColors" :key="'ref'+c.code">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-4 h-4 rounded flex-shrink-0" :style="'background:'+c.hex"></span>
                            <code class="text-amber-400 font-mono w-6" x-text="'^'+c.code"></code>
                            <span class="text-gray-500" x-text="c.name"></span>
                            <span class="text-gray-700 ml-auto font-mono text-[10px]" x-text="c.hex"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- TIPS --}}
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-5 relative overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-amber-600/40 to-transparent"></div>
                <h3 class="text-sm font-semibold text-gray-200 flex items-center gap-2 mb-3">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs">💡</span>
                    {{ __('messages.ng_tips') }}
                </h3>
                <div class="space-y-2 text-xs text-gray-400">
                    <p>{{ __('messages.ng_tip_1') }}</p>
                    <p>{{ __('messages.ng_tip_2') }}</p>
                    <p>{{ __('messages.ng_tip_3') }}</p>
                    <p>{{ __('messages.ng_tip_4') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div x-show="toast" x-transition class="fixed bottom-6 right-6 px-4 py-2.5 bg-gray-800 border border-emerald-500 rounded-lg text-emerald-400 text-sm font-medium shadow-xl z-50">
        <span x-text="'✓ '+toastMsg"></span>
    </div>
</div>

<script>
function nicknameGenerator(){return{
nickname:'',renderedPreview:'<span class="text-gray-600">{{ __('messages.ng_preview_placeholder') }}</span>',
copied:false,toast:false,toastMsg:'',showGradient:false,gradientStart:'1',gradientEnd:'4',gradientText:'',
standardColors:[
{code:'0',hex:'#000000',name:'Black',dark:true},{code:'1',hex:'#FF0000',name:'Red',dark:false},
{code:'2',hex:'#00FF00',name:'Green',dark:false},{code:'3',hex:'#FFFF00',name:'Yellow',dark:false},
{code:'4',hex:'#0000FF',name:'Blue',dark:true},{code:'5',hex:'#00FFFF',name:'Cyan',dark:false},
{code:'6',hex:'#FF00FF',name:'Magenta',dark:false},{code:'7',hex:'#FFFFFF',name:'White',dark:false},
{code:'8',hex:'#FF7F00',name:'Orange',dark:false},{code:'9',hex:'#808080',name:'Gray',dark:true}],
extendedColors:[
{code:'a',hex:'#BFBFBF',name:'Light Gray',dark:false},{code:'b',hex:'#BFBFBF',name:'Light Gray 2',dark:false},
{code:'c',hex:'#007F00',name:'Dark Green',dark:true},{code:'d',hex:'#7F7F00',name:'Dark Yellow',dark:true},
{code:'e',hex:'#00007F',name:'Dark Blue',dark:true},{code:'f',hex:'#7F0000',name:'Dark Red',dark:true},
{code:'g',hex:'#7F3F00',name:'Brown',dark:true},{code:'h',hex:'#FF9933',name:'Light Orange',dark:false},
{code:'i',hex:'#007F7F',name:'Teal',dark:true},{code:'j',hex:'#7F007F',name:'Purple',dark:true},
{code:'k',hex:'#007FFF',name:'Sky Blue',dark:false},{code:'l',hex:'#7F00FF',name:'Violet',dark:true},
{code:'m',hex:'#3399CC',name:'Steel Blue',dark:false},{code:'n',hex:'#CCFFCC',name:'Mint',dark:false},
{code:'o',hex:'#006633',name:'Forest',dark:true},{code:'p',hex:'#FF0033',name:'Crimson',dark:false},
{code:'q',hex:'#B21919',name:'Maroon',dark:true},{code:'r',hex:'#993300',name:'Rust',dark:true},
{code:'s',hex:'#CC9933',name:'Gold',dark:false},{code:'t',hex:'#999933',name:'Olive',dark:true},
{code:'u',hex:'#FFFFBF',name:'Light Yellow',dark:false},{code:'v',hex:'#FFFF7F',name:'Pale Yellow',dark:false},
{code:'w',hex:'#BFBFBF',name:'Silver',dark:false},{code:'x',hex:'#7F7F7F',name:'Medium Gray',dark:true},
{code:'y',hex:'#3F3F3F',name:'Dark Gray',dark:true},{code:'z',hex:'#000000',name:'Black 2',dark:true}],
presets:[
{code:'^1W^3o^2l^5f^4f^6i^1l^3e^2s'},{code:'^1►^7 Pro ^1G^3a^2m^5e^4r'},
{code:'^4[^7WF^4] ^3Player'},{code:'^8::^1F^7r^4a^1g^7g^4e^1r^8::'},
{code:'^0[^7SniPer^0]^1Elite'},{code:'^6*^5*^6* ^7MeDiC ^6*^5*^6*'},
{code:'^1E^2n^3e^4m^5y ^6D^7o^8w^9n'},{code:'^k>>^7Shadow^k<<'},
{code:'^s★ ^7Wolffiles ^s★'},{code:'^p♥^7Killer^p♥'}],
colorMap:{},
get allColors(){return[...this.standardColors,...this.extendedColors]},
init(){this.allColors.forEach(c=>this.colorMap[c.code]=c.hex);this.updatePreview()},
insertColor(code){const i=this.$el.querySelector('input[type="text"]');if(!i){this.nickname+='^'+code;this.updatePreview();return}
const s=i.selectionStart||this.nickname.length,e=i.selectionEnd||this.nickname.length;
this.nickname=this.nickname.substring(0,s)+'^'+code+this.nickname.substring(e);this.updatePreview();
this.$nextTick(()=>{const p=s+2;i.setSelectionRange(p,p);i.focus()})},
updatePreview(){this.renderedPreview=this.nickname?this.renderCode(this.nickname):'<span class="text-gray-600">{{ __('messages.ng_preview_placeholder') }}</span>'},
renderCode(code){if(!code)return'';let h='',cc='#FFFFFF',i=0;while(i<code.length){if(code[i]==='^'&&i+1<code.length){
const c=code[i+1].toLowerCase();if(this.colorMap[c]){cc=this.colorMap[c];i+=2;continue}}
const ch=code[i]===' '?'&nbsp;':this.esc(code[i]);h+=`<span style="color:${cc}">${ch}</span>`;i++}return h},
esc(t){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[t]||t},
stripColors(t){return t.replace(/\^[0-9a-zA-Z]/g,'')},
applyRainbow(){const p=this.stripColors(this.nickname)||'Rainbow',c=['1','8','3','2','5','4','6'];let r='',i=0;
for(const ch of p){if(ch===' '){r+=' ';continue}r+='^'+c[i%c.length]+ch;i++}this.nickname=r;this.updatePreview()},
applyGradient(){this.gradientText=this.stripColors(this.nickname)||'';this.showGradient=!this.showGradient},
buildGradient(){const t=this.gradientText||this.stripColors(this.nickname)||'Gradient';
const sH=this.colorMap[this.gradientStart],eH=this.colorMap[this.gradientEnd];if(!sH||!eH)return;
const chars=[...t].filter(c=>c!==' '),len=chars.length,codes=this.gradCodes(this.gradientStart,this.gradientEnd,len);
let r='',gi=0;for(const ch of t){if(ch===' '){r+=' ';continue}r+='^'+codes[gi]+ch;gi++}
this.nickname=r;this.updatePreview();this.showGradient=false},
gradCodes(sc,ec,n){if(n<=1)return[sc];const sH=this.colorMap[sc],eH=this.colorMap[ec];
const sR=parseInt(sH.slice(1,3),16),sG=parseInt(sH.slice(3,5),16),sB=parseInt(sH.slice(5,7),16);
const eR=parseInt(eH.slice(1,3),16),eG=parseInt(eH.slice(3,5),16),eB=parseInt(eH.slice(5,7),16);
const codes=[];for(let i=0;i<n;i++){const t=i/(n-1);codes.push(this.closest(
Math.round(sR+(eR-sR)*t),Math.round(sG+(eG-sG)*t),Math.round(sB+(eB-sB)*t)))}return codes},
closest(r,g,b){let best='7',bd=Infinity;for(const c of this.allColors){const h=c.hex;
const cr=parseInt(h.slice(1,3),16),cg=parseInt(h.slice(3,5),16),cb=parseInt(h.slice(5,7),16);
const d=(r-cr)**2+(g-cg)**2+(b-cb)**2;if(d<bd){bd=d;best=c.code}}return best},
applyRandom(){const p=this.stripColors(this.nickname)||'Random',c=this.allColors.map(c=>c.code);let r='';
for(const ch of p){if(ch===' '){r+=' ';continue}r+='^'+c[Math.floor(Math.random()*c.length)]+ch}this.nickname=r;this.updatePreview()},
applyAlternate(){const p=this.stripColors(this.nickname)||'Alternate';let r='',i=0;
for(const ch of p){if(ch===' '){r+=' ';continue}r+='^'+(i%2===0?'1':'7')+ch;i++}this.nickname=r;this.updatePreview()},
async copyNickname(){try{await navigator.clipboard.writeText(this.nickname)}catch(e){const a=document.createElement('textarea');
a.value=this.nickname;document.body.appendChild(a);a.select();document.execCommand('copy');document.body.removeChild(a)}
this.copied=true;this.showToast('{{ __('messages.ng_copied_toast') }}');setTimeout(()=>this.copied=false,2000)},
showToast(m){this.toastMsg=m;this.toast=true;setTimeout(()=>this.toast=false,2500)}}}
</script>
</x-layouts.app>
