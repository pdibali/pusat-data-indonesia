@php
    $user       = auth()->user();
    $isCustomer = $user?->group_id === 3;
    $isPengelola = $user?->group_id === 2;
    $isSuperAdmin = $user?->group_id === 1;
    $isAdmin    = in_array($user?->group_id, [1, 2]);

    $menus = [
        (object)[
            "title"        => "Data",
            "path"         => "/data",
            "icon"         => "fas fa-database",
            "active"       => request()->segment(1) === 'data',
            "children"     => [],
            "onlyCustomer" => false,
            "onlyAdmin"    => false,
        ],
        (object)[
            "title"        => "Riwayat Berlangganan",
            "path"         => "/transaksi/riwayat",
            "icon"         => "fas fa-file-invoice-dollar",
            "active"       => request()->is('transaksi/*'),
            "children"     => [],
            "onlyCustomer" => false,
            "onlyAdmin"    => false,
            "customerOnly" => true,
        ],
        (object)[
            "title"        => "Langganan",
            "path"         => "/langganan",
            "icon"         => "fas fa-star",
            "active"       => request()->is('langganan'),
            "children"     => [],
            "onlyCustomer" => false,
            "onlyAdmin"    => false,
            "customerOnly" => true,
        ],
        // (object)[
        //     "title"        => "Organisasi",
        //     "path"         => "/organization/team",
        //     "icon"         => "fas fa-building",
        //     "active"       => request()->is('organization*'),
        //     "children"     => [],
        //     "onlyCustomer" => false,
        //     "onlyAdmin"    => false,
        // ],
        (object)[
            "title"        => "Manajemen Data",
            "path"         => "/data",
            "icon"         => "fas fa-cogs",
            "active"       => request()->is('anomaly/control*'),
            "onlyCustomer" => true,
            "onlyAdmin"    => false,
            "children"     => array_filter([
                $isAdmin ? (object)[
                    "title"     => "Control Anomali",
                    "path"      => "/anomaly/control",
                    "icon"      => "fa-solid fa-shield-halved",
                    "active"    => request()->is('anomaly/control*'),
                    "onlyAdmin" => true,
                ] : null,
            ]),
        ],
        (object)[
            "title"        => "Metadata",
            "path"         => "/metadata",
            "icon"         => "fas fa-folder-open",
            "active"       => request()->is('metadata*'),
            "onlyCustomer" => true,
            "onlyAdmin"    => false,
            "children" => [
                (object)["title" => "All Metadata", "path" => "/metadata",          "icon" => "fa-solid fa-floppy-disk",     "active" => request()->is('metadata'),          "onlyAdmin" => false],
                (object)["title" => "Approval",     "path" => "/metadata/approval", "icon" => "fa-solid fa-clipboard-check", "active" => request()->is('metadata/approval'), "onlyAdmin" => false],
            ],
        ],
        (object)[
            "title"        => "Dimensi",
            "path"         => "/dimensi",
            "icon"         => "fas fa-cubes",
            "active"       => request()->is('dimensi_*'),
            "onlyCustomer" => true,
            "onlyAdmin"    => false,
            "children" => [
                (object)["title" => "Dimensi Waktu",  "path" => "/dimensi_waktu",  "icon" => "fa-solid fa-clock",        "active" => request()->segment(1) === 'dimensi_waktu',  "onlyAdmin" => false],
                (object)["title" => "Dimensi Lokasi", "path" => "/dimensi_lokasi", "icon" => "fa-solid fa-location-dot", "active" => request()->segment(1) === 'dimensi_lokasi', "onlyAdmin" => false],
            ],
        ],
        (object)[
            "title"        => "Kelola Master Data",
            "path"         => "#",
            "icon"         => "fas fa-users-cog",
            "onlyCustomer" => true,
            "onlyAdmin"    => false,
            "active"       => request()->segment(1) === 'admin' && in_array(request()->segment(2), ['users','groups','klasifikasi','produsen','rujukan','satuan']),
            "children" => array_filter([
                $isSuperAdmin ? (object)["title" => "Kelola User",  "path" => "/admin/users",  "icon" => "fas fa-user",  "active" => request()->segment(2) === 'users',  "onlyAdmin" => false] : null,
                $isSuperAdmin ? (object)["title" => "Kelola Group", "path" => "/admin/groups", "icon" => "fas fa-users", "active" => request()->segment(2) === 'groups', "onlyAdmin" => false] : null,
                (object)["title" => "Kelola Klasifikasi", "path" => "/admin/klasifikasi", "icon" => "fas fa-tags",     "active" => request()->segment(2) === 'klasifikasi', "onlyAdmin" => false],
                (object)["title" => "Kelola Produsen",    "path" => "/admin/produsen",    "icon" => "fas fa-industry", "active" => request()->segment(2) === 'produsen',     "onlyAdmin" => false],
                (object)["title" => "Kelola Satuan",     "path" => "/admin/satuan",      "icon" => "fas fa-ruler",    "active" => request()->segment(2) === 'satuan',      "onlyAdmin" => false],
                (object)["title" => "Kelola Rujukan",     "path" => "/admin/rujukan",     "icon" => "fas fa-file-alt", "active" => request()->segment(2) === 'rujukan',      "onlyAdmin" => false],
            ]),
        ],
        (object)[
            "title"        => "Kelola Layanan",
            "path"         => "#",
            "icon"         => "fas fa-box-open",
            "onlyCustomer" => true,
            "onlyAdmin"    => false,
            "active"       => request()->segment(1) === 'admin' && request()->segment(2) === 'layanan',
            "children" => [
                (object)["title" => "Layanan", "path" => "/admin/layanan", "icon" => "fas fa-store", "active" => request()->segment(2) === 'layanan', "onlyAdmin" => false],
            ],
        ],
        (object)[
            "title"        => "Transaksi",
            "path"         => "#",
            "icon"         => "fas fa-receipt",
            "onlyCustomer" => true,
            "onlyAdmin"    => false,
            "active"       => request()->segment(1) === 'admin' && in_array(request()->segment(2), ['transaksi-admin']),
            "badge"        => $pendingVerifikasiCount ?? 0,
            "children" => [
                (object)["title" => "Dashboard",        "path" => "/admin/transaksi-admin/dashboard", "icon" => "fas fa-chart-pie", "active" => request()->is('admin/transaksi-admin/dashboard'), "onlyAdmin" => false],
                (object)["title" => "Daftar Transaksi", "path" => "/admin/transaksi-admin",           "icon" => "fas fa-list-alt",  "active" => request()->is('admin/transaksi-admin') && !request()->is('admin/transaksi-admin/dashboard'), "onlyAdmin" => false, "badge" => $pendingVerifikasiCount ?? 0],
            ],
        ],
        // (object)[
        //     "title"        => "Organisasi",
        //     "path"         => "/admin/organizations",
        //     "icon"         => "fas fa-building",
        //     "active"       => request()->segment(1) === 'admin' && request()->segment(2) === 'organizations',
        //     "children"     => [],
        //     "onlyCustomer" => true,
        //     "onlyAdmin"    => false,
        // ],
    ];
@endphp

<aside class="w-56 h-full flex flex-col shadow-xl lg:shadow-none"
       style="background: linear-gradient(180deg, #0c4a6e 0%, #0369a1 100%);">

    {{-- ── Logo + Close button (mobile) ── --}}
    <div class="px-4 py-2 flex items-center justify-center border-b border-white/10">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo/PDIB-transparan-A.png') }}"
                alt="Pusat Data Indonesia Bali"
                class="h-10 w-auto object-contain">
        </div>

        {{-- Close button — only visible on mobile --}}
        <button @click="sidebarOpen = false"
                class="lg:hidden w-7 h-7 flex items-center justify-center rounded-md
                    text-white/60 hover:text-white hover:bg-white/10 transition-colors">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>

    {{-- ── Nav ── --}}
    <nav class="flex-1 overflow-y-auto py-3 px-3 scrollbar-none">
        <ul class="flex flex-col gap-0.5">
            @foreach ($menus as $menu)
                @if($isCustomer && $menu->onlyCustomer)
                    @continue
                @endif
                @if(!$isAdmin && $menu->onlyAdmin)
                    @continue
                @endif
                @if($isAdmin && ($menu->customerOnly ?? false))
                    @continue
                @endif

                @php
                    $hasChildren = !empty($menu->children);
                    $isOpen      = $menu->active;
                @endphp

                <li x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }">

                    @if($hasChildren)
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between px-2.5 py-2 rounded-md
                                    transition-colors text-xs
                                    {{ $menu->active
                                        ? 'bg-white/15 text-white font-semibold'
                                        : 'text-white/70 hover:bg-white/10 hover:text-white' }}
                                    relative">
                            @if($menu->active)
                                <span class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-3/5 bg-sky-300 rounded-r-full"></span>
                            @endif
                            <div class="flex items-center gap-2.5">
                                <i class="{{ $menu->icon }} w-4 text-center text-[11px]"></i>
                                <span>{{ $menu->title }}</span>
                                @if(($menu->badge ?? 0) > 0)
                                    <span class="flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[9px] font-bold leading-none animate-pulse">
                                        {{ $menu->badge > 9 ? '9+' : $menu->badge }}
                                    </span>
                                @endif
                            </div>
                            <i class="fas fa-chevron-down text-[8px] opacity-50 transition-transform duration-200"
                            :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        @else
                        <a href="{{ url($menu->path) }}"
                        @click="sidebarOpen = false"
                        class="flex items-center justify-between px-2.5 py-2 rounded-md
                                transition-colors text-xs
                                {{ $menu->active
                                    ? 'bg-white/15 text-white font-semibold'
                                    : 'text-white/70 hover:bg-white/10 hover:text-white' }}
                                relative">
                            @if($menu->active)
                                <span class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-3/5 bg-sky-300 rounded-r-full"></span>
                            @endif
                            <div class="flex items-center gap-2.5">
                                <i class="{{ $menu->icon }} w-4 text-center text-[11px]"></i>
                                <span>{{ $menu->title }}</span>
                            </div>
                            @if(($menu->badge ?? 0) > 0)
                                <span class="flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[9px] font-bold leading-none animate-pulse">
                                    {{ $menu->badge > 9 ? '9+' : $menu->badge }}
                                </span>
                            @endif
                        </a>
                        @endif

                    @if($hasChildren)
                    <ul x-show="open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="mt-0.5 ml-3 pl-3 border-l border-sky-400/25 space-y-0.5">
                        @foreach($menu->children as $child)
                        <li>
                            <a href="{{ url($child->path) }}"
                            @click="sidebarOpen = false"
                            class="flex items-center gap-2.5 px-2 py-1.5 rounded-md text-[11px]
                                    transition-colors justify-between
                                    {{ $child->active
                                        ? 'text-sky-300 font-semibold'
                                        : 'text-white/55 hover:text-white hover:bg-white/6' }}">
                                <span class="flex items-center gap-2.5">
                                    <i class="{{ $child->icon }} text-[10px]"></i>
                                    <span>{{ $child->title }}</span>
                                </span>
                                @if(($child->badge ?? 0) > 0)
                                    <span class="flex items-center justify-center min-w-[15px] h-3.5 px-1 rounded-full bg-red-500 text-white text-[8px] font-bold leading-none">
                                        {{ $child->badge > 9 ? '9+' : $child->badge }}
                                    </span>
                                @endif
                            </a>
                        </li>
                        @endforeach
                    </ul>
                    @endif

                </li>
            @endforeach
        </ul>
    </nav>

    {{-- ── Logout ── --}}
    <div class="p-4 border-t border-white/10">
        <p class="text-white/30 text-xs">
            &copy; {{ date('Y') }} Pusat Data Indonesia Bali.
        </p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('logoutForm');
        const btn = document.getElementById('logoutBtn');

        if (!form || !btn) return;

        form.addEventListener('submit', function () {

            // Cegah double submit
            if (btn.disabled) {
                return false;
            }

            btn.disabled = true;

            btn.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Logging out...</span>
            `;
        });
    });
    </script>

</aside>