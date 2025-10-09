<div
    x-data="{
        visible: @entangle('visible').live,
        hideTimeout: null,
        dismiss() {
            this.visible = false;
            $wire.hide();
        },
        init() {
            this.$watch('visible', (value) => {
                clearTimeout(this.hideTimeout);
                if (value) {
                    this.hideTimeout = setTimeout(() => this.dismiss(), 3000);
                }
            });
        }
    }"
    x-cloak
    class="pointer-events-none fixed inset-0 z-[60] flex items-start justify-end px-4 py-6 sm:py-8 sm:px-8"
    aria-live="polite"
    aria-atomic="true"
>
    <div
        class="toast toast-top toast-end"
        x-show="visible"
        x-transition:enter="transform ease-out duration-200"
        x-transition:enter-start="translate-y-2 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transform ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="alert {{ $this->alertClasses }} shadow-lg pointer-events-auto" role="status" x-on:click.prevent="dismiss()">
            <span class="text-sm font-medium">{{ $message }}</span>
        </div>
    </div>
</div>
