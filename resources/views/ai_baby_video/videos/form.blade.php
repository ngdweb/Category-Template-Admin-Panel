@extends('partials.layout')
@section('title', isset($video) ? 'Edit Video' : 'Add Video')
@section('container')
    <style>
        .btn-custom-back {
            color: #4e73df;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }

        .btn-custom-back:hover {
            color: #2e59d9;
        }

        .form-card {
            background-color: #fff;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .video-preview {
            max-width: 150px;
            max-height: 100px;
        }

        .preview-container {
            position: relative;
            display: inline-block;
        }

        .remove-media-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74a3b;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            z-index: 10;
        }

        .preview-container:hover .remove-media-btn {
            display: flex;
        }
    </style>

    <div class="container mt-4 mb-5">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-{{ isset($video) ? 'pencil-square' : 'plus-circle' }} me-2 text-primary"></i>
                    {{ isset($video) ? 'Edit Video' : 'Add New Video' }}
                </h1>
                <p class="page-subtitle">{{ isset($video) ? 'Update video details' : 'Create a new AI Baby Video' }}
                </p>
            </div>
            <div>
                <a href="{{ route('ai-baby-video.videos.index') }}" data-back-to-list class="btn-custom-back">
                    <i class="bi bi-arrow-left me-2"></i>Back to Videos
                </a>
            </div>
        </div>



        <div class="form-card">
            <form method="POST"
                action="{{ isset($video) ? route('ai-baby-video.videos.update', $video->id) : route('ai-baby-video.videos.store') }}"
                id="videoForm" enctype="multipart/form-data">
                @csrf
                @if (isset($video))
                    @method('PUT')
                @endif

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $video->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="video_path" class="form-label">Video File</label>
                        <input type="file" class="form-control" id="video_path" name="video_path" accept="video/*"
                            onchange="previewVideoUpload(this)">
                        <small class="text-muted">Upload a video for preview.</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="video_thumbnail" class="form-label">Video Thumbnail</label>
                        <input type="file" class="form-control" id="video_thumbnail" name="video_thumbnail" accept="image/*"
                            onchange="previewThumbnailUpload(this)">
                        <small class="text-muted">Upload a thumbnail image.</small>
                    </div>
                </div>

                <input type="hidden" name="remove_video" id="remove_video" value="0">
                <input type="hidden" name="remove_thumbnail" id="remove_thumbnail" value="0">

                <div id="videoPreviewContainer"
                    class="mb-3 {{ isset($video) && ($video->video_path || $video->video_thumbnail) ? '' : 'd-none' }}">
                    <div class="d-flex align-items-start gap-4 p-3 bg-light rounded border">
                        <div id="videoPreviewWrapper" class="{{ (isset($video) && $video->video_path) ? '' : 'd-none' }}">
                            <span class="d-block text-muted small mb-2 fw-bold">Video Preview</span>
                            @php
                                $videoAsset = '';
                                if (isset($video) && $video->video_path) {
                                    if (Str::startsWith($video->video_path, 'upload/')) {
                                        $videoAsset = asset($video->video_path);
                                    } else {
                                        $videoAsset = asset('upload/AI Baby Video/' . $video->category->category_name . '/video/' . $video->video_path);
                                    }
                                }
                            @endphp
                            <div class="preview-container">
                                <button type="button" class="remove-media-btn" onclick="removeVideo()" title="Remove Video">
                                    <i class="bi bi-x"></i>
                                </button>
                                <video id="previewVideo" src="{{ $videoAsset }}" controls
                                    class="video-preview rounded shadow-sm {{ $videoAsset ? '' : 'd-none' }}"></video>
                            </div>
                        </div>
                        <div id="thumbPreviewWrapper"
                            class="{{ (isset($video) && $video->video_thumbnail) ? '' : 'd-none' }}">
                            <span class="d-block text-muted small mb-2 fw-bold">Thumbnail Preview</span>
                            @php
                                $thumbAsset = '';
                                if (isset($video) && $video->video_thumbnail) {
                                    if (Str::startsWith($video->video_thumbnail, 'upload/')) {
                                        $thumbAsset = asset($video->video_thumbnail);
                                    } else {
                                        $thumbAsset = asset('upload/AI Baby Video/' . $video->category->category_name . '/video thumbanail/' . $video->video_thumbnail);
                                    }
                                }
                            @endphp
                            <div class="preview-container">
                                <button type="button" class="remove-media-btn" onclick="removeThumbnail()"
                                    title="Remove Thumbnail">
                                    <i class="bi bi-x"></i>
                                </button>
                                <img id="thumbPreview" src="{{ $thumbAsset }}"
                                    class="rounded shadow-sm {{ $thumbAsset ? '' : 'd-none' }}"
                                    style="max-width: 150px; border: 1px solid #ddd;">
                            </div>
                        </div>
                    </div>
                </div>

              <div class="mb-3">
    <label for="ai_prompt" class="form-label">
        AI Prompt 
        <small class="text-muted">
            (<span id="charCount">0</span> characters)
        </small>
    </label>

    <textarea
        class="form-control"
        id="ai_prompt"
        name="ai_prompt"
        rows="3"
        placeholder="Enter AI Prompt">{{ old('ai_prompt', $video->ai_prompt ?? '') }}</textarea>

    <div class="form-text text-end">
        Max 2990 characters
    </div>
</div>
                <div class="mb-3">
                    <label for="video_title" class="form-label">Video Title</label>
                    <input type="text" class="form-control" id="video_title" name="video_title"
                        value="{{ old('video_title', $video->video_title ?? '') }}" placeholder="Enter Video Title"
                        required>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="name_change" name="name_change"
                            value="1" {{ old('name_change', $video->name_change ?? 0) ? 'checked' : '' }}>
                        <label class="form-check-label" for="name_change">Name Change</label>
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-primary py-2" id="submitBtn">
                        <i class="bi bi-{{ isset($video) ? 'check-circle-fill' : 'plus-lg' }} me-2"></i>
                        {{ isset($video) ? 'Update Video' : 'Add Video' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SweetAlert2 and Thumbnail Gen Logic -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if(session('success'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: "{{ session('success') }}",
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                });
            @endif
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
            @endif
                    });

        function previewVideoUpload(input) {
            if (input.files && input.files[0]) {
                var file = input.files[0];
                var fileURL = URL.createObjectURL(file);
                var videoPreview = document.getElementById('previewVideo');

                videoPreview.src = fileURL;
                videoPreview.classList.remove('d-none');
                document.getElementById('videoPreviewWrapper').classList.remove('d-none');
                document.getElementById('videoPreviewContainer').classList.remove('d-none');
                document.getElementById('remove_video').value = '0';
            }
        }

        function previewThumbnailUpload(input) {
            if (input.files && input.files[0]) {
                var file = input.files[0];
                var reader = new FileReader();
                reader.onload = function (e) {
                    var thumbPreview = document.getElementById('thumbPreview');
                    thumbPreview.src = e.target.result;
                    thumbPreview.classList.remove('d-none');
                    document.getElementById('thumbPreviewWrapper').classList.remove('d-none');
                    document.getElementById('videoPreviewContainer').classList.remove('d-none');
                    document.getElementById('remove_thumbnail').value = '0';
                }
                reader.readAsDataURL(file);
            }
        }

        function removeVideo() {
            document.getElementById('video_path').value = '';
            document.getElementById('previewVideo').src = '';
            document.getElementById('previewVideo').classList.add('d-none');
            document.getElementById('videoPreviewWrapper').classList.add('d-none');
            document.getElementById('remove_video').value = '1';

            checkContainerVisibility();
        }

        function removeThumbnail() {
            document.getElementById('video_thumbnail').value = '';
            document.getElementById('thumbPreview').src = '';
            document.getElementById('thumbPreview').classList.add('d-none');
            document.getElementById('thumbPreviewWrapper').classList.add('d-none');
            document.getElementById('remove_thumbnail').value = '1';

            checkContainerVisibility();
        }

        function checkContainerVisibility() {
            var hasVideo = !document.getElementById('videoPreviewWrapper').classList.contains('d-none');
            var hasThumb = !document.getElementById('thumbPreviewWrapper').classList.contains('d-none');

            if (!hasVideo && !hasThumb) {
                document.getElementById('videoPreviewContainer').classList.add('d-none');
            }
        }
    </script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const PROMPT_LIMIT = 2990;
        const textarea = document.getElementById("ai_prompt");
        const charCount = document.getElementById("charCount");
        const form = document.getElementById("videoForm");

        const HAS_EXISTING_VIDEO = {{ isset($video) && $video->video_path ? 'true' : 'false' }};
        const HAS_EXISTING_THUMB = {{ isset($video) && $video->video_thumbnail ? 'true' : 'false' }};

        function updateCount() {
            const len = textarea.value.length;
            charCount.textContent = len;
            const over = len > PROMPT_LIMIT;
            textarea.classList.toggle('is-invalid', over);
            textarea.style.borderColor = over ? '#dc3545' : '';
            charCount.style.color = over ? '#dc3545' : '';
        }

        updateCount();
        textarea.addEventListener("input", updateCount);

        // Validate everything the server requires BEFORE submitting, so a server
        // reject never reloads the page and wipes the already-selected video/thumbnail.
        form.addEventListener("submit", function (e) {
            const categoryInput = document.getElementById('category_id');
            const videoInput = document.getElementById('video_path');
            const thumbInput = document.getElementById('video_thumbnail');
            const titleInput = document.getElementById('video_title');
            const removeVideo = document.getElementById('remove_video').value === '1';
            const removeThumb = document.getElementById('remove_thumbnail').value === '1';

            const hasVideo = (videoInput.files && videoInput.files.length > 0) || (HAS_EXISTING_VIDEO && !removeVideo);
            const hasThumb = (thumbInput.files && thumbInput.files.length > 0) || (HAS_EXISTING_THUMB && !removeThumb);

            const errs = [];

            if (textarea.value.length > PROMPT_LIMIT) {
                errs.push('AI Prompt must not exceed ' + PROMPT_LIMIT + ' characters (current: ' + textarea.value.length + ').');
            }
            if (!categoryInput.value) errs.push('Please select a category.');
            if (!hasVideo) errs.push('Please choose a video file.');
            if (!hasThumb) errs.push('Please choose a video thumbnail.');
            if (!titleInput.value.trim()) errs.push('Please enter a video title.');

            if (videoInput.files && videoInput.files[0]) {
                const vf = videoInput.files[0];
                const vext = vf.name.split('.').pop().toLowerCase();
                if (['mp4', 'mov', 'avi', 'wmv'].indexOf(vext) === -1) errs.push('Video must be mp4, mov, avi or wmv.');
                if (vf.size > 50 * 1024 * 1024) errs.push('Video must be 50MB or smaller.');
            }
            if (thumbInput.files && thumbInput.files[0]) {
                const tf = thumbInput.files[0];
                const text = tf.name.split('.').pop().toLowerCase();
                if (['jpeg', 'jpg', 'png', 'gif', 'webp'].indexOf(text) === -1) errs.push('Thumbnail must be jpeg, jpg, png, gif or webp.');
                if (tf.size > 5 * 1024 * 1024) errs.push('Thumbnail must be 5MB or smaller.');
            }

            if (errs.length) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Please complete the form',
                    html: errs.join('<br>')
                });
            }
        });
    });
</script>
@endsection