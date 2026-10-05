@extends('partials.layout')
@section('title', 'Add Details to ' . $subcategory->title)
@section('container')

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-header bg-gradient-primary text-black">
                        Add {{ request('origin') === 'video' ? 'Videos' : 'Images' }} & Description for {{ $subcategory->title }}
                    </div>

                    <div class="card-body p-4">
                        <form id="saveDetailsForm" action="{{ route('subcategories.saveDetails', $subcategory->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="origin" value="{{ request('origin') }}">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" rows="4" class="form-control">{{ old('description', $subcategory->description) }}</textarea>
                            </div>

                            @if ((request('origin') === 'video' && $subcategory->videos) || (request('origin') !== 'video' && $subcategory->images))
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Existing {{ request('origin') === 'video' ? 'Videos' : 'Images' }}</label>
                                    <div class="row g-3" id="existingImagesWrapper">
                                        @php
                                            $existingData = request('origin') === 'video' ? $subcategory->videos : $subcategory->images;
                                            $existingItems = json_decode($existingData, true) ?? [];
                                        @endphp
                                        @foreach ($existingItems as $img)
                                            <div class="col-md-4 image-block">
                                                <div class="card h-100">
                                                    @if(request('origin') === 'video')
                                                        <video class="card-img-top" style="width:100%;height:auto;max-height:320px;object-fit:contain;background:#000;" controls>
                                                            <source src="{{ asset('upload/AI Baby Video/' . $subcategory->category_name . '/' . $subcategory->title . '/video/' . $img['file']) }}" type="video/{{ strtolower(pathinfo($img['file'], PATHINFO_EXTENSION)) }}">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    @else
                                                        <img src="{{ asset('upload/' . $subcategory->category_name . '/' . $subcategory->title . '/' . $img['file']) }}"
                                                            class="card-img-top" style="width:100%;height:auto;" alt="Image">
                                                    @endif
                                                    <div class="card-body p-2">
                                                        <div class="mb-1">
                                                            <label class="small text-muted">Prompt</label>
                                                            <input type="text"
                                                                name="existing_prompts[{{ $img['file'] }}]"
                                                                value="{{ $img['prompt'] ?? '' }}"
                                                                class="form-control form-control-sm prompt-input">
                                                            <small class="text-muted"><span class="prompt-counter">{{ strlen($img['prompt'] ?? '') }}</span>/2990</small>
                                                        </div>
                                                        <div class="mb-1">
                                                            <label class="small text-muted">{{ request('origin') === 'video' ? 'Video' : 'Image' }} Title</label>
                                                            <input type="text"
                                                                name="{{ request('origin') === 'video' ? 'existing_video_title' : 'existing_image_title' }}[{{ $img['file'] }}]"
                                                                value="{{ $img['video_title'] ?? ($img['image_title'] ?? '') }}"
                                                                class="form-control form-control-sm">
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="small text-muted">Replace {{ request('origin') === 'video' ? 'Video' : 'Image' }}</label>
                                                            <input type="file"
                                                                name="replace_images[{{ $img['file'] }}]"
                                                                accept="{{ request('origin') === 'video' ? 'video/*' : 'image/*' }}"
                                                                class="form-control form-control-sm">
                                                        </div>
                                                        <div class="form-check form-switch mb-1">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="existing_name_change[{{ $img['file'] }}]"
                                                                value="1"
                                                                {{ !empty($img['name_change']) ? 'checked' : '' }}>
                                                            <label class="form-check-label small">Name Change</label>
                                                        </div>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="remove_images[]"
                                                                value="{{ $img['file'] }}">
                                                            <label class="form-check-label small text-danger">Remove</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Add New {{ request('origin') === 'video' ? 'Videos' : 'Images' }}</label>
                                <div id="newImagesWrapper"></div>
                                <button type="button" id="addImageBtn" class="btn btn-outline-primary btn-sm mt-2">+ Add
                                    {{ request('origin') === 'video' ? 'Video' : 'Image' }}</button>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-success px-4">Save</button>
                                <a href="{{ route('subcategories.show', $subcategory->id) }}"
                                    class="btn btn-outline-secondary px-4">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: @json($errors->all()).join(' | '),
                });
            @elseif(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: @json(session('error')),
                });
            @elseif(session('success'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: @json(session('success')),
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                });
            @endif

            const PROMPT_LIMIT = 2990;

            function applyPromptValidity(input) {
                const counter = input.parentElement.querySelector('.prompt-counter');
                const len = input.value.length;
                if (counter) counter.textContent = len;
                const over = len > PROMPT_LIMIT;
                input.classList.toggle('is-invalid', over);
                input.style.borderColor = over ? '#dc3545' : '';
                if (counter) counter.style.color = over ? '#dc3545' : '';
            }

            // Initialize counters for any pre-existing prompt inputs
            document.querySelectorAll('.prompt-input').forEach(applyPromptValidity);

            // Live character counter for any .prompt-input on the page (add + edit)
            document.body.addEventListener('input', function (e) {
                if (e.target.classList && e.target.classList.contains('prompt-input')) {
                    applyPromptValidity(e.target);
                }
            });

            const origin = '{{ request('origin') }}';

            // Block submit for client-detectable errors so a server reject (which
            // wipes the already-selected video + generated thumbnail) never happens.
            const detailsForm = document.getElementById('saveDetailsForm');
            if (detailsForm) {
                detailsForm.addEventListener('submit', function (e) {
                    // 1) Prompt length
                    const overInputs = Array.from(document.querySelectorAll('.prompt-input'))
                        .filter(el => el.value.length > PROMPT_LIMIT);
                    if (overInputs.length > 0) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Prompt too long',
                            text: overInputs.length + ' prompt(s) exceed ' + PROMPT_LIMIT + ' characters. Please shorten them and try again.'
                        });
                        return;
                    }

                    // 2) Video-specific pre-checks (only for new video rows)
                    if (origin === 'video') {
                        const MAX_VIDEO_MB = 50; // server limit is 51200 KB
                        const fileInputs = Array.from(document.querySelectorAll('#newImagesWrapper .file-input'));
                        for (const input of fileInputs) {
                            const file = input.files[0];
                            if (!file) continue;

                            if (file.size > MAX_VIDEO_MB * 1024 * 1024) {
                                e.preventDefault();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Video too large',
                                    text: 'Each video must be ' + MAX_VIDEO_MB + 'MB or smaller. Please choose a smaller file.'
                                });
                                return;
                            }

                            const row = input.closest('.image-row');
                            const thumb = row ? row.querySelector('.thumbnail-input') : null;
                            if (thumb && !thumb.value) {
                                e.preventDefault();
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Please wait',
                                    text: 'The video thumbnail is still being generated. Wait a moment and click Save again.'
                                });
                                return;
                            }
                        }
                    }
                });
            }

            const wrapper = document.getElementById('newImagesWrapper');
            const addBtn = document.getElementById('addImageBtn');

            addBtn.addEventListener('click', function() {
                const div = document.createElement('div');
                div.classList.add('card', 'mb-3', 'image-row', 'new-image-card');

                 div.innerHTML = `
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="small text-muted d-block mb-1">${origin === 'video' ? 'Video' : 'Image'}</label>
                        <input type="file" name="images[]" accept="${origin === 'video' ? 'video/*' : '.webp'}" class="form-control form-control-sm file-input" required>
                        ${origin === 'video'
                            ? '<input type="hidden" name="video_thumbnails[]" class="thumbnail-input"><div class="mt-2"><img class="thumbnail-preview" style="max-width:100%;max-height:180px;border-radius:6px;border:1px solid #dee2e6;display:none;"></div>'
                            : '<div class="mt-2 new-img-preview-wrap" style="display:none;"><img class="new-img-preview" style="width:100%;height:auto;border-radius:8px;border:1px solid #dee2e6;" alt="Preview"></div>'}
                    </div>
                    <div class="col-md-8 d-flex flex-column">
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label class="small text-muted mb-1">${origin === 'video' ? 'Video' : 'Image'} Title</label>
                                <input type="text" name="${origin === 'video' ? 'video_title' : 'image_title'}[]" placeholder="Enter ${origin === 'video' ? 'video' : 'image'} title" class="form-control form-control-sm">
                            </div>
                            <div class="col-sm-6">
                                <label class="small text-muted mb-1 d-block">Name Change</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="name_change[]" value="1">
                                    <label class="form-check-label small">Enable</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 d-flex flex-column flex-grow-1">
                            <label class="small text-muted mb-1">Prompt</label>
                            <textarea name="prompts[]" placeholder="Enter prompt" class="form-control form-control-sm prompt-input" style="flex:1 1 auto;min-height:200px;resize:vertical;"></textarea>
                            <small class="text-muted mt-1"><span class="prompt-counter">0</span>/2990</small>
                        </div>
                    </div>
                    <div class="col-12 text-end border-top pt-2 mt-1">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-new"><i class="bi bi-trash me-1"></i>Remove</button>
                    </div>
                </div>
            </div>
        `;
                wrapper.appendChild(div);
            });

            wrapper.addEventListener('click', function(e) {
                const removeBtn = e.target.closest('.remove-new');
                if (removeBtn) {
                    removeBtn.closest('.image-row').remove();
                }
            });

             // File validation + thumbnail generation event delegation
             wrapper.addEventListener('change', function(e) {
                if (!e.target.classList.contains('file-input')) return;
                const file = e.target.files[0];
                if (!file) return;

                // Enforce webp-only for image uploads (add time)
                if (origin !== 'video') {
                    const isWebp = file.type === 'image/webp' || file.name.toLowerCase().endsWith('.webp');
                    if (!isWebp) {
                        e.target.value = '';
                        // Hide preview if shown
                        const prevWrap = e.target.closest('.image-row').querySelector('.new-img-preview-wrap');
                        if (prevWrap) { prevWrap.style.display = 'none'; }
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid File Format',
                            text: 'Only .webp images are allowed. Please select a .webp file.',
                            confirmButtonColor: '#d33'
                        });
                        return;
                    }
                    // Show full image preview
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        const wrap = e.target.closest('.image-row').querySelector('.new-img-preview-wrap');
                        const img  = e.target.closest('.image-row').querySelector('.new-img-preview');
                        if (wrap && img) { img.src = ev.target.result; wrap.style.display = 'block'; }
                    };
                    reader.readAsDataURL(file);
                }

                // Thumbnail generation for video uploads
                if (e.target.accept.includes('video')) {
                    generateThumbnail(file, e.target);
                }
            });

            function generateThumbnail(file, inputElement) {
                const video = document.createElement('video');
                video.preload = 'metadata';
                video.src = URL.createObjectURL(file);
                video.muted = true;
                video.playsInline = true;

                // Load metadata to get duration
                video.onloadedmetadata = function() {
                     // Seek to 1 second or 25% of duration if short
                    let seekTime = 1.0;
                    if (video.duration < 1.0) seekTime = 0.0; 
                    video.currentTime = seekTime;
                };

                video.onseeked = function() {
                     // Create canvas
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    
                    // Convert to base64
                    const dataURL = canvas.toDataURL('image/jpeg', 0.7);
                    
                    // Find sibling input and preview
                    const parent = inputElement.closest('.image-row') || inputElement.closest('div');
                    const thumbInput = parent.querySelector('.thumbnail-input');
                    const thumbPreview = parent.querySelector('.thumbnail-preview');

                    if (thumbInput) thumbInput.value = dataURL;
                    if (thumbPreview) {
                        thumbPreview.src = dataURL;
                        thumbPreview.style.display = 'block';
                    }

                    // Cleanup
                    URL.revokeObjectURL(video.src);
                };
            }
        });
    </script>

@endsection
