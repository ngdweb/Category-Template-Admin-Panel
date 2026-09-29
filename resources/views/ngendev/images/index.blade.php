@extends('partials.layout')
@section('title', 'Ngendev Images Management')
@section('container')
    <style>
        .stats-badge { background: #eaecf4; color: #5a5c69; padding: .5rem 1rem; border-radius: .35rem; font-size: .85rem; font-weight: 700; }
        .main-card { background: #fff; border-radius: .35rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, .15); padding: 1.5rem; margin-bottom: 2rem; position: relative; }
        .table-responsive { margin-left: 0 !important; margin-right: 0 !important; padding-left: 0 !important; padding-right: 0 !important; }
        .data-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0; }
        .data-table th { background: #f8f9fc; color: #5a5c69; font-weight: 700; padding: .75rem; border-bottom: 1px solid #e3e6f0; }
        .data-table td { padding: .75rem; vertical-align: middle; border-bottom: 1px solid #e3e6f0; word-wrap: break-word; }
        .data-table .col-id { width: 5%; text-align: center; }
        .data-table .col-category { width: 13%; }
        .data-table .col-model { width: 11%; }
        .data-table .col-thumb { width: 9%; }
        .data-table .col-prompt { width: 52%; }
        .data-table .col-action { width: 10%; }
        .prompt-user { font-size: .85rem; }
        .prompt-default { font-size: .78rem; color: #858796; margin-top: .25rem; }
        .prompt-default-label { font-weight: 600; color: #5a5c69; }
        .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: .35rem; color: #fff !important; text-decoration: none; transition: all 0.2s; border: none; }
        .action-btn i { font-size: 0.9rem; color: #fff !important; }
        .edit-btn { background-color: #0dcaf0; }
        .delete-btn { background-color: #bb2d3b; }
        .form-card { background: #fff; border-radius: .35rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, .15); padding: 1.5rem; margin-bottom: 2rem; }
        .img-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: .35rem; }
        .img-thumbnail { max-height: 100px; object-fit: contain; }
        .empty-state { text-align: center; padding: 3rem 1rem; }
        .empty-state-icon { font-size: 3.5rem; color: #b7b9cc; margin-bottom: 1rem; }
        .empty-state-title { color: #5a5c69; margin-bottom: 0.5rem; }
        .empty-state-text { color: #858796; margin-bottom: 1.5rem; }
        .sortable-ghost { opacity: 0.4; }
        .sortable-chosen { transform: scale(1.05); }
        .sortable-drag { transform: rotate(5deg); }
        .sortable-item:hover { box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }

        /* Filters row */
        .filters-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .filters-left { display: flex; align-items: center; gap: .75rem; }
        .custom-select-arrow {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%235a5c69' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right .75rem center;
            background-size: 14px 12px;
            padding-right: 2.25rem;
            cursor: pointer;
        }
        .per-page-select { border: 1px solid #d1d3e2; border-radius: .35rem; padding: .5rem .75rem; width: 88px; background-color: #fff; }
        .category-filter { border: 1px solid #d1d3e2; border-radius: .35rem; padding: .5rem 1rem; min-width: 220px; background-color: #fff; }
        .search-container { display: flex; justify-content: flex-end; }
        .search-container .input-group { width: 350px; }
        .search-container .form-control { border: 1px solid #d1d3e2; border-radius: .35rem 0 0 .35rem; padding: .5rem 1rem; }

        /* Pagination */
        .pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e3e6f0; flex-wrap: wrap; gap: .75rem; }
        .pagination-info { color: #6e707e; font-size: .9rem; }
        .pagination { display: flex; flex-wrap: wrap; gap: 4px; padding: 0; margin: 0; list-style: none; }
        .pagination .page-item { list-style: none; }
        .pagination .page-item .page-link { color: #4e73df; padding: .375rem .75rem; border: 1px solid #dddfeb; font-size: .9rem; cursor: pointer; background-color: #fff; border-radius: .25rem; text-decoration: none; display: inline-block; line-height: 1.5; min-width: 36px; text-align: center; transition: all .15s ease-in-out; }
        .pagination .page-item.active .page-link { background-color: #4e73df; border-color: #4e73df; color: #fff; }
        .pagination .page-item.disabled .page-link { color: #b7b9cc; pointer-events: none; background-color: #f8f9fc; }
        .pagination .page-item .page-link:hover { background-color: #eaecf4; border-color: #dddfeb; color: #2e59d9; }
        .pagination .page-item.active .page-link:hover { background-color: #4e73df; color: #fff; }

        .loading-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, .7); display: flex; justify-content: center; align-items: center; z-index: 10; border-radius: .35rem; }
    </style>

    <div class="container mt-4 mb-5">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title"><i class="bi bi-robot me-2"></i>Ngendev Images Management</h1>
                <p class="page-subtitle">Manage all Ngendev images in the system</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#bulkNameChangeModal">
                    <i class="bi bi-toggles me-2"></i>Name Change
                </button>
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#indexingModal">
                    <i class="bi bi-arrow-up-down me-2"></i>Indexing
                </button>
                <span class="stats-badge"><i class="bi bi-collection"></i> Total: <span id="totalCount">{{ $images->total() }}</span> Images</span>
            </div>
        </div>

        <div class="form-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-3">
                    <h4 id="formTitle" class="mb-0"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Ngendev Image</h4>
                    <span id="editIdDisplay" class="d-none badge bg-secondary fs-6" style="font-weight:500;letter-spacing:.03em;">ID: <span id="editIdValue"></span></span>
                </div>
                <button type="button" id="cancelEdit" class="btn btn-outline-secondary d-none"><i class="bi bi-x-lg me-1"></i>Cancel Edit</button>
            </div>

            <form id="ngendevImageForm" method="POST" enctype="multipart/form-data" action="{{ route('ngendev.images.store') }}">
                @csrf
                <input type="hidden" id="formMethod" name="_method" value="POST">
                <input type="hidden" id="editId" name="id" value="">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="ai_model" class="form-label">Model</label>
                        <select class="form-select" id="ai_model" name="ai_model" required>
                            <option value="Ngendev Image">Ngendev Image</option>
                            <option value="Ngendev Figure">Ngendev Figure</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="mb-3">
                            <label for="ai_prompt" class="form-label d-flex justify-content-between align-items-center">
                                <span>Prompt</span>
                                <small class="text-muted"><span id="aiPromptCounter">0</span>/<span id="aiPromptMax">2990</span></small>
                            </label>
                            <textarea class="form-control" id="ai_prompt" name="ai_prompt" rows="4" placeholder="Enter your prompt..." required></textarea>
                            <div id="aiPromptWarning" style="display:none;" class="mt-1">
                                <div class="alert alert-danger py-1 px-2 mb-0" style="font-size:.8rem;">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    Prompt exceeds <strong id="aiPromptWarnLimit"></strong> characters. Default prompt will not be appended. Shorten to enable saving.
                                </div>
                            </div>
                        </div>
                        <div class="mb-0" id="defaultPromptWrapper">
                            <label for="default_prompt" class="form-label d-flex justify-content-between align-items-center">
                                <span>Default Prompt</span>
                                <small class="text-muted"><span id="defaultPromptCounter">0</span>/2990</small>
                            </label>
                            <textarea class="form-control" id="default_prompt" rows="4" maxlength="2990" placeholder="Default prompt..." readonly style="background-color:#f8f9fa;cursor:not-allowed;resize:none;">Ultra-photorealistic portrait of the exact same person as the reference image. Preserve identical facial identity, bone structure, proportions, and all features (eyes, nose, mouth, jawline, skin detail) with zero modification. Maintain the original expression, emotion, and mood. No beautification or facial alteration. Natural lighting, lifelike skin texture, high dynamic range, and maximum identity fidelity.</textarea>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="mb-3">
                            <label for="no_of_image" class="form-label">No of Image</label>
                            <input type="number" class="form-control" id="no_of_image" name="no_of_image" value="1" min="1" required>
                        </div>
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="name_change" name="name_change" value="1">
                            <label class="form-check-label" for="name_change">Name Change</label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3 d-none" id="imageHintWrapper">
                        <label for="image_hint" class="form-label">Image Hint</label>
                        <input type="text" class="form-control" id="image_hint" name="image_hint" maxlength="255" placeholder="Enter image hint">
                        <small class="form-text text-muted">Shown only when Name Change is enabled.</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept=".webp" onchange="previewImage(this)">
                        <small class="text-warning fw-bold"><i class="bi bi-exclamation-triangle me-1"></i>Only .webp images are allowed</small>
                        <div class="form-text">Upload image (max 4 MB)</div>
                        <div id="imagePreview" class="mt-2 d-none">
                            <img id="previewImg" src="#" alt="Preview" class="img-thumbnail">
                        </div>
                    </div>
                </div>
                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-primary py-2" id="submitBtn"><i class="bi bi-plus-lg me-2"></i>Add Image</button>
                </div>
            </form>
        </div>

        <div class="main-card">
            <div class="filters-row">
                <div class="filters-left">
                    <span>Show</span>
                    <select id="per_page" class="per-page-select custom-select-arrow">
                        @foreach ([10, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <span>entries</span>
                    <select id="category_filter" class="category-filter custom-select-arrow">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="search-container">
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by prompt, model, or category..." value="{{ $search }}">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="ajax-container">
                @include('ngendev.images.table')
            </div>
        </div>
    </div>

    <!-- Indexing Modal -->
    <div class="modal fade" id="indexingModal" tabindex="-1" aria-labelledby="indexingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="indexingModalLabel">
                        <i class="bi bi-arrow-up-down me-2"></i>Image Indexing
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="categorySelect" class="form-label">Select Category</label>
                                <select class="form-select" id="categorySelect">
                                    <option value="">Choose a category...</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Drag and drop images to reorder them. The new order will be saved automatically.
                            </div>
                        </div>
                    </div>

                    <div id="imagesContainer" class="row" style="min-height: 200px;">
                        <div class="col-12 text-center text-muted py-5">
                            <i class="bi bi-image fs-1"></i>
                            <p class="mt-2">Select a category to view and reorder images</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveOrderBtn" style="display: none;">
                        <i class="bi bi-save me-2"></i>Save Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Name Change Modal -->
    <div class="modal fade" id="bulkNameChangeModal" tabindex="-1" aria-labelledby="bulkNameChangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkNameChangeModalLabel">
                        <i class="bi bi-toggles me-2"></i>Bulk Name Change by Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Click <strong>Set True</strong> or <strong>Set False</strong> on a category to update the
                        <code>name_change</code> field for <strong>all images</strong> in that category at once.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px;">#</th>
                                    <th>Category</th>
                                    <th class="text-center" style="width:170px;">Current State</th>
                                    <th class="text-center" style="width:170px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="bulkNameChangeTbody">
                                @foreach ($categories as $i => $category)
                                    <tr data-category-id="{{ $category->id }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $category->category_name }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary nc-state-badge">Loading...</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-grid gap-1">
                                                <button type="button" class="btn btn-sm btn-set-true text-nowrap" style="background-color:#198754;border:1px solid #198754;color:#fff;border-radius:.25rem;" data-category-id="{{ $category->id }}">
                                                    <i class="bi bi-check-lg"></i> Set True
                                                </button>
                                                <button type="button" class="btn btn-sm btn-set-false text-nowrap" style="background-color:#dc3545;border:1px solid #dc3545;color:#fff;border-radius:.25rem;width:auto;height:auto;padding:.25rem .5rem;" data-category-id="{{ $category->id }}">
                                                    <i class="bi bi-x-lg"></i> Set False
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- jQuery and Bootstrap are loaded by the layout (head/body) -- don't reload them here --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        const PROMPT_LIMIT = 2990;
        const DEFAULT_PROMPT_TEXT = 'Ultra-photorealistic portrait of the exact same person as the reference image. Preserve identical facial identity, bone structure, proportions, and all features (eyes, nose, mouth, jawline, skin detail) with zero modification. Maintain the original expression, emotion, and mood. No beautification or facial alteration. Natural lighting, lifelike skin texture, high dynamic range, and maximum identity fidelity.';
        const USER_PROMPT_LIMIT = PROMPT_LIMIT - DEFAULT_PROMPT_TEXT.length;

        function toggleImageHint() {
            const checked = document.getElementById('name_change').checked;
            const wrapper = document.getElementById('imageHintWrapper');
            if (checked) wrapper.classList.remove('d-none');
            else {
                wrapper.classList.add('d-none');
                document.getElementById('image_hint').value = '';
            }
        }

        function updateCounter(textareaId, counterId, limit) {
            const ta = document.getElementById(textareaId);
            const counter = document.getElementById(counterId);
            if (!ta || !counter) return;
            const len = ta.value.length;
            counter.textContent = len;
            const over = len >= limit;
            counter.style.color = over ? '#dc3545' : '';
        }

        function updateUserPromptCounter() {
            const ta = document.getElementById('ai_prompt');
            const counter = document.getElementById('aiPromptCounter');
            const warning = document.getElementById('aiPromptWarning');
            const warnLimit = document.getElementById('aiPromptWarnLimit');
            const defWrapper = document.getElementById('defaultPromptWrapper');
            if (!ta || !counter) return;
            const len = ta.value.length;
            counter.textContent = len;
            const over = len > USER_PROMPT_LIMIT;
            counter.style.color = over ? '#dc3545' : '';
            ta.style.borderColor = over ? '#dc3545' : '';
            ta.style.backgroundColor = over ? '#fff0f0' : '';
            if (warnLimit) warnLimit.textContent = USER_PROMPT_LIMIT;
            if (warning) warning.style.display = over ? '' : 'none';
            if (defWrapper) defWrapper.style.display = over ? 'none' : '';
        }

        function updatePromptCounter() { updateUserPromptCounter(); }

        window.previewImage = function (input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (!file.name.toLowerCase().endsWith('.webp')) {
                    Swal.fire({ icon: 'warning', title: 'Invalid File Format', text: 'Only .webp images are allowed! Please select a .webp file.' });
                    input.value = '';
                    document.getElementById('imagePreview').classList.add('d-none');
                    return;
                }
                const maxSize = 4 * 1024 * 1024;
                if (file.size > maxSize) {
                    Swal.fire({ icon: 'error', title: 'File Too Large', text: 'The selected image exceeds 4 MB. Please choose a smaller file.' });
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            }
        };

        function editImage(button) {
            const id = button.getAttribute('data-id');
            const category = button.getAttribute('data-category');
            const model = button.getAttribute('data-model') || 'Ngendev Image';
            const prompt = button.getAttribute('data-prompt');
            const noOfImage = button.getAttribute('data-noofimage');
            const nameChange = button.getAttribute('data-namechange');
            const imageHint = button.getAttribute('data-imagehint') || '';
            const imagePath = button.getAttribute('data-image');

            document.getElementById('formTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-info"></i>Edit Ngendev Image';
            document.getElementById('submitBtn').innerHTML = '<i class="bi bi-save me-2"></i>Update Image';
            document.getElementById('cancelEdit').classList.remove('d-none');
            document.getElementById('editIdValue').textContent = id;
            document.getElementById('editIdDisplay').classList.remove('d-none');

            document.getElementById('ngendevImageForm').action = "{{ url('ngendev/images') }}/" + id;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('editId').value = id;
            document.getElementById('category_id').value = category;
            document.getElementById('ai_model').value = model;
            document.getElementById('ai_prompt').value = prompt || '';
            updateUserPromptCounter();
            document.getElementById('default_prompt').value = DEFAULT_PROMPT_TEXT;
            updateCounter('default_prompt', 'defaultPromptCounter', PROMPT_LIMIT);
            document.getElementById('no_of_image').value = noOfImage;
            document.getElementById('name_change').checked = (nameChange == 1);
            document.getElementById('image_hint').value = imageHint;
            toggleImageHint();

            if (imagePath) {
                const categoryName = button.closest('tr').querySelector('td:nth-child(2) strong').textContent.trim();
                const imgUrl = "{{ asset('upload/ngendev/images') }}/" + categoryName + '/category_image/' + imagePath;
                document.getElementById('previewImg').src = imgUrl;
                document.getElementById('imagePreview').classList.remove('d-none');
            } else {
                document.getElementById('imagePreview').classList.add('d-none');
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function confirmDelete(button) {
            const id = button.getAttribute('data-id');
            const categoryName = button.getAttribute('data-category');
            Swal.fire({
                title: 'Are you sure?',
                text: `Are you sure you want to delete the Ngendev image "${categoryName}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteForm-' + id).submit();
                }
            });
        }

        function resetForm() {
            document.getElementById('formTitle').innerHTML = '<i class="bi bi-plus-circle me-2 text-primary"></i>Add New Ngendev Image';
            document.getElementById('submitBtn').innerHTML = '<i class="bi bi-plus-lg me-2"></i>Add Image';
            document.getElementById('cancelEdit').classList.add('d-none');
            document.getElementById('editIdDisplay').classList.add('d-none');
            document.getElementById('editIdValue').textContent = '';
            document.getElementById('ngendevImageForm').action = "{{ route('ngendev.images.store') }}";
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('editId').value = '';
            document.getElementById('ngendevImageForm').reset();
            document.getElementById('no_of_image').value = 1;
            document.getElementById('name_change').checked = false;
            document.getElementById('image_hint').value = '';
            document.getElementById('ai_prompt').value = '';
            document.getElementById('default_prompt').value = DEFAULT_PROMPT_TEXT;
            updateUserPromptCounter();
            updateCounter('default_prompt', 'defaultPromptCounter', PROMPT_LIMIT);
            toggleImageHint();
            document.getElementById('imagePreview').classList.add('d-none');
        }

        $(document).ready(function () {
            document.getElementById('name_change').addEventListener('change', toggleImageHint);
            toggleImageHint();

            const promptEl = document.getElementById('ai_prompt');
            if (promptEl) {
                promptEl.removeAttribute('maxlength');
                const maxDisplay = document.getElementById('aiPromptMax');
                if (maxDisplay) maxDisplay.textContent = USER_PROMPT_LIMIT;
                promptEl.addEventListener('input', updateUserPromptCounter);
                updateUserPromptCounter();
            }

            const defaultPromptEl = document.getElementById('default_prompt');
            if (defaultPromptEl) {
                updateCounter('default_prompt', 'defaultPromptCounter', PROMPT_LIMIT);
            }

            const hintEl = document.getElementById('image_hint');
            if (hintEl) {
                hintEl.addEventListener('input', function () {
                    if (hintEl.value.trim() !== '') {
                        hintEl.classList.remove('is-invalid');
                        hintEl.style.borderColor = '';
                    }
                });
            }

            // AJAX loader for table
            function loadImages(page) {
                const $card = $('.main-card');
                $card.find('.loading-overlay').remove();
                $card.append('<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>');

                const ajaxUrl = "{{ route('ngendev.images.index') }}";
                const ajaxData = {
                    page: page || 1,
                    per_page: $('#per_page').val(),
                    search: $('#searchInput').val(),
                    category_id: $('#category_filter').val(),
                };
                console.log('[loadImages] GET', ajaxUrl, ajaxData);

                // Safety: force overlay removal after 35s no matter what
                const safetyTimer = setTimeout(function () {
                    $card.find('.loading-overlay').remove();
                    console.warn('[loadImages] safety timer fired; overlay forcibly removed');
                }, 35000);

                function showFailure(msg, detail) {
                    // Never leave the container blank: replace with an explicit failure block.
                    $('#ajax-container').html(
                        '<div class="alert alert-danger m-3">'
                        + '<strong>' + msg + '</strong><br>'
                        + '<small class="text-muted">' + (detail || '') + '</small><br>'
                        + '<button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="window.loadImages(1)">Retry</button>'
                        + '</div>'
                    );
                }

                $.ajax({
                    url: ajaxUrl,
                    type: 'GET',
                    dataType: 'json',
                    timeout: 30000,
                    cache: false,
                    data: ajaxData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    success: function (res) {
                        console.log('[loadImages] success', res);
                        if (res && typeof res.html === 'string' && res.html.length > 0) {
                            $('#ajax-container').html(res.html);
                            $('#totalCount').text(res.total ?? 0);
                        } else {
                            console.error('[loadImages] unexpected/empty response:', res);
                            showFailure('Empty or unexpected response from server.', 'Open DevTools console for the parsed payload.');
                        }
                    },
                    error: function (xhr, status) {
                        console.error('[loadImages] AJAX error status=', status, 'http=', xhr.status, 'body=', xhr.responseText);
                        showFailure('Failed to load images (' + (xhr.status || status) + ').',
                            'Status: ' + status + ' | HTTP: ' + xhr.status + ' | Check storage/logs/laravel.log');
                    },
                    complete: function () {
                        clearTimeout(safetyTimer);
                        $card.find('.loading-overlay').remove();
                    }
                });
            }
            window.loadImages = loadImages;

            // Filter handlers
            $('#per_page').on('change', function () { loadImages(1); });
            $('#category_filter').on('change', function () { loadImages(1); });

            // Search with debounce
            let searchTimer = null;
            $('#searchInput').on('keyup', function (e) {
                clearTimeout(searchTimer);
                if (e.key === 'Enter') {
                    loadImages(1);
                    return;
                }
                searchTimer = setTimeout(() => loadImages(1), 500);
            });
            $('#clearSearch').on('click', function () {
                $('#searchInput').val('');
                loadImages(1);
            });

            // Pagination click (delegated)
            $(document).on('click', '#ajax-container .pagination a.page-link', function (e) {
                e.preventDefault();
                const page = $(this).attr('data-page');
                if (page) loadImages(page);
            });

            // Double-click prompt cell to copy full prompt to clipboard
            $(document).on('dblclick', '.prompt-copy', function () {
                var fullPrompt = $(this).attr('data-full-prompt');
                if (!fullPrompt) return;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(fullPrompt).then(function () {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Prompt copied to clipboard!', showConfirmButton: false, timer: 2000, timerProgressBar: true });
                    }).catch(function () { fallbackCopy(fullPrompt); });
                } else {
                    fallbackCopy(fullPrompt);
                }
            });

            function fallbackCopy(text) {
                var el = document.createElement('textarea');
                el.value = text;
                el.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;';
                document.body.appendChild(el);
                el.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(el);
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Prompt copied!', showConfirmButton: false, timer: 2000 });
            }

            $('#cancelEdit').on('click', function () { resetForm(); });

            $('#ngendevImageForm').on('submit', function (e) {
                e.preventDefault();
                const promptVal = document.getElementById('ai_prompt').value;
                if (promptVal.length > USER_PROMPT_LIMIT) {
                    Swal.fire({ icon: 'warning', title: 'Prompt Too Long', text: 'Your prompt is ' + promptVal.length + ' characters. Maximum allowed is ' + USER_PROMPT_LIMIT + '. Please shorten your prompt to save.' });
                    document.getElementById('ai_prompt').focus();
                    return;
                }
                const nameChangeChecked = document.getElementById('name_change').checked;
                const hintEl2 = document.getElementById('image_hint');
                const hintVal = hintEl2.value.trim();
                if (nameChangeChecked && hintVal === '') {
                    hintEl2.classList.add('is-invalid');
                    hintEl2.style.borderColor = '#dc3545';
                    hintEl2.focus();
                    Swal.fire({ icon: 'error', title: 'Image Hint required', text: 'Image Hint is required when Name Change is enabled.' });
                    return;
                }
                hintEl2.classList.remove('is-invalid');
                hintEl2.style.borderColor = '';

                const formData = new FormData(this);
                const url = this.action;
                const method = document.getElementById('formMethod').value;

                Swal.fire({
                    title: method === 'POST' ? 'Adding Image...' : 'Updating Image...',
                    text: 'Please wait while we process your request',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (response) {
                        loadImages(1);
                        resetForm();
                        Swal.fire({
                            icon: 'success', title: 'Success!',
                            text: method === 'POST' ? 'Image added successfully!' : 'Image updated successfully!',
                            timer: 2000, showConfirmButton: false
                        });
                    },
                    error: function (xhr) {
                        let errMsg = 'An error occurred. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) errMsg = xhr.responseJSON.message;
                        Swal.fire({ icon: 'error', title: 'Error!', text: errMsg });
                    }
                });
            });

            // Indexing modal
            let sortableInstance = null;
            let currentCategoryId = null;

            $('#categorySelect').on('change', function () {
                const categoryId = this.value;
                if (categoryId) {
                    loadCategoryImages(categoryId);
                    currentCategoryId = categoryId;
                } else {
                    document.getElementById('imagesContainer').innerHTML = `
                        <div class="col-12 text-center text-muted py-5">
                            <i class="bi bi-image fs-1"></i>
                            <p class="mt-2">Select a category to view and reorder images</p>
                        </div>`;
                    document.getElementById('saveOrderBtn').style.display = 'none';
                    if (sortableInstance) { sortableInstance.destroy(); sortableInstance = null; }
                }
            });

            function loadCategoryImages(categoryId) {
                document.getElementById('imagesContainer').innerHTML = `
                    <div class="col-12 text-center text-muted py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Loading images...</p>
                    </div>`;

                $.ajax({
                    url: "{{ route('ngendev.images.indexing') }}",
                    type: 'GET',
                    data: { category_id: categoryId },
                    success: function (response) {
                        if (response.images && response.images.length > 0) {
                            displayImages(response.images);
                            initializeSortable();
                            document.getElementById('saveOrderBtn').style.display = 'inline-block';
                        } else {
                            document.getElementById('imagesContainer').innerHTML = `
                                <div class="col-12 text-center text-muted py-5">
                                    <i class="bi bi-image fs-1"></i>
                                    <p class="mt-2">No images found for this category</p>
                                </div>`;
                            document.getElementById('saveOrderBtn').style.display = 'none';
                        }
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error!', text: 'Failed to load images for this category.' });
                    }
                });
            }

            function displayImages(images) {
                const container = document.getElementById('imagesContainer');
                container.innerHTML = '';
                images.forEach((image, index) => {
                    const imageHtml = `
                        <div class="col-md-3 mb-3" data-image-id="${image.id}">
                            <div class="card sortable-item" style="cursor: move;">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-primary me-2">${index + 1}</span>
                                        <small class="text-muted">ID: ${image.id}</small>
                                    </div>
                                    ${image.image_url ?
                                        `<img src="${image.image_url}" class="img-fluid rounded" style="height:200px;object-fit:contain;width:100%;background:#f8f9fa;" alt="Image" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                        <div class="bg-light rounded" style="height:200px;display:none;align-items:center;justify-content:center;">
                                            <i class="bi bi-image text-muted fs-4"></i>
                                        </div>` :
                                        `<div class="bg-light rounded" style="height:200px;display:flex;align-items:center;justify-content:center;">
                                            <i class="bi bi-image text-muted fs-4"></i>
                                        </div>`
                                    }
                                    <div class="mt-2">
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                                            ${image.ai_prompt ? image.ai_prompt.substring(0, 50) + '...' : 'No prompt'}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    container.innerHTML += imageHtml;
                });
            }

            function initializeSortable() {
                if (sortableInstance) sortableInstance.destroy();
                const container = document.getElementById('imagesContainer');
                sortableInstance = new Sortable(container, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function () { updateOrderNumbers(); }
                });
            }

            function updateOrderNumbers() {
                const items = document.querySelectorAll('#imagesContainer .col-md-3');
                items.forEach((item, index) => {
                    const badge = item.querySelector('.badge');
                    if (badge) badge.textContent = index + 1;
                });
            }

            $('#saveOrderBtn').on('click', function () {
                if (!currentCategoryId) return;
                const items = document.querySelectorAll('#imagesContainer .col-md-3');
                const orderData = [];
                items.forEach((item, index) => {
                    orderData.push({ id: item.getAttribute('data-image-id'), sort_order: index + 1 });
                });

                Swal.fire({
                    title: 'Saving Order...',
                    text: 'Please wait while we update the image order',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: "{{ route('ngendev.images.updateOrder') }}",
                    type: 'POST',
                    data: { _token: "{{ csrf_token() }}", category_id: currentCategoryId, order: orderData },
                    success: function () {
                        Swal.fire({ icon: 'success', title: 'Success!', text: 'Image order updated successfully!', timer: 2000, showConfirmButton: false });
                        loadImages(1);
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error!', text: 'Failed to save image order.' });
                    }
                });
            });

            // Bulk Name Change
            (function () {
                const statsUrl = "{{ route('ngendev.images.nameChangeStats') }}";
                const bulkUrl = "{{ route('ngendev.images.bulkNameChange') }}";
                const csrfToken = "{{ csrf_token() }}";
                const modalEl = document.getElementById('bulkNameChangeModal');

                function renderBadge($cell, total, trueCount) {
                    let label, cls;
                    if (total === 0) { label = 'No images'; cls = 'bg-secondary'; }
                    else if (trueCount === total) { label = 'All True (' + total + ')'; cls = 'bg-success'; }
                    else if (trueCount === 0) { label = 'All False (' + total + ')'; cls = 'bg-danger'; }
                    else { label = 'Mixed (' + trueCount + '/' + total + ' true)'; cls = 'bg-warning text-dark'; }
                    $cell.find('.nc-state-badge').removeClass().addClass('badge nc-state-badge ' + cls).text(label);
                }

                function loadStats() {
                    $.get(statsUrl, function (resp) {
                        const stats = resp.stats || {};
                        $('#bulkNameChangeTbody tr').each(function () {
                            const $row = $(this);
                            const catId = $row.data('category-id');
                            const stat = stats[catId];
                            const total = stat ? parseInt(stat.total) : 0;
                            const trueCount = stat ? parseInt(stat.true_count) : 0;
                            renderBadge($row, total, trueCount);
                        });
                    });
                }

                modalEl.addEventListener('show.bs.modal', loadStats);

                $('#bulkNameChangeTbody').on('click', '.btn-set-true, .btn-set-false', function () {
                    const $btn = $(this);
                    const categoryId = $btn.data('category-id');
                    const value = $btn.hasClass('btn-set-true') ? 1 : 0;
                    const label = value ? 'true' : 'false';

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Set name_change to ' + label + ' for all images in this category?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, set ' + label,
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: value ? '#198754' : '#dc3545',
                    }).then((result) => {
                        if (!result.isConfirmed) return;
                        $btn.prop('disabled', true);
                        $.ajax({
                            url: bulkUrl,
                            type: 'POST',
                            data: { _token: csrfToken, category_id: categoryId, name_change: value },
                            success: function (resp) {
                                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: resp.message || 'Updated successfully!', showConfirmButton: false, timer: 3500, timerProgressBar: true });
                                loadStats();
                                if (typeof window.loadImages === 'function') window.loadImages(1);
                            },
                            error: function (xhr) {
                                Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to update.' });
                            },
                            complete: function () { $btn.prop('disabled', false); }
                        });
                    });
                });
            })();
        });
    </script>
@endsection
