<header class="bg-white border-b border-slate-200 px-3 lg:px-6 h-14 flex items-center justify-between sticky top-0 z-40">

    <div class="flex items-center gap-3">
        {{-- ── Hamburger (mobile only, auth only) ── --}}
        @auth
        <button @click="sidebarOpen = !sidebarOpen"
                class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg
                       text-slate-500 hover:bg-slate-100 hover:text-slate-700
                       transition-colors shrink-0"
                aria-label="Toggle menu">
            {{-- Animated hamburger / X --}}
            <span class="block w-5 relative" style="height: 14px;">
                <span class="absolute block w-full h-0.5 bg-current rounded transition-all duration-200"
                      :class="sidebarOpen ? 'top-1/2 -translate-y-1/2 rotate-45' : 'top-0'"
                      style="top: 0;"></span>
                <span class="absolute block w-full h-0.5 bg-current rounded transition-all duration-200"
                      :class="sidebarOpen ? 'opacity-0' : 'opacity-100'"
                      style="top: 50%; transform: translateY(-50%);"></span>
                <span class="absolute block w-full h-0.5 bg-current rounded transition-all duration-200"
                      :class="sidebarOpen ? 'top-1/2 -translate-y-1/2 -rotate-45' : 'bottom-0'"
                      style="bottom: 0;"></span>
            </span>
        </button>
        @endauth

        {{-- ── Breadcrumb ── --}}
        <div class="flex items-center gap-1.5 text-xs text-slate-400">
            <a href="{{ route('landing') }}" class="flex items-center gap-1.5 text-slate-500 hover:text-sky-600 transition-colors">
                <i class="fas fa-home text-[10px]"></i>
                <span class="font-semibold">Beranda</span>
            </a>
            <span>/</span>
            <span class="text-sky-600 font-semibold capitalize">
                {{ ucfirst(request()->segment(1) ?? 'Dashboard') }}
            </span>
        </div>
    </div>

    {{-- ── Right side ── --}}
    <div class="flex items-center gap-2 lg:gap-3">

        {{-- User pill --}}
        @auth
            @php $user = Auth::user(); @endphp

            <div x-data="{ open: false }" class="relative">
                <button type="button"
                        @click="open = !open"
                        @click.outside="open = false"
                        class="flex items-center gap-2 rounded-xl px-3 py-2 text-left transition-colors hover:bg-slate-100"
                        aria-haspopup="menu"
                        :aria-expanded="open.toString()">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center
                                 text-white text-[10px] font-bold tracking-wide shrink-0
                                 bg-gradient-to-br from-sky-500 to-sky-700">
                        {{ strtoupper(substr($user->name ?? '', 0, 2)) }}
                    </div>
                    <div class="hidden sm:block leading-tight">
                        <p class="text-xs font-semibold text-slate-800 max-w-28 truncate">{{ $user->name ?? '' }}</p>
                        <p class="text-[10px] text-slate-400">{{ optional($user->group)->title }}</p>
                    </div>
                    <i class="fas fa-chevron-down text-slate-400 text-xs hidden sm:block"></i>
                </button>

                <div x-show="open" x-transition
                     style="display: none;"
                     class="absolute right-0 z-50 mt-2 w-52 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                    <a href="{{ route('profile.edit') }}"
                       @click="open = false"
                       class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-100">
                        Edit Profil
                    </a>
                    <a href="{{ route('user-password.edit') }}"
                       @click="open = false"
                       class="block px-4 py-3 text-sm text-slate-700 hover:bg-slate-100">
                        Ubah Password
                    </a>
                    @if((int) $user->group_id === 1)
                        <a href="{{ route('admin.activity_logs.index') }}"
                           @click="open = false"
                           class="block px-4 py-3 text-xs text-slate-500 hover:bg-slate-50 hover:text-slate-700">
                            Log Aktivitas
                        </a>
                    @endif
                    <div class="border-t border-slate-200"></div>
                    <form action="{{ url('/logout') }}" method="POST"
                          x-data="{ submitting: false }"
                          @submit="if (submitting) { $event.preventDefault(); return; } submitting = true">
                        @csrf
                        <button type="submit"
                                :disabled="submitting"
                                :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
                                class="w-full text-left px-4 py-3 text-sm text-red-700 hover:bg-rose-50">
                            <span x-show="!submitting">Logout</span>
                            <span x-show="submitting" style="display: none;">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Logout...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        @else
            <a href="{{ route('login') }}"
               class="text-xs text-sky-600 font-semibold hover:text-sky-800 transition-colors">
                Masuk
            </a>
        @endauth

    </div>

</header>