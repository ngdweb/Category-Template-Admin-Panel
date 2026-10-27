<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>

    <!-- Favicon -->
    <link href="{{ asset('NiceAdmin/images/icon/logo-2023 copy.png') }}" rel="icon" />

    <!-- Preconnect to speed up font/CDN handshake -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <!-- Google Fonts (single Poppins request with display=swap, no layout shift) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- CSS (each library loaded ONCE) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('NiceAdmin/assets/css/style.css') }}">

    {{-- jQuery loaded once in head so inline view scripts can use $ before body --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        #sidebar-nav .nav-link {
            transition: all 0.2s ease-in-out;
        }

        #sidebar-nav .nav-link:hover {
            background: #3b3b4d !important;
            color: #fff !important;
        }

        #sidebar-nav .nav-link.active {
            background: #007bff !important;
            color: #fff !important;
        }

        #sidebar-nav ul.collapse .nav-link:hover {
            background: #2a2a3b !important;
        }

        .sidebar-nav .nav-link i {
            margin-right: 5px !important;
        }
    </style>

</head>

<body>
    <aside id="sidebar" class="sidebar">
        <div class="p-3 border-bottom">
            <a class="sidebar-brand d-flex align-items-center" href="{{ route('dashboard.clearGroup') }}">
                <span class="fw-bold fs-5 text-white">NGD Admin</span>
            </a>
        </div>

        @php $sidebarGroup = session('dashboard_group'); @endphp
        <ul class="sidebar-nav flex-column p-2" id="sidebar-nav" style="background:#1e1e2d; min-height:100vh;">

            {{-- Dashboard --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
                Component
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-3
                {{ request()->is('dashboard') || request()->is('/') || request()->routeIs('dashboard') ? 'active bg-primary text-white' : 'text-light' }}"
                    href="{{ url('dashboard') }}">
                    <i class="bi bi-speedometer me-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            @if ($sidebarGroup)
                <li class="nav-item mb-1">
                    <a class="nav-link d-flex align-items-center px-3 py-2 rounded-3 text-light"
                        href="{{ route('dashboard.clearGroup') }}" style="background:#2a2a3b;">
                        <i class="bi bi-arrow-left-right me-2 text-info"></i>
                        <span>Switch Group ({{ $sidebarGroup === 'baby' ? 'AI Baby' : 'AI NGD' }})</span>
                    </a>
                </li>
            @endif

            @if ($sidebarGroup === 'ngd')
            {{-- Notify Module --}}
            @php
                $isNotifyActive = request()->is('notifications*') || request()->is('firebase-projects*');
            @endphp
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #f6c23e;">
                <i class="bi bi-megaphone-fill me-1"></i> NOTIFY MODULE
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-3 {{ request()->is('notifications') || (request()->is('notifications/*') && !request()->is('notifications/settings')) ? 'active bg-primary text-white' : 'text-light' }}"
                    href="{{ route('notifications.index') }}">
                    <i class="bi bi-bell me-2"></i><span>Notifications</span>
                </a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-3 {{ request()->is('notifications/settings') ? 'active bg-primary text-white' : 'text-light' }}"
                    href="{{ route('notifications.settings') }}">
                    <i class="bi bi-gear me-2"></i><span>Notification Settings</span>
                </a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-3 {{ request()->is('firebase-projects*') ? 'active bg-primary text-white' : 'text-light' }}"
                    href="{{ route('firebase-projects.index') }}">
                    <i class="bi bi-fire me-2"></i><span>Firebase Projects</span>
                </a>
            </li>
            @endif

            @if ($sidebarGroup === 'baby')
            {{-- AI Image Module Header --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
                AI Baby Image Module
            </li>

            @php
                $currentRoute = request()->route()->getName();
                $currentSubId = request()->route('id');
                $currentPath = request()->path();

                $isSubRoute =
                    str_starts_with($currentRoute ?? '', 'subcategories.') ||
                    str_contains($currentPath, 'subcategories/subcategory') ||
                    str_contains($currentPath, 'subcategories/subcategories');

                $categories = \Illuminate\Support\Facades\Cache::remember(
                    'sidebar.ai_image_categories',
                    60,
                    fn() => \App\Models\AiImageCategory::orderBy('id')->get()
                );
                $allSubs = $allSubs ?? \Illuminate\Support\Facades\Cache::remember(
                    'sidebar.subcategories_grouped',
                    60,
                    fn() => \App\Models\Subcategory::select('id', 'title', 'category_name')->get()->groupBy('category_name')
                );

                $isBabyPhotoActive = false;

                foreach ($categories as $cat) {
                    $subs = $allSubs[$cat->name] ?? [];

                    // Check if subcategory form is open
                    $subActive =
                        $isSubRoute &&
                        $currentRoute === 'subcategories.form' &&
                        request('category_name') === $cat->name;

                    // Check if show page of this subcategory
                    $activeSub =
                        $isSubRoute && $currentRoute === 'subcategories.show'
                        ? collect($subs)->first(fn($s) => $currentSubId == $s->id)
                        : null;

                    // Check if add-details page of this subcategory
                    $isSubcategoryRoute = str_contains($currentPath, "subcategories/subcategory/{$currentSubId}");
                    $isSubcategoryDetailsRoute = str_contains(
                        $currentPath,
                        "subcategories/subcategories/{$currentSubId}/add-details",
                    );

                    if ($subActive || $activeSub || $isSubcategoryRoute || $isSubcategoryDetailsRoute) {
                        $isBabyPhotoActive = true;
                        break;
                    }
                }

                $isBabyPhotoSettingActive = request()->routeIs('ai-image-baby-photo-setting.index');
                $isBabyPhotoActive = $isBabyPhotoActive || $isBabyPhotoSettingActive;
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link collapse-toggle d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-light
        {{ $isBabyPhotoActive ? 'bg-secondary' : '' }}" href="javascript:void(0);" data-target="#babyPhotoCollapse"
                    style="transition: all 0.2s;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-image-fill me-2 text-info"></i>
                        <span class="fw-semibold">AI Image Baby Photo</span>
                    </div>
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </a>

                <ul id="babyPhotoCollapse" class="submenu-list nav flex-column ps-4 mt-2"
                    style="display: {{ $isBabyPhotoActive ? 'block' : 'none' }};">

                    {{-- Baby Photo Setting --}}
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('ai-image-baby-photo-setting.index') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('ai-image-baby-photo-setting.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-gear me-2"></i>
                            <span>AI Image Baby Photo Setting</span>
                        </a>
                    </li>

                    @foreach ($categories as $cat)
                        @php
                            $catId = 'cat-' . Str::slug($cat->name, '-');

                            $isImageOrigin = !request()->has('origin') || request('origin') === 'image';

                            $subActive =
                                $isSubRoute &&
                                $currentRoute === 'subcategories.form' &&
                                request('category_name') === $cat->name;

                            $activeSub =
                                $isSubRoute && $currentRoute === 'subcategories.show'
                                ? collect($allSubs[$cat->name] ?? [])->first(fn($s) => $currentSubId == $s->id)
                                : null;

                            $isOpen = ($subActive || $activeSub) && $isImageOrigin;
                        @endphp

                        <li class="nav-item mb-1">
                            <a class="nav-link collapse-toggle d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-light
                                {{ $isOpen ? 'bg-secondary' : '' }}" href="javascript:void(0);" data-target="#{{ $catId }}"
                                style="transition: all 0.2s;">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-folder-fill me-2 text-warning"></i>
                                    <span class="fw-semibold">{{ $cat->name }}</span>
                                </div>
                                <i class="bi bi-chevron-down small chevron-icon"></i>
                            </a>

                            <ul id="{{ $catId }}" class="submenu-list nav flex-column ps-4 mt-2"
                                style="display: {{ $isOpen ? 'block' : 'none' }}">

                                {{-- Add Subcategory --}}
                                <li class="mb-1">
                                    <a href="{{ route('subcategories.form', ['category_name' => $cat->name, 'origin' => 'image']) }}" class="nav-link d-flex align-items-center px-2 py-1 rounded-2
                                            {{ $subActive && $isImageOrigin ? 'active bg-primary text-white' : 'text-light' }}"
                                        style="transition: all 0.2s;">
                                        <i class="bi bi-plus-circle me-2"></i>
                                        <span>Add Subcategory</span>
                                    </a>
                                </li>

                                {{-- Existing Subcategories --}}
                                @foreach ($allSubs[$cat->name] ?? [] as $sub)
                                                    <li class="mb-1">
                                                        <a href="{{ route('subcategories.show', ['id' => $sub->id, 'origin' => 'image']) }}" class="nav-link d-flex align-items-center px-2 py-1 rounded-2
                                                                                                            {{ $isSubRoute && $currentRoute === 'subcategories.show' && $currentSubId == $sub->id && $isImageOrigin
                                    ? 'active bg-primary text-white'
                                    : 'text-light' }}">
                                                            <i class="bi bi-circle me-2"></i>
                                                            <span>{{ $sub->title }}</span>
                                                        </a>
                                                    </li>
                                @endforeach

                                {{-- Toggle button --}}
                                <li class="mb-1 mt-1 d-flex align-items-center px-2 py-1 rounded-2">
                                    <span class="text-light me-2">{{ $cat->status ? 'Published' : 'Draft' }}</span>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input toggle-status" type="checkbox"
                                            data-name="{{ $cat->name }}" {{ $cat->status ? 'checked' : '' }}>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </li>

            <script>
                $(document).on('change', '.toggle-status', function () {
                    let $toggled = $(this);
                    let categoryName = $toggled.data('name');
                    let status = $toggled.is(':checked') ? 1 : 0;
                    let imageToggleUrl = "{{ route('ai-image-categories.toggle-status') }}";

                    let url = imageToggleUrl;
                    if ($toggled.hasClass('video-category')) {
                        url = "{{ route('ai-video-categories.toggle-status') }}";
                    }

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            name: categoryName,
                            status: status
                        },
                        success: function (res) {
                            if (res.success) {
                                $toggled.closest('li').find('span').first().text(res.status ? 'Published' : 'Draft');

                                // Opposition: when turning one Published ON, turn all other image category toggles OFF
                                if (status === 1 && !$toggled.hasClass('video-category')) {
                                    $('.toggle-status').not($toggled).not('.video-category').filter(':checked').each(function () {
                                        let $other = $(this);
                                        let otherName = $other.data('name');
                                        $.ajax({
                                            url: imageToggleUrl,
                                            type: 'POST',
                                            data: {
                                                _token: "{{ csrf_token() }}",
                                                name: otherName,
                                                status: 0
                                            },
                                            success: function (r) {
                                                if (r.success) {
                                                    $other.prop('checked', false);
                                                    $other.closest('li').find('span').first().text('Draft');
                                                }
                                            }
                                        });
                                    });
                                }
                            }
                        },
                        error: function (err) {
                            console.log('Error updating status', err);
                        }
                    });
                });
            </script>
            @endif

            @if ($sidebarGroup === 'baby')
            {{-- AI Baby Video Module --}}
            @php
                $isBabyVideoSettingActive = request()->routeIs('ai-baby-video-module-setting.*');
                $isBabyVideoCategoryActive = request()->routeIs('ai-baby-video.categories.*');
                $isBabyVideoItemActive = request()->routeIs('ai-baby-video.videos.*');

                $isBabyVideoActive = $isBabyVideoSettingActive || $isBabyVideoCategoryActive || $isBabyVideoItemActive;
            @endphp

            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
                AI Baby Video Module
            </li>

            <li class="nav-item mb-1">
                <a class="nav-link collapse-toggle d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-light
            {{ $isBabyVideoActive ? 'bg-secondary' : '' }}" href="javascript:void(0);" data-target="#babyVideoCollapse"
                    style="transition: all 0.2s;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-camera-video-fill me-2 text-warning"></i>
                        <span class="fw-semibold">AI Baby Video Module</span>
                    </div>
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </a>

                <ul id="babyVideoCollapse" class="submenu-list nav flex-column ps-4 mt-2"
                    style="display: {{ $isBabyVideoActive ? 'block' : 'none' }};">

                    {{-- Baby Video Setting --}}
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('ai-baby-video-module-setting.index') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('ai-baby-video-module-setting.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-gear me-2"></i>
                            <span>AI Baby Video Setting</span>
                        </a>
                    </li>

                    {{-- AI Video Category --}}
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ $isBabyVideoCategoryActive ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('ai-baby-video.categories.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-tags me-2"></i>
                            <span>AI Video Category</span>
                        </a>
                    </li>

                    {{-- AI Baby Video --}}
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ $isBabyVideoItemActive ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('ai-baby-video.videos.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-play-btn me-2"></i>
                            <span>AI Baby Video</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            @if ($sidebarGroup === 'ngd')
            {{-- AI Image NGD Module --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
               AI Image NGD Module
            </li>
            @php
                $isNGDActive = request()->is('ngendev/categories*') || request()->is('ngendev/images*');
                $isNGDSettingActive = request()->routeIs('ai-image-ngd-setting.index');
                $isNGDActive = $isNGDActive || $isNGDSettingActive;
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link collapse-toggle d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-light
                {{ $isNGDActive ? 'bg-secondary' : '' }}" href="javascript:void(0);" data-target="#ngdCollapse"
                    style="transition: all 0.2s;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-robot me-2 text-success"></i>
                        <span class="fw-semibold">AI Image NGD</span>
                    </div>
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </a>

                {{-- NGD Module Submenu --}}
                <ul id="ngdCollapse" class="submenu-list nav flex-column ps-4 mt-2"
                    style="display: {{ $isNGDActive ? 'block' : 'none' }};">

                    {{-- AI Image NGD Setting --}}
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('ai-image-ngd-setting.index') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('ai-image-ngd-setting.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-gear me-2"></i>
                            <span>AI Image NGD Setting</span>
                        </a>
                    </li>

                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->is('ngendev/categories*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('ngendev.categories.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-tags me-2"></i>
                            <span>AI Category</span>
                        </a>
                    </li>

                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->is('ngendev/images*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('ngendev.images.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-image me-2"></i>
                            <span>AI Image</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- AI Video NGD Module --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
               AI Video NGD Module
            </li>
            @php
                $isVideoNGDActive = request()->is('ngendev/video-categories*') || request()->is('ngendev/videos*');
                $isVideoNGDSettingActive = request()->routeIs('ai-video-ngd-setting.index');
                $isVideoNGDActive = $isVideoNGDActive || $isVideoNGDSettingActive;
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link collapse-toggle d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-light
                {{ $isVideoNGDActive ? 'bg-secondary' : '' }}" href="javascript:void(0);" data-target="#videoNgdCollapse"
                    style="transition: all 0.2s;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-camera-reels me-2 text-success"></i>
                        <span class="fw-semibold">AI Video NGD</span>
                    </div>
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </a>

                {{-- Video NGD Module Submenu --}}
                <ul id="videoNgdCollapse" class="submenu-list nav flex-column ps-4 mt-2"
                    style="display: {{ $isVideoNGDActive ? 'block' : 'none' }};">

                    {{-- AI Video NGD Setting --}}
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('ai-video-ngd-setting.index') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('ai-video-ngd-setting.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-gear me-2"></i>
                            <span>AI Video NGD Setting</span>
                        </a>
                    </li>

                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->is('ngendev/video-categories*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('ngendev-video-categories.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-tags me-2"></i>
                            <span>AI Video Category</span>
                        </a>
                    </li>

                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->is('ngendev/videos*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('ngendev-videos.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-camera-video me-2"></i>
                            <span>AI Video</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Filter AI Image Module --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
               Filter AI Image Module
            </li>
            @php
                $isFilterAIActive = request()->is('filter-ai-image/categories*') || request()->is('filter-ai-image/images*');
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link collapse-toggle d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-light
                {{ $isFilterAIActive ? 'bg-secondary' : '' }}" href="javascript:void(0);" data-target="#filterAICollapse"
                    style="transition: all 0.2s;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-filter-circle me-2 text-primary"></i>
                        <span class="fw-semibold">Filter AI Image</span>
                    </div>
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </a>

                {{-- Filter AI Module Submenu --}}
                <ul id="filterAICollapse" class="submenu-list nav flex-column ps-4 mt-2"
                    style="display: {{ $isFilterAIActive ? 'block' : 'none' }};">


                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->is('filter-ai-image/categories*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('filter-ai-image.categories.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-tags me-2"></i>
                            <span>Filter AI Category</span>
                        </a>
                    </li>

                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->is('filter-ai-image/images*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('filter-ai-image.images.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-image me-2"></i>
                            <span>Filter AI Image</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Top Slider Module --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
               Top Slider Module
            </li>
            @php
                $isTopSliderActive = request()->routeIs('top-slider.categories.*');
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link collapse-toggle d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-light
                {{ $isTopSliderActive ? 'bg-secondary' : '' }}" href="javascript:void(0);" data-target="#topSliderCollapse"
                    style="transition: all 0.2s;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-images me-2 text-info"></i>
                        <span class="fw-semibold">Top Slider Module</span>
                    </div>
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </a>

                {{-- Top Slider Submenu --}}
                <ul id="topSliderCollapse" class="submenu-list nav flex-column ps-4 mt-2"
                    style="display: {{ $isTopSliderActive ? 'block' : 'none' }};">

                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('top-slider.categories.*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('top-slider.categories.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-tags me-2"></i>
                            <span>Top Slider Category</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Lips Sync Module --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
                Lips Sync Module
            </li>
            @php
                $isLipsSyncActive = request()->routeIs('lips-sync.*');
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link collapse-toggle d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-light
                {{ $isLipsSyncActive ? 'bg-secondary' : '' }}" href="javascript:void(0);" data-target="#lipsSyncCollapse"
                    style="transition: all 0.2s;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-music-note-beamed me-2 text-info"></i>
                        <span class="fw-semibold">Lips Sync Module</span>
                    </div>
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </a>

                <ul id="lipsSyncCollapse" class="submenu-list nav flex-column ps-4 mt-2"
                    style="display: {{ $isLipsSyncActive ? 'block' : 'none' }};">
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('lips-sync.categories.*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('lips-sync.categories.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-grid-3x3-gap me-2"></i>
                            <span>Lips Sync Category</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('lips-sync.items.*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('lips-sync.items.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-collection-play me-2"></i>
                            <span>Lips Sync</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            @if ($sidebarGroup === 'baby')
            {{-- Dynamic Photo Frame Module --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
                Dynamic Photo Frame Module
            </li>
            @php
                $isDynamicFrameActive = request()->routeIs('dynamic-photo-frame.*');
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link collapse-toggle d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-light
                {{ $isDynamicFrameActive ? 'bg-secondary' : '' }}" href="javascript:void(0);" data-target="#dynamicFrameCollapse"
                    style="transition: all 0.2s;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-easel-fill me-2 text-info"></i>
                        <span class="fw-semibold">Dynamic Photo Frame</span>
                    </div>
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </a>

                <ul id="dynamicFrameCollapse" class="submenu-list nav flex-column ps-4 mt-2"
                    style="display: {{ $isDynamicFrameActive ? 'block' : 'none' }};">
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('dynamic-photo-frame.categories.*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('dynamic-photo-frame.categories.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-tags me-2"></i>
                            <span>Dynamic Photo Frame Category</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('dynamic-photo-frame.frames.*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('dynamic-photo-frame.frames.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-images me-2"></i>
                            <span>Dynamic Photo Frame</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Sticker Module --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
                Sticker Module
            </li>
            @php
                $isStickerActive = request()->routeIs('sticker.*');
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link collapse-toggle d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-light
                {{ $isStickerActive ? 'bg-secondary' : '' }}" href="javascript:void(0);" data-target="#stickerCollapse"
                    style="transition: all 0.2s;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-stickies-fill me-2 text-warning"></i>
                        <span class="fw-semibold">Sticker Module</span>
                    </div>
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </a>

                <ul id="stickerCollapse" class="submenu-list nav flex-column ps-4 mt-2"
                    style="display: {{ $isStickerActive ? 'block' : 'none' }};">
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('sticker.categories.*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('sticker.categories.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-tags me-2"></i>
                            <span>Sticker Category</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('sticker.stickers.*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('sticker.stickers.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-emoji-smile me-2"></i>
                            <span>Sticker</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Font Module --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
                Font Module
            </li>
            @php
                $isFontActive = request()->routeIs('fonts.*');
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-3 {{ $isFontActive ? 'active bg-primary text-white' : 'text-light' }}"
                    href="{{ route('fonts.index') }}" style="transition: all 0.2s;">
                    <i class="bi bi-fonts me-2 text-info"></i>
                    <span class="fw-semibold">Font Module</span>
                </a>
            </li>

            {{-- Filter Module --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
                Filter Module
            </li>
            @php
                $isFilterModuleActive = request()->routeIs('filter.categories.*') || request()->routeIs('filter.filters.*');
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link collapse-toggle d-flex align-items-center justify-content-between px-3 py-2 rounded-3 text-light
                {{ $isFilterModuleActive ? 'bg-secondary' : '' }}" href="javascript:void(0);" data-target="#filterModuleCollapse"
                    style="transition: all 0.2s;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-funnel-fill me-2 text-info"></i>
                        <span class="fw-semibold">Filter Module</span>
                    </div>
                    <i class="bi bi-chevron-down small chevron-icon"></i>
                </a>

                <ul id="filterModuleCollapse" class="submenu-list nav flex-column ps-4 mt-2"
                    style="display: {{ $isFilterModuleActive ? 'block' : 'none' }};">
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('filter.categories.*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('filter.categories.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-tags me-2"></i>
                            <span>Category</span>
                        </a>
                    </li>
                    <li class="nav-item mb-1">
                        <a class="nav-link d-flex align-items-center px-2 py-1 rounded-2 {{ request()->routeIs('filter.filters.*') ? 'active bg-primary text-white' : 'text-light' }}"
                            href="{{ route('filter.filters.index') }}" style="transition: all 0.2s;">
                            <i class="bi bi-sliders me-2"></i>
                            <span>Filters</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Doodle Module --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
                Doodle Module
            </li>
            @php
                $isDoodleActive = request()->routeIs('doodles.*');
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-3 {{ $isDoodleActive ? 'active bg-primary text-white' : 'text-light' }}"
                    href="{{ route('doodles.index') }}" style="transition: all 0.2s;">
                    <i class="bi bi-stars me-2 text-warning"></i>
                    <span class="fw-semibold">Doodle Module</span>
                </a>
            </li>

            {{-- Baby AI Home Screen Slider Module --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
                Baby AI Home Screen Slider
            </li>
            @php
                $isBabyAiHomeSliderActive = request()->routeIs('baby-ai-home-slider.*');
            @endphp

            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-3 {{ $isBabyAiHomeSliderActive ? 'active bg-primary text-white' : 'text-light' }}"
                    href="{{ route('baby-ai-home-slider.index') }}" style="transition: all 0.2s;">
                    <i class="bi bi-house-heart-fill me-2 text-warning"></i>
                    <span class="fw-semibold">Baby AI Home Screen Slider</span>
                </a>
            </li>
            @endif

            {{-- API URL --}}
            <li class="sidebar-header" style="padding: 1.5rem 0.5rem 0.375rem; font-size: .90rem; color: #ced4da;">
                API
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-3 {{ request()->is('apiList') ? 'active bg-primary text-white' : 'text-light' }}"
                    href="{{ url('apiList') }}" style="transition: all 0.2s;">
                    <img src="https://cdn-icons-png.flaticon.com/512/103/103093.png" alt="API Icon" width="18"
                        height="18" style="margin-right: 8px; filter: brightness(0) invert(1);">
                    <span>API URL</span>
                </a>
            </li>
        </ul>

        <style>
            #sidebar-nav .nav-link {
                transition: all 0.2s ease-in-out;
                cursor: pointer;
            }

            #sidebar-nav .nav-link:hover {
                background: #3b3b4d !important;
                color: #fff !important;
            }

            #sidebar-nav .nav-link.active {
                background: #007bff !important;
                color: #fff !important;
            }

            #sidebar-nav ul.submenu-list .nav-link:hover {
                background: #2a2a3b !important;
            }

            #sidebar-nav ul.submenu-list {
                transition: all 0.3s ease;
                overflow: hidden;
            }

            .chevron-icon {
                transition: transform 0.3s ease;
            }

            .collapsed .chevron-icon {
                transform: rotate(0deg);
            }

            .expanded .chevron-icon {
                transform: rotate(180deg);
            }
        </style>
    </aside>

    <!-- Header -->
    <header id="header" class="header fixed-top d-flex align-items-center">
        <div class="d-flex align-items-center justify-content-between toggle-wrapper">
            <div class="left">
                <a href="#" class="js-sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list" style="font-size: 2.5rem; color: black"></i>
                </a>
            </div>
        </div>

        <a href="javascript:void(0);" id="clearCacheBtn" title="Clear Cache & Logs">
            <i class="fas fa-broom" style="font-size: 24px; color: #9aa5df; margin-left:20px;"></i>
        </a>

        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">
                <li class="nav-item d-block d-lg-none">
                    <a class="nav-link nav-icon search-bar-toggle" href="#">
                        <i class="bi bi-search"></i>
                    </a>
                </li>

                <li class="nav-item dropdown pe-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                        <img src="{{ asset('NiceAdmin/images/icon/logo-2023 copy.png') }}" alt="Profile"
                            class="rounded-circle" />
                        <span class="d-none d-md-block dropdown-toggle ps-2">NGD Admin</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                        <li class="dropdown-header">
                            <h6>NGD Admin</h6>
                            <span>Developer</span>
                        </li>
                        <li>
                            <hr class="dropdown-divider" />
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="{{ url('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Log Out</span>
                            </a>
                            <form id="logout-form" action="{{ url('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main">
        @section('container')
        @show
    </main>

    <!-- Vendor JS (each library loaded ONCE) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script src="{{ asset('NiceAdmin/assets/js/main.js') }}"></script>

    <!-- existing sidebar toggle state -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const sidebar = document.getElementById("sidebar");
            const header = document.getElementById("header");
            const main = document.querySelector(".main");
            const toggleBtn = document.getElementById("sidebarToggle");
            const isClosed = localStorage.getItem('sidebarClosed') === 'true';
            if (isClosed) {
                sidebar.classList.add("closed");
                main.classList.add("full");
            }

            toggleBtn.addEventListener("click", function (e) {
                e.preventDefault();
                sidebar.classList.toggle("closed");
                main.classList.toggle("full");
                localStorage.setItem('sidebarClosed', sidebar.classList.contains("closed"));
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll('.collapse-toggle').forEach(function (toggle) {
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();

                    const targetId = this.getAttribute('data-target');
                    const targetMenu = document.querySelector(targetId);

                    if (targetMenu) {
                        if (targetMenu.style.display === 'none' || targetMenu.style.display ===
                            '') {
                            targetMenu.style.display = 'block';
                            this.classList.remove('collapsed');
                            this.classList.add('expanded');
                        } else {
                            targetMenu.style.display = 'none';
                            this.classList.remove('expanded');
                            this.classList.add('collapsed');
                        }
                    }
                });

                const targetId = toggle.getAttribute('data-target');
                const targetMenu = document.querySelector(targetId);
                if (targetMenu && targetMenu.style.display === 'block') {
                    toggle.classList.add('expanded');
                } else {
                    toggle.classList.add('collapsed');
                }
            });
        });
    </script>

    <script>
        (function () {
            function normalizePath(href) {
                try {
                    const url = new URL(href, location.origin);
                    return url.pathname + url.search;
                } catch (e) {
                    return href;
                }
            }

            function restoreSidebarState() {
                const activePath = localStorage.getItem('sidebar_active_path');
                if (activePath) {

                    // Always default behavior over localstorage highlighting since blade sets it correctly
                    let backendActiveFound = false;
                    document.querySelectorAll('#sidebar-nav a.nav-link.active').forEach(a => {
                         backendActiveFound = true;
                    });

                    if(!backendActiveFound) {
                        document.querySelectorAll('#sidebar-nav a.nav-link').forEach(a => {
                            const p = normalizePath(a.getAttribute('href') || '');
                            if (p === activePath) {
                                a.classList.remove('text-light');
                                a.classList.add('active', 'bg-primary', 'text-white');
                            }
                        });
                    }
                }
            }

            function saveActiveLinkByElement(el) {
                const href = el.getAttribute('href') || '';
                const path = normalizePath(href);
                localStorage.setItem('sidebar_active_path', path);
            }

            document.addEventListener('DOMContentLoaded', function () {
                restoreSidebarState();

                document.querySelectorAll('#sidebar-nav a.nav-link:not(.collapse-toggle)').forEach(link => {
                    link.addEventListener('click', function (e) {
                        saveActiveLinkByElement(link);
                    });
                });
            });
        })();
    </script>

    <script>
        document.getElementById('clearCacheBtn').addEventListener('click', function(e) {
            e.preventDefault();

            Swal.fire({
                title: 'Clear Cache & Logs?',
                text: "This will clear all application cache and truncate log files.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, clear it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Clearing...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('system.clearCache') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Cleared!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error!', 'An error occurred while clearing cache.', 'error');
                        }
                    });
                }
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert-success, .alert-danger, .alert-warning');
                alerts.forEach(function(alertEl) {
                    var bsAlert = bootstrap.Alert.getInstance(alertEl);
                    if (!bsAlert) {
                        bsAlert = new bootstrap.Alert(alertEl);
                    }
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
    {{-- Global: smart back-to-list (uses history.back() if previous page was same origin) + sessionStorage flash --}}
    <script>
        (function () {
            function smartBack(fallbackHref) {
                try {
                    if (document.referrer && new URL(document.referrer).origin === window.location.origin
                        && document.referrer !== window.location.href) {
                        window.history.back();
                        return;
                    }
                } catch (e) {}
                if (fallbackHref) window.location.href = fallbackHref;
            }
            window.smartBack = smartBack;

            document.addEventListener('click', function (e) {
                var a = e.target.closest('a[data-back-to-list]');
                if (!a) return;
                e.preventDefault();
                smartBack(a.getAttribute('href'));
            });

            // Intercept hidden delete-form submissions → AJAX + refresh current pagination page in place.
            // form.submit() does NOT fire submit events, so we monkey-patch the prototype.
            function isDeleteForm(form) {
                if (!form || !form.id || form.id.indexOf('deleteForm-') !== 0) return false;
                var m = form.querySelector('input[name="_method"]');
                return m && m.value.toUpperCase() === 'DELETE';
            }

            function refreshCurrentList() {
                var activeLink = document.querySelector('.pagination .page-item.active a.page-link[data-page]');
                if (activeLink) { activeLink.click(); return true; }
                var firstLink = document.querySelector('.pagination a.page-link[data-page="1"]');
                if (firstLink) { firstLink.click(); return true; }
                return false;
            }

            function handleAjaxDelete(form) {
                var fd = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html,application/json' },
                    redirect: 'manual',
                    credentials: 'same-origin'
                }).then(function (response) {
                    var ok = response.type === 'opaqueredirect' || response.ok || response.status === 0;
                    if (!ok) throw new Error('HTTP ' + response.status);

                    if (!refreshCurrentList()) {
                        window.location.reload();
                        return;
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'top-end',
                            icon: 'success', title: 'Deleted successfully',
                            showConfirmButton: false, timer: 2000
                        });
                    }
                }).catch(function (err) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Delete failed', text: (err && err.message) || 'Please try again.' });
                    } else {
                        alert('Delete failed. Please try again.');
                    }
                });
            }

            // Monkey-patch HTMLFormElement.prototype.submit so the existing
            // "deleteForm-{id}.submit()" calls across all modules route through AJAX.
            var nativeSubmit = HTMLFormElement.prototype.submit;
            HTMLFormElement.prototype.submit = function () {
                if (isDeleteForm(this)) {
                    handleAjaxDelete(this);
                    return;
                }
                return nativeSubmit.apply(this, arguments);
            };

            // Also intercept user-triggered submits (button clicks) as a safety net.
            document.addEventListener('submit', function (e) {
                if (!isDeleteForm(e.target)) return;
                e.preventDefault();
                handleAjaxDelete(e.target);
            });

            document.addEventListener('DOMContentLoaded', function () {
                @if(session('success'))
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true, position: 'top-end',
                        icon: 'success', title: @json(session('success')),
                        showConfirmButton: false, timer: 2500
                    });
                }
                @endif
                @if(session('error'))
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true, position: 'top-end',
                        icon: 'error', title: @json(session('error')),
                        showConfirmButton: false, timer: 3500
                    });
                }
                @endif
                try {
                    var raw = sessionStorage.getItem('admin_flash');
                    if (!raw) return;
                    sessionStorage.removeItem('admin_flash');
                    var data = JSON.parse(raw);
                    if (data && data.message && typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'top-end',
                            icon: data.icon || 'success', title: data.message,
                            showConfirmButton: false, timer: 2500
                        });
                    }
                } catch (e) {}
            });
        })();
    </script>
    @yield('scripts')
    <script src="{{ asset('js/admin-media-protect.js') }}?v={{ @filemtime(public_path('js/admin-media-protect.js')) ?: '1' }}"></script>
</body>

</html>
