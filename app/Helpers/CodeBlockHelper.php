<?php

namespace App\Helpers;

class CodeBlockHelper
{
    public static function getHljsLang(string $lang): string
    {
        $map = [
            'php' => 'php',
            'js' => 'javascript',
            'javascript' => 'javascript',
            'ts' => 'typescript',
            'typescript' => 'typescript',
            'css' => 'css',
            'html' => 'html',
            'blade' => 'html',
            'json' => 'json',
            'bash' => 'bash',
            'sh' => 'bash',
            'shell' => 'bash',
            'sql' => 'sql',
            'yaml' => 'yaml',
            'yml' => 'yaml',
            'jsx' => 'javascript',
            'tsx' => 'typescript',
            'md' => 'markdown',
            'markdown' => 'markdown',
            'python' => 'python',
            'py' => 'python',
            'ruby' => 'ruby',
            'go' => 'go',
            'rust' => 'rust',
            'java' => 'java',
            'c' => 'c',
            'cpp' => 'cpp',
            'dockerfile' => 'dockerfile',
            'nginx' => 'nginx',
            'vue' => 'xml',
            'xml' => 'xml',
        ];

        return $map[$lang] ?? 'plaintext';
    }
}
