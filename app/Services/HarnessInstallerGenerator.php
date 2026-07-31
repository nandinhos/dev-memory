<?php

namespace App\Services;

use App\Models\HarnessProfile;

class HarnessInstallerGenerator
{
    public function __construct(
        private HarnessProfileService $profileService,
    ) {}

    /**
     * Gera o script Bash idempotente para instalação de um perfil de harness.
     */
    public function generateScript(HarnessProfile $profile): string
    {
        $plan = $this->profileService->provisionPlan($profile);

        $harnessLabel = $profile->harness->label();
        $profileName = $profile->name;
        $version = $profile->version;

        $script = [];
        $script[] = '#!/usr/bin/env bash';
        $script[] = '# Dev Memory Hub - Script de Provisão de Harness';
        $script[] = "# Harness: {$harnessLabel} | Perfil: {$profileName} | Versão: {$version}";
        $script[] = '# Gerado automaticamente pelo Dev Memory Hub';
        $script[] = '';
        $script[] = 'set -euo pipefail';
        $script[] = '';
        $script[] = 'GREEN="\033[0;32m"';
        $script[] = 'YELLOW="\033[1;33m"';
        $script[] = 'RED="\033[0;31m"';
        $script[] = 'NC="\033[0m" # No Color';
        $script[] = '';
        $script[] = 'echo -e "${GREEN}==> Iniciando provisão de harness: '.$harnessLabel.' ('.$profileName.' v'.$version.')${NC}"';
        $script[] = '';

        foreach ($plan['steps'] as $step) {
            $path = $step['path'];
            $content = $step['content'];
            $hadSecrets = $step['had_secrets'];

            // Converte caminhos com ~ para $HOME
            $bashPath = str_starts_with($path, '~/')
                ? '"$HOME/'.substr($path, 2).'"'
                : '"'.$path.'"';

            $script[] = "# Passo {$step['order']}: {$path}";
            $script[] = "TARGET={$bashPath}";
            $script[] = 'DIR=$(dirname "$TARGET")';
            $script[] = 'mkdir -p "$DIR"';

            // Verificação de sobrescrita com confirmação se interativo
            $script[] = 'if [ -f "$TARGET" ]; then';
            $script[] = '    echo -e "${YELLOW}  [AVISO] Arquivo já existe: $TARGET${NC}"';
            $script[] = '    if [ -t 0 ]; then';
            $script[] = '        read -p "  Deseja sobrescrever? (s/N): " -n 1 -r';
            $script[] = '        echo';
            $script[] = '        if [[ ! $REPLY =~ ^[Ss]$ ]]; then';
            $script[] = '            echo -e "${YELLOW}  Ignorado: $TARGET${NC}"';
            $script[] = '            continue';
            $script[] = '        fi';
            $script[] = '    fi';
            $script[] = 'fi';

            // Grava o arquivo usando EOF seguro
            $escapedEof = 'EOF_HEREDOC_'.md5($path);
            $script[] = "cat <<'{$escapedEof}' > \"\$TARGET\"";
            $script[] = $content;
            $script[] = $escapedEof;
            $script[] = 'echo -e "${GREEN}  [OK] Criado/Atualizado: $TARGET${NC}"';

            if ($hadSecrets) {
                $script[] = 'echo -e "${RED}  [ATENÇÃO] Este arquivo possui segredos redigidos ([REDACTED]). Preencha as credenciais reais no arquivo!${NC}"';
            }

            $script[] = '';
        }

        $script[] = 'echo -e "${GREEN}==> Provisão do harness concluída com sucesso!${NC}"';
        $script[] = 'echo -e "${GREEN}    Conexão com o Dev Memory Hub configurada.${NC}"';

        return implode("\n", $script)."\n";
    }
}
