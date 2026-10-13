<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/js/app.ts', 'resources/css/app.css'])
    <title>Registrasi - Pusat Data Indonesia Bali</title>
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-50 p-4 sm:p-6 lg:p-8">

    <div class="w-full max-w-3xl bg-white rounded-sm border border-slate-200 shadow-xl overflow-hidden flex flex-col sm:flex-row">

        {{-- Panel Kiri: Branding --}}
        <div class="w-full sm:w-2/5 bg-stikom-blue p-8 flex flex-col justify-center">
            
        </div>

        {{-- Panel Kanan: Form --}}
        <div class="w-full sm:w-3/5 p-8">

            {{-- Ringkasan Error Server (Laravel) --}}
            @if ($errors->any())
                <div class="p-4 mb-6 bg-red-50 border-l-4 border-red-600 rounded-r-xl" role="alert">
                    <h2 class="text-sm font-bold text-red-800">Terdapat kesalahan pendaftaran:</h2>
                    <ul class="mt-2 text-xs text-red-700 list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="p-4 mb-6 bg-green-50 border-l-4 border-green-600 rounded-r-xl" role="status">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            <h1 class="mb-5 text-2xl text-center font-poppins font-bold text-stikom-blue tracking-tight">
                DAFTAR AKUN
            </h1>

            <form action="/register" method="POST" novalidate class="space-y-5">
                @csrf
                <input type="hidden" name="invitation_token" value="{{ old('invitation_token', request('invitation_token')) }}">
                <input type="hidden" name="invitation_email" value="{{ old('invitation_email', request('email')) }}">

                {{-- Nama Lengkap --}}
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-800 mb-1.5">
                        Nama Lengkap <span class="text-red-600" aria-hidden="true">*</span>
                        <span class="sr-only">(wajib diisi)</span>
                    </label>
                    <input
                        id="name" type="text" name="name" value="{{ old('name') }}"
                        required tabindex="1" autocomplete="name"
                        class="w-full px-2 py-1.5 bg-white text-xs text-slate-900 placeholder-slate-400 border-2 border-slate-300 rounded-lg transition-all duration-200 focus:outline-none focus:border-stikom-blue focus:ring-4 focus:ring-blue-100 @error('name') border-red-400 @enderror"
                    />
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Username --}}
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-800 mb-1.5">
                        Username <span class="text-red-600" aria-hidden="true">*</span>
                        <span class="sr-only">(wajib diisi)</span>
                    </label>
                    <input
                        id="username" type="text" name="username" value="{{ old('username') }}"
                        required tabindex="2" autocomplete="username"
                        class="w-full px-2 py-1.5 bg-white text-xs text-slate-900 placeholder-slate-400 border-2 border-slate-300 rounded-lg transition-all duration-200 focus:outline-none focus:border-stikom-blue focus:ring-4 focus:ring-blue-100 @error('username') border-red-400 @enderror"
                    />
                    @error('username')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-800 mb-1.5">
                        Email <span class="text-red-600" aria-hidden="true">*</span>
                        <span class="sr-only">(wajib diisi)</span>
                    </label>
                    <input
                        id="email" type="email" name="email" value="{{ old('email', request('email')) }}"
                        {{ request('invitation_token') ? 'readonly' : '' }}
                        required tabindex="3" autocomplete="email"
                        class="w-full px-2 py-1.5 bg-white text-xs text-slate-900 placeholder-slate-400 border-2 border-slate-300 rounded-lg transition-all duration-200 focus:outline-none focus:border-stikom-blue focus:ring-4 focus:ring-blue-100 @error('email') border-red-400 @enderror"
                    />
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @if(request('invitation_token'))
                        <p class="mt-2 text-xs text-slate-500">Anda sedang mendaftar sebagai akun yang diundang. Gunakan email yang sama dengan undangan untuk menerima akses organisasi.</p>
                    @endif
                </div>

                @if(request('invitation_token'))
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        Anda memiliki undangan organisasi untuk email <strong>{{ request('email') }}</strong>. Setelah mendaftar, akun Anda akan otomatis bergabung dengan organisasi jika email cocok.
                    </div>
                @endif

                @include('components.user-address-field', ['registration' => true])

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-800 mb-1.5">
                        Password <span class="text-red-600" aria-hidden="true">*</span>
                        <span class="sr-only">(wajib diisi)</span>
                    </label>
                    <div class="relative flex items-center" id="reg-password-wrapper">
                        <input
                            id="password" type="password" name="password"
                            required tabindex="4" autocomplete="new-password"
                            class="w-full pl-4 pr-12 py-1.5 bg-white text-xs text-slate-900 border-2 border-slate-300 rounded-lg transition-all duration-200 focus:outline-none focus:border-stikom-blue focus:ring-4 focus:ring-blue-100 @error('password') border-red-400 @enderror"
                        />
                        <button type="button" id="togglePassword" tabindex="-1"
                            class="absolute right-3 p-1.5 text-slate-500 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-stikom-blue rounded-lg transition-colors">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12s-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75A3.75 3.75 0 1 0 12 8.25a3.75 3.75 0 0 0 0 7.5z" />
                            </svg>
                            <span class="sr-only">Tampilkan password</span>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi Password --}}
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-800 mb-1.5">
                        Konfirmasi Password <span class="text-red-600" aria-hidden="true">*</span>
                        <span class="sr-only">(wajib diisi)</span>
                    </label>
                    <div class="relative flex items-center" id="reg-password-confirmation-wrapper">
                        <input
                            id="password_confirmation" type="password" name="password_confirmation"
                            required tabindex="5" autocomplete="new-password"
                            class="w-full pl-4 pr-12 py-1.5 bg-white text-xs text-slate-900 border-2 border-slate-300 rounded-lg transition-all duration-200 focus:outline-none focus:border-stikom-blue focus:ring-4 focus:ring-blue-100"
                        />
                        <button type="button" id="togglePasswordConfirmation" tabindex="-1"
                            class="absolute right-3 p-1.5 text-slate-500 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-stikom-blue rounded-lg transition-colors">
                            <svg id="eyeIconConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-5 h-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12s-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75A3.75 3.75 0 1 0 12 8.25a3.75 3.75 0 0 0 0 7.5z" />
                            </svg>
                            <span class="sr-only">Tampilkan konfirmasi password</span>
                        </button>
                    </div>
                </div>

                {{-- Kebijakan Privasi --}}
                <div class="flex items-start gap-3">
                    <input
                        id="privacy_policy" type="checkbox" name="privacy_policy"
                        tabindex="6" required
                        class="mt-1 h-4 w-4 rounded border-slate-300 text-stikom-blue focus:ring-stikom-blue cursor-pointer"
                    />
                    <label for="privacy_policy" class="text-sm text-slate-600">
                        Saya telah membaca dan menyetujui
                        <button type="button" id="openPrivacyModal" tabindex="-1"
                            class="text-stikom-blue hover:underline font-medium">
                            Kebijakan Privasi
                        </button>
                    </label>
                </div>

                {{-- Tombol Daftar --}}
                <div class="pt-2">
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">
                    <button type="submit" tabindex="7"
                        class="btn flex w-full justify-center rounded-sm bg-linear-to-r from-blue-500 to-blue-400 px-3 py-1.5 text-white text-sm/6 font-semibold hover:from-blue-700 hover:to-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 shadow-lg shadow-blue-500/30 transition duration-300 ease-in-out hover:scale-104">
                        Daftar
                    </button>
                </div>

                <p class="text-center text-xs text-slate-500">
                    Sudah punya akun?
                    <a href="/login" class="text-stikom-blue hover:underline font-medium">Login</a>
                </p>
            </form>
        </div>
    </div>

    {{-- Modal Kebijakan Privasi --}}
    <div id="privacyModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-lg bg-white rounded-xl shadow-xl">

            <div class="flex items-center justify-between bg-stikom-blue px-6 py-4 rounded-t-xl">
                <h3 class="text-white font-bold text-base tracking-wide">Kebijakan Privasi</h3>
                <button type="button" id="closePrivacyModal" class="text-white hover:text-gray-200 text-xl font-bold">
                    &times;
                </button>
            </div>

            <div class="px-6 py-5 max-h-96 overflow-y-auto text-xs text-gray-700 space-y-4">
                <p class="font-semibold text-gray-900">1. Pengumpulan Data</p>
                <p>Kami mengumpulkan nama, username, email, dan alamat yang Anda berikan saat mendaftar untuk pengelolaan akun dan memahami sebaran pengguna layanan, termasuk pengguna dari luar Indonesia.</p>

                <p class="font-semibold text-gray-900">2. Penggunaan Data</p>
                <p>Data yang dikumpulkan digunakan untuk autentikasi, komunikasi terkait layanan, serta peningkatan kualitas platform Pusat Data Indonesia Bali.</p>

                <p class="font-semibold text-gray-900">3. Keamanan Data</p>
                <p>Kami menerapkan langkah-langkah keamanan teknis untuk melindungi data Anda dari akses tidak sah, perubahan, atau penghapusan.</p>

                <p class="font-semibold text-gray-900">4. Berbagi Data</p>
                <p>Kami tidak menjual atau membagikan data pribadi Anda kepada pihak ketiga tanpa persetujuan Anda, kecuali diwajibkan oleh hukum.</p>

                <p class="font-semibold text-gray-900">5. Hak Pengguna</p>
                <p>Anda berhak mengakses, memperbarui, atau menghapus data pribadi Anda dengan menghubungi administrator platform.</p>

                <p class="font-semibold text-gray-900">6. Perubahan Kebijakan</p>
                <p>Kebijakan ini dapat diperbarui sewaktu-waktu tanpa pemberitahuan sebelumnya.</p>
            </div>

            <div class="px-6 py-4 border-t flex justify-end gap-3">
                <button type="button" id="closePrivacyModalBtn"
                    class="px-4 py-2 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Tutup
                </button>
                <button type="button" id="agreePrivacyBtn"
                    class="px-4 py-2 text-xs text-white bg-stikom-blue rounded-lg hover:bg-blue-700 font-semibold">
                    Saya Setuju
                </button>
            </div>
        </div>
    </div>

    @php $recaptchaSiteKey = env('RECAPTCHA_SITE_KEY'); @endphp
    @if($recaptchaSiteKey)
        <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
    @endif

    <script>
        function setupPasswordToggle(inputId, toggleId, iconId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            const icon = document.getElementById(iconId);

            toggle.addEventListener("click", function () {
                if (input.type === "password") {
                    input.type = "text";
                    icon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58a3 3 0 004.24 4.24" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.1A9.77 9.77 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a15.3 15.3 0 01-4.24 5.33" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.53 6.53A15.05 15.05 0 002.25 12s3.75 7.5 9.75 7.5c1.24 0 2.39-.22 3.44-.61" />`;
                } else {
                    input.type = "password";
                    icon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12s-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75A3.75 3.75 0 1 0 12 8.25a3.75 3.75 0 0 0 0 7.5z" />`;
                }
            });
        }

        setupPasswordToggle("password", "togglePassword", "eyeIcon");
        setupPasswordToggle("password_confirmation", "togglePasswordConfirmation", "eyeIconConfirmation");

        // Modal Kebijakan Privasi
        const modal = document.getElementById('privacyModal');
        const openBtn = document.getElementById('openPrivacyModal');
        const closeBtn = document.getElementById('closePrivacyModal');
        const closeBtnFooter = document.getElementById('closePrivacyModalBtn');
        const agreeBtn = document.getElementById('agreePrivacyBtn');
        const checkbox = document.getElementById('privacy_policy');

        openBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        closeBtn.addEventListener('click', closeModal);
        closeBtnFooter.addEventListener('click', closeModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        agreeBtn.addEventListener('click', () => {
            checkbox.checked = true;
            closeModal();
        });

        const form = document.querySelector('form[action="/register"]');
        const tokenInput = document.getElementById('g-recaptcha-response');
        const submitBtn = form.querySelector('button[type="submit"]');
        const siteKey = '{{ $recaptchaSiteKey }}';
        let isSubmitting = false;

        function lockSubmit() {
            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.dataset.label = submitBtn.textContent;
            submitBtn.textContent = 'Memproses...';
            submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }

        function unlockSubmit() {
            isSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.textContent = submitBtn.dataset.label || 'Daftar';
            submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
        }

        form.addEventListener('submit', function (event) {
            // Klik kedua dan seterusnya langsung dibuang
            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            // Biarkan validasi HTML5 jalan dulu (form memakai novalidate, jadi cek manual)
            lockSubmit();

            if (!siteKey || tokenInput.value) {
                return; // submit normal, tombol sudah terkunci
            }

            event.preventDefault();

            if (!window.grecaptcha) {
                form.submit();
                return;
            }

            window.grecaptcha.ready(function () {
                window.grecaptcha.execute(siteKey, { action: 'register' })
                    .then(function (token) {
                        tokenInput.value = token;
                        form.submit(); // tidak memicu event submit lagi
                    })
                    .catch(function () {
                        unlockSubmit(); // reCAPTCHA gagal, izinkan coba lagi
                    });
            });
        });

        // Kalau user menekan Back (bfcache), tombol jangan tetap terkunci
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) unlockSubmit();
        });
    </script>
</body>
</html>