@props([
    'name' => 'screenshots',
    'label' => 'Screenshots',
    'required' => false,
    'maxFiles' => 10,
    'maxSizePerFile' => 10485760,  // 10 MB
    'accept' => 'image/*',
    'helpText' => null,
])

@php
    $componentId = 'imgp_' . \Illuminate\Support\Str::random(8);
@endphp

<div id="{{ $componentId }}" class="image-picker"
     data-max-files="{{ $maxFiles }}"
     data-max-size="{{ $maxSizePerFile }}"
     data-i18n-no-files="{{ __('upload.no_files_selected') }}"
     data-i18n-files-selected="{{ __('upload.files_selected') }}"
     data-i18n-one-file-selected="{{ __('upload.one_file_selected') }}"
     data-i18n-remove="{{ __('upload.remove_file') }}">

    <label class="block text-sm font-medium text-gray-300 mb-2">
        {{ $label }} @if($required) <span class="text-red-400">*</span> @endif
    </label>

    {{-- Hidden native input --}}
    <input type="file" name="{{ $name }}[]" multiple accept="{{ $accept }}" class="ip-input hidden">

    {{-- Custom UI --}}
    <div class="bg-gray-700 border border-gray-600 rounded-lg p-3 flex items-center gap-3">
        <button type="button" class="ip-button bg-amber-600 hover:bg-amber-500 text-white text-sm font-medium px-4 py-2 rounded transition whitespace-nowrap">
            {{ __('upload.choose_images') }}
        </button>
        <span class="ip-status text-gray-400 text-sm flex-1 truncate">{{ __('upload.no_files_selected') }}</span>
    </div>

    {{-- Preview Grid --}}
    <div class="ip-preview hidden grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 mt-3"></div>

    @if($helpText)
        <p class="text-gray-500 text-sm mt-2">{{ $helpText }}</p>
    @endif
</div>

@once
@push('scripts')
<script>
(function() {
    'use strict';

    function init(el) {
        const maxFiles = parseInt(el.dataset.maxFiles);
        const maxSize = parseInt(el.dataset.maxSize);
        const i18nNoFiles = el.dataset.i18nNoFiles;
        const i18nFilesSelected = el.dataset.i18nFilesSelected;
        const i18nOneFileSelected = el.dataset.i18nOneFileSelected;
        const i18nRemove = el.dataset.i18nRemove;

        const input = el.querySelector('.ip-input');
        const button = el.querySelector('.ip-button');
        const status = el.querySelector('.ip-status');
        const preview = el.querySelector('.ip-preview');

        let files = [];

        function syncInput() {
            const dt = new DataTransfer();
            files.forEach(f => dt.items.add(f));
            input.files = dt.files;
        }

        function updateUI() {
            if (files.length === 0) {
                status.textContent = i18nNoFiles;
                preview.classList.add('hidden');
                preview.innerHTML = '';
                return;
            }

            status.textContent = files.length === 1
                ? i18nOneFileSelected
                : i18nFilesSelected.replace(':count', files.length);

            preview.classList.remove('hidden');
            preview.innerHTML = '';

            files.forEach((file, idx) => {
                const card = document.createElement('div');
                card.className = 'relative group rounded-lg overflow-hidden border border-gray-600 bg-gray-800';

                const img = document.createElement('img');
                img.className = 'w-full h-24 object-cover';
                img.alt = file.name;
                const reader = new FileReader();
                reader.onload = (e) => { img.src = e.target.result; };
                reader.readAsDataURL(file);

                const overlay = document.createElement('div');
                overlay.className = 'absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center';

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'bg-red-600 hover:bg-red-500 text-white text-xs px-3 py-1 rounded';
                removeBtn.textContent = i18nRemove;
                removeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    files.splice(idx, 1);
                    syncInput();
                    updateUI();
                });

                const name = document.createElement('div');
                name.className = 'absolute bottom-0 left-0 right-0 bg-black/70 text-white text-xs px-2 py-1 truncate';
                name.textContent = file.name;

                overlay.appendChild(removeBtn);
                card.appendChild(img);
                card.appendChild(overlay);
                card.appendChild(name);
                preview.appendChild(card);
            });
        }

        button.addEventListener('click', () => input.click());

        input.addEventListener('change', (e) => {
            const newFiles = Array.from(e.target.files).filter(f => {
                if (f.size > maxSize) {
                    console.warn('File too large:', f.name);
                    return false;
                }
                return true;
            });

            // Append (statt replace) damit User mehrfach auswählen kann
            const remaining = maxFiles - files.length;
            files = files.concat(newFiles.slice(0, remaining));
            syncInput();
            updateUI();
        });

        updateUI();
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.image-picker').forEach(init);
    });
})();
</script>
@endpush
@endonce
