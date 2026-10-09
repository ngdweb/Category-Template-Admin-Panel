@extends('partials.layout')
@section('title', 'Ngendev Category Management')
@section('container')
    <style>
        .stats-badge {
            background-color: #eaecf4;
            color: #5a5c69;
            padding: .5rem 1rem;
            border-radius: .35rem;
            font-size: .85rem;
            font-weight: 700;
        }

        .main-card {
            background-color: #fff;
            border-radius: .35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, .15);
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background-color: #f8f9fc;
            color: #5a5c69;
            font-weight: 700;
            padding: .75rem;
            border-bottom: 1px solid #e3e6f0;
        }

        .data-table td {
            padding: .75rem;
            vertical-align: middle;
            border-bottom: 1px solid #e3e6f0;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: .35rem;
            color: #fff;
            text-decoration: none;
        }

        .edit-btn {
            background-color: var(--bs-info);
        }

        .delete-btn {
            background-color: var(--bs-danger);
            border: none;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state-icon {
            font-size: 3.5rem;
            color: #b7b9cc;
            margin-bottom: 1rem;
        }

        .empty-state-title {
            color: #5a5c69;
            margin-bottom: .5rem;
        }

        .empty-state-text {
            color: #858796;
            margin-bottom: 1.5rem;
        }

        .category-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }

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

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, .7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            border-radius: .35rem;
        }
    </style>

    <div class="container mt-4 mb-5">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title"><i class="bi bi-tags me-2"></i>Ngendev Video Category Management</h1>
                <p class="page-subtitle">Manage all Ngendev video categories in the system</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="stats-badge">
                    <i class="bi bi-collection"></i> Total: <span id="totalCount">{{ $categories->total() }}</span>
                    Categories
                </span>
                <div class="d-flex align-items-center bg-white p-2 rounded shadow-sm border">
                    <label class="form-check-label me-2 fw-bold small text-secondary" for="couple-status-toggle">Couple
                        Status:</label>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="couple-status-toggle" {{ isset($coupleActive) && $coupleActive ? 'checked' : '' }}>
                    </div>
                </div>
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#indexingModal">
                    <i class="bi bi-arrow-up-down me-2"></i>Indexing
                </button>
                <a href="{{ route('ngendev-video-categories.create') }}" class="btn btn-primary"><i
                        class="bi bi-plus-lg me-2"></i>Add Category</a>
            </div>
        </div>

        @if (session('success'))
            <div id="success-alert" class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle-fill me-2"></i><strong>{{ session('success') }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div id="error-alert" class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>{{ session('error') }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

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
                </div>
                <div class="search-container">
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search categories..." value="{{ $search }}">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="ajax-container">
                @include('ngendev_video_category.table')
            </div>
        </div>
    </div>

    <!-- Indexing Modal -->
    <div class="modal fade" id="indexingModal" tabindex="-1" aria-labelledby="indexingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="indexingModalLabel">
                        <i class="bi bi-arrow-up-down me-2"></i>Category Indexing
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Drag and drop categories to reorder them. The new order will be saved automatically.
                    </div>

                    <div id="categoriesContainer" class="list-group" style="min-height: 200px;">
                        <div class="col-12 text-center text-muted py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading categories...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveOrderBtn">
                        <i class="bi bi-save me-2"></i>Save Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm
                        Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category "<span id="categoryToDelete"></span>"?</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        let selectedCategoryId = null;

        $(document).ready(function () {
            // Auto dismiss alerts after 5 sec
            ['success-alert', 'error-alert'].forEach(id => {
                const alertEl = document.getElementById(id);
                if (alertEl) {
                    setTimeout(() => {
                        new bootstrap.Alert(alertEl).close();
                    }, 5000);
                }
            });

            // Delete (delegated so it works after AJAX swaps)
            $(document).on('click', '.delete-btn', function () {
                selectedCategoryId = $(this).data('id');
                $('#categoryToDelete').text($(this).data('name'));
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });
            $('#confirmDelete').on('click', function () {
                if (selectedCategoryId) {
                    document.getElementById(`deleteForm-${selectedCategoryId}`).submit();
                    // The global handler routes the delete through AJAX (no page reload),
                    // so close the confirm modal ourselves.
                    const modalEl = document.getElementById('deleteModal');
                    const modal = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                    if (modal) modal.hide();
                }
            });

            // AJAX loader
            function loadCategories(page) {
                const $card = $('.main-card');
                $card.append('<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>');

                $.ajax({
                    url: "{{ route('ngendev-video-categories.index') }}",
                    type: 'GET',
                    data: {
                        page: page || 1,
                        per_page: $('#per_page').val(),
                        search: $('#searchInput').val(),
                    },
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    dataType: 'json',
                    success: function (res) {
                        if (res && typeof res.html === 'string') {
                            $('#ajax-container').html(res.html);
                            $('#totalCount').text(res.total ?? 0);
                            // Re-init tooltips after content swap
                            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                            tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
                        } else {
                            console.error('Unexpected response:', res);
                            Swal.fire({ icon: 'error', title: 'Error', text: 'Unexpected server response. See console.' });
                        }
                    },
                    error: function (xhr) {
                        console.error('AJAX error', xhr.status, xhr.responseText);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load categories (' + xhr.status + ').' });
                    },
                    complete: function () {
                        $card.find('.loading-overlay').remove();
                    }
                });
            }
            // Expose for other handlers (indexing save callback)
            window.loadCategories = loadCategories;

            // Filter handlers
            $('#per_page').on('change', function () { loadCategories(1); });

            // Search with debounce
            let searchTimer = null;
            $('#searchInput').on('keyup', function (e) {
                clearTimeout(searchTimer);
                if (e.key === 'Enter') {
                    loadCategories(1);
                    return;
                }
                searchTimer = setTimeout(() => loadCategories(1), 500);
            });
            $('#clearSearch').on('click', function () {
                $('#searchInput').val('');
                loadCategories(1);
            });

            // Pagination click (delegated)
            $(document).on('click', '#ajax-container .pagination a.page-link', function (e) {
                e.preventDefault();
                const page = $(this).attr('data-page');
                if (page) loadCategories(page);
            });

            // Indexing Logic
            let sortableInstance = null;
            const indexingModal = document.getElementById('indexingModal');

            indexingModal.addEventListener('show.bs.modal', function () {
                loadCategoriesForIndexing();
            });

            function loadCategoriesForIndexing() {
                const container = document.getElementById('categoriesContainer');
                container.innerHTML = `
                    <div class="col-12 text-center text-muted py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading categories...</p>
                    </div>`;

                $.ajax({
                    url: "{{ route('ngendev-video-categories.indexing') }}",
                    type: 'GET',
                    success: function (response) {
                        if (response.categories && response.categories.length > 0) {
                            displayCategories(response.categories);
                            initializeSortable();
                        } else {
                            container.innerHTML = `
                                <div class="col-12 text-center text-muted py-5">
                                    <i class="bi bi-tags fs-1"></i>
                                    <p class="mt-2">No categories found</p>
                                </div>`;
                        }
                    },
                    error: function (xhr) {
                        console.error('Error loading categories:', xhr.responseText);
                        container.innerHTML = `
                            <div class="col-12 text-center text-danger py-5">
                                <i class="bi bi-exclamation-triangle fs-1"></i>
                                <p class="mt-2">Error loading categories</p>
                            </div>`;
                    }
                });
            }

            function displayCategories(categories) {
                const container = document.getElementById('categoriesContainer');
                container.innerHTML = '';

                categories.forEach((category, index) => {
                    const categoryHtml = `
                        <div class="list-group-item d-flex align-items-center justify-content-between sortable-item" data-id="${category.id}" style="cursor: move;">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-grip-vertical me-2 text-muted"></i>
                                <span class="fw-bold me-2">${index + 1}.</span>
                                <span>${category.category_name}</span>
                            </div>
                            <span class="badge bg-secondary rounded-pill">ID: ${category.id}</span>
                        </div>`;
                    container.innerHTML += categoryHtml;
                });
            }

            function initializeSortable() {
                if (sortableInstance) {
                    sortableInstance.destroy();
                }

                const container = document.getElementById('categoriesContainer');
                sortableInstance = new Sortable(container, {
                    animation: 150,
                    ghostClass: 'bg-light',
                    onEnd: function (evt) {
                        updateOrderNumbers();
                    }
                });
            }

            function updateOrderNumbers() {
                const items = document.querySelectorAll('#categoriesContainer .list-group-item');
                items.forEach((item, index) => {
                    const numberSpan = item.querySelector('.fw-bold');
                    if (numberSpan) {
                        numberSpan.textContent = (index + 1) + '.';
                    }
                });
            }

            document.getElementById('saveOrderBtn').addEventListener('click', function () {
                const items = document.querySelectorAll('#categoriesContainer .list-group-item');
                const orderData = [];

                items.forEach((item, index) => {
                    const id = item.getAttribute('data-id');
                    orderData.push({
                        id: id,
                        sort_order: index + 1
                    });
                });

                const btn = this;
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';

                $.ajax({
                    url: "{{ route('ngendev-video-categories.updateOrder') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        order: orderData
                    },
                    success: function (response) {
                        btn.disabled = false;
                        btn.innerHTML = originalText;

                        if (response.success) {
                            const modal = bootstrap.Modal.getInstance(indexingModal);
                            modal.hide();

                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // refresh table via AJAX instead of full reload
                                loadCategories(1);
                            });
                        }
                    },
                    error: function (xhr) {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        console.error('Error saving order:', xhr.responseText);

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Something went wrong!'
                        });
                    }
                });
            });

            // Status Toggle (delegated)
            $(document).on('change', '.status-toggle', function () {
                const id = $(this).data('id');
                const isChecked = $(this).is(':checked');
                const status = isChecked ? 1 : 0;
                const badge = $(`#status-badge-${id}`);

                $.ajax({
                    url: "{{ route('ngendev-video-categories.updateStatus') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id,
                        status: status
                    },
                    success: function (res) {
                        if (res.success) {
                            if (isChecked) {
                                badge.removeClass('bg-danger').addClass('bg-success').text('Active');
                            } else {
                                badge.removeClass('bg-success').addClass('bg-danger').text('Inactive');
                            }

                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });

                            Toast.fire({
                                icon: 'success',
                                title: res.message
                            });
                        } else {
                            // Revert if success is false
                            $(`#status-${id}`).prop('checked', !isChecked);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.message
                            });
                        }
                    },
                    error: function (xhr) {
                        // Revert on error
                        $(`#status-${id}`).prop('checked', !isChecked);

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update status'
                        });
                    }
                });
            });

            // Global Couple Status Toggle
            $('#couple-status-toggle').on('change', function () {
                const isChecked = $(this).is(':checked');
                const status = isChecked ? 1 : 0;

                $.ajax({
                    url: "{{ route('ngendev-video-categories.updateCoupleStatus') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        status: status
                    },
                    success: function (res) {
                        if (res.success) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });

                            Toast.fire({
                                icon: 'success',
                                title: res.message
                            });
                            // Reload table to reflect coupled categories' new statuses
                            loadCategories(1);
                        }
                    },
                    error: function (xhr) {
                        // Revert on error
                        $('#couple-status-toggle').prop('checked', !isChecked);

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update global couple status'
                        });
                    }
                });
            });

            // Type Change (delegated)
            $(document).on('change', '.type-select', function () {
                const id = $(this).data('id');
                const type = $(this).val();

                $.ajax({
                    url: "{{ route('ngendev-video-categories.updateType') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id,
                        type: type
                    },
                    success: function (res) {
                        if (res.success) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });

                            Toast.fire({
                                icon: 'success',
                                title: res.message
                            });
                            // Refresh so the status toggle disabled state / values update
                            loadCategories(1);
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update type'
                        });
                    }
                });
            });

        });
    </script>
@endsection
