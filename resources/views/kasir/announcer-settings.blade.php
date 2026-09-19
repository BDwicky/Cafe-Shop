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

    <!-- 3. GRID UTAMA PENGATURAN (2 KOLOM: MODEL SUARA & FORMAT/CHIME) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- KOLOM KIRI: PILIHAN MODEL SUARA & AKSEN (lg:col-span-7) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- BAGIAN 1: MODEL SUARA UTAMA -->
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

            <!-- BAGIAN 2: PENGATURAN TEMPO & PITCH SUARA -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                <div class="border-b border-[#E4DCCC] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🎚️</span>
                        <h2 class="text-lg font-serif font-bold text-[#1F1812]">
                            2. Kecepatan & Nada Bicara
                        </h2>
                    </div>
                    <p class="text-xs text-[#7A6A58] mt-0.5">
                        Sesuaikan tempo bicara agar tidak tergesa-gesa dan nyaman didengar pengunjung kafe.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
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
                            <span>Berat / Rendah (0.8)</span>
                            <span>Natural (1.0)</span>
                            <span>Ceria / Tinggi (1.2)</span>
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
                        <span class="font-mono font-bold px-2.5 py-0.5 rounded-full bg-[#5F7F42]/15 text-[#5F7F42] text-xs" x-text="form.duck_volume + '%'"></span>
                    </div>
                    <input type="range" min="5" max="35" step="1" x-model="form.duck_volume"
                           class="w-full accent-[#D9973E] cursor-pointer h-2 bg-[#E4DCCC] rounded-lg">
                </div>
            </div>

        </div>

        <!-- KOLOM KANAN: FORMAT KALIMAT & NADA DERING (lg:col-span-5) -->
        <div class="lg:col-span-5 space-y-6">

            <!-- BAGIAN 3: TEMPLATE KALIMAT PANGGILAN -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                <div class="border-b border-[#E4DCCC] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">💬</span>
                        <h2 class="text-lg font-serif font-bold text-[#1F1812]">
                            3. Format Kalimat Panggilan
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

            <!-- BAGIAN 4: GAYA NADA DERING (CHIME BELL) -->
            <div class="bg-white border border-[#E4DCCC] rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                <div class="border-b border-[#E4DCCC] pb-3 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🔔</span>
                            <h2 class="text-lg font-serif font-bold text-[#1F1812]">
                                4. Nada Dering Bel (Chime)
                            </h2>
                        </div>
                        <p class="text-xs text-[#7A6A58] mt-0.5">
                            Bunyi lonceng lembut sebelum pengumuman dimulai.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <!-- Chime 1: Ding Dong Kafe -->
                    <div class="p-3.5 border-2 rounded-xl flex items-center justify-between transition-all cursor-pointer shadow-2xs"
                         :class="form.chime_style === 'ding_dong' ? 'border-[#D9973E] bg-[#D9973E]/10' : 'border-[#E4DCCC] hover:border-[#D9973E]/60 bg-[#FAF7F2]/40'"
                         @click="form.chime_style = 'ding_dong'">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="chime_style" value="ding_dong" x-model="form.chime_style" class="accent-[#D9973E]">
                            <span class="font-serif font-bold text-xs text-[#1F1812]">Ding-Dong Kafe</span>
                        </div>
                        <button type="button" @click.stop="playChime('ding_dong')" title="Dengarkan Nada"
                                class="text-xs text-[#D9973E] hover:text-[#1F1812] font-bold p-1">🔊</button>
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
                                class="text-xs text-[#D9973E] hover:text-[#1F1812] font-bold p-1">🔊</button>
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
                                class="text-xs text-[#D9973E] hover:text-[#1F1812] font-bold p-1">🔊</button>
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
                                  x-text="form.adzan_target_volume === 0 ? '0% (Mute/Hening Total)' : form.adzan_target_volume + '%'"></span>
                        </div>
                        <input type="range" min="0" max="30" step="5" x-model="form.adzan_target_volume"
                               class="w-full accent-[#5F7F42] cursor-pointer h-2 bg-[#E4DCCC] rounded-lg">
                        <div class="flex justify-between text-[10px] text-[#A89A85] font-mono">
                            <span>0% (Hening)</span>
                            <span class="text-[#5F7F42] font-bold">10% (Rekomendasi)</span>
                            <span>20% (Latar Pelan)</span>
                            <span>30%</span>
                        </div>
                    </div>

                    <!-- Durasi Adzan -->
                    <div class="space-y-2 pt-2 border-t border-[#F2EDE4]">
                        <label class="block text-xs font-mono font-bold text-[#5C4D3C] uppercase tracking-wider">
                            Durasi Mode Adzan:
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="p-2.5 border-2 rounded-xl text-center cursor-pointer transition text-xs font-mono"
                                   :class="form.adzan_duration_minutes == 3 ? 'border-[#5F7F42] bg-[#5F7F42]/10 font-bold text-[#1F1812]' : 'border-[#E4DCCC] hover:border-[#5F7F42]/50 text-[#7A6A58]'">
                                <input type="radio" value="3" x-model="form.adzan_duration_minutes" class="sr-only">
                                <div>3 Menit</div>
                                <div class="text-[9px] text-[#A89A85]">Singkat</div>
                            </label>
                            <label class="p-2.5 border-2 rounded-xl text-center cursor-pointer transition text-xs font-mono"
                                   :class="form.adzan_duration_minutes == 5 ? 'border-[#5F7F42] bg-[#5F7F42]/10 font-bold text-[#1F1812]' : 'border-[#E4DCCC] hover:border-[#5F7F42]/50 text-[#7A6A58]'">
                                <input type="radio" value="5" x-model="form.adzan_duration_minutes" class="sr-only">
                                <div>5 Menit</div>
                                <div class="text-[9px] text-[#5F7F42] font-bold">Cukup Adzan Saja</div>
                            </label>
                            <label class="p-2.5 border-2 rounded-xl text-center cursor-pointer transition text-xs font-mono"
                                   :class="form.adzan_duration_minutes == 7 ? 'border-[#5F7F42] bg-[#5F7F42]/10 font-bold text-[#1F1812]' : 'border-[#E4DCCC] hover:border-[#5F7F42]/50 text-[#7A6A58]'">
                                <input type="radio" value="7" x-model="form.adzan_duration_minutes" class="sr-only">
                                <div>7 Menit</div>
                                <div class="text-[9px] text-[#A89A85]">Adzan + Doa</div>
                            </label>
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

            <!-- TOMBOL SIMPAN AKSI -->
            <div class="pt-2">
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
            rate: parseFloat(initialSettings.rate || 1.0),
            pitch: parseFloat(initialSettings.pitch || 1.05),
            duck_volume: parseInt(initialSettings.duck_volume || 12),
            adzan_mode_enabled: initialSettings.adzan_mode_enabled !== undefined ? !!initialSettings.adzan_mode_enabled : true,
            adzan_target_volume: parseInt(initialSettings.adzan_target_volume ?? 10),
            adzan_duration_minutes: parseInt(initialSettings.adzan_duration_minutes ?? 5),
        },
        sampleName: 'Budi',
        sampleCode: '42',
        availableVoices: [],
        isPlayingSample: false,
        saving: false,
        prayerSchedule: @js($prayerSchedule ?? null),
        isTestingAdzan: false,
        isManualAdzanActive: false,

        testAdzanMode() {
            if (this.isTestingAdzan) return;
            this.isTestingAdzan = true;

            if (window.customToast) {
                window.customToast({
                    message: '🕌 [UJI COBA] Memasuki Waktu Adzan Maghrib (Surabaya & Sidoarjo). Volume musik otomatis diturunkan ke ' + this.form.adzan_target_volume + '%...',
                    type: 'info',
                    duration: 7000
                });
            }

            if (window.SoundStationHub && typeof window.SoundStationHub.sendCommand === 'function') {
                window.SoundStationHub.sendCommand('TEST_ADZAN_MODE');
            } else if (window.SoundStation && typeof window.SoundStation.triggerTestAdzanMode === 'function') {
                window.SoundStation.triggerTestAdzanMode();
            }

            setTimeout(() => {
                this.isTestingAdzan = false;
            }, 8500);
        },

        init() {
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
        },

        toggleManualAdzan() {
            this.isManualAdzanActive = !this.isManualAdzanActive;
            const action = this.isManualAdzanActive ? 'start' : 'stop';

            if (window.SoundStationHub && typeof window.SoundStationHub.sendCommand === 'function') {
                window.SoundStationHub.sendCommand('TOGGLE_MANUAL_ADZAN', { action: action });
            } else if (window.SoundStation && typeof window.SoundStation.toggleManualAdzanMode === 'function') {
                window.SoundStation.toggleManualAdzanMode(action);
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
        },

        async saveSettings() {
            this.saving = true;
            try {
                const res = await fetch('{{ route('kasir.announcer.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                if (res.ok) {
                    const data = await res.json();
                    try {
                        localStorage.setItem('pos_soundstation_announcer_settings', JSON.stringify(this.form));
                        // Broadcast ke tab lain
                        if (window.SoundStationHub && typeof window.SoundStationHub.broadcast === 'function') {
                            window.SoundStationHub.broadcast('announcer_settings_updated', this.form);
                        }
                    } catch (e) {}

                    if (window.customToast) {
                        window.customToast({
                            message: '✓ Pengaturan suara announcer berhasil disimpan!',
                            type: 'success',
                            duration: 3500
                        });
                    }
                } else {
                    alert('Gagal menyimpan pengaturan. Silakan coba lagi.');
                }
            } catch (e) {
                alert('Terjadi kesalahan koneksi.');
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>
@endsection
