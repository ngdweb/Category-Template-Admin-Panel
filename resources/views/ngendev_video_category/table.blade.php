<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th width="15%">Image</th>
                <th width="20%">Category Name</th>
                <th width="15%">Type</th>
                <th width="15%">Status</th>
                <th width="30%" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
                <tr id="row-{{ $category->id }}">
                    <td>
                        @php $images = json_decode($category->category_image, true); @endphp
                        @if (!empty($images) && isset($images[0]))
                            <img loading="lazy" decoding="async" src="{{ asset('upload/ngendev/videos/' . rawurlencode($category->category_name) . '/category_thumbnail_image/' . rawurlencode($images[0])) }}"
                                class="category-image">
                        @else
                            <div class="bg-light rounded d-flex align-items-center justify-content-center category-image">
                                <i class="bi bi-image text-muted fs-4"></i>
                            </div>
                        @endif
                    </td>
                    <td>
                        <strong class="text-dark">{{ $category->category_name }}</strong>
                    </td>
                    <td>
                        <select class="form-select form-select-sm type-select" data-id="{{ $category->id }}" style="width: 100px;">
                            <option value="Solo" {{ $category->type == 'Solo' ? 'selected' : '' }}>Solo</option>
                            <option value="Couple" {{ $category->type == 'Couple' ? 'selected' : '' }}>Couple</option>
                        </select>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="form-check form-switch m-0 me-2">
                                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                    id="status-{{ $category->id }}" data-id="{{ $category->id }}"
                                    data-type="{{ $category->type }}"
                                    {{ $category->status ? 'checked' : '' }}>
                            </div>
                            <span id="status-badge-{{ $category->id }}"
                                class="badge {{ $category->status ? 'bg-success' : 'bg-danger' }}">
                                {{ $category->status ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('notifications.form', ['module' => 'ngendev_video', 'id' => $category->id]) }}" class="action-btn notify-btn" title="Send notification" style="background-color:#f6c23e;color:#fff;">
                                <i class="bi bi-bell-fill"></i>
                            </a>
                            <a href="{{ route('ngendev-video-categories.edit', $category->id) }}" class="action-btn edit-btn"
                                data-bs-toggle="tooltip" title="Edit Category">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form id="deleteForm-{{ $category->id }}"
                                action="{{ route('ngendev-video-categories.destroy', $category->id) }}" method="POST"
                                class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="action-btn delete-btn" data-id="{{ $category->id }}"
                                    data-name="{{ $category->category_name }}" data-bs-toggle="tooltip"
                                    title="Delete Category">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="bi bi-tags"></i>
                            </div>
                            <h4 class="empty-state-title">No Categories Found</h4>
                            <p class="empty-state-text">Get started by creating your first Ngendev video category</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($categories->total() > 0)
    <div class="pagination-container">
        <div class="pagination-info">
            Showing {{ $categories->firstItem() }} to {{ $categories->lastItem() }} of {{ $categories->total() }} entries
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                @if ($categories->onFirstPage())
                    <li class="page-item disabled"><span class="page-link">Previous</span></li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="{{ $categories->currentPage() - 1 }}">Previous</a>
                    </li>
                @endif

                @php
                    $currentPage = $categories->currentPage();
                    $lastPage = $categories->lastPage();
                @endphp

                @if ($lastPage <= 8)
                    @for ($p = 1; $p <= $lastPage; $p++)
                        <li class="page-item {{ $p == $currentPage ? 'active' : '' }}">
                            <a class="page-link" href="#" data-page="{{ $p }}">{{ $p }}</a>
                        </li>
                    @endfor
                @else
                    @php
                        $start = max(1, $currentPage - 3);
                        $end = min($lastPage, $start + 7);
                        if ($end - $start < 7) {
                            $start = max(1, $end - 7);
                        }
                    @endphp

                    @if ($start > 1)
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="1">1</a>
                        </li>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    @endif

                    @for ($p = $start; $p <= $end; $p++)
                        <li class="page-item {{ $p == $currentPage ? 'active' : '' }}">
                            <a class="page-link" href="#" data-page="{{ $p }}">{{ $p }}</a>
                        </li>
                    @endfor

                    @if ($end < $lastPage)
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="{{ $lastPage }}">{{ $lastPage }}</a>
                        </li>
                    @endif
                @endif

                @if ($categories->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="{{ $categories->currentPage() + 1 }}">Next</a>
                    </li>
                @else
                    <li class="page-item disabled"><span class="page-link">Next</span></li>
                @endif
            </ul>
        </nav>
    </div>
@endif
