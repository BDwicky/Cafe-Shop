{{-- COMPONENT CUSTOM CONFIRMATION & ALERT MODAL + TOAST NOTIFICATIONS --}}
<div x-data="{
        // Modal Confirm State
        confirmOpen: false,
        confirmTitle: 'Konfirmasi',
        confirmMessage: '',
        confirmType: 'warning', // 'warning', 'danger', 'info'
        confirmBtnText: 'Ya',
        cancelBtnText: 'Batal',
        confirmResolve: null,

        // Modal Alert State
        alertOpen: false,
        alertTitle: 'Info',
        alertMessage: '',
        alertType: 'info', // 'success', 'error', 'info', 'warning'
        alertBtnText: 'OK',
        alertResolve: null,

        // Toast Notifications
        toasts: [],

        init() {
            // Listeners untuk custom confirm
            window.addEventListener('open-custom-confirm', (e) => {
                this.confirmTitle = e.detail.title || 'Konfirmasi';
                this.confirmMessage = e.detail.message || '';
                this.confirmType = e.detail.type || 'warning';
                this.confirmBtnText = e.detail.confirmText || 'Ya';
                this.cancelBtnText = e.detail.cancelText || 'Batal';
                this.confirmResolve = e.detail.resolve;
                this.confirmOpen = true;
            });

            // Listeners untuk custom alert
            window.addEventListener('open-custom-alert', (e) => {
                this.alertTitle = e.detail.title || 'Info';
                this.alertMessage = e.detail.message || '';
                this.alertType = e.detail.type || 'info';
                this.alertBtnText = e.detail.btnText || 'OK';
                this.alertResolve = e.detail.resolve;
                this.alertOpen = true;
            });

            // Listeners untuk custom toast
            window.addEventListener('show-custom-toast', (e) => {
                const id = Date.now() + Math.random();
                const toast = {
                    id: id,
                    message: e.detail.message || '',
                    type: e.detail.type || 'info'
                };
                this.toasts.push(toast);
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, e.detail.duration || 2000);
            });
        },

        handleConfirm(result) {
            this.confirmOpen = false;
            if (this.confirmResolve) {
                this.confirmResolve(result);
                this.confirmResolve = null;
            }
        },

        handleAlertClose() {
            this.alertOpen = false;
            if (this.alertResolve) {
                this.alertResolve();
                this.alertResolve = null;
            }
        }
     }"
     class="relative z-50">

    <!-- GLOBAL CONFIRMATION MODAL -->
    <div x-show="confirmOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/70 backdrop-blur-xs flex items-center justify-center p-3 z-[9999]"
         style="display: none;"
         @keydown.escape.window="handleConfirm(false)">

        <div @click.outside="handleConfirm(false)"
             x-show="confirmOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-[320px] bg-[#1F1812] border shadow-2xl p-4 relative select-none text-[#F7F3EC]"
             :class="{
                'border-red-500/70': confirmType === 'danger',
                'border-[#D9973E]': confirmType === 'warning',
                'border-[#3A3026]': confirmType === 'info'
             }">

            <div class="flex items-start gap-3">
                <!-- ICON BADGE -->
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold border"
                     :class="{
                        'bg-red-500/15 border-red-500/40 text-red-400': confirmType === 'danger',
                        'bg-[#D9973E]/15 border-[#D9973E]/40 text-[#D9973E]': confirmType === 'warning',
                        'bg-blue-500/15 border-blue-500/40 text-blue-400': confirmType === 'info'
                     }">
                    <template x-if="confirmType === 'danger'"><span>!</span></template>
                    <template x-if="confirmType === 'warning'"><span>?</span></template>
                    <template x-if="confirmType === 'info'"><span>i</span></template>
                </div>

                <!-- TITLE & MESSAGE -->
                <div class="min-w-0 flex-1">
                    <h3 class="font-bold text-sm text-[#F7F3EC] leading-tight" x-text="confirmTitle"></h3>
                    <p class="mt-1 text-xs text-[#C4B6A3] leading-snug" x-text="confirmMessage"></p>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="mt-3.5 pt-2.5 border-t border-[#3A3026] flex items-center justify-end gap-2">
                <button type="button"
                        @click="handleConfirm(false)"
                        class="px-3 py-1.5 bg-[#2A211A] hover:bg-[#3A3026] border border-[#3A3026] text-[11px] font-mono uppercase tracking-wider text-[#A89A85] hover:text-[#F7F3EC] transition">
                    <span x-text="cancelBtnText"></span>
                </button>
                <button type="button"
                        @click="handleConfirm(true)"
                        class="px-3.5 py-1.5 font-mono text-[11px] uppercase tracking-wider font-bold transition shadow flex items-center gap-1"
                        :class="{
                            'bg-red-600 hover:bg-red-700 text-white': confirmType === 'danger',
                            'bg-[#D9973E] hover:bg-[#c4842e] text-[#1F1812]': confirmType === 'warning',
                            'bg-[#F7F3EC] hover:bg-white text-[#1F1812]': confirmType === 'info'
                        }">
                    <span x-text="confirmBtnText"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- GLOBAL ALERT MODAL -->
    <div x-show="alertOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/70 backdrop-blur-xs flex items-center justify-center p-3 z-[9999]"
         style="display: none;"
         @keydown.escape.window="handleAlertClose()">

        <div @click.outside="handleAlertClose()"
             x-show="alertOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-[320px] bg-[#1F1812] border shadow-2xl p-4 relative select-none text-[#F7F3EC]"
             :class="{
                'border-[#5F7F42]': alertType === 'success',
                'border-red-500': alertType === 'error',
                'border-[#D9973E]': alertType === 'warning',
                'border-[#3A3026]': alertType === 'info'
             }">

            <div class="flex items-start gap-3">
                <!-- ICON BADGE -->
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold border"
                     :class="{
                        'bg-[#5F7F42]/15 border-[#5F7F42]/40 text-[#5F7F42]': alertType === 'success',
                        'bg-red-500/15 border-red-500/40 text-red-400': alertType === 'error',
                        'bg-[#D9973E]/15 border-[#D9973E]/40 text-[#D9973E]': alertType === 'warning',
                        'bg-blue-500/15 border-blue-500/40 text-blue-400': alertType === 'info'
                     }">
                    <template x-if="alertType === 'success'"><span>✓</span></template>
                    <template x-if="alertType === 'error'"><span>✕</span></template>
                    <template x-if="alertType === 'warning'"><span>!</span></template>
                    <template x-if="alertType === 'info'"><span>i</span></template>
                </div>

                <!-- TITLE & MESSAGE -->
                <div class="min-w-0 flex-1">
                    <h3 class="font-bold text-sm text-[#F7F3EC] leading-tight" x-text="alertTitle"></h3>
                    <p class="mt-1 text-xs text-[#C4B6A3] leading-snug" x-text="alertMessage"></p>
                </div>
            </div>

            <div class="mt-3.5 pt-2.5 border-t border-[#3A3026] flex justify-end">
                <button type="button"
                        @click="handleAlertClose()"
                        class="px-3.5 py-1.5 bg-[#D9973E] hover:bg-[#c4842e] text-[#1F1812] font-mono text-[11px] font-bold uppercase tracking-wider transition shadow">
                    <span x-text="alertBtnText"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATIONS CONTAINER (POJOK KANAN ATAS) -->
    <div class="fixed top-4 right-4 z-[99999] flex flex-col items-end gap-1.5 pointer-events-none max-w-xs w-auto px-2 sm:px-0">
        <template x-for="t in toasts" :key="t.id">
            <div class="pointer-events-auto bg-[#1F1812] border shadow-lg px-3 py-1.5 flex items-center gap-2 text-[#F7F3EC] text-xs transition-all duration-200"
                 :class="{
                    'border-[#5F7F42]': t.type === 'success',
                    'border-red-500': t.type === 'error',
                    'border-[#D9973E]': t.type === 'warning',
                    'border-[#3A3026]': t.type === 'info'
                 }">
                <span class="font-bold text-xs shrink-0"
                      :class="{
                        'text-[#5F7F42]': t.type === 'success',
                        'text-red-400': t.type === 'error',
                        'text-[#D9973E]': t.type === 'warning',
                        'text-blue-400': t.type === 'info'
                      }">
                    <template x-if="t.type === 'success'"><span>✓</span></template>
                    <template x-if="t.type === 'error'"><span>✕</span></template>
                    <template x-if="t.type === 'warning'"><span>!</span></template>
                    <template x-if="t.type === 'info'"><span>i</span></template>
                </span>
                <span class="font-medium text-[#F7F3EC] leading-tight" x-text="t.message"></span>
                <button type="button" @click="toasts = toasts.filter(item => item.id !== t.id)"
                        class="text-[10px] text-[#A89A85] hover:text-white shrink-0 ml-1 leading-none">✕</button>
            </div>
        </template>
    </div>

</div>

<script>
    // Global Helper Function: Custom Confirm Promise
    window.customConfirm = function({ title = 'Konfirmasi', message = '', type = 'warning', confirmText = 'Ya', cancelText = 'Batal' } = {}) {
        return new Promise((resolve) => {
            window.dispatchEvent(new CustomEvent('open-custom-confirm', {
                detail: { title, message, type, confirmText, cancelText, resolve }
            }));
        });
    };

    // Global Helper Function: Custom Alert Promise
    window.customAlert = function({ title = 'Info', message = '', type = 'info', btnText = 'OK' } = {}) {
        return new Promise((resolve) => {
            window.dispatchEvent(new CustomEvent('open-custom-alert', {
                detail: { title, message, type, btnText, resolve }
            }));
        });
    };

    // Global Helper Function: Custom Toast Notification
    window.customToast = function({ message = '', type = 'info', duration = 2000 } = {}) {
        window.dispatchEvent(new CustomEvent('show-custom-toast', {
            detail: { message, type, duration }
        }));
    };

    // Global Form Confirmation Interceptor
    document.addEventListener('submit', function(e) {
        const form = e.target;
        const confirmMsg = form.getAttribute('data-confirm');
        if (confirmMsg) {
            e.preventDefault();
            window.customConfirm({
                title: form.getAttribute('data-confirm-title') || 'Konfirmasi',
                message: confirmMsg,
                type: form.getAttribute('data-confirm-type') || 'warning',
                confirmText: form.getAttribute('data-confirm-btn') || 'Ya',
                cancelText: form.getAttribute('data-cancel-btn') || 'Batal'
            }).then((ok) => {
                if (ok) {
                    form.removeAttribute('data-confirm');
                    form.submit();
                }
            });
        }
    });
</script>
