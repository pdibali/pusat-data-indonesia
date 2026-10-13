<div class="flex flex-col gap-1.5 {{ ($registration ?? false) ? '' : 'sm:col-span-2' }}">
    <label for="user-address" class="text-xs font-medium {{ ($registration ?? false) ? 'text-slate-800' : 'text-gray-500' }}">
        Alamat <span class="text-red-500">*</span>
    </label>
    <textarea tabindex="4" id="user-address" name="address" required maxlength="1000" rows="3"
              autocomplete="street-address"
              class="w-full border @error('address') border-red-500 @else border-gray-300 @enderror
                     {{ ($registration ?? false) ? 'text-xs text-slate-900' : 'text-sm text-gray-700' }}
                     rounded-lg px-3 py-2 placeholder-gray-400 focus:outline-none focus:border-blue-400 transition"
              placeholder="Masukkan alamat, kota/kabupaten, dan negara">{{ old('address', $user->address ?? '') }}</textarea>
    @error('address') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
</div>
