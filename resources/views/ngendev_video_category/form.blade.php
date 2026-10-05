@extends('partials.layout')
@section('title', isset($category) ? 'Edit Category' : 'Add Category')
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

        .category-image-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
    </style>

    <div class="container mt-4 mb-5">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-{{ isset($category) ? 'pencil-square' : 'plus-circle' }} me-2 text-primary"></i>
                    {{ isset($category) ? 'Edit Category' : 'Add New Category' }}
                </h1>
                <p class="page-subtitle">{{ isset($category) ? 'Update category details' : 'Create a new Ngendev category' }}
                </p>
            </div>
            <div>
                <a href="{{ route('ngendev-video-categories.index') }}" data-back-to-list class="btn-custom-back">
                    <i class="bi bi-arrow-left me-2"></i>Back to Categories
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="form-card">
            <form method="POST" enctype="multipart/form-data"
                action="{{ isset($category) ? route('ngendev-video-categories.update', $category->id) : route('ngendev-video-categories.store') }}"
                id="categoryForm">
                @csrf
                @if (isset($category))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="category_name" class="form-label">Category Name</label>
                    <input type="text" class="form-control" id="category_name" name="category_name"
                        value="{{ old('category_name', $category->category_name ?? '') }}" required
                        placeholder="Enter category name">
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="Solo" {{ (old('type', $category->type ?? '') == 'Solo') ? 'selected' : '' }}>Solo</option>
                        <option value="Couple" {{ (old('type', $category->type ?? '') == 'Couple') ? 'selected' : '' }}>Couple</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="category_image" class="form-label">Category Image(s)</label>
                    <input type="file" class="form-control" id="category_image" name="category_image[]" accept="image/*"
                        multiple onchange="previewImage(this)" {{ isset($category) ? '' : 'required' }}>

                    <div class="mt-3">
                        @if (isset($category) && $category->category_image)
                            @php $images = json_decode($category->category_image, true); @endphp
                            @if (!empty($images))
                                <div id="currentImageContainer">
                                    <p class="mb-1">Current Images:</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($images as $img)
    <img
        src="{{ asset(
            'upload/ngendev/videos/' .
            rawurlencode($category->category_name) .
            '/category_thumbnail_image/' .
            rawurlencode($img)
        ) }}"
        class="category-image-preview"
    >
@endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        <div id="newImagePreviewContainer" class="d-none mt-3">
                            <p class="mb-1">New Image Previews:</p>
                            <div id="newImagePreviewList" class="d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" value="1"
                            {{ old('status', $category->status ?? 1) ? 'checked' : '' }}>
                        <label class="form-check-label" for="status">Active Status</label>
                    </div>
                    <small class="text-muted" id="status-help">
                        Solo categories are always active.
                    </small>
                </div>

                @include('notifications._after_save_toggle', ['inlineNotification' => true])
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary py-2">
                        <i class="bi bi-{{ isset($category) ? 'check-circle-fill' : 'plus-lg' }} me-2"></i>
                        {{ isset($category) ? 'Update Category' : 'Add Category' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            let previewContainer = document.getElementById('newImagePreviewContainer');
            let previewList = document.getElementById('newImagePreviewList');
            previewList.innerHTML = "";

            if (input.files && input.files.length > 0) {
                previewContainer.classList.remove('d-none');
                Array.from(input.files).forEach(file => {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        let img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = "category-image-preview";
                        previewList.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                });
            } else {
                previewContainer.classList.add('d-none');
            }
        }

        const COUPLE_ACTIVE = {{ isset($coupleActive) && $coupleActive ? 'true' : 'false' }};

        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('type');
            const statusCheck = document.getElementById('status');
            const statusHelp = document.getElementById('status-help');

            function updateStatusVisibility(isTypeChange) {
                if (typeSelect.value === 'Solo') {
                    statusCheck.checked = true;
                    statusCheck.disabled = true;
                    statusHelp.textContent = 'Solo categories are always active.';
                    statusHelp.style.display = 'block';
                } else {
                    // Couple
                    if (!COUPLE_ACTIVE) {
                        statusCheck.checked = false;
                        statusCheck.disabled = true;
                        statusHelp.textContent = 'Global Couple Status is OFF — this category will be saved as inactive.';
                    } else {
                        statusCheck.disabled = false;
                        if (isTypeChange) statusCheck.checked = true;
                        statusHelp.textContent = 'Global Couple Status is ON — you can set this category active or inactive.';
                    }
                    statusHelp.style.display = 'block';
                }
            }

            typeSelect.addEventListener('change', function() { updateStatusVisibility(true); });

            updateStatusVisibility(false);
        });
    </script>
@endsection
