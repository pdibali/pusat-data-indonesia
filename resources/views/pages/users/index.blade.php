{{-- resources/views/pages/users/index.blade.php --}}
@extends('layouts.main')

@section('title', 'Kelola User')

@section('content')
<div class="page-layout">

    {{-- Header --}}
    <div class="page-header flex flex-col sm:flex-row items-start sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-600">Kelola User</h2>
            <p class="text-xs text-gray-500 mt-0.5">Manajemen akun pengguna sistem</p>
        </div>
        <a href="{{ route('admin.users.create') }}"
           class="btn-primary text-xs justify-center sm:w-auto">
            <i class="fas fa-plus"></i> Tambah User
        </a>
    </div>

    {{-- Flash --}}
    @include('layouts.alert')

    <div class="card-panel p-3 flex flex-col sm:flex-row flex-wrap gap-2 sm:items-center">
        {{-- Search dengan clear button --}}
        <div class="relative flex-1 min-w-0 sm:min-w-48">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            <input type="text" name="search" value="{{ request('search') }}"
                form="filter-users"
                placeholder="Cari nama, email, username..."
                oninput="autoSubmitDebounce(this)"
                class="w-full pl-8 pr-8 text-xs rounded-lg px-3 py-2 border ...">
            @if(request('search'))
                <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-400">
                    <i class="fas fa-times text-xs"></i>
                </a>
            @endif
        </div>

        <select name="group_id" form="filter-users"
                onchange="this.form.submit()"
                class="w-full sm:w-auto text-xs rounded-lg px-3 py-2 border ...">
            <option value="">Semua Group</option>
            @foreach ($groups as $group)
                <option value="{{ $group->group_id }}" {{ request('group_id') == $group->group_id ? 'selected' : '' }}>
                    {{ $group->title }}
                </option>
            @endforeach
        </select>

        {{-- Reset hanya muncul kalau ada filter aktif --}}
        @if(request('search') || request('group_id'))
            <a href="{{ route('admin.users.index') }}"
            class="text-xs text-gray-400 hover:text-red-500 px-2 py-2 transition flex items-center gap-1 self-start sm:self-auto">
                <i class="fas fa-times-circle"></i> Reset
            </a>
        @endif
    </div>
    <form id="filter-users" method="GET" action="{{ route('admin.users.index') }}"></form>

    @if ($users->isEmpty())
        <div class="card-panel flex flex-col items-center justify-center py-16 text-gray-500">
            <i class="fas fa-users text-4xl mb-3 opacity-30"></i>
            <p class="text-sm font-medium">Belum ada user</p>
            <p class="text-xs mt-1">Klik "Tambah User" untuk menambahkan user baru</p>
        </div>
    @else

        {{-- ── MOBILE: card list ── --}}
        <div class="space-y-3 md:hidden">
            @foreach ($users as $i => $user)
                <div class="card-panel p-4">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 shrink-0 rounded-full bg-green-500/20 flex items-center justify-center text-green-400 font-bold text-xs">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-700 truncate">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                            </div>
                        </div>
                        <span class="shrink-0 text-[11px] text-gray-500">
                            #{{ $users->firstItem() + $i }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 mb-3">
                        <span class="px-2 py-0.5 rounded-full text-xs bg-green-500/10 text-green-400 border border-green-500/20">
                            {{ $user->group->title ?? '-' }}
                        </span>
                        @if ($user->status === 1)
                            <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <i class="fas fa-check-circle text-xs"></i> Aktif
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs bg-red-500/10 text-red-400 border border-red-500/20">
                                <i class="fas fa-circle-xmark text-xs"></i> Nonaktif
                            </span>
                        @endif
                        @if ($user->locked_at)
                            <span class="px-2 py-0.5 rounded-full text-xs bg-red-500/10 text-red-400 border border-red-500/20">
                                <i class="fas fa-lock text-xs"></i> Terkunci
                            </span>
                        @endif
                    </div>

                    <div class="text-xs text-gray-500 space-y-1 mb-3">
                        <div class="flex justify-between gap-2">
                            <span class="text-gray-600">Username</span>
                            <span class="text-gray-500 truncate">{{ $user->username }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-gray-600">Terdaftar</span>
                            <span class="text-gray-500">{{ $user->registerdate ? $user->registerdate->format('d M Y') : '-' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-3 border-t border-white/5">
                        <a href="{{ route('admin.users.show', $user) }}"
                           class="flex-1 flex items-center justify-center gap-1.5 p-2 rounded-md bg-blue-500/10 text-blue-400 text-xs"
                           title="Detail">
                            <i class="fas fa-eye text-xs"></i> Detail
                        </a>
                        <a href="{{ route('admin.users.edit', $user) }}"
                           class="flex-1 flex items-center justify-center gap-1.5 p-2 rounded-md bg-green-500/10 text-green-400 text-xs"
                           title="Edit">
                            <i class="fas fa-edit text-xs"></i> Edit
                        </a>
                        <form action="{{ route('admin.users.toggle_status', $user) }}" method="POST" class="flex-1">
                            @csrf
                            @if ($user->status === 1)
                                <button type="submit"
                                        class="w-full flex items-center justify-center gap-1.5 p-2 rounded-md bg-yellow-500/10 text-yellow-400 text-xs"
                                        title="Nonaktifkan"
                                        onclick="return confirm('Yakin ingin menonaktifkan user {{ addslashes($user->name) }}?')">
                                    <i class="fas fa-ban text-xs"></i> Nonaktif
                                </button>
                            @else
                                <button type="submit"
                                        class="w-full flex items-center justify-center gap-1.5 p-2 rounded-md bg-emerald-500/10 text-emerald-400 text-xs"
                                        title="Aktifkan">
                                    <i class="fas fa-check text-xs"></i> Aktifkan
                                </button>
                            @endif
                        </form>
                        @if ($user->locked_at)
                            <form action="{{ route('admin.users.unlock', $user) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center justify-center gap-1.5 p-2 rounded-md bg-blue-500/10 text-blue-400 text-xs"
                                        title="Buka kunci"
                                        onclick="return confirm('Yakin ingin membuka kunci user {{ addslashes($user->name) }}?')">
                                    <i class="fas fa-unlock text-xs"></i> Unlock
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ── DESKTOP/TABLET: table ── --}}
        <div class="card-panel hidden md:block">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b text-gray-500 text-gray-400 bg-white/5">
                            <th class="text-left px-4 py-3 font-semibold">#</th>
                            <th class="text-left px-4 py-3 font-semibold">Nama</th>
                            <th class="text-left px-4 py-3 font-semibold">Username</th>
                            <th class="text-left px-4 py-3 font-semibold">Email</th>
                            <th class="text-left px-4 py-3 font-semibold">Group</th>
                            <th class="text-left px-4 py-3 font-semibold">Status</th>
                            <th class="text-left px-4 py-3 font-semibold">Terdaftar</th>
                            <th class="text-center px-4 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($users as $i => $user)
                            <tr class="hover:bg-white/5 transition text-gray-300">
                                <td class="px-4 py-3 text-gray-500">{{ $users->firstItem() + $i }}</td>
                                <td class="px-4 py-3 font-medium text-gray-600">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-gray-400">{{ $user->username }}</td>
                                <td class="px-4 py-3 text-gray-400">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-green-500/10 text-green-400 border border-green-500/20">
                                        {{ $user->group->title ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($user->status === 1)
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            <i class="fas fa-check-circle text-xs"></i> Aktif
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-red-500/10 text-red-400 border border-red-500/20">
                                            <i class="fas fa-circle-xmark text-xs"></i> Nonaktif
                                        </span>
                                    @endif

                                    @if ($user->locked_at)
                                        <span class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-red-500/10 text-red-400 border border-red-500/20">
                                            <i class="fas fa-lock text-xs"></i> Terkunci
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ $user->registerdate ? $user->registerdate->format('d M Y') : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.users.show', $user) }}"
                                           class="p-1.5 rounded-md bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition"
                                           title="Detail">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                           class="p-1.5 rounded-md bg-green-500/10 text-green-400 hover:bg-green-500/20 transition"
                                           title="Edit">
                                            <i class="fas fa-edit text-xs"></i>
                                        </a>
                                        <form action="{{ route('admin.users.toggle_status', $user) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @if ($user->status === 1)
                                                <button type="submit"
                                                        class="p-1.5 rounded-md bg-yellow-500/10 text-yellow-400 hover:bg-yellow-500/20 transition"
                                                        title="Nonaktifkan"
                                                        onclick="return confirm('Yakin ingin menonaktifkan user {{ addslashes($user->name) }}?')">
                                                    <i class="fas fa-ban text-xs"></i>
                                                </button>
                                            @else
                                                <button type="submit"
                                                        class="p-1.5 rounded-md bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition"
                                                        title="Aktifkan">
                                                    <i class="fas fa-check text-xs"></i>
                                                </button>
                                            @endif
                                        </form>

                                        @if ($user->locked_at)
                                            <form action="{{ route('admin.users.unlock', $user) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit"
                                                        class="p-1.5 rounded-md bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition"
                                                        title="Buka kunci"
                                                        onclick="return confirm('Yakin ingin membuka kunci user {{ addslashes($user->name) }}?')">
                                                    <i class="fas fa-unlock text-xs"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($users->hasPages())
                <div class="px-4 py-3 border-t text-gray-500">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        {{-- Pagination (mobile, di luar table wrapper) --}}
        @if ($users->hasPages())
            <div class="md:hidden card-panel px-4 py-3 text-gray-500">
                {{ $users->links() }}
            </div>
        @endif

    @endif

</div>
@endsection