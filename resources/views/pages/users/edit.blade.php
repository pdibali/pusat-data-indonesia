{{-- resources/views/pages/users/edit.blade.php --}}
@extends('layouts.main')

@section('title', 'Edit User')

@section('content')
<div class="max-w-2xl space-y-4">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-gray-500">
        <a href="{{ route('admin.users.index') }}" class="hover:text-green-500 transition">Kelola User</a>
        <i class="fas fa-chevron-right text-[10px]"></i>
        <span class="text-gray-300 truncate">Edit: {{ $user->name }}</span>
    </div>

    {{-- Card --}}
    <div class="card-panel p-6">
        <h2 class="text-sm font-semibold text-gray-200 mb-5">
            <i class="fas fa-user-edit text-green-400 mr-2"></i>Edit User
        </h2>

        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            @include('pages.users.form')

            <div class="flex flex-col sm:flex-row gap-3 mt-6 pt-5 border-t border-white/10">
                <button type="submit"
                        class="btn-primary justify-center">
                    <i class="fas fa-save"></i> Perbarui
                </button>
                <a href="{{ route('admin.users.index') }}"
                class="flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 text-gray-400 text-xs px-5 py-2.5 rounded-lg transition">
                    <i class="fas fa-arrow-left"></i> Batal
                </a>
            </div>
        </form>
    </div>

</div>
@endsection