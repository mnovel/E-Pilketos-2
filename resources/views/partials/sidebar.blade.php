<div class="sidebar-wrapper" id="sidebar">
    {{-- ============ BRAND ============ --}}
    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isOperator() ? route('operator.dashboard') : route('voter.dashboard')) }}" class="sidebar-brand">
        <i class="bi bi-asterisk"></i>
        <span>Pilketos</span>
    </a>

    <div class="flex-grow-1 overflow-y-auto">

        {{-- ==========================================
             MENU UTAMA
             ========================================== --}}
        <div class="sidebar-menu-section">
            <div class="sidebar-menu-title">Menu</div>
            <ul class="sidebar-menu-list">
                <li class="sidebar-menu-item">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="sidebar-menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" title="Dashboard">
                            <i class="bi bi-grid-fill"></i>
                            <span>Dashboard</span>
                        </a>
                    @elseif (auth()->user()->isOperator())
                        <a href="{{ route('operator.dashboard') }}" class="sidebar-menu-link {{ request()->routeIs('operator.dashboard') ? 'active' : '' }}" title="Dashboard">
                            <i class="bi bi-grid-fill"></i>
                            <span>Dashboard</span>
                        </a>
                    @else
                        <a href="{{ route('voter.dashboard') }}" class="sidebar-menu-link {{ request()->routeIs('voter.dashboard') ? 'active' : '' }}" title="Dashboard">
                            <i class="bi bi-grid-fill"></i>
                            <span>Dashboard</span>
                        </a>
                    @endif
                </li>

                {{-- Scanner QR — voter only --}}
                @if (auth()->user()->isVoter())
                    <li class="sidebar-menu-item">
                        <a href="{{ route('voter.scan') }}" class="sidebar-menu-link {{ request()->routeIs('voter.scan') ? 'active' : '' }}" title="Scan QR">
                            <i class="bi bi-qr-code-scan"></i>
                            <span>Scan QR</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>

        {{-- ==========================================
             MANAJEMEN — admin only
             ========================================== --}}
        @if (auth()->user()->isAdmin())
            <div class="sidebar-menu-section">
                <div class="sidebar-menu-title">Manajemen</div>
                <ul class="sidebar-menu-list">
                    {{-- Kelola Kelas --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('admin.classes.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}" title="Kelola Kelas">
                            <i class="bi bi-mortarboard"></i>
                            <span>Kelas</span>
                        </a>
                    </li>
                    {{-- Operator --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('admin.operators.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.operators.*') ? 'active' : '' }}" title="Operator">
                            <i class="bi bi-person-badge-fill"></i>
                            <span>Operator</span>
                        </a>
                    </li>

                    {{-- Pemilih --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('admin.voters.index') }}"
                            class="sidebar-menu-link {{ request()->routeIs('admin.voters.*') && !request()->routeIs('admin.voters.export.*') ? 'active' : '' }}" title="Pemilih">
                            <i class="bi bi-people-fill"></i>
                            <span>Pemilih</span>
                        </a>
                    </li>

                    {{-- Export Voter --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('admin.voters.export.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.voters.export.*') ? 'active' : '' }}" title="Export Voter">
                            <i class="bi bi-download"></i>
                            <span>Export Voter</span>
                        </a>
                    </li>

                    {{-- Cetak Kartu Voter --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('admin.voter-cards.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.voter-cards.*') ? 'active' : '' }}" title="Cetak Kartu Voter">
                            <i class="bi bi-person-badge"></i>
                            <span>Cetak Kartu</span>
                        </a>
                    </li>

                    {{-- Pemilihan --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('admin.elections.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.elections.*') ? 'active' : '' }}" title="Pemilihan">
                            <i class="bi bi-calendar-event"></i>
                            <span>Pemilihan</span>
                        </a>
                    </li>

                    {{-- Kandidat --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('admin.candidates.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.candidates.*') ? 'active' : '' }}" title="Kandidat">
                            <i class="bi bi-person-badge"></i>
                            <span>Kandidat</span>
                        </a>
                    </li>

                    {{-- Sesi Voting --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('admin.sessions.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}" title="Sesi Voting">
                            <i class="bi bi-clock-history"></i>
                            <span>Sesi Voting</span>
                        </a>
                    </li>

                    {{-- Activity Log --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('admin.activity-logs.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}" title="Activity Log">
                            <i class="bi bi-journal-text"></i>
                            <span>Activity Log</span>
                        </a>
                    </li>

                    {{-- Device Log --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('admin.device-logs.index') }}" class="sidebar-menu-link {{ request()->routeIs('admin.device-logs.*') ? 'active' : '' }}" title="Log Device">
                            <i class="bi bi-hdd-network"></i>
                            <span>Log Device</span>
                        </a>
                    </li>

                </ul>
            </div>
        @endif

        {{-- ==========================================
             DEVICE VOTING — admin & operator only
             ========================================== --}}
        @if (auth()->user()->isAdmin() || auth()->user()->isOperator())
            <div class="sidebar-menu-section">
                <div class="sidebar-menu-title">Device Voting</div>
                <ul class="sidebar-menu-list">

                    {{-- Check-in --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('device.checkin.index') }}" target="_blank" rel="noopener" class="sidebar-menu-link" title="Device Check-in (buka tab baru)">
                            <i class="bi bi-door-open-fill"></i>
                            <span>Check-in</span>
                            <i class="bi bi-box-arrow-up-right ms-auto" style="font-size: 0.75rem; opacity: 0.5;"></i>
                        </a>
                    </li>

                    {{-- Voting --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ route('device.voting.index') }}" target="_blank" rel="noopener" class="sidebar-menu-link" title="Device Voting (buka tab baru)">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>Voting</span>
                            <i class="bi bi-box-arrow-up-right ms-auto" style="font-size: 0.75rem; opacity: 0.5;"></i>
                        </a>
                    </li>

                </ul>
            </div>
        @endif

        {{-- ==========================================
             HASIL — semua role
             ========================================== --}}
        <div class="sidebar-menu-section">
            <div class="sidebar-menu-title">Hasil</div>
            <ul class="sidebar-menu-list">
                <li class="sidebar-menu-item">
                    @if (auth()->user()->isAdmin())
                        {{-- Admin: ke list elections dulu untuk pilih election --}}
                        <a href="{{ route('admin.elections.index', ['status' => 'published']) }}" class="sidebar-menu-link {{ request()->routeIs('admin.elections.hasil') ? 'active' : '' }}"
                            title="Hasil Pemilihan">
                            <i class="bi bi-bar-chart-fill"></i>
                            <span>Hasil Pemilihan</span>
                        </a>
                    @else
                        {{-- Operator/voter: ke halaman publik (kalau ada) --}}
                        <a href="{{ url('/hasil') }}" class="sidebar-menu-link" title="Hasil Pemilihan">
                            <i class="bi bi-bar-chart-fill"></i>
                            <span>Hasil Pemilihan</span>
                        </a>
                    @endif
                </li>
            </ul>
        </div>

    </div>

    {{-- ============ PROFILE CARD ============ --}}
    <div class="sidebar-profile">
        <div class="sidebar-profile-avatar-wrap"
            style="
                width: 40px; height: 40px;
                background: #c6f135;
                color: #1a2e1a;
                border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                font-weight: 700; font-size: 1rem;
             ">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="sidebar-profile-info">
            <div class="sidebar-profile-name">{{ auth()->user()->name }}</div>
            <div class="sidebar-profile-email">{{ auth()->user()->email }}</div>
        </div>
    </div>
</div>
