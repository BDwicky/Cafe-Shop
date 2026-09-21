@extends('kasir.app')

@section('title', 'Pengaturan Suara Announcer')

@section('content')
<div x-data="announcerSettingsManager(@js($settings))" class="w-full p-4 sm:p-6 space-y-6 pb-12">

    <!-- 1. HEADER HALAMAN -->
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-[#E4DCCC]">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl sm:text-3xl font-serif font-bold text-[#1F1812] tracking-tight">
                    Pengaturan Suara Announcer
                </h1>
                <span class="px-2.5 py-0.5 rounded-full bg-[#D9973E]/15 border border-[#D9973E]/30 text-[#B5762A] font-mono text-xs font-bold">
                    Voice Engine & TTS
                </span>
            </div>
            <p class="font-sans text-xs text-[#8A7B66] mt-1">
                Kustomisasi aksen, model suara pemanggil pesanan kasir, format kalimat, dan nada dering bel kafe.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <!-- Auto-Save Status Indicator -->
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-[#E4DCCC] text-[11px] font-mono shadow-2xs">
                <span x-show="autoSaveStatus === 'saving'" class="text-[#8A7B66] flex items-center gap-1.5">
                    <span class="animate-spin text-xs">⟳</span> Menyimpan...
                </span>
                <span x-show="autoSaveStatus === 'saved'" class="text-[#5F7F42] font-bold flex items-center gap-1.5">
                    <span class="text-xs">✓</span> Tersimpan otomatis
                </span>
                <span x-show="autoSaveStatus === 'error'" class="text-rose-600 font-bold flex items-center gap-1.5">
                    <span class="text-xs">✕</span> Gagal menyimpan
                </span>
                <span x-show="!autoSaveStatus" class="text-[#A89A85] flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#5F7F42]"></span> Siap
                </span>
            </div>

            <a href="{{ route('kasir.music.index') }}"
               class="px-4 py-2.5 bg-white hover:bg-[#FAF7F2] text-[#1F1812] border border-[#E4DCCC] hover:border-[#D9973E] font-mono text-xs font-bold rounded-xl transition shadow-2xs flex items-center gap-1.5 active:scale-98">
                <span>‹ Sound Station</span>
            </a>
            <button type="button"
                    @click="saveSettings()"
                    :disabled="saving"
                    class="px-5 py-2.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-wider font-bold rounded-xl transition-all shadow-md flex items-center gap-2 cursor-pointer disabled:opacity-50 active:scale-98">
                <span x-show="saving" class="animate-spin text-sm">⟳</span>
                <span x-show="!saving">✓ Simpan Pengaturan</span>
            </button>
        </div>
    </header>

    <!-- FLASH NOTIFICATION BANNER -->
    @if (session('success'))
        <div class="p-4 bg-[#5F7F42]/10 border border-[#5F7F42]/40 rounded-2xl text-[#1F1812] text-xs font-mono flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-[#5F7F42] text-white flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-[#8A7B66] hover:text-[#1F1812] p-1 text-sm font-bold leading-none">✕</button>
        </div>
    @endif

    <!-- 2. KARTU UJI COBA SUARA LANGSUNG (LIVE PREVIEW INTERACTIVE) -->
    <div class="bg-gradient-to-r from-[#17110C] via-[#241A13] to-[#17110C] text-[#F7F3EC] border border-[#3A2C20] p-6 sm:p-7 rounded-2xl shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-[#D9973E]/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
            <div class="space-y-2 max-w-xl">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 bg-[#D9973E] text-[#1F1812] font-mono text-[10px] uppercase tracking-widest font-extrabold rounded-full shadow-2xs">
                        Live Preview
                    </span>
                    <span class="font-mono text-xs text-[#A89A85]">Dengarkan hasil konfigurasi langsung</span>
                </div>
                <h3 class="text-xl sm:text-2xl font-serif font-bold text-white tracking-tight">
                    Uji Coba Suara & Nada Dering Announcer
                </h3>
                <p class="text-xs text-[#C4B6A3] leading-relaxed">
                    Kalimat yang akan diucapkan:
                    <span class="text-[#E5A44B] font-serif italic font-medium" x-text="'&ldquo;' + generateSampleText() + '&rdquo;'"></span>
                </p>
                <div x-show="form.voice_model !== 'device_voice'" class="pt-2">
                    <div class="flex items-center justify-between text-[11px] font-mono text-[#D9973E] mb-1.5">
                        <span>🔊 Pemutar Audio MP3 Google TTS:</span>
                        <span class="text-[#A89A85] text-[10px]">Klik tombol ▶ di bawah atau tombol Putar</span>
                    </div>
                    <audio id="announcer-preview-audio" controls class="w-full h-8 rounded-lg opacity-90 hover:opacity-100 transition shadow-inner" :src="getAudioUrl()"></audio>
                </div>
            </div>

            <!-- Input Sampel Nama & Tombol Test -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                <div class="flex items-center gap-2">
                    <input type="text" x-model="sampleName" placeholder="Nama Sampel"
                           class="w-32 px-3.5 py-2.5 bg-[#2A211A] border border-[#4A3B2E] text-xs text-white font-mono rounded-xl focus:outline-none focus:border-[#D9973E] shadow-inner font-medium">
                    <input type="text" x-model="sampleCode" placeholder="No. 42"
                           class="w-20 px-3.5 py-2.5 bg-[#2A211A] border border-[#4A3B2E] text-xs text-white font-mono rounded-xl focus:outline-none focus:border-[#D9973E] text-center shadow-inner font-medium">
                </div>

                <button type="button"
                        @click="testAnnouncement()"
                        :disabled="isPlayingSample"
                        class="px-6 py-3 bg-[#D9973E] hover:bg-[#E5A44B] text-[#1F1812] font-mono text-xs uppercase tracking-wider font-extrabold rounded-xl transition-all shadow-lg flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 active:scale-98">
                    <span x-show="isPlayingSample" class="animate-spin text-sm">⟳</span>
                    <span x-show="!isPlayingSample" class="text-sm">▶</span>
                    <span x-text="isPlayingSample ? 'Sedang Memutar...' : 'Putar Uji Coba Suara'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 3. GRID UTAMA PENGATURAN (2 KOLOM SEIMBANG: SUARA & FORMAT vs PEREDAM & OTOMASI) -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">

        <!-- ======================================================== -->
        <!-- KOLOM KIRI: KARAKTER SUARA, FORMAT PANGGILAN & BEL CHIME -->
        <!-- ======================================================== -->
        <div class="space-y-6">

            <!-- BAGIAN 1: MODEL SUARA & AKSEN -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                <div class="border-b border-[#E4DCCC] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🎙️</span>
                        <h2 class="text-lg font-serif font-bold text-[#1F1812]">
                            1. Pilih Model Suara & Aksen
                        </h2>
                    </div>
                    <p class="text-xs text-[#7A6A58] mt-0.5">
                        Pilih suara pemanggil pesanan kasir. Model 1-5 menggunakan engine Google TTS resmi bersuara jernih.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">

                    <!-- Opsi 1: Mbak Google Indonesia (Viral TikTok / Asli) -->
                    <label class="border-2 rounded-2xl p-4.5 cursor-pointer transition-all relative flex flex-col justify-between shadow-2xs"
                           :class="form.voice_model === 'mbak_google' ? 'border-[#D9973E] bg-[#D9973E]/10 ring-2 ring-[#D9973E]/20' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/50'">
                        <input type="radio" name="voice_model" value="mbak_google" x-model="form.voice_model" class="sr-only">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 bg-[#5F7F42] text-white font-mono text-[9px] uppercase tracking-wider font-extrabold rounded-full shadow-2xs">
                                    ★ Viral TikTok / Asli
                                </span>
                                <span class="text-2xl">👩‍💼</span>
                            </div>
                            <h4 class="font-serif font-bold text-sm text-[#1F1812] mt-2.5">
                                Mbak Google (Wanita Viral)
                            </h4>
                            <p class="text-xs text-[#7A6A58] mt-1 leading-relaxed">
                                Suara wanita Google Translate yang viral di TikTok dan medsos. 100% natural bahasa Indonesia, jernih, dan standar kafe.
                            </p>
                        </div>
                        <div class="mt-3.5 pt-2 border-t border-[#EAE2D5] flex items-center justify-between text-[11px] font-mono text-[#5C4D3C]">
                            <span>Google TTS (id) - Normal</span>
                            <span class="font-bold text-[#D9973E]" x-show="form.voice_model === 'mbak_google'">✓ Aktif</span>
                        </div>
                    </label>

                    <!-- Opsi 2: Mbak Google Santai (Tempo Lembut) -->
                    <label class="border-2 rounded-2xl p-4.5 cursor-pointer transition-all relative flex flex-col justify-between shadow-2xs"
                           :class="form.voice_model === 'ms_gadis' ? 'border-[#D9973E] bg-[#D9973E]/10 ring-2 ring-[#D9973E]/20' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/50'">
                        <input type="radio" name="voice_model" value="ms_gadis" x-model="form.voice_model" class="sr-only">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 bg-[#D9973E] text-white font-mono text-[9px] uppercase tracking-wider font-extrabold rounded-full shadow-2xs">
                                    Tempo Santai (0.9x)
                                </span>
                                <span class="text-2xl">☕</span>
                            </div>
                            <h4 class="font-serif font-bold text-sm text-[#1F1812] mt-2.5">
                                Mbak Google (Tempo Santai)
                            </h4>
                            <p class="text-xs text-[#7A6A58] mt-1 leading-relaxed">
                                Suara wanita Google Translate dengan tempo yang lebih pelan dan tenang, cocok untuk suasana kafe santai/lounge.
                            </p>
                        </div>
                        <div class="mt-3.5 pt-2 border-t border-[#EAE2D5] flex items-center justify-between text-[11px] font-mono text-[#5C4D3C]">
                            <span>Google TTS (id) - 0.9x</span>
                            <span class="font-bold text-[#D9973E]" x-show="form.voice_model === 'ms_gadis'">✓ Aktif</span>
                        </div>
                    </label>

                    <!-- Opsi 3: Mbak Google Cepat (Tempo Ringkas) -->
                    <label class="border-2 rounded-2xl p-4.5 cursor-pointer transition-all relative flex flex-col justify-between shadow-2xs"
                           :class="form.voice_model === 'ms_ardi' ? 'border-[#D9973E] bg-[#D9973E]/10 ring-2 ring-[#D9973E]/20' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/50'">
                        <input type="radio" name="voice_model" value="ms_ardi" x-model="form.voice_model" class="sr-only">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 bg-[#8A7B66] text-white font-mono text-[9px] uppercase tracking-wider font-extrabold rounded-full shadow-2xs">
                                    Tempo Gesit (1.15x)
                                </span>
                                <span class="text-2xl">⚡</span>
                            </div>
                            <h4 class="font-serif font-bold text-sm text-[#1F1812] mt-2.5">
                                Mbak Google (Tempo Gesit)
                            </h4>
                            <p class="text-xs text-[#7A6A58] mt-1 leading-relaxed">
                                Suara wanita Google Translate dengan tempo lebih cepat, praktis dan tanggap untuk jam sibuk atau antrean take-away.
                            </p>
                        </div>
                        <div class="mt-3.5 pt-2 border-t border-[#EAE2D5] flex items-center justify-between text-[11px] font-mono text-[#5C4D3C]">
                            <span>Google TTS (id) - 1.15x</span>
                            <span class="font-bold text-[#D9973E]" x-show="form.voice_model === 'ms_ardi'">✓ Aktif</span>
                        </div>
                    </label>

                    <!-- Opsi 4: Aksen Kafe Nusantara (Jawa Medok) -->
                    <label class="border-2 rounded-2xl p-4.5 cursor-pointer transition-all relative flex flex-col justify-between shadow-2xs"
                           :class="form.voice_model === 'google_local' ? 'border-[#D9973E] bg-[#D9973E]/10 ring-2 ring-[#D9973E]/20' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/50'">
                        <input type="radio" name="voice_model" value="google_local" x-model="form.voice_model" class="sr-only">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 bg-amber-700 text-white font-mono text-[9px] uppercase tracking-wider font-extrabold rounded-full shadow-2xs">
                                    Aksen Nusantara (jv)
                                </span>
                                <span class="text-2xl">🏛️</span>
                            </div>
                            <h4 class="font-serif font-bold text-sm text-[#1F1812] mt-2.5">
                                Aksen Nusantara (Jawa Medok)
                            </h4>
                            <p class="text-xs text-[#7A6A58] mt-1 leading-relaxed">
                                Suara Google Translate dengan logat bernuansa daerah nusantara yang ramah, akrab, santai, dan bersahabat.
                            </p>
                        </div>
                        <div class="mt-3.5 pt-2 border-t border-[#EAE2D5] flex items-center justify-between text-[11px] font-mono text-[#5C4D3C]">
                            <span>Google TTS (jv)</span>
                            <span class="font-bold text-[#D9973E]" x-show="form.voice_model === 'google_local'">✓ Aktif</span>
                        </div>
                    </label>

                    <!-- Opsi 5: English Cafe Accent (Wanita Internasional) -->
                    <label class="border-2 rounded-2xl p-4.5 cursor-pointer transition-all relative flex flex-col justify-between shadow-2xs"
                           :class="form.voice_model === 'english_cafe' ? 'border-[#D9973E] bg-[#D9973E]/10 ring-2 ring-[#D9973E]/20' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/50'">
                        <input type="radio" name="voice_model" value="english_cafe" x-model="form.voice_model" class="sr-only">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 bg-purple-700 text-white font-mono text-[9px] uppercase tracking-wider font-extrabold rounded-full shadow-2xs">
                                    English Tourist Cafe
                                </span>
                                <span class="text-2xl">🌐</span>
                            </div>
                            <h4 class="font-serif font-bold text-sm text-[#1F1812] mt-2.5">
                                English Cafe Accent (Wanita)
                            </h4>
                            <p class="text-xs text-[#7A6A58] mt-1 leading-relaxed">
                                Suara wanita Google Translate bahasa Inggris internasional (*"Order for {name}, ready at the counter"*), cocok untuk kafe turis.
                            </p>
                        </div>
                        <div class="mt-3.5 pt-2 border-t border-[#EAE2D5] flex items-center justify-between text-[11px] font-mono text-[#5C4D3C]">
                            <span>Google TTS (en)</span>
                            <span class="font-bold text-[#D9973E]" x-show="form.voice_model === 'english_cafe'">✓ Aktif</span>
                        </div>
                    </label>

                    <!-- Opsi 6: Suara Spesifik Perangkat (Device Voice) -->
                    <label class="border-2 rounded-2xl p-4.5 cursor-pointer transition-all relative flex flex-col justify-between shadow-2xs"
                           :class="form.voice_model === 'device_voice' ? 'border-[#D9973E] bg-[#D9973E]/10 ring-2 ring-[#D9973E]/20' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/50'">
                        <input type="radio" name="voice_model" value="device_voice" x-model="form.voice_model" class="sr-only">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 bg-gray-700 text-white font-mono text-[9px] uppercase tracking-wider font-extrabold rounded-full shadow-2xs">
                                    Kustom Komputer
                                </span>
                                <span class="text-2xl">💻</span>
                            </div>
                            <h4 class="font-serif font-bold text-sm text-[#1F1812] mt-2.5">
                                Suara Terpasang di Komputer
                            </h4>
                            <p class="text-xs text-[#7A6A58] mt-1 leading-relaxed">
                                Pilih salah satu voice pack dari seluruh daftar suara yang terpasang di sistem operasi komputer Anda.
                            </p>
                        </div>
                        <div class="mt-3.5 pt-2 border-t border-[#EAE2D5] text-[11px] font-mono text-[#5C4D3C]">
                            <span x-text="availableVoices.length + ' suara terdeteksi'"></span>
                        </div>
                    </label>

                </div>

                <!-- Dropdown Jika Memilih Device Voice -->
                <div x-show="form.voice_model === 'device_voice'" x-transition class="p-4 bg-[#FAF7F2] border border-[#E4DCCC] rounded-2xl space-y-2">
                    <label class="block font-mono text-[11px] uppercase tracking-wider text-[#5C4D3C] font-bold">
                        Pilih Suara Terpasang:
                    </label>
                    <select x-model="form.device_voice_name" class="w-full px-3.5 py-2.5 bg-white border border-[#E4DCCC] focus:border-[#D9973E] rounded-xl text-xs font-mono font-medium shadow-2xs">
                        <option value="">-- Pilih dari daftar suara perangkat --</option>
                        <template x-for="v in availableVoices" :key="v.name">
                            <option :value="v.name" x-text="v.name + ' (' + v.lang + ')'"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- BAGIAN 2: TEMPLATE KALIMAT PANGGILAN -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                <div class="border-b border-[#E4DCCC] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">💬</span>
                        <h2 class="text-lg font-serif font-bold text-[#1F1812]">
                            2. Format Kalimat Panggilan
                        </h2>
                    </div>
                    <p class="text-xs text-[#7A6A58] mt-0.5">
                        Pilih gaya kalimat yang diucapkan saat pesanan selesai disiapkan.
                    </p>
                </div>

                <div class="space-y-2.5">
                    <!-- Template 1: Ringkas (Recommended) -->
                    <label class="p-3.5 border-2 rounded-xl cursor-pointer block transition-all shadow-2xs"
                           :class="form.template_type === 'concise' ? 'border-[#D9973E] bg-[#D9973E]/10' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/40'">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="template_type" value="concise" x-model="form.template_type" class="accent-[#D9973E]">
                            <span class="font-serif font-bold text-xs text-[#1F1812]">Ringkas & Ramah (~3 detik)</span>
                            <span class="px-2 py-0.5 bg-[#5F7F42] text-white font-mono text-[9px] rounded-full font-bold">Terbaik</span>
                        </div>
                        <p class="text-[11px] text-[#7A6A58] mt-1 pl-5 italic font-mono">
                            "Pesanan Kak {name}, siap diambil di kasir."
                        </p>
                    </label>

                    <!-- Template 2: Formal / Standar -->
                    <label class="p-3.5 border-2 rounded-xl cursor-pointer block transition-all shadow-2xs"
                           :class="form.template_type === 'formal' ? 'border-[#D9973E] bg-[#D9973E]/10' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/40'">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="template_type" value="formal" x-model="form.template_type" class="accent-[#D9973E]">
                            <span class="font-serif font-bold text-xs text-[#1F1812]">Sopan & Lengkap (~6 detik)</span>
                        </div>
                        <p class="text-[11px] text-[#7A6A58] mt-1 pl-5 italic font-mono">
                            "Panggilan untuk Kak {name}, pesanan nomor {code} sudah siap. Silakan ambil di kasir."
                        </p>
                    </label>

                    <!-- Template 3: Gaya Bandara / Mal -->
                    <label class="p-3.5 border-2 rounded-xl cursor-pointer block transition-all shadow-2xs"
                           :class="form.template_type === 'airport' ? 'border-[#D9973E] bg-[#D9973E]/10' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/40'">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="template_type" value="airport" x-model="form.template_type" class="accent-[#D9973E]">
                            <span class="font-serif font-bold text-xs text-[#1F1812]">Gaya Bandara / Mal (~8 detik)</span>
                        </div>
                        <p class="text-[11px] text-[#7A6A58] mt-1 pl-5 italic font-mono">
                            "Perhatian, pesanan nomor {code} atas nama Kak {name}, siap diambil di meja kasir. Terima kasih."
                        </p>
                    </label>

                    <!-- Template 4: English Cafe -->
                    <label class="p-3.5 border-2 rounded-xl cursor-pointer block transition-all shadow-2xs"
                           :class="form.template_type === 'english' ? 'border-[#D9973E] bg-[#D9973E]/10' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/40'">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="template_type" value="english" x-model="form.template_type" class="accent-[#D9973E]">
                            <span class="font-serif font-bold text-xs text-[#1F1812]">English Cafe Style (~4 detik)</span>
                        </div>
                        <p class="text-[11px] text-[#7A6A58] mt-1 pl-5 italic font-mono">
                            "Order for {name}, ready for pickup at the counter."
                        </p>
                    </label>

                    <!-- Template 5: Kustom Format Sendiri -->
                    <label class="p-3.5 border-2 rounded-xl cursor-pointer block transition-all shadow-2xs"
                           :class="form.template_type === 'custom' ? 'border-[#D9973E] bg-[#D9973E]/10' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/40'">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="template_type" value="custom" x-model="form.template_type" class="accent-[#D9973E]">
                            <span class="font-serif font-bold text-xs text-[#1F1812]">Tulis Format Sendiri (Kustom)</span>
                        </div>
                    </label>

                    <!-- Input Kustom Format -->
                    <div x-show="form.template_type === 'custom'" x-transition class="space-y-2 pt-1 pl-2">
                        <textarea x-model="form.custom_template" rows="2" maxlength="250"
                                  placeholder="Gunakan tag {name} dan {code}..."
                                  class="w-full px-3.5 py-2.5 bg-[#FAF7F2] border border-[#E4DCCC] focus:border-[#D9973E] rounded-xl text-xs font-mono text-[#1F1812] focus:outline-none shadow-2xs font-medium"></textarea>
                        <p class="text-[10px] text-[#8A7B66] font-mono">
                            Variabel: <code class="text-[#D9973E] font-bold">{name}</code> (Nama Pelanggan), <code class="text-[#D9973E] font-bold">{code}</code> (Nomor Meja / Antrean).
                        </p>
                    </div>
                </div>
            </div>

            <!-- BAGIAN 3: GAYA NADA DERING (CHIME BELL) -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                <div class="border-b border-[#E4DCCC] pb-3 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🔔</span>
                            <h2 class="text-lg font-serif font-bold text-[#1F1812]">
                                3. Nada Dering Bel (Chime)
                            </h2>
                        </div>
                        <p class="text-xs text-[#7A6A58] mt-0.5">
                            Bunyi lonceng lembut sebelum pengumuman dimulai.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <!-- Chime 1: Ding Dong Kafe -->
                    <div class="p-3.5 border-2 rounded-xl flex items-center justify-between transition-all cursor-pointer shadow-2xs"
                         :class="form.chime_style === 'ding_dong' ? 'border-[#D9973E] bg-[#D9973E]/10' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/40'"
                         @click="form.chime_style = 'ding_dong'">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="chime_style" value="ding_dong" x-model="form.chime_style" class="accent-[#D9973E]">
                            <span class="font-serif font-bold text-xs text-[#1F1812]">Ding-Dong Kafe</span>
                        </div>
                        <button type="button" @click.stop="playChime('ding_dong')" title="Dengarkan Nada"
                                class="text-[#D9973E] hover:text-[#1F1812] hover:bg-[#D9973E]/20 p-1.5 rounded-lg transition flex items-center justify-center cursor-pointer">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.5A2.25 2.25 0 002.25 9.75v4.5A2.25 2.25 0 004.5 16.5h1.94l4.5 4.5c.944.945 2.56.276 2.56-1.06V4.06zM17.75 12c0-1.34-.54-2.56-1.42-3.44a1 1 0 10-1.42 1.42c.52.52.84 1.24.84 2.02s-.32 1.5-.84 2.02a1 1 0 101.42 1.42c.88-.88 1.42-2.1 1.42-3.44zM21.25 12c0-2.31-.94-4.41-2.46-5.93a1 1 0 10-1.42 1.42A6.38 6.38 0 0119.25 12c0 1.76-.72 3.36-1.88 4.51a1 1 0 101.42 1.42A8.38 8.38 0 0021.25 12z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Chime 2: Airport Chime -->
                    <div class="p-3.5 border-2 rounded-xl flex items-center justify-between transition-all cursor-pointer shadow-2xs"
                         :class="form.chime_style === 'airport' ? 'border-[#D9973E] bg-[#D9973E]/10' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/40'"
                         @click="form.chime_style = 'airport'">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="chime_style" value="airport" x-model="form.chime_style" class="accent-[#D9973E]">
                            <span class="font-serif font-bold text-xs text-[#1F1812]">Airport 3-Tone</span>
                        </div>
                        <button type="button" @click.stop="playChime('airport')" title="Dengarkan Nada"
                                class="text-[#D9973E] hover:text-[#1F1812] hover:bg-[#D9973E]/20 p-1.5 rounded-lg transition flex items-center justify-center cursor-pointer">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.5A2.25 2.25 0 002.25 9.75v4.5A2.25 2.25 0 004.5 16.5h1.94l4.5 4.5c.944.945 2.56.276 2.56-1.06V4.06zM17.75 12c0-1.34-.54-2.56-1.42-3.44a1 1 0 10-1.42 1.42c.52.52.84 1.24.84 2.02s-.32 1.5-.84 2.02a1 1 0 101.42 1.42c.88-.88 1.42-2.1 1.42-3.44zM21.25 12c0-2.31-.94-4.41-2.46-5.93a1 1 0 10-1.42 1.42A6.38 6.38 0 0119.25 12c0 1.76-.72 3.36-1.88 4.51a1 1 0 101.42 1.42A8.38 8.38 0 0021.25 12z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Chime 3: Elevator Bell -->
                    <div class="p-3.5 border-2 rounded-xl flex items-center justify-between transition-all cursor-pointer shadow-2xs"
                         :class="form.chime_style === 'bell' ? 'border-[#D9973E] bg-[#D9973E]/10' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/40'"
                         @click="form.chime_style = 'bell'">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="chime_style" value="bell" x-model="form.chime_style" class="accent-[#D9973E]">
                            <span class="font-serif font-bold text-xs text-[#1F1812]">Denting Bel (Soft)</span>
                        </div>
                        <button type="button" @click.stop="playChime('bell')" title="Dengarkan Nada"
                                class="text-[#D9973E] hover:text-[#1F1812] hover:bg-[#D9973E]/20 p-1.5 rounded-lg transition flex items-center justify-center cursor-pointer">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M13.5 4.06c0-1.336-1.616-2.005-2.56-1.06l-4.5 4.5H4.5A2.25 2.25 0 002.25 9.75v4.5A2.25 2.25 0 004.5 16.5h1.94l4.5 4.5c.944.945 2.56.276 2.56-1.06V4.06zM17.75 12c0-1.34-.54-2.56-1.42-3.44a1 1 0 10-1.42 1.42c.52.52.84 1.24.84 2.02s-.32 1.5-.84 2.02a1 1 0 101.42 1.42c.88-.88 1.42-2.1 1.42-3.44zM21.25 12c0-2.31-.94-4.41-2.46-5.93a1 1 0 10-1.42 1.42A6.38 6.38 0 0119.25 12c0 1.76-.72 3.36-1.88 4.51a1 1 0 101.42 1.42A8.38 8.38 0 0021.25 12z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Chime 4: None -->
                    <div class="p-3.5 border-2 rounded-xl flex items-center justify-between transition-all cursor-pointer shadow-2xs"
                         :class="form.chime_style === 'none' ? 'border-[#D9973E] bg-[#D9973E]/10' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/40'"
                         @click="form.chime_style = 'none'">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="chime_style" value="none" x-model="form.chime_style" class="accent-[#D9973E]">
                            <span class="font-serif font-bold text-xs text-[#1F1812]">Tanpa Bel (Hening)</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ======================================================== -->
        <!-- KOLOM KANAN: PEREDAM MUSIK, ADZAN & JEDA TUTUP TOKO      -->
        <!-- ======================================================== -->
        <div class="space-y-6">

            <!-- BAGIAN 4: PENGATURAN TEMPO, PITCH & PEREDAM MUSIK KAFE -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                <div class="border-b border-[#E4DCCC] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🎚️</span>
                        <h2 class="text-lg font-serif font-bold text-[#1F1812]">
                            4. Nada Bicara & Peredam Musik Kafe
                        </h2>
                    </div>
                    <p class="text-xs text-[#7A6A58] mt-0.5">
                        Sesuaikan dinamika suara pengumuman dan tingkat peredaman lagu saat kasir berbicara.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Speed / Rate Slider -->
                    <div class="space-y-2.5 p-3.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl shadow-2xs">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-mono uppercase tracking-wider text-[#5C4D3C] font-bold">Kecepatan (Speed):</span>
                            <span class="font-mono font-bold px-2 py-0.5 rounded-full bg-[#D9973E]/15 text-[#B5762A] text-xs" x-text="form.rate + 'x'"></span>
                        </div>
                        <input type="range" min="0.75" max="1.3" step="0.05" x-model="form.rate"
                               class="w-full accent-[#D9973E] cursor-pointer h-2 bg-[#E4DCCC] rounded-lg">
                        <div class="flex justify-between text-[10px] font-mono text-[#8A7B66]">
                            <span>Santai (0.8x)</span>
                            <span>Normal (1.0x)</span>
                            <span>Cepat (1.25x)</span>
                        </div>
                    </div>

                    <!-- Pitch Slider -->
                    <div class="space-y-2.5 p-3.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl shadow-2xs">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-mono uppercase tracking-wider text-[#5C4D3C] font-bold">Nada Suara (Pitch):</span>
                            <span class="font-mono font-bold px-2 py-0.5 rounded-full bg-[#D9973E]/15 text-[#B5762A] text-xs" x-text="form.pitch"></span>
                        </div>
                        <input type="range" min="0.8" max="1.25" step="0.05" x-model="form.pitch"
                               class="w-full accent-[#D9973E] cursor-pointer h-2 bg-[#E4DCCC] rounded-lg">
                        <div class="flex justify-between text-[10px] font-mono text-[#8A7B66]">
                            <span>Berat (0.8)</span>
                            <span>Natural (1.0)</span>
                            <span>Ceria (1.2)</span>
                        </div>
                    </div>
                </div>

                <!-- Audio Ducking Setting -->
                <div class="pt-4 border-t border-[#F2EDE4] space-y-2.5">
                    <div class="flex items-center justify-between text-xs">
                        <div>
                            <span class="font-mono uppercase tracking-wider text-[#5C4D3C] font-bold">Peredam Musik Kafe (Audio Ducking):</span>
                            <p class="text-[11px] text-[#8A7B66] mt-0.5">Tingkat volume lagu YouTube yang diturunkan saat suara pengumuman berbicara.</p>
                        </div>
                        <span class="font-mono font-bold px-2.5 py-0.5 rounded-full bg-[#5F7F42]/15 text-[#5F7F42] text-xs shrink-0 ml-2"
                              x-text="form.duck_volume === 0 ? '0% (Hening Total)' : form.duck_volume + '%'"></span>
                    </div>
                    <input type="range" min="0" max="35" step="1" x-model.number="form.duck_volume"
                           @input="onDuckVolumeInput()" @change="onDuckVolumeChange()"
                           class="w-full accent-[#D9973E] cursor-pointer h-2 bg-[#E4DCCC] rounded-lg">
                    <div class="flex justify-between text-[10px] text-[#A89A85] font-mono">
                        <span :class="form.duck_volume == 0 ? 'text-[#5F7F42] font-bold' : ''">0% (Hening)</span>
                        <span :class="form.duck_volume == 2 ? 'text-[#5F7F42] font-bold' : ''">2%</span>
                        <span :class="form.duck_volume == 12 ? 'text-[#5F7F42] font-bold' : ''">12% (Standar)</span>
                        <span>25%</span>
                        <span>35%</span>
                    </div>

                    <!-- Opsi Cepat Persentase Volume Peredam Musik Kafe -->
                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                        <span class="text-[10px] font-mono text-[#8A7B66] mr-1">Opsi Cepat:</span>
                        <button type="button" @click="onDuckVolumeInput(0); onDuckVolumeChange()"
                                class="px-2 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                :class="form.duck_volume === 0 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                            0% (Hening)
                        </button>
                        <button type="button" @click="onDuckVolumeInput(2); onDuckVolumeChange()"
                                class="px-2.5 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                :class="form.duck_volume === 2 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                            2%
                        </button>
                        <button type="button" @click="onDuckVolumeInput(5); onDuckVolumeChange()"
                                class="px-2 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                :class="form.duck_volume === 5 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                            5%
                        </button>
                        <button type="button" @click="onDuckVolumeInput(12); onDuckVolumeChange()"
                                class="px-2 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                :class="form.duck_volume === 12 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                            12% (Standar)
                        </button>
                        <button type="button" @click="onDuckVolumeInput(25); onDuckVolumeChange()"
                                class="px-2 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                :class="form.duck_volume === 25 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                            25%
                        </button>
                        <button type="button" @click="onDuckVolumeInput(35); onDuckVolumeChange()"
                                class="px-2 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                :class="form.duck_volume === 35 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                            35%
                        </button>
                    </div>
                </div>
            </div>

            <!-- BAGIAN 5: MODE HORMAT WAKTU ADZAN (SURABAYA & SIDOARJO) -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                <div class="border-b border-[#E4DCCC] pb-3.5 flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🕌</span>
                            <h2 class="text-lg font-serif font-bold text-[#1F1812]">
                                5. Mode Hormat Waktu Adzan
                            </h2>
                        </div>
                        <p class="text-xs text-[#7A6A58] mt-1">
                            Otomatis menurunkan volume musik kafe saat adzan berkumandang (Wilayah <strong>Surabaya & Sidoarjo</strong>).
                        </p>
                    </div>

                    <!-- Switch Toggle -->
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-0.5">
                        <input type="checkbox" x-model="form.adzan_mode_enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-[#D8CFC4] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-[#D8CFC4] after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5F7F42]"></div>
                    </label>
                </div>

                <div x-show="form.adzan_mode_enabled" x-transition class="space-y-4">
                    <!-- Slider Target Volume Adzan -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-mono font-bold text-[#5C4D3C] uppercase tracking-wider">Persentase Volume saat Adzan:</span>
                            <span class="font-mono font-bold px-2.5 py-0.5 rounded-full bg-[#5F7F42]/15 text-[#5F7F42] text-xs"
                                  x-text="form.adzan_target_volume === 0 ? '0% (Mute/Hening Total)' : (form.adzan_target_volume === 1 ? '1% (Super Hening)' : (form.adzan_target_volume === 2 ? '2% (Hening Sayup)' : form.adzan_target_volume + '%'))"></span>
                        </div>
                        <input type="range" min="0" max="30" step="1" x-model.number="form.adzan_target_volume"
                               @input="onAdzanVolumeInput()" @change="onAdzanVolumeChange()"
                               class="w-full accent-[#5F7F42] cursor-pointer h-2 bg-[#E4DCCC] rounded-lg">
                        <div class="flex justify-between text-[10px] text-[#A89A85] font-mono">
                            <span :class="form.adzan_target_volume == 0 ? 'text-[#5F7F42] font-bold' : ''">0% (Mute)</span>
                            <span :class="form.adzan_target_volume == 1 ? 'text-[#5F7F42] font-bold' : ''">1%</span>
                            <span :class="form.adzan_target_volume == 2 ? 'text-[#5F7F42] font-bold' : ''">2%</span>
                            <span :class="form.adzan_target_volume == 10 ? 'text-[#5F7F42] font-bold' : ''">10% (Rekomendasi)</span>
                            <span>20%</span>
                            <span>30%</span>
                        </div>

                        <!-- Opsi Cepat Persentase Volume Hening Adzan -->
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <span class="text-[10px] font-mono text-[#8A7B66] mr-1">Opsi Cepat:</span>
                            <button type="button" @click="onAdzanVolumeInput(0); onAdzanVolumeChange()"
                                    class="px-2 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                    :class="form.adzan_target_volume === 0 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                                0% (Mute)
                            </button>
                            <button type="button" @click="onAdzanVolumeInput(1); onAdzanVolumeChange()"
                                    class="px-2 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                    :class="form.adzan_target_volume === 1 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                                1% (Super Hening)
                            </button>
                            <button type="button" @click="onAdzanVolumeInput(2); onAdzanVolumeChange()"
                                    class="px-2.5 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                    :class="form.adzan_target_volume === 2 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                                2% (Hening Sayup)
                            </button>
                            <button type="button" @click="onAdzanVolumeInput(5); onAdzanVolumeChange()"
                                    class="px-2 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                    :class="form.adzan_target_volume === 5 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                                5%
                            </button>
                            <button type="button" @click="onAdzanVolumeInput(10); onAdzanVolumeChange()"
                                    class="px-2 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                    :class="form.adzan_target_volume === 10 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                                10% (Rekomendasi)
                            </button>
                            <button type="button" @click="onAdzanVolumeInput(20); onAdzanVolumeChange()"
                                    class="px-2 py-0.5 text-[10px] font-mono rounded-lg border transition cursor-pointer select-none active:scale-95"
                                    :class="form.adzan_target_volume === 20 ? 'bg-[#5F7F42] text-white border-[#5F7F42] font-bold shadow-xs' : 'bg-white border-[#E4DCCC] text-[#7A6A58] hover:border-[#5F7F42]/50'">
                                20%
                            </button>
                        </div>
                    </div>

                    <!-- Durasi Adzan -->
                    <div class="space-y-2 pt-2 border-t border-[#F2EDE4]">
                        <label class="block text-xs font-mono font-bold text-[#5C4D3C] uppercase tracking-wider">
                            Durasi Mode Adzan:
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button"
                                    @click="form.adzan_duration_minutes = 3"
                                    class="p-2.5 border-2 rounded-xl text-center cursor-pointer transition text-xs font-mono select-none active:scale-98"
                                    :class="form.adzan_duration_minutes == 3 ? 'border-[#5F7F42] bg-[#5F7F42]/10 font-bold text-[#1F1812]' : 'border-[#E4DCCC] hover:border-[#5F7F42]/50 text-[#7A6A58]'">
                                <div>3 Menit</div>
                                <div class="text-[9px] text-[#A89A85]">Singkat</div>
                            </button>
                            <button type="button"
                                    @click="form.adzan_duration_minutes = 5"
                                    class="p-2.5 border-2 rounded-xl text-center cursor-pointer transition text-xs font-mono select-none active:scale-98"
                                    :class="form.adzan_duration_minutes == 5 ? 'border-[#5F7F42] bg-[#5F7F42]/10 font-bold text-[#1F1812]' : 'border-[#E4DCCC] hover:border-[#5F7F42]/50 text-[#7A6A58]'">
                                <div>5 Menit</div>
                                <div class="text-[9px] text-[#5F7F42] font-bold">Cukup Adzan Saja</div>
                            </button>
                            <button type="button"
                                    @click="form.adzan_duration_minutes = 7"
                                    class="p-2.5 border-2 rounded-xl text-center cursor-pointer transition text-xs font-mono select-none active:scale-98"
                                    :class="form.adzan_duration_minutes == 7 ? 'border-[#5F7F42] bg-[#5F7F42]/10 font-bold text-[#1F1812]' : 'border-[#E4DCCC] hover:border-[#5F7F42]/50 text-[#7A6A58]'">
                                <div>7 Menit</div>
                                <div class="text-[9px] text-[#A89A85]">Adzan + Doa</div>
                            </button>
                        </div>
                    </div>

                    <!-- Jadwal Sholat Hari Ini Surabaya & Sidoarjo -->
                    <template x-if="prayerSchedule && prayerSchedule.schedule">
                        <div class="bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl p-3.5 space-y-2">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-mono font-bold text-[#1F1812] flex items-center gap-1.5">
                                    <span>📍</span>
                                    <span>Jadwal Sholat Surabaya & Sidoarjo</span>
                                </span>
                                <span class="font-mono text-[10px] text-[#5F7F42] font-bold" x-text="prayerSchedule.date_formatted || 'Hari Ini'"></span>
                            </div>

                            <div class="grid grid-cols-5 gap-1 text-center font-mono">
                                <div class="p-1.5 rounded-lg bg-white border border-[#E4DCCC]/80">
                                    <div class="text-[10px] text-[#8A7B66]">Subuh</div>
                                    <div class="text-xs font-bold text-[#1F1812] mt-0.5" x-text="prayerSchedule.schedule.subuh"></div>
                                </div>
                                <div class="p-1.5 rounded-lg bg-white border border-[#E4DCCC]/80">
                                    <div class="text-[10px] text-[#8A7B66]">Dzuhur</div>
                                    <div class="text-xs font-bold text-[#1F1812] mt-0.5" x-text="prayerSchedule.schedule.dzuhur"></div>
                                </div>
                                <div class="p-1.5 rounded-lg bg-white border border-[#E4DCCC]/80">
                                    <div class="text-[10px] text-[#8A7B66]">Ashar</div>
                                    <div class="text-xs font-bold text-[#1F1812] mt-0.5" x-text="prayerSchedule.schedule.ashar"></div>
                                </div>
                                <div class="p-1.5 rounded-lg bg-white border border-[#E4DCCC]/80">
                                    <div class="text-[10px] text-[#8A7B66]">Maghrib</div>
                                    <div class="text-xs font-bold text-[#1F1812] mt-0.5" x-text="prayerSchedule.schedule.maghrib"></div>
                                </div>
                                <div class="p-1.5 rounded-lg bg-white border border-[#E4DCCC]/80">
                                    <div class="text-[10px] text-[#8A7B66]">Isya</div>
                                    <div class="text-xs font-bold text-[#1F1812] mt-0.5" x-text="prayerSchedule.schedule.isya"></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Tombol Trigger Manual & Uji Coba Mode Adzan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                        <button type="button"
                                @click="toggleManualAdzan()"
                                class="py-2.5 px-4 font-mono text-xs font-bold rounded-xl transition flex items-center justify-center gap-2 cursor-pointer border shadow-sm active:scale-98"
                                :class="isManualAdzanActive
                                    ? 'bg-[#5F7F42] border-[#85BF5C] text-white animate-pulse shadow-[0_0_12px_rgba(95,127,66,0.5)]'
                                    : 'bg-[#5F7F42]/10 hover:bg-[#5F7F42]/20 border-[#5F7F42]/40 text-[#3C5726]'">
                            <span>🕌</span>
                            <span x-text="isManualAdzanActive ? 'Matikan Mode Adzan ✕' : 'Nyalakan Mode Adzan Manual ↗'"></span>
                        </button>
                        <button type="button"
                                @click="testAdzanMode()"
                                :disabled="isTestingAdzan"
                                class="py-2.5 px-4 bg-[#1F1812]/5 hover:bg-[#1F1812]/10 border border-[#E4DCCC] text-[#5C4D3C] font-mono text-xs font-bold rounded-xl transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 active:scale-98">
                            <span x-show="!isTestingAdzan">⏱ Uji Coba Singkat (8 Detik)</span>
                            <span x-show="isTestingAdzan" class="animate-spin">⟳</span>
                            <span x-show="isTestingAdzan">Menguji Coba (8 Detik)...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- BAGIAN 6: JEDA OTOMATIS SAAT TUTUP TOKO (AUTO-PAUSE 00:00 WIB) -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                <div class="border-b border-[#E4DCCC] pb-3.5 flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🌙</span>
                            <h2 class="text-lg font-serif font-bold text-[#1F1812]">
                                6. Jeda Otomatis Jam Tutup Toko
                            </h2>
                        </div>
                        <p class="text-xs text-[#7A6A58] mt-1">
                            Otomatis menjeda pemutar musik saat jam operasional toko berakhir agar lagu tidak terus berjalan semalaman jika kasir lupa mematikan musik.
                        </p>
                    </div>

                    <!-- Switch Toggle -->
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-0.5">
                        <input type="checkbox" x-model="form.auto_pause_midnight" class="sr-only peer">
                        <div class="w-11 h-6 bg-[#D8CFC4] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-[#D8CFC4] after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5F7F42]"></div>
                    </label>
                </div>

                <div x-show="form.auto_pause_midnight" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Jam Tutup Kafe -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-mono font-bold text-[#5C4D3C] uppercase tracking-wider">
                                Jam Tutup Toko (Auto-Pause):
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="time" x-model="form.closing_time"
                                       class="w-full px-3.5 py-2.5 bg-[#FAF7F2] border border-[#E4DCCC] text-xs text-[#1F1812] font-mono rounded-xl focus:outline-none focus:border-[#D9973E] font-bold shadow-inner">
                            </div>
                            <p class="text-[10px] text-[#A89A85] font-mono">
                                Default: <strong>00:00 WIB</strong> (tengah malam).
                            </p>
                        </div>

                        <!-- Jam Buka Kembali Kafe -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-mono font-bold text-[#5C4D3C] uppercase tracking-wider">
                                Jam Buka Kembali:
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="time" x-model="form.reopen_time"
                                       class="w-full px-3.5 py-2.5 bg-[#FAF7F2] border border-[#E4DCCC] text-xs text-[#1F1812] font-mono rounded-xl focus:outline-none focus:border-[#D9973E] font-bold shadow-inner">
                            </div>
                            <p class="text-[10px] text-[#A89A85] font-mono">
                                Default: <strong>06:00 WIB</strong> (pagi hari).
                            </p>
                        </div>
                    </div>

                    <div class="p-3.5 bg-[#FAF7F2] border border-[#E4DCCC] rounded-xl text-xs space-y-1.5 text-[#7A6A58]">
                        <div class="flex items-center gap-2 text-[#1F1812] font-mono font-bold text-[11px]">
                            <span class="text-sm">🛡️</span>
                            <span>Pengamanan Otomatis Aktif:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-[11px] text-[#5C4D3C]">
                            <li>Tepat pada jam tutup, pemutar audio akan otomatis di-<strong>Pause</strong> dan status jeda dikirim ke Display TV.</li>
                            <li>Lagu antrean yang berakhir pada rentang jam tutup <strong>tidak akan memutar lagu berikutnya secara otomatis</strong>.</li>
                            <li>Jika kasir menutup tab browser, status server langsung disetel ke <strong>Pause</strong> untuk keamanan.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- TOMBOL SIMPAN AKSI -->
            <div class="pt-1">
                <button type="button"
                        @click="saveSettings()"
                        :disabled="saving"
                        class="w-full py-3.5 bg-[#1F1812] hover:bg-[#D9973E] text-[#F7F3EC] hover:text-[#1F1812] font-mono text-xs uppercase tracking-widest font-extrabold rounded-xl transition-all shadow-lg flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 active:scale-98">
                    <span x-show="saving" class="animate-spin text-sm">⟳</span>
                    <span x-show="!saving">✓ Simpan Pengaturan Announcer ›</span>
                </button>
            </div>

        </div>

    </div>

</div>

<script>
function announcerSettingsManager(initialSettings) {
    return {
        form: {
            voice_model: initialSettings.voice_model || 'mbak_google',
            device_voice_name: initialSettings.device_voice_name || '',
            template_type: initialSettings.template_type || 'concise',
            custom_template: initialSettings.custom_template || 'Pesanan Kak {name}, siap diambil di kasir.',
            chime_style: initialSettings.chime_style || 'ding_dong',
            rate: parseFloat(initialSettings.rate ?? 1.0),
            pitch: parseFloat(initialSettings.pitch ?? 1.05),
            duck_volume: initialSettings.duck_volume !== undefined ? parseInt(initialSettings.duck_volume) : 12,
            adzan_mode_enabled: initialSettings.adzan_mode_enabled !== undefined ? !!initialSettings.adzan_mode_enabled : true,
            adzan_target_volume: parseInt(initialSettings.adzan_target_volume ?? 10),
            adzan_duration_minutes: parseInt(initialSettings.adzan_duration_minutes ?? 5),
            auto_pause_midnight: initialSettings.auto_pause_midnight !== undefined ? !!initialSettings.auto_pause_midnight : true,
            closing_time: initialSettings.closing_time || '00:00',
            reopen_time: initialSettings.reopen_time || '06:00',
        },
        sampleName: 'Budi',
        sampleCode: '42',
        availableVoices: [],
        isPlayingSample: false,
        saving: false,
        autoSaveStatus: '',
        _autoSaveTimer: null,
        _initialized: false,
        prayerSchedule: @js($prayerSchedule ?? null),
        isTestingAdzan: false,
        isManualAdzanActive: false,

        testAdzanMode() {
            if (this.isTestingAdzan) return;
            this.isTestingAdzan = true;

            const targetVol = Math.max(0, Math.min(100, parseInt(this.form.adzan_target_volume ?? 10)));

            if (window.customToast) {
                window.customToast({
                    message: '🕌 [UJI COBA] Memasuki Waktu Adzan Maghrib (Surabaya & Sidoarjo). Volume musik otomatis diturunkan ke ' + targetVol + '%...',
                    type: 'info',
                    duration: 7000
                });
            }

            // Simpan lokal & broadcast segera ke tab host pemutar musik
            try {
                localStorage.setItem('pos_soundstation_announcer_settings', JSON.stringify(this.form));
                if (typeof BroadcastChannel !== 'undefined') {
                    const bc = new BroadcastChannel('cafe_soundstation_sync');
                    bc.postMessage({
                        type: 'ANNOUNCER_SETTINGS_UPDATED',
                        settings: this.form
                    });
                    bc.close();
                }
            } catch (e) {}

            if (window.SoundStationHub && typeof window.SoundStationHub.sendCommand === 'function') {
                window.SoundStationHub.sendCommand('TEST_ADZAN_MODE', { target_volume: targetVol });
            } else if (window.SoundStation && typeof window.SoundStation.triggerTestAdzanMode === 'function') {
                window.SoundStation.triggerTestAdzanMode(targetVol);
            }

            setTimeout(() => {
                this.isTestingAdzan = false;
            }, 8500);
        },

        init() {
            // Sinkronkan localStorage dari data server terkini (server adalah single source of truth)
            try {
                localStorage.setItem('pos_soundstation_announcer_settings', JSON.stringify(this.form));
            } catch (e) {}

            this.loadBrowserVoices();
            if ('speechSynthesis' in window && window.speechSynthesis.onvoiceschanged !== undefined) {
                window.speechSynthesis.onvoiceschanged = () => {
                    this.loadBrowserVoices();
                };
            }

            // Dengarkan sinkronisasi status adzan dari host pemutar
            if (typeof BroadcastChannel !== 'undefined') {
                try {
                    const bc = new BroadcastChannel('cafe_soundstation_sync');
                    bc.onmessage = (e) => {
                        if (e.data && e.data.type === 'ADZAN_MODE_STARTED') {
                            this.isManualAdzanActive = true;
                        } else if (e.data && e.data.type === 'ADZAN_MODE_ENDED') {
                            this.isManualAdzanActive = false;
                        }
                    };
                } catch (e) {}
            }

            // Watch perubahan form untuk Auto-Save otomatis (deep watch)
            this.$nextTick(() => {
                this._initialized = true;
                this.$watch('form', () => {
                    if (this._initialized) {
                        this.triggerAutoSave();
                    }
                }, { deep: true });
            });

            // Pastikan data tersimpan instan ke server & localStorage saat meninggalkan halaman
            window.addEventListener('beforeunload', () => {
                try {
                    if (this._autoSaveTimer) {
                        clearTimeout(this._autoSaveTimer);
                        this._autoSaveTimer = null;
                    }
                    if (this.form.closing_time && this.form.closing_time.length > 5) {
                        this.form.closing_time = this.form.closing_time.substring(0, 5);
                    }
                    if (this.form.reopen_time && this.form.reopen_time.length > 5) {
                        this.form.reopen_time = this.form.reopen_time.substring(0, 5);
                    }
                    this.form.duck_volume = parseInt(this.form.duck_volume ?? 12);
                    this.form.adzan_target_volume = parseInt(this.form.adzan_target_volume ?? 10);
                    localStorage.setItem('pos_soundstation_announcer_settings', JSON.stringify(this.form));
                    fetch('{{ route('kasir.announcer.save') }}', {
                        method: 'POST',
                        keepalive: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ...this.form,
                            _token: '{{ csrf_token() }}'
                        })
                    });
                } catch (e) {}
            });
        },

        onDuckVolumeInput(val = null) {
            if (val !== null && typeof val !== 'undefined') {
                this.form.duck_volume = parseInt(val);
            }
            const duckVol = Math.max(0, Math.min(50, parseInt(this.form.duck_volume ?? 12)));
            this.form.duck_volume = duckVol;
            this.triggerAutoSave();

            // Broadcast penyesuaian volume ducking instan (real-time) ke pemutar musik aktif
            if (window.SoundStationHub && typeof window.SoundStationHub.sendCommand === 'function') {
                window.SoundStationHub.sendCommand('SET_DUCK_VOLUME', { duck_volume: duckVol });
            }
        },

        onDuckVolumeChange() {
            if (this._autoSaveTimer) {
                clearTimeout(this._autoSaveTimer);
                this._autoSaveTimer = null;
            }
            this.executeServerSave(false);
        },

        onAdzanVolumeInput(val = null) {
            if (val !== null && typeof val !== 'undefined') {
                this.form.adzan_target_volume = parseInt(val);
            }
            const targetVol = Math.max(0, Math.min(100, parseInt(this.form.adzan_target_volume ?? 10)));
            this.form.adzan_target_volume = targetVol;
            this.triggerAutoSave();

            // Broadcast penyesuaian volume instan (real-time) ke pemutar musik aktif
            if (window.SoundStationHub && typeof window.SoundStationHub.sendCommand === 'function') {
                window.SoundStationHub.sendCommand('SET_ADZAN_VOLUME', { target_volume: targetVol });
            } else if (window.SoundStation && typeof window.SoundStation.setLiveAdzanVolume === 'function') {
                window.SoundStation.setLiveAdzanVolume(targetVol);
            }
        },

        onAdzanVolumeChange() {
            if (this._autoSaveTimer) {
                clearTimeout(this._autoSaveTimer);
                this._autoSaveTimer = null;
            }
            this.executeServerSave(false);
        },

        triggerAutoSave() {
            // 1. Langsung simpan di localStorage & broadcast ke tab lain
            try {
                localStorage.setItem('pos_soundstation_announcer_settings', JSON.stringify(this.form));
                if (typeof BroadcastChannel !== 'undefined') {
                    const bc = new BroadcastChannel('cafe_soundstation_sync');
                    bc.postMessage({
                        type: 'ANNOUNCER_SETTINGS_UPDATED',
                        settings: this.form
                    });
                    bc.close();
                }
            } catch (e) {}

            this.autoSaveStatus = 'saving';

            if (this._autoSaveTimer) {
                clearTimeout(this._autoSaveTimer);
            }

            // 2. Debounce penyimpanan ke server (350ms)
            this._autoSaveTimer = setTimeout(() => {
                this.executeServerSave(false);
            }, 350);
        },

        async executeServerSave(isManual = false) {
            if (isManual) this.saving = true;
            try {
                // Normalisasi string waktu menjadi format HH:mm jika memiliki detik
                if (this.form.closing_time && this.form.closing_time.length > 5) {
                    this.form.closing_time = this.form.closing_time.substring(0, 5);
                }
                if (this.form.reopen_time && this.form.reopen_time.length > 5) {
                    this.form.reopen_time = this.form.reopen_time.substring(0, 5);
                }

                this.form.duck_volume = parseInt(this.form.duck_volume ?? 12);
                this.form.adzan_target_volume = parseInt(this.form.adzan_target_volume ?? 10);
                this.form.adzan_duration_minutes = parseInt(this.form.adzan_duration_minutes ?? 5);

                const payload = {
                    ...this.form,
                    _token: '{{ csrf_token() }}'
                };

                const res = await fetch('{{ route('kasir.announcer.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (res.ok) {
                    const data = await res.json().catch(() => ({}));
                    if (data && data.settings) {
                        // Sinkronkan state lokal dengan data yang terkonfirmasi tersimpan di server
                        Object.keys(data.settings).forEach(k => {
                            if (typeof this.form[k] !== 'undefined') {
                                this.form[k] = data.settings[k];
                            }
                        });
                        try {
                            localStorage.setItem('pos_soundstation_announcer_settings', JSON.stringify(this.form));
                        } catch (e) {}
                    }

                    this.autoSaveStatus = 'saved';
                    setTimeout(() => {
                        if (this.autoSaveStatus === 'saved') this.autoSaveStatus = '';
                    }, 2500);

                    if (isManual && window.customToast) {
                        window.customToast({
                            message: '✓ Pengaturan suara announcer & adzan berhasil disimpan ke server!',
                            type: 'success',
                            duration: 3500
                        });
                    }
                } else {
                    this.autoSaveStatus = 'error';
                    const data = await res.json().catch(() => ({}));
                    const msg = data.message || 'Gagal menyimpan pengaturan ke server.';
                    if (isManual && window.customToast) {
                        window.customToast({
                            message: '✕ ' + msg,
                            type: 'error',
                            duration: 4000
                        });
                    }
                }
            } catch (e) {
                this.autoSaveStatus = 'error';
            } finally {
                if (isManual) this.saving = false;
            }
        },

        async saveSettings() {
            await this.executeServerSave(true);
        },

        toggleManualAdzan() {
            this.isManualAdzanActive = !this.isManualAdzanActive;
            const action = this.isManualAdzanActive ? 'start' : 'stop';
            const targetVol = Math.max(0, Math.min(100, parseInt(this.form.adzan_target_volume ?? 10)));

            // Segera simpan dan broadcast pengaturan terbaru
            this.triggerAutoSave();

            if (window.SoundStationHub && typeof window.SoundStationHub.sendCommand === 'function') {
                window.SoundStationHub.sendCommand('TOGGLE_MANUAL_ADZAN', {
                    action: action,
                    target_volume: targetVol,
                    duration_minutes: this.form.adzan_duration_minutes
                });
            } else if (window.SoundStation && typeof window.SoundStation.toggleManualAdzanMode === 'function') {
                window.SoundStation.toggleManualAdzanMode(action, targetVol);
            }
        },

        loadBrowserVoices() {
            if ('speechSynthesis' in window) {
                this.availableVoices = window.speechSynthesis.getVoices() || [];
            }
        },

        generateSampleText() {
            const name = this.sampleName || 'Pelanggan';
            const code = this.sampleCode || '01';

            if (this.form.template_type === 'concise') {
                return `Pesanan Kak ${name}, siap diambil di kasir.`;
            } else if (this.form.template_type === 'formal') {
                return `Panggilan untuk Kak ${name}, pesanan nomor ${code} sudah siap. Silakan ambil di kasir.`;
            } else if (this.form.template_type === 'airport') {
                return `Perhatian, pesanan nomor ${code} atas nama Kak ${name}, siap diambil di meja kasir. Terima kasih.`;
            } else if (this.form.template_type === 'english') {
                return `Order for ${name}, ready for pickup at the counter.`;
            } else if (this.form.template_type === 'custom') {
                return (this.form.custom_template || 'Pesanan Kak {name}, siap diambil di kasir.')
                    .replace(/\{name\}/gi, name)
                    .replace(/\{code\}/gi, code);
            }
            return `Pesanan Kak ${name}, siap diambil di kasir.`;
        },

        playChime(style) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const now = ctx.currentTime;

                if (style === 'ding_dong') {
                    const osc1 = ctx.createOscillator();
                    const gain1 = ctx.createGain();
                    osc1.type = 'sine';
                    osc1.frequency.setValueAtTime(659.25, now); // E5
                    gain1.gain.setValueAtTime(0.35, now);
                    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                    osc1.connect(gain1);
                    gain1.connect(ctx.destination);
                    osc1.start(now);
                    osc1.stop(now + 0.55);

                    const osc2 = ctx.createOscillator();
                    const gain2 = ctx.createGain();
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(523.25, now + 0.22); // C5
                    gain2.gain.setValueAtTime(0.35, now + 0.22);
                    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.85);
                    osc2.connect(gain2);
                    gain2.connect(ctx.destination);
                    osc2.start(now + 0.22);
                    osc2.stop(now + 0.9);
                } else if (style === 'airport') {
                    // 3-Tone Airport Chime: F4 (349Hz) -> A4 (440Hz) -> C5 (523Hz)
                    const tones = [349.23, 440.00, 523.25];
                    tones.forEach((freq, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        const t = now + (i * 0.18);
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, t);
                        gain.gain.setValueAtTime(0.3, t);
                        gain.gain.exponentialRampToValueAtTime(0.001, t + 0.45);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(t);
                        osc.stop(t + 0.5);
                    });
                } else if (style === 'bell') {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, now); // D5 bell
                    gain.gain.setValueAtTime(0.4, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.85);
                }
            } catch (e) {
                console.warn('Web Audio error:', e);
            }
        },

        getAudioUrl() {
            let lang = 'id';
            let text = this.generateSampleText();

            if (this.form.voice_model === 'english_cafe') {
                lang = 'en';
                if (!/order for|ready at the counter/i.test(text)) {
                    text = `Order for ${this.sampleName || 'customer'}, ready for pickup at the counter.`;
                }
            } else if (this.form.voice_model === 'google_local') {
                lang = 'jv'; // Aksen medok lokal nusantara
            } else {
                lang = 'id'; // Mbak Google Indonesia
            }

            return window.location.origin + '/music/tts?lang=' + lang + '&text=' + encodeURIComponent(text);
        },

        testAnnouncement() {
            if (this.isPlayingSample) return;
            this.isPlayingSample = true;

            const text = this.generateSampleText();

            // Opsi 6: Suara terpasang di komputer (Web Speech API)
            if (this.form.voice_model === 'device_voice') {
                if (this.form.chime_style !== 'none') {
                    this.playChime(this.form.chime_style);
                    setTimeout(() => {
                        this.playWebSpeechDevice(text, this.form.device_voice_name, this.form.rate, this.form.pitch, () => {
                            this.isPlayingSample = false;
                        });
                    }, 1000);
                } else {
                    this.playWebSpeechDevice(text, this.form.device_voice_name, this.form.rate, this.form.pitch, () => {
                        this.isPlayingSample = false;
                    });
                }
                return;
            }

            // Opsi 1 - 5: Google TTS MP3 streaming
            const ttsUrl = this.getAudioUrl();

            // Tentukan kecepatan putar
            let playRate = parseFloat(this.form.rate) || 1.0;
            if (this.form.voice_model === 'ms_gadis') {
                playRate = 0.9; // Tempo santai
            } else if (this.form.voice_model === 'ms_ardi') {
                playRate = 1.15; // Tempo cepat
            }

            let audio = document.getElementById('announcer-preview-audio');
            if (!audio) {
                audio = new Audio();
            }

            audio.src = ttsUrl;
            audio.playbackRate = playRate;

            const finish = () => {
                this.isPlayingSample = false;
            };

            audio.onended = finish;
            audio.onerror = (e) => {
                console.error('[Announcer] Gagal memutar Google TTS:', e);
                finish();
                alert('Audio Google TTS tidak dapat dimuat. Pastikan server terhubung ke internet.');
            };

            if (this.form.chime_style !== 'none') {
                this.playChime(this.form.chime_style);
                setTimeout(() => {
                    const playPromise = audio.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(err => {
                            console.warn('[Announcer] Audio play blocked:', err);
                            finish();
                        });
                    }
                }, 900);
            } else {
                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.catch(err => {
                        console.warn('[Announcer] Audio play blocked:', err);
                        finish();
                    });
                }
            }
        },

        playWebSpeechDevice(text, deviceVoiceName, rate, pitch, callback) {
            if (!('speechSynthesis' in window)) {
                if (callback) callback();
                return;
            }

            window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(text);
            utter.rate = parseFloat(rate) || 1.0;
            utter.pitch = parseFloat(pitch) || 1.0;

            const voices = window.speechSynthesis.getVoices() || [];
            if (deviceVoiceName) {
                const v = voices.find(x => x.name === deviceVoiceName);
                if (v) utter.voice = v;
            }

            utter.onend = () => { if (callback) callback(); };
            utter.onerror = () => { if (callback) callback(); };
            window.speechSynthesis.speak(utter);
        }
    };
}
</script>
@endsection
