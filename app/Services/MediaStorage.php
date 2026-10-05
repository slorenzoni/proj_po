<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Arquivos enviados pelo painel (logos, fotos, banners, capas, documentos).
 *
 * As tabelas guardam o caminho relativo no disco de mídia (config filesystems.media_disk),
 * nunca a URL completa — assim o disco pode mudar (ex.: de local para S3) sem migrar dados.
 */
final class MediaStorage
{
    /**
     * Grava o arquivo com nome aleatório e devolve o caminho a ser salvo no banco.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, ['disk' => $this->diskName()]);

        if ($path === false) {
            throw new RuntimeException("Não foi possível gravar o arquivo em [{$directory}].");
        }

        return $path;
    }

    /**
     * Grava o novo arquivo e apaga o anterior, se houver.
     */
    public function replace(?string $currentPath, UploadedFile $file, string $directory): string
    {
        $path = $this->store($file, $directory);

        $this->delete($currentPath);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            $this->disk()->delete($path);
        }
    }

    public function url(?string $path): ?string
    {
        return $path === null || $path === '' ? null : $this->disk()->url($path);
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    private function diskName(): string
    {
        return (string) config('filesystems.media_disk');
    }
}
