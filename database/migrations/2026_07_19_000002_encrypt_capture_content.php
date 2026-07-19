<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Re-grava raw_content/sanitized_content das captures existentes
 * criptografados (o cast 'encrypted' no model cuida dos novos). Idempotente:
 * pula valores que já são payload Crypt válido.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('captures')->select('id', 'raw_content', 'sanitized_content')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('captures')->where('id', $row->id)->update([
                    'raw_content' => $this->encryptOnce($row->raw_content),
                    'sanitized_content' => $this->encryptOnce($row->sanitized_content),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('captures')->select('id', 'raw_content', 'sanitized_content')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('captures')->where('id', $row->id)->update([
                    'raw_content' => $this->decryptOnce($row->raw_content),
                    'sanitized_content' => $this->decryptOnce($row->sanitized_content),
                ]);
            }
        });
    }

    private function encryptOnce(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Já criptografado? não re-encripta.
        try {
            Crypt::decryptString($value);

            return $value;
        } catch (Throwable) {
            return Crypt::encryptString($value);
        }
    }

    private function decryptOnce(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value; // já em claro
        }
    }
};
