<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Thumbnail</th>
                <th>Video</th>
                <th>Prompt</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($videos as $video)
                <tr id="row-{{ $video->id }}">
                    <td><strong>{{ $video->category?->category_name ?? 'N/A' }}</strong></td>
                    <td>
                        @if ($video->video_thumbnail)
                            <img loading="lazy" decoding="async" src="{{ asset('upload/ngendev/videos/' . ($video->category?->category_name ?? 'unknown') . '/video_thumbnail/' . $video->video_thumbnail) }}"
                                width="80">
                        @else
                            <div class="text-muted">No thumbnail</div>
                        @endif
                    </td>
                    <td>
                        @if ($video->video_path)
                            <video src="{{ asset('upload/ngendev/videos/' . ($video->category?->category_name ?? 'unknown') . '/category_video/' . $video->video_path) }}"
                                width="80" controls></video>
                        @else
                            <div class="text-muted">No video</div>
                        @endif
                    </td>
                    <td>
                        <div class="text-truncate" style="max-width:300px;"
                            title="{{ $video->ai_prompt }}">
                            {{ $video->ai_prompt }}
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
                            <input class="form-check-input ngd-video-status-toggle" type="checkbox" role="switch"
                                id="vid-status-{{ $video->id }}" data-id="{{ $video->id }}" {{ ($video->status ?? 1) ? 'checked' : '' }}>
                            <span id="vid-status-badge-{{ $video->id }}" class="badge {{ ($video->status ?? 1) ? 'bg-success' : 'bg-secondary' }}">
                                {{ ($video->status ?? 1) ? 'On' : 'Off' }}
                            </span>
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="action-btn edit-btn"
                                data-id="{{ $video->id }}"
                                data-category="{{ $video->category_id }}"
                                data-model="{{ $video->ai_model }}"
                                data-prompt="{{ $video->ai_prompt }}"
                                data-noofvideo="{{ $video->no_of_video ?? $video->no_of_image }}"
                                data-namechange="{{ $video->name_change }}"
                                data-imagehint="{{ $video->image_hint }}"
                                data-thumbnail="{{ $video->video_thumbnail }}"
                                data-video="{{ $video->video_path }}" onclick="editImage(this)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form id="deleteForm-{{ $video->id }}"
                                action="{{ route('ngendev-videos.destroy', $video->id) }}"
                                method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="action-btn delete-btn"
                                    data-id="{{ $video->id }}"
                                    data-category="{{ $video->category?->category_name ?? 'N/A' }}"
                                    onclick="confirmDelete(this)">
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
                            <div class="empty-state-icon">
                                <i class="bi bi-robot"></i>
                            </div>
                            <h4 class="empty-state-title">No Ngendev Videos Found</h4>
                            <p class="empty-state-text">Get started by adding your first Ngendev video</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($videos->total() > 0)
    <div class="pagination-container">
        <div class="pagination-info">
            Showing {{ $videos->firstItem() }} to {{ $videos->lastItem() }} of {{ $videos->total() }} entries
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                @if ($videos->onFirstPage())
                    <li class="page-item disabled"><span class="page-link">Previous</span></li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="{{ $videos->currentPage() - 1 }}">Previous</a>
                    </li>
                @endif

                @php
                    $currentPage = $videos->currentPage();
                    $lastPage = $videos->lastPage();
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

                @if ($videos->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="{{ $videos->currentPage() + 1 }}">Next</a>
                    </li>
                @else
                    <li class="page-item disabled"><span class="page-link">Next</span></li>
                @endif
            </ul>
        </nav>
    </div>
@endif
