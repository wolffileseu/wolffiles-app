@props([
    'name' => 'file',           // Name des hidden field das den S3-Key trägt
    'target' => 'files',        // 'files', 'demos', oder 'fastdl'
    'accept' => '*/*',          // Accept-Pattern für File-Picker (z.B. ".pk3,.zip")
    'maxSize' => 5368709120,    // 5 GB default
    'label' => 'File',          // Label-Text
    'required' => true,
])

@php
    $componentId = 'mpu_' . \Illuminate\Support\Str::random(8);
    $maxSizeHuman = round($maxSize / 1024 / 1024 / 1024, 1) . ' GB';
@endphp

<div id="{{ $componentId }}" class="multipart-uploader"
     data-target="{{ $target }}"
     data-max-size="{{ $maxSize }}"
     data-i18n-computing-hash="{{ __('upload.computing_hash') }}"
     data-i18n-starting-upload="{{ __('upload.starting_upload') }}"
     data-i18n-uploading="{{ __('upload.uploading') }}"
     data-i18n-finalizing="{{ __('upload.finalizing') }}"
     data-i18n-too-large="{{ __('upload.too_large') }}"
     data-i18n-upload-failed="{{ __('upload.upload_failed') }}"
     data-i18n-duplicate-exists="{{ __('upload.duplicate_exists') }}"
     data-i18n-aborted="{{ __('upload.aborted') }}"
     data-i18n-network-error="{{ __('upload.network_error') }}">
    <label class="block text-sm font-medium text-gray-300 mb-2">
        {{ $label }} @if($required) <span class="text-red-400">*</span> @endif
        <span class="text-gray-500 text-xs ml-2">({{ __('upload.max_size_label') }} {{ $maxSizeHuman }})</span>
    </label>

    {{-- Hidden inputs that get filled after successful upload --}}
    <input type="hidden" name="{{ $name }}_s3_key" class="mpu-s3-key" {{ $required ? 'required' : '' }}>
    <input type="hidden" name="{{ $name }}_filename" class="mpu-filename">
    <input type="hidden" name="{{ $name }}_size" class="mpu-size">
    <input type="hidden" name="{{ $name }}_hash" class="mpu-hash">
    <input type="hidden" name="{{ $name }}_content_type" class="mpu-ctype">

    {{-- Drop zone --}}
    <div class="mpu-dropzone bg-gray-700 border-2 border-dashed border-gray-600 rounded-lg p-8 text-center cursor-pointer hover:border-amber-500 transition">
        <input type="file" class="mpu-input hidden" accept="{{ $accept }}">

        <div class="mpu-idle">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <p class="text-gray-300 font-medium">{{ __('upload.click_or_drop') }}</p>
            <p class="text-gray-500 text-sm mt-1">{{ __('upload.up_to_size', ['size' => $maxSizeHuman]) }}</p>
        </div>

        <div class="mpu-active hidden text-left">
            <div class="flex items-center justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <p class="mpu-fname text-white font-medium truncate"></p>
                    <p class="mpu-fsize text-gray-400 text-xs"></p>
                </div>
                <button type="button" class="mpu-cancel ml-3 text-red-400 hover:text-red-300 text-sm">{{ __('upload.cancel') }}</button>
            </div>

            <div class="bg-gray-800 rounded-full h-2 overflow-hidden">
                <div class="mpu-bar bg-amber-500 h-full transition-all" style="width:0%"></div>
            </div>

            <div class="flex justify-between mt-1">
                <span class="mpu-status text-gray-400 text-xs">{{ __('upload.preparing') }}</span>
                <span class="mpu-percent text-amber-400 text-xs font-mono">0%</span>
            </div>
        </div>

        <div class="mpu-done hidden">
            <svg class="mx-auto h-12 w-12 text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="mpu-done-name text-green-400 font-medium"></p>
            <p class="text-gray-400 text-xs mt-1">{{ __('upload.success') }}</p>
            <button type="button" class="mpu-reset mt-3 text-amber-400 hover:text-amber-300 text-sm underline">{{ __('upload.choose_other') }}</button>
        </div>

        <div class="mpu-error hidden">
            <svg class="mx-auto h-12 w-12 text-red-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="mpu-error-msg text-red-400 font-medium"></p>
            <button type="button" class="mpu-reset mt-3 text-amber-400 hover:text-amber-300 text-sm underline">{{ __('upload.try_again') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function() {
    'use strict';

    const PART_SIZE = 100 * 1024 * 1024; // 100MB
    const PARALLEL_PARTS = 3;             // 3 concurrent chunks

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        if (bytes < 1073741824) return (bytes / 1048576).toFixed(1) + ' MB';
        return (bytes / 1073741824).toFixed(2) + ' GB';
    }

    async function sha256(file, onProgress) {
        // Streaming SHA-256 in 4MB chunks via crypto.subtle
        // Note: crypto.subtle.digest needs the whole buffer; for large files we use a helper below
        // Falls back to chunked approach via SubtleCrypto + incremental wouldn't work directly,
        // so we read whole file in chunks but only feed digest at end. Memory is the issue.
        // Solution: use SubtleCrypto digest on full ArrayBuffer (browser handles disk-spilling)
        // For 5GB this is risky; we use a simple chunked-streaming approach via libs - but to keep
        // things lean, we hash in 64MB chunks and concatenate (NOT a real SHA - skip if too big)

        // Pragmatic: hash files up to 1GB inline; skip hash for larger (no duplicate check)
        if (file.size > 1024 * 1024 * 1024) {
            return null; // skip hash
        }

        const buf = await file.arrayBuffer();
        const hash = await crypto.subtle.digest('SHA-256', buf);
        return Array.from(new Uint8Array(hash))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    }

    async function postJSON(url, body) {
        const resp = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        });
        const data = await resp.json().catch(() => ({}));
        if (!resp.ok) {
            const err = new Error(data.message || data.error || ('HTTP ' + resp.status));
            err.status = resp.status;
            err.data = data;
            throw err;
        }
        return data;
    }

    async function uploadPart(url, blob, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('PUT', url);
            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable && onProgress) onProgress(e.loaded);
            };
            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const etag = xhr.getResponseHeader('ETag');
                    resolve(etag);
                } else {
                    reject(new Error('Part upload failed: HTTP ' + xhr.status));
                }
            };
            xhr.onerror = () => reject(new Error('Part upload network error'));
            xhr.onabort = () => reject(new Error('aborted'));
            xhr.send(blob);
            // Store xhr globally for abort
            uploadPart._current = uploadPart._current || [];
            uploadPart._current.push(xhr);
        });
    }

    function init(el) {
        const target = el.dataset.target;
        const maxSize = parseInt(el.dataset.maxSize);
        const dropzone = el.querySelector('.mpu-dropzone');
        const input = el.querySelector('.mpu-input');
        const idle = el.querySelector('.mpu-idle');
        const active = el.querySelector('.mpu-active');
        const done = el.querySelector('.mpu-done');
        const errorBox = el.querySelector('.mpu-error');
        const fname = el.querySelector('.mpu-fname');
        const fsize = el.querySelector('.mpu-fsize');
        const bar = el.querySelector('.mpu-bar');
        const status = el.querySelector('.mpu-status');
        const percent = el.querySelector('.mpu-percent');
        const doneName = el.querySelector('.mpu-done-name');
        const errorMsg = el.querySelector('.mpu-error-msg');

        const hiddenKey = el.querySelector('.mpu-s3-key');
        const hiddenName = el.querySelector('.mpu-filename');
        const hiddenSize = el.querySelector('.mpu-size');
        const hiddenHash = el.querySelector('.mpu-hash');
        const hiddenCtype = el.querySelector('.mpu-ctype');

        let activeXHRs = [];
        let aborted = false;
        let currentUpload = null;

        function showState(state) {
            idle.classList.toggle('hidden', state !== 'idle');
            active.classList.toggle('hidden', state !== 'active');
            done.classList.toggle('hidden', state !== 'done');
            errorBox.classList.toggle('hidden', state !== 'error');
        }

        function reset() {
            aborted = true;
            activeXHRs.forEach(x => { try { x.abort(); } catch (e) {} });
            activeXHRs = [];
            if (currentUpload && currentUpload.uploadId && currentUpload.key) {
                postJSON('/upload-api/abort', {
                    uploadId: currentUpload.uploadId,
                    key: currentUpload.key
                }).catch(() => {});
            }
            currentUpload = null;
            input.value = '';
            hiddenKey.value = '';
            hiddenName.value = '';
            hiddenSize.value = '';
            hiddenHash.value = '';
            hiddenCtype.value = '';
            bar.style.width = '0%';
            percent.textContent = '0%';
            showState('idle');
            aborted = false;
        }

        async function startUpload(file) {
            if (file.size > maxSize) {
                errorMsg.textContent = (el.dataset.i18nTooLarge || 'File too large (:size). Maximum: :max').replace(':size', formatBytes(file.size)).replace(':max', formatBytes(maxSize));
                showState('error');
                return;
            }

            fname.textContent = file.name;
            fsize.textContent = formatBytes(file.size);
            showState('active');
            aborted = false;
            activeXHRs = [];

            try {
                // 1. Hash berechnen
                status.textContent = el.dataset.i18nComputingHash || 'Computing file hash…';
                bar.style.width = '5%';
                let hash = null;
                try { hash = await sha256(file); } catch (e) { console.warn('Hash failed:', e); }
                if (aborted) return;

                // 2. Init Multipart
                status.textContent = el.dataset.i18nStartingUpload || 'Starting upload…';
                bar.style.width = '10%';

                let initData;
                try {
                    initData = await postJSON('/upload-api/init', {
                        filename: file.name,
                        size: file.size,
                        content_type: file.type || 'application/octet-stream',
                        target: target,
                        file_hash: hash,
                    });
                } catch (e) {
                    if (e.status === 409 && e.data && e.data.error === 'duplicate') {
                        errorMsg.innerHTML = (el.dataset.i18nDuplicateExists || 'This file already exists: :title').replace(':title', '<strong>' + (e.data.existing_title || '') + '</strong>');
                        showState('error');
                        return;
                    }
                    throw e;
                }

                if (aborted) {
                    postJSON('/upload-api/abort', { uploadId: initData.uploadId, key: initData.key }).catch(() => {});
                    return;
                }

                currentUpload = initData;

                // 3. Datei in Chunks zerlegen + parallel hochladen
                const totalParts = Math.ceil(file.size / PART_SIZE);
                const parts = [];
                const partProgress = new Array(totalParts).fill(0);

                function updateProgress() {
                    const total = partProgress.reduce((a, b) => a + b, 0);
                    const pct = Math.min(99, Math.round((total / file.size) * 90 + 10));
                    bar.style.width = pct + '%';
                    percent.textContent = pct + '%';
                    status.textContent = (el.dataset.i18nUploading || 'Uploading…') + ' (' + formatBytes(total) + ' / ' + formatBytes(file.size) + ')';
                }

                async function uploadOnePart(partNum) {
                    const start = (partNum - 1) * PART_SIZE;
                    const end = Math.min(start + PART_SIZE, file.size);
                    const blob = file.slice(start, end);

                    const signResp = await postJSON('/upload-api/sign', {
                        uploadId: initData.uploadId,
                        key: initData.key,
                        partNumber: partNum,
                    });
                    if (aborted) throw new Error('aborted');

                    const etag = await new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        activeXHRs.push(xhr);
                        xhr.open('PUT', signResp.url);
                        xhr.upload.onprogress = (e) => {
                            if (e.lengthComputable) {
                                partProgress[partNum - 1] = e.loaded;
                                updateProgress();
                            }
                        };
                        xhr.onload = () => {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                partProgress[partNum - 1] = blob.size;
                                resolve(xhr.getResponseHeader('ETag'));
                            } else {
                                reject(new Error('Part ' + partNum + ' failed: HTTP ' + xhr.status));
                            }
                        };
                        xhr.onerror = () => reject(new Error('Part ' + partNum + ' network error'));
                        xhr.onabort = () => reject(new Error('aborted'));
                        xhr.send(blob);
                    });

                    return { PartNumber: partNum, ETag: etag };
                }

                // Parallel-Upload mit Pool
                let nextPart = 1;
                const inFlight = [];
                const completed = [];

                async function worker() {
                    while (nextPart <= totalParts && !aborted) {
                        const myPart = nextPart++;
                        const result = await uploadOnePart(myPart);
                        completed.push(result);
                    }
                }

                const workers = [];
                for (let i = 0; i < Math.min(PARALLEL_PARTS, totalParts); i++) {
                    workers.push(worker());
                }
                await Promise.all(workers);

                if (aborted) return;

                // 4. Complete
                status.textContent = el.dataset.i18nFinalizing || 'Finalizing…';
                bar.style.width = '99%';

                const completeResp = await postJSON('/upload-api/complete', {
                    uploadId: initData.uploadId,
                    key: initData.key,
                    parts: completed,
                });

                // 5. Hidden Inputs füllen
                hiddenKey.value = completeResp.key;
                hiddenName.value = completeResp.filename;
                hiddenSize.value = completeResp.size;
                hiddenHash.value = completeResp.file_hash || '';
                hiddenCtype.value = completeResp.content_type || '';

                bar.style.width = '100%';
                percent.textContent = '100%';
                doneName.textContent = file.name;
                showState('done');
                currentUpload = null;
            } catch (err) {
                if (err.message === 'aborted') {
                    return;
                }
                console.error('Upload error:', err);
                errorMsg.textContent = err.message || el.dataset.i18nUploadFailed || 'Upload failed';
                showState('error');
                if (currentUpload && currentUpload.uploadId) {
                    postJSON('/upload-api/abort', {
                        uploadId: currentUpload.uploadId,
                        key: currentUpload.key
                    }).catch(() => {});
                }
                currentUpload = null;
            }
        }

        // Event Wiring
        dropzone.addEventListener('click', (e) => {
            if (e.target.closest('.mpu-cancel') || e.target.closest('.mpu-reset')) return;
            if (active.classList.contains('hidden') === false) return; // disable during upload
            input.click();
        });

        input.addEventListener('change', (e) => {
            if (e.target.files.length > 0) startUpload(e.target.files[0]);
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('border-amber-500');
        });
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('border-amber-500');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-amber-500');
            if (e.dataTransfer.files.length > 0) startUpload(e.dataTransfer.files[0]);
        });

        el.querySelectorAll('.mpu-cancel, .mpu-reset').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                reset();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.multipart-uploader').forEach(init);
    });
})();
</script>
@endpush
@endonce
