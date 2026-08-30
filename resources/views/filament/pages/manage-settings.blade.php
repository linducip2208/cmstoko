<x-filament-panels::page>
    <form wire:submit="save" class="w-full">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <button type="submit" class="fi-btn relative inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 transition-colors">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</x-filament-panels::page>
