@extends('partials.layout')
@section('title', $subcategory->title)
@section('container')

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0 rounded-4">

                    <div
                        class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center py-3 px-4 rounded-top">
                        <h4 class="mb-0" style="color:black">
                            <i class="bi bi-folder2-open fs-4"></i> {{ $subcategory->title }}
                        </h4>
                        <span class="badge bg-light text-dark fw-semibold">{{ $subcategory->category_name }}</span>
                    </div>

                    @if ($subcategory->category_thumbnail_image)
                        <div class="text-center mt-3">
                            @php $pathPrefix = request('origin') === 'video' ? 'upload/AI Baby Video/' : 'upload/'; @endphp
                            <img src="{{ asset($pathPrefix . $subcategory->category_name . '/' . $subcategory->title . '/category_thumbnail/' . $subcategory->category_thumbnail_image) }}"
                                class="img-fluid rounded shadow-sm" style="max-height:200px;" alt="Category Thumbnail">
                        </div>
                    @endif

                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h6 class="text-uppercase text-muted mb-1">Title</h6>
                                <p class="fw-semibold">{{ $subcategory->title }}</p>
                            </div>
                            <div class="col-12 mb-3">
                                <h6 class="text-uppercase text-muted mb-1">Status</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="publishedToggle"
                                            {{ ($subcategory->status ?? 1) ? 'checked' : '' }}
                                            style="width:3rem;height:1.5rem;cursor:pointer;">
                                    </div>
                                    <span id="publishedLabel" class="fw-semibold {{ ($subcategory->status ?? 1) ? 'text-success' : 'text-secondary' }}">
                                        {{ ($subcategory->status ?? 1) ? 'Published' : 'Draft' }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <h6 class="text-uppercase text-muted mb-1">Category</h6>
                                <p class="fw-semibold">{{ $subcategory->category_name }}</p>
                            </div>
                            <div class="col-12 mb-3">
                                <h6 class="text-uppercase text-muted mb-1">Trending Status</h6>
                                <p>
                                    <span
                                        class="badge cursor-pointer {{ $subcategory->trending ? 'bg-success' : 'bg-secondary' }}"
                                        id="trendingStatusBadge"
                                        onclick="toggleTrendingStatus({{ $subcategory->id }}, {{ $subcategory->trending }})"
                                        style="cursor: pointer;">
                                        {{ $subcategory->trending ? 'Trending' : 'Not Trending' }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-12 mb-3">
                                <h6 class="text-uppercase text-muted mb-1">Description</h6>
                                <p>{{ $subcategory->description ?? 'No description yet.' }}</p>
                            </div>
                        </div>

                        @php
                            $is_video = request('origin') === 'video';
                            $dataJson = $is_video ? $subcategory->videos : $subcategory->images;
                            $imagesArray = json_decode($dataJson, true) ?? [];
                            $siblingModel = $is_video ? \App\Models\AiVideoSubcategory::class : \App\Models\Subcategory::class;
                            $trendingSiblingIds = $siblingModel::where('category_name', $subcategory->category_name)
                                ->where('id', '!=', $subcategory->id)
                                ->where('trending', 1)
                                ->pluck('id')
                                ->toArray();
                        @endphp
                        @if (count($imagesArray) > 0)
                            <div class="mb-4">
                                <h6 class="text-uppercase text-muted mb-2">
                                    {{ request('origin') === 'video' ? 'Videos' : 'Images' }}
                                </h6>
                                <div class="row">
                                    @foreach ($imagesArray as $index => $img)
                                        <div class="col-md-4 mb-3">
                                            <div class="card shadow-sm position-relative image-card">
                                                @if(request('origin') === 'video')
                                                    <video class="card-img-top" style="height:200px; object-fit:cover;" controls>
                                                        <source
                                                            src="{{ asset('upload/AI Baby Video/' . $subcategory->category_name . '/' . $subcategory->title . '/video/' . $img['file']) }}"
                                                            type="video/{{ strtolower(pathinfo($img['file'], PATHINFO_EXTENSION)) }}">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                @else
                                                    <img src="{{ asset('upload/' . $subcategory->category_name . '/' . $subcategory->title . '/' . $img['file']) }}"
                                                        class="card-img-top" style="height:200px; object-fit:cover;" alt="Image">
                                                @endif

                                                <button
                                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-image-btn"
                                                    data-url="{{ route('subcategories.deleteImage', ['subcategoryId' => $subcategory->id, 'file' => $img['file'], 'origin' => request('origin')]) }}">
                                                    <i class="bi bi-x text-white"></i>
                                                </button>

                                                <div class="card-body p-2">
                                                    @if (!empty($img['video_title']) || !empty($img['image_title']))
                                                        <p class="mb-0 text-primary fw-semibold">
                                                            {{ $img['video_title'] ?? $img['image_title'] }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="d-flex gap-2">
                            <a href="{{ route('subcategories.addDetailsForm', ['id' => $subcategory->id, 'origin' => request('origin')]) }}"
                                class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Add
                                {{ request('origin') === 'video' ? 'Videos' : 'Images' }} & Description
                            </a>

                            <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                                data-bs-target="#editSubcategoryModal">
                                <i class="bi bi-pencil-square"></i> Edit Subcategory
                            </button>

                            <form id="deleteForm"
                                action="{{ route('subcategories.destroy', ['id' => $subcategory->id, 'origin' => request('origin')]) }}"
                                method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-outline-secondary" id="deleteSubcategoryBtn">
                                    <i class="bi bi-trash"></i> Delete Subcategory
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card-footer bg-light d-flex justify-content-between align-items-center rounded-bottom p-3">
                        <a href="{{ route('subcategories.index', ['origin' => request('origin')]) }}"
                            class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editSubcategoryModal" tabindex="-1" aria-labelledby="editSubcategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Subcategory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="saveDetailsForm" action="{{ route('subcategories.saveDetails', $subcategory->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="origin" value="{{ request('origin') }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">ID</label>
                            <input type="text" class="form-control" value="{{ $subcategory->id }}" readonly
                                style="background-color:#f8f9fa;cursor:not-allowed;color:#6c757d;font-weight:600;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Category Name</label>
                            <input type="text" class="form-control" value="{{ $subcategory->category_name }}" readonly>
                            <input type="hidden" name="category_name" value="{{ $subcategory->category_name }}">
                        </div>

                        <div class="mb-3 d-flex align-items-center">
                            <label class="form-label fw-semibold me-3 mb-0">Trending</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="trendingSwitchModal" name="trending"
                                    value="1" {{ old('trending', $subcategory->trending ?? 0) ? 'checked' : '' }}
                                    style="width: 3em; height: 1.5em;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Subcategory Title <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ $subcategory->title }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control"
                                rows="4">{{ $subcategory->description }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Thumbnail
                                {{ request('origin') === 'video' ? 'Video' : 'Image' }}</label>
                            @if ($subcategory->category_thumbnail_image)
                                <div class="mb-2">
                                    @php $pathPrefix = request('origin') === 'video' ? 'upload/AI Baby Video/' : 'upload/'; @endphp
                                    <img src="{{ asset($pathPrefix . $subcategory->category_name . '/' . $subcategory->title . '/category_thumbnail/' . $subcategory->category_thumbnail_image) }}"
                                        class="img-fluid rounded mb-2" style="height:120px; object-fit:cover;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_thumbnail"
                                            id="removeThumbnail">
                                        <label class="form-check-label" for="removeThumbnail">Remove current
                                            thumbnail</label>
                                    </div>
                                </div>
                            @endif
                            <input type="file" name="category_thumbnail_image" accept="image/*" class="form-control">
                        </div>

                        @if (count($imagesArray) > 0)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Edit Existing
                                    {{ request('origin') === 'video' ? 'Videos' : 'Images' }}</label>
                                <div class="row">
                                    @foreach ($imagesArray as $index => $img)
                                        <div class="col-md-6 mb-3">
                                            <div class="card p-2">
                                                @if(request('origin') === 'video')
                                                    <video class="img-fluid rounded mb-2" style="height:120px; object-fit:cover;"
                                                        controls>
                                                        <source
                                                            src="{{ asset('upload/AI Baby Video/' . $subcategory->category_name . '/' . $subcategory->title . '/video/' . $img['file']) }}"
                                                            type="video/{{ strtolower(pathinfo($img['file'], PATHINFO_EXTENSION)) }}">
                                                    </video>
                                                @else
                                                    <img src="{{ asset('upload/' . $subcategory->category_name . '/' . $subcategory->title . '/' . $img['file']) }}"
                                                        class="img-fluid rounded mb-2" style="height:120px; object-fit:cover;">
                                                @endif

                                                <label class="small text-muted">Prompt</label>
                                                <input type="text" name="existing_prompts[{{ $img['file'] }}]"
                                                    value="{{ $img['prompt'] ?? '' }}" class="form-control prompt-input mb-1">
                                                <small class="text-muted d-block mb-2"><span class="prompt-counter">{{ strlen($img['prompt'] ?? '') }}</span>/2990</small>

                                                <label
                                                    class="small text-muted">{{ request('origin') === 'video' ? 'Video' : 'Image' }}
                                                    Title</label>
                                                <input type="text"
                                                    name="{{ request('origin') === 'video' ? 'existing_video_title' : 'existing_image_title' }}[{{ $img['file'] }}]"
                                                    value="{{ $img['video_title'] ?? ($img['image_title'] ?? '') }}"
                                                    class="form-control mb-2">

                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="existing_name_change[{{ $img['file'] }}]" value="1" {{ !empty($img['name_change']) ? 'checked' : '' }}>
                                                    <label class="form-check-label">Enable Name Change</label>
                                                </div>

                                                <label class="small text-muted">Replace
                                                    {{ request('origin') === 'video' ? 'Video' : 'Image' }}</label>
                                                <input type="file" name="replace_images[{{ $img['file'] }}]"
                                                    accept="{{ request('origin') === 'video' ? 'video/*' : 'image/*' }}"
                                                    class="form-control mb-2">

                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="remove_images[]"
                                                        value="{{ $img['file'] }}" id="removeImage{{ $index }}">
                                                    <label class="form-check-label" for="removeImage{{ $index }}">Remove this
                                                        {{ request('origin') === 'video' ? 'video' : 'image' }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Update</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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

            document.querySelectorAll('.prompt-input').forEach(applyPromptValidity);

            document.body.addEventListener('input', function (e) {
                if (e.target.classList && e.target.classList.contains('prompt-input')) {
                    applyPromptValidity(e.target);
                }
            });

            const detailsForm = document.getElementById('saveDetailsForm');
            if (detailsForm) {
                detailsForm.addEventListener('submit', function (e) {
                    const overInputs = Array.from(document.querySelectorAll('.prompt-input'))
                        .filter(el => el.value.length > PROMPT_LIMIT);
                    if (overInputs.length > 0) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Prompt too long',
                            text: overInputs.length + ' prompt(s) exceed ' + PROMPT_LIMIT + ' characters. Please shorten them and try again.'
                        });
                    }
                });
            }
        });

        document.getElementById('deleteSubcategoryBtn').addEventListener('click', function () {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will delete the subcategory and all its {{ request('origin') === 'video' ? 'videos' : 'images' }}!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteForm').submit();
                }
            });
        });

        document.querySelectorAll('.delete-image-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const url = this.getAttribute('data-url');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will delete this {{ request('origin') === 'video' ? 'video' : 'image' }}!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
        const trendingSiblingIds = @json($trendingSiblingIds);
        const updateStatusUrl = '{{ route('subcategories.updateStatus') }}';
        const csrfToken = '{{ csrf_token() }}';
        const currentOrigin = '{{ request('origin') }}';

        // Published / Draft toggle
        const publishedToggle = document.getElementById('publishedToggle');
        const publishedLabel  = document.getElementById('publishedLabel');

        if (publishedToggle) {
            publishedToggle.addEventListener('change', function () {
                const newStatus = this.checked ? 1 : 0;

                fetch(updateStatusUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ id: {{ $subcategory->id }}, status: newStatus, origin: currentOrigin })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        publishedLabel.textContent = newStatus ? 'Published' : 'Draft';
                        publishedLabel.className = 'fw-semibold ' + (newStatus ? 'text-success' : 'text-secondary');

                        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                        Toast.fire({ icon: 'success', title: newStatus ? 'Set to Published' : 'Set to Draft' });
                    } else {
                        publishedToggle.checked = !publishedToggle.checked;
                        Swal.fire('Error', 'Failed to update status', 'error');
                    }
                })
                .catch(function () {
                    publishedToggle.checked = !publishedToggle.checked;
                    Swal.fire('Error', 'Something went wrong', 'error');
                });
            });
        }

        function setTrendingOff(sibId) {
            fetch(updateStatusUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ id: sibId, trending: 0, origin: currentOrigin })
            });
        }

        function toggleTrendingStatus(id, currentStatus) {
            let newStatus = currentStatus ? 0 : 1;
            let badge = document.getElementById('trendingStatusBadge');

            fetch(updateStatusUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ id: id, trending: newStatus, origin: currentOrigin })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (newStatus) {
                            badge.classList.remove('bg-secondary');
                            badge.classList.add('bg-success');
                            badge.innerText = 'Trending';
                            badge.setAttribute('onclick', `toggleTrendingStatus(${id}, 1)`);
                            // Turn off all siblings that were trending (exclusive toggle)
                            trendingSiblingIds.forEach(function(sibId) { setTrendingOff(sibId); });
                        } else {
                            badge.classList.remove('bg-success');
                            badge.classList.add('bg-secondary');
                            badge.innerText = 'Not Trending';
                            badge.setAttribute('onclick', `toggleTrendingStatus(${id}, 0)`);
                        }

                        const Toast = Swal.mixin({
                            toast: true, position: 'top-end',
                            showConfirmButton: false, timer: 3000, timerProgressBar: true
                        });
                        Toast.fire({ icon: 'success', title: 'Trending status updated successfully' });
                    } else {
                        Swal.fire('Error', 'Failed to update status', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Something went wrong', 'error');
                });
        }
    </script>

    <style>
        .image-card {
            transition: all 0.3s ease;
        }

        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .delete-image-btn {
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
            background-color: #dc3545 !important;
            border: none;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .image-card:hover .delete-image-btn {
            opacity: 1;
            transform: scale(1);
        }

        .delete-image-btn:hover {
            background-color: #c82333 !important;
            transform: scale(1.1);
        }

        .delete-image-btn i {
            font-size: 14px;
            line-height: 1;
        }
    </style>

@endsection