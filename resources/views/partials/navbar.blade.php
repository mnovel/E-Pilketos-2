<header class="navbar-custom">
    <div class="navbar-left">
        <button class="btn-desktop-toggle d-none d-xl-flex align-items-center justify-content-center me-3" id="desktop-sidebar-toggle" aria-label="Minimize Sidebar">
            <i class="bi bi-chevron-bar-left"></i>
        </button>

        <button class="sidebar-toggle-btn me-2" id="sidebar-toggle" aria-label="Toggle Navigation">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <div class="navbar-search-wrapper">
        <input type="text" class="navbar-search-input" placeholder="Cari sesuatu..." id="main-search">
        <button class="navbar-search-btn" aria-label="Search">
            <i class="bi bi-search"></i>
        </button>
    </div>

    <div class="navbar-actions">
        <button class="navbar-action-btn me-1" aria-label="Toggle Fullscreen" id="btn-fullscreen">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>

        {{-- Notifikasi --}}
        <div class="dropdown">
            <button class="navbar-action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="btn-notifications">
                <i class="bi bi-bell"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end dropdown-menu-notification p-0">
                <div class="notification-header">
                    <h6 class="notification-title">Notifikasi</h6>
                </div>
                <div class="notification-list">
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-bell-slash fs-4 d-block mb-2"></i>
                        Tidak ada notifikasi
                    </div>
                </div>
            </div>
        </div>

        {{-- Profile --}}
        <div class="dropdown ms-2">
            <button class="navbar-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="profile-dropdown">
                <span
                    style="
                    width: 32px; height: 32px;
                    background: #c6f135; color: #1a2e1a;
                    border-radius: 50%;
                    display: inline-flex; align-items: center; justify-content: center;
                    font-weight: 700; font-size: 0.85rem;
                ">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
                <span class="navbar-profile-name d-none d-md-inline ms-2">
                    {{ auth()->user()->name }}
                </span>
                <i class="bi bi-chevron-down navbar-profile-caret"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-profile">
                <li class="dropdown-header">
                    <div class="fw-bold">{{ auth()->user()->name }}</div>
                    <small class="text-muted">{{ auth()->user()->email }}</small>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('profile.index') }}">
                        <i class="bi bi-person"></i> Profil Saya
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
