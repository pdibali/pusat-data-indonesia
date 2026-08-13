@extends('layouts.main')

@section('title', 'Detail User')

@section('content')
<div class="max-w-2xl space-y-4">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-gray-500">
        <a href="{{ route('admin.users.index') }}" class="hover:text-green-500 transition">Kelola User</a>
        <i class="fas fa-chevron-right text-[10px]"></i>
        <span class="text-gray-300">Detail</span>
    </div>

    {{-- Card --}}
    <div class="card-panel p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 shrink-0 rounded-full bg-green-500/20 flex items-center justify-center text-green-400 font-bold text-sm">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-700 truncate">{{ $user->name }}</h2>
                    <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                </div>
            </div>
            <span class="self-start sm:self-auto shrink-0 px-2.5 py-1 rounded-full text-xs bg-green-500/10 text-green-400 border border-green-500/20">
                {{ $user->group->title ?? '-' }}
            </span>
        </div>

        <dl class="space-y-3 text-xs">
            @foreach ([
                ['label' => 'Username',  'value' => $user->username],
                ['label' => 'Email',     'value' => $user->email],
                ['label' => 'Group',     'value' => $user->group->title ?? '-'],
                ['label' => 'Status',    'value' => $user->block ? 'Diblokir' : 'Aktif'],
                ['label' => 'Terdaftar', 'value' => $user->registerdate?->format('d M Y H:i')],
                ['label' => 'Login Terakhir', 'value' => $user->lastvisitdate?->format('d M Y H:i')],
            ] as $item)
                <div class="flex flex-col sm:flex-row sm:gap-4 py-2.5 border-b border-white/5">
                    <dt class="sm:w-32 shrink-0 text-gray-700 font-semibold mb-0.5 sm:mb-0">{{ $item['label'] }}</dt>
                    <dd class="text-gray-600 wrap-break-words">{{ $item['value'] ?? '-' }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="flex flex-col sm:flex-row gap-3 mt-6">
            <a href="{{ route('admin.users.edit', $user) }}"
            class="btn-primary justify-center">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('admin.users.index') }}"
            class="flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 text-gray-400 text-xs px-4 py-2 rounded-lg transition">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

</div>
@endsection