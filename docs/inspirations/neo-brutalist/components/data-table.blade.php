@props([
    'colunas'   => [],
    'linhas'    => [],
    'listrado'  => false,
])

<div
    x-data="{
        sortBy: null,
        sortDir: 'asc',
        linhas: {{ Js::from($linhas) }},
        get sorted() {
            if (!this.sortBy) return this.linhas;
            return [...this.linhas].sort((a, b) => {
                const va = a[this.sortBy] ?? '';
                const vb = b[this.sortBy] ?? '';
                if (va < vb) return this.sortDir === 'asc' ? -1 : 1;
                if (va > vb) return this.sortDir === 'asc' ? 1 : -1;
                return 0;
            });
        },
        setSort(chave) {
            if (this.sortBy === chave) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = chave;
                this.sortDir = 'asc';
            }
        }
    }"
    {{ $attributes->merge(['class' => 'overflow-x-auto neo-border shadow-neo']) }}
>
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-white border-b-4 border-black">
                @foreach($colunas as $coluna)
                    <th
                        class="px-4 py-3 font-heading font-bold uppercase tracking-wider text-xs text-left border-r-2 border-black last:border-r-0{{ $coluna['ordenavel'] ?? false ? ' cursor-pointer select-none hover:bg-neo-teal transition-colors duration-100' : '' }}"
                        @if($coluna['ordenavel'] ?? false)
                            @click="setSort('{{ $coluna['chave'] }}')"
                        @endif
                    >
                        <span class="flex items-center gap-1">
                            {{ $coluna['rotulo'] ?? $coluna['chave'] }}
                            @if($coluna['ordenavel'] ?? false)
                                <span x-show="sortBy === '{{ $coluna['chave'] }}'" class="font-body text-base leading-none">
                                    <span x-show="sortDir === 'asc'" aria-hidden="true">↑</span>
                                    <span x-show="sortDir === 'desc'" aria-hidden="true">↓</span>
                                </span>
                                <span x-show="sortBy !== '{{ $coluna['chave'] }}'" class="font-body text-base leading-none text-gray-400" aria-hidden="true">↕</span>
                            @endif
                        </span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <template x-if="sorted.length === 0">
                <tr>
                    <td colspan="{{ count($colunas) }}" class="py-10 text-center font-body text-gray-400">
                        Nenhum dado encontrado.
                    </td>
                </tr>
            </template>
            <template x-for="(linha, idx) in sorted" :key="idx">
                <tr
                    class="border-b-2 border-black last:border-b-0 hover:bg-neo-teal/20 transition-colors"
                    :class="{{ $listrado ? '(idx % 2 === 1) ? \'bg-[#F0EAD6]\' : \'bg-white\'' : '\'bg-white\''}}"
                >
                    @foreach($colunas as $coluna)
                        <td class="px-4 py-3 font-body text-sm border-r-2 border-black last:border-r-0" x-text="linha['{{ $coluna['chave'] }}'] ?? '—'"></td>
                    @endforeach
                </tr>
            </template>
        </tbody>
    </table>
</div>
