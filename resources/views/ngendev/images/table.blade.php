<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-id">#ID</th>
                <th class="col-category">Category</th>
                <th class="col-thumb">Image</th>
                <th class="col-prompt">Prompt</th>
                <th class="col-status">Status</th>
                <th class="col-action text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($images as $img)
                <tr id="row-{{ $img->id }}">
                    <td><span class="badge bg-secondary">{{ $img->id }}</span></td>
                    <td><strong>{{ $img->category?->category_name ?? 'N/A' }}</strong></td>
                    <td>
                        @if ($img->image_path && $img->category)
                            <img loading="lazy" decoding="async" src="{{ asset('upload/ngendev/images/' . $img->category?->category_name . '/category_image/' . $img->image_path) }}" class="img-thumb" alt="">
                        @else
                            <div class="img-thumb bg-light d-flex align-items-center justify-content-center"><i class="bi bi-image text-muted"></i></div>
                        @endif
                    </td>
                    @php
                        $defaultPrompt = 'Ultra-photorealistic portrait of the exact same person as the reference image. Preserve identical facial identity, bone structure, proportions, and all features (eyes, nose, mouth, jawline, skin detail) with zero modification. Maintain the original expression, emotion, and mood. No beautification or facial alteration. Natural lighting, lifelike skin texture, high dynamic range, and maximum identity fidelity.';
                        $userLimit = 2990 - strlen($defaultPrompt);
                        $fullPrompt = (strlen($img->ai_prompt) <= $userLimit)
                            ? trim($img->ai_prompt . ' ' . $defaultPrompt)
                            : $img->ai_prompt;
                    @endphp
                    <td>
                        <div class="text-truncate prompt-copy" style="font-size:.85rem;cursor:copy;" title="Double-click to copy full prompt" data-full-prompt="{{ $fullPrompt }}">
                            {{ \Illuminate\Support\Str::limit($fullPrompt, 120) }}
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                            <input class="form-check-input ngd-img-status-toggle" type="checkbox" role="switch"
                                id="img-status-{{ $img->id }}" data-id="{{ $img->id }}" {{ ($img->status ?? 1) ? 'checked' : '' }}>
                            <span id="img-status-badge-{{ $img->id }}" class="badge {{ ($img->status ?? 1) ? 'bg-success' : 'bg-secondary' }}">
                                {{ ($img->status ?? 1) ? 'On' : 'Off' }}
                            </span>
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="action-btn edit-btn"
                                data-id="{{ $img->id }}"
                                data-category="{{ $img->category_id }}"
                                data-model="{{ $img->ai_model }}"
                                data-prompt="{{ $img->ai_prompt }}"
                                data-noofimage="{{ $img->no_of_image }}"
                                data-namechange="{{ $img->name_change }}"
                                data-imagehint="{{ $img->image_hint }}"
                                data-image="{{ $img->image_path }}" onclick="editImage(this)" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form id="deleteForm-{{ $img->id }}" action="{{ route('ngendev.images.destroy', $img->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="action-btn delete-btn" data-id="{{ $img->id }}" data-category="{{ $img->category?->category_name ?? 'N/A' }}" onclick="confirmDelete(this)" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-robot"></i></div>
                            <h4 class="empty-state-title">No Ngendev Images Found</h4>
                            <p class="empty-state-text">No images match your current search criteria.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($images->total() > 0)
    <div class="pagination-container">
        <div class="pagination-info">
            Showing {{ $images->firstItem() }} to {{ $images->lastItem() }} of {{ $images->total() }} entries
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                @if ($images->onFirstPage())
                    <li class="page-item disabled"><span class="page-link">Previous</span></li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="{{ $images->currentPage() - 1 }}">Previous</a>
                    </li>
                @endif

                @php
                    $currentPage = $images->currentPage();
                    $lastPage = $images->lastPage();
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

                @if ($images->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="{{ $images->currentPage() + 1 }}">Next</a>
                    </li>
                @else
                    <li class="page-item disabled"><span class="page-link">Next</span></li>
                @endif
            </ul>
        </nav>
    </div>
@endif
