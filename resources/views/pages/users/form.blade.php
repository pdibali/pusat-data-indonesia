<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

    {{-- Nama Lengkap --}}
    <div class="flex flex-col gap-1.5">
        <label class="text-xs font-medium text-gray-400">
            Nama lengkap <span class="text-red-400">*</span>
        </label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
               class="bg-white/5 border @error('name') border-red-500/50 @else border-gray-300 @enderror
                      text-gray-600 text-sm rounded-lg px-3 py-2 placeholder-gray-600
                      focus:outline-none focus:border-blue-400/60 transition"
               placeholder="Masukkan nama lengkap">
        @error('name') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Username --}}
    <div style="display:flex; flex-direction:column; gap:6px;">
        <label class="text-xs font-medium text-gray-400">
            Username <span class="text-red-400">*</span>
        </label>
        <input type="text" name="username" value="{{ old('username', $user->username ?? '') }}"
               class="bg-white/5 border @error('username') border-red-500/50 @else border-gray-300 @enderror
                      text-gray-600 text-sm rounded-lg px-3 py-2 placeholder-gray-600
                      focus:outline-none focus:border-blue-400/60 transition"
               placeholder="Masukkan username">
        @error('username') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Email --}}
    <div style="display:flex; flex-direction:column; gap:6px;">
        <label class="text-xs font-medium text-gray-400">
            Email <span class="text-red-400">*</span>
        </label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
               class="bg-white/5 border @error('email') border-red-500/50 @else border-gray-300 @enderror
                      text-gray-600 text-sm rounded-lg px-3 py-2 placeholder-gray-600
                      focus:outline-none focus:border-blue-400/60 transition"
               placeholder="Masukkan email">
        @error('email') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Group --}}
    <div style="display:flex; flex-direction:column; gap:6px;">
        <label class="text-xs font-medium text-gray-400">
            Group <span class="text-red-400">*</span>
        </label>
        <select name="group_id"
                class="bg-white/5 border @error('group_id') border-red-500/50 @else border-gray-300 @enderror
                       text-gray-600 text-sm rounded-lg px-3 py-2
                       focus:outline-none focus:border-blue-400/60 transition">
            <option value="">-- Pilih group --</option>
            @foreach ($groups as $group)
                <option value="{{ $group->group_id }}"
                    {{ old('group_id', $user->group_id ?? '') == $group->group_id ? 'selected' : '' }}>
                    {{ $group->title }}
                </option>
            @endforeach
        </select>
        @error('group_id') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Password --}}
    <div style="display:flex; flex-direction:column; gap:6px;">
        <label class="text-xs font-medium text-gray-400">
            Password
            @isset($user)
                <span class="text-gray-600 font-normal">(kosongkan jika tidak diubah)</span>
            @else
                <span class="text-red-400">*</span>
            @endisset
        </label>
        <input type="password" name="password"
               class="bg-white/5 border @error('password') border-red-500/50 @else border-gray-300 @enderror
                      text-gray-600 text-sm rounded-lg px-3 py-2 placeholder-gray-600
                      focus:outline-none focus:border-blue-400/60 transition"
               placeholder="{{ isset($user) ? 'Biarkan kosong jika tidak diubah' : 'Min. 8 karakter' }}">
        @error('password') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Konfirmasi Password --}}
    <div style="display:flex; flex-direction:column; gap:6px;">
        <label class="text-xs font-medium text-gray-400">
            Konfirmasi password
            @unless(isset($user)) <span class="text-red-400">*</span> @endunless
        </label>
        <input type="password" name="password_confirmation"
               class="bg-white/5 border border-gray-300 text-gray-600 text-sm rounded-lg px-3 py-2
                      placeholder-gray-600 focus:outline-none focus:border-blue-400/60 transition"
               placeholder="Ulangi password">
    </div>

</div>