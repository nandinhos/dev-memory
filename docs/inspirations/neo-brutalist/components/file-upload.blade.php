@props([
    'name'     => 'file',
    'label'    => 'Upload File',
    'accept'   => '*',
    'multiple' => false,
])

<div
    x-data="{
        dragging: false,
        files: [],
        triggerInput() { $refs.fileInput.click(); },
        handleFiles(newFiles) {
            this.files = Array.from(newFiles);
        }
    }"
    class="w-full"
>
    <span class="block text-xs font-bold font-body uppercase tracking-wider mb-2">{{ $label }}</span>

    {{-- Drop zone --}}
    <div
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="dragging = false; handleFiles($event.dataTransfer.files)"
        @click="triggerInput"
        :class="dragging ? 'bg-neo-yellow' : 'bg-white hover:bg-neo-bg'"
        class="border-4 border-dashed border-black shadow-neo cursor-pointer transition-colors duration-100 p-8 flex flex-col items-center justify-center gap-3"
        role="button"
        tabindex="0"
        @keydown.enter="triggerInput"
        aria-label="{{ $label }}"
    >
        {{-- Upload icon --}}
        <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 10l-4-4-4 4M12 6v10" />
        </svg>

        <p class="font-body font-bold text-sm uppercase tracking-wide text-center">
            Drag &amp; drop or <span class="underline">click to upload</span>
        </p>

        {{-- File list --}}
        <template x-if="files.length > 0">
            <ul class="mt-2 w-full space-y-1" @click.stop>
                <template x-for="(file, i) in files" :key="i">
                    <li class="flex items-center gap-2 bg-neo-green border-2 border-black px-3 py-1 font-body text-xs font-bold">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 4H7a2 2 0 01-2-2V6a2 2 0 012-2h5l5 5v13a2 2 0 01-2 2z" />
                        </svg>
                        <span x-text="file.name" class="truncate"></span>
                    </li>
                </template>
            </ul>
        </template>
    </div>

    {{-- Hidden input --}}
    <input
        x-ref="fileInput"
        type="file"
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        accept="{{ $accept }}"
        @if($multiple) multiple @endif
        @change="handleFiles($event.target.files)"
        class="sr-only"
        aria-hidden="true"
    />
</div>
