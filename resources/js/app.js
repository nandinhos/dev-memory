import './bootstrap';

// highlight.js bundlado (sem CDN): core + só as linguagens que o hub usa,
// para não carregar o pacote inteiro (~1MB de linguagens que nunca aparecem).
import hljs from 'highlight.js/lib/core';
import 'highlight.js/styles/atom-one-dark.css';

import php from 'highlight.js/lib/languages/php';
import javascript from 'highlight.js/lib/languages/javascript';
import typescript from 'highlight.js/lib/languages/typescript';
import css from 'highlight.js/lib/languages/css';
import xml from 'highlight.js/lib/languages/xml';
import json from 'highlight.js/lib/languages/json';
import bash from 'highlight.js/lib/languages/bash';
import sql from 'highlight.js/lib/languages/sql';
import yaml from 'highlight.js/lib/languages/yaml';
import markdown from 'highlight.js/lib/languages/markdown';
import python from 'highlight.js/lib/languages/python';
import dockerfile from 'highlight.js/lib/languages/dockerfile';

hljs.registerLanguage('php', php);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('typescript', typescript);
hljs.registerLanguage('css', css);
hljs.registerLanguage('html', xml); // blade/html/vue mapeiam para xml
hljs.registerLanguage('json', json);
hljs.registerLanguage('bash', bash);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('yaml', yaml);
hljs.registerLanguage('markdown', markdown);
hljs.registerLanguage('python', python);
hljs.registerLanguage('dockerfile', dockerfile);

window.hljs = hljs;

// Realça só os blocos ainda não realçados (evita re-trabalho e o warning do
// hljs). Chamado no load, na navegação SPA e após cada morph do Livewire —
// assim o realce sobrevive a qualquer atualização de componente.
function highlightAll() {
    document.querySelectorAll('pre code:not([data-highlighted])').forEach((el) => {
        hljs.highlightElement(el);
    });
}

document.addEventListener('DOMContentLoaded', highlightAll);
document.addEventListener('livewire:navigated', highlightAll);
document.addEventListener('livewire:init', () => {
    if (window.Livewire) {
        window.Livewire.hook('morph.updated', () => highlightAll());
    }
});
