<?php

namespace App\Filament\Resources\Medias\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected array $storedFiles = [];

    public function beforeCreate(): void
    {
        $this->storedFiles = [];
    }

    public function afterCreate(): void
    {
        //
    }

    /**
     * Multiple uploads: persist each through the hardened MediaService.
     */
    public function create(bool $another = false): void
    {
        $data = $this->form->getState();
        $service = app(MediaService::class);

        $uploaded = $data['upload'] ?? [];
        $count = 0;
        $errors = [];

        foreach ((array) $uploaded as $tempPath) {
            try {
                $fullPath = Storage::disk('temp')->path($tempPath);

                $file = new \Illuminate\Http\UploadedFile(
                    $fullPath,
                    basename($tempPath),
                    null,
                    null,
                    true, // test-mode: file already on disk
                );

                $media = $service->store($file, auth()->id());

                $media->update([
                    'title' => $data['title'] ?? null,
                    'alt' => $data['alt'] ?? null,
                    'caption' => $data['caption'] ?? null,
                ]);

                Storage::disk('temp')->delete($tempPath);
                $count++;
            } catch (\Throwable $e) {
                $errors[] = basename($tempPath).': '.$e->getMessage();
            }
        }

        if ($count > 0) {
            Notification::make()->title("{$count} berkas tersimpan.")->success()->send();
        }

        foreach ($errors as $error) {
            Notification::make()->title('Gagal unggah')->body($error)->danger()->send();
        }

        $this->redirect(static::$resource::getUrl('index'));
    }
}
