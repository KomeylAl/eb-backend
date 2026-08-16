<?php

namespace App\Services;

use App\Exceptions\MediaException;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    /**
     * @return array<string, mixed>
     */
    public function collection(string $key): array
    {
        $collection = config("media.collections.{$key}");

        if (! is_array($collection)) {
            throw new MediaException("Unknown media collection [{$key}].");
        }

        return $collection;
    }

    public function isLibraryCollection(string $key): bool
    {
        return (bool) ($this->collection($key)['library'] ?? false);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function store(
        UploadedFile $file,
        string $collectionKey,
        array $context = [],
        ?string $customName = null,
        ?string $folderId = null,
        ?string $uploadedBy = null,
    ): Media {
        $config = $this->collection($collectionKey);
        $this->assertValidUpload($file, $config);

        $disk = $config['disk'];
        $directory = $this->interpolate((string) $config['path'], $context);
        $filename = $this->uniqueFilename($disk, $directory, $file, $customName);

        $stored = $file->storeAs($directory, $filename, $disk);

        if (! $stored) {
            throw new MediaException('Failed to store uploaded file.');
        }

        return Media::query()->create([
            'disk' => $disk,
            'path' => $stored,
            'collection' => $collectionKey,
            'folder_id' => $folderId,
            'original_name' => $file->getClientOriginalName(),
            'name' => pathinfo($filename, PATHINFO_FILENAME),
            'mime' => $file->getMimeType() ?: $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'visibility' => $config['visibility'] ?? 'public',
            'uploaded_by' => $uploadedBy,
        ]);
    }

    /**
     * Store a new upload or reuse an existing library file. Returns the path
     * to persist on the entity, or null when neither input was provided.
     *
     * @param  array<string, mixed>  $context
     */
    public function assign(
        string $collectionKey,
        ?UploadedFile $file,
        ?string $mediaId,
        array $context = [],
        ?string $uploadedBy = null,
    ): ?string {
        if ($file) {
            return $this->store($file, $collectionKey, $context, null, null, $uploadedBy)->path;
        }

        if (filled($mediaId)) {
            $media = Media::query()->find($mediaId);

            if (! $media) {
                throw new MediaException('Selected media was not found.');
            }

            if (
                $media->collection !== $collectionKey
                && ! $this->isLibraryCollection($media->collection)
            ) {
                throw new MediaException('This file cannot be attached from the library.');
            }

            return $media->path;
        }

        return null;
    }

    public function rename(Media $media, string $name): Media
    {
        $name = trim($name);

        if ($name === '') {
            throw new MediaException('File name cannot be empty.');
        }

        $disk = Storage::disk($media->disk);
        $directory = trim(dirname($media->path), '.');
        $extension = pathinfo($media->path, PATHINFO_EXTENSION);
        $slug = Str::slug($name) ?: 'file';
        $filename = $extension ? $slug.'.'.$extension : $slug;
        $desired = $directory === '' ? $filename : $directory.'/'.$filename;
        $newPath = $this->uniquePath($media->disk, $desired, $media->path);

        if ($newPath !== $media->path && $disk->exists($media->path)) {
            $disk->move($media->path, $newPath);
        }

        $media->update([
            'path' => $newPath,
            'name' => $slug,
        ]);

        return $media->refresh();
    }

    public function moveToFolder(Media $media, ?string $folderId): Media
    {
        $media->update(['folder_id' => $folderId]);

        return $media->refresh();
    }

    public function delete(Media $media): void
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }

    /**
     * Remove a non-library file (and its catalog row). Library files stay on disk.
     */
    public function deleteByPath(?string $path, ?string $disk = null): void
    {
        if (! $path) {
            return;
        }

        $query = Media::query()->where('path', $path);

        if ($disk) {
            $query->where('disk', $disk);
        }

        $media = $query->first();

        if ($media) {
            if ($this->isLibraryCollection($media->collection)) {
                return;
            }

            $this->delete($media);

            return;
        }

        Storage::disk($disk ?? 'public')->delete($path);
    }

    public function indexDisk(string $disk = 'public'): int
    {
        $prefixMap = config('media.prefix_map', []);
        $created = 0;

        foreach (Storage::disk($disk)->allFiles() as $path) {
            $basename = basename($path);

            if (str_starts_with($basename, '.')) {
                continue;
            }

            if (Media::query()->where('disk', $disk)->where('path', $path)->exists()) {
                continue;
            }

            $root = explode('/', $path)[0];
            $collection = $prefixMap[$root] ?? 'library';
            $config = config("media.collections.{$collection}") ?? config('media.collections.library');

            Media::query()->create([
                'disk' => $disk,
                'path' => $path,
                'collection' => $collection,
                'folder_id' => null,
                'original_name' => $basename,
                'name' => pathinfo($basename, PATHINFO_FILENAME),
                'mime' => Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream',
                'size' => Storage::disk($disk)->size($path) ?: 0,
                'visibility' => $config['visibility'] ?? 'public',
                'uploaded_by' => null,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function interpolate(string $template, array $context = []): string
    {
        $replacements = array_merge([
            'year' => now()->format('Y'),
            'month' => now()->format('m'),
            'day' => now()->format('d'),
        ], $context);

        $path = preg_replace_callback('/\{([a-z_]+)\}/', function (array $matches) use ($replacements) {
            $value = $replacements[$matches[1]] ?? 'misc';
            $slug = Str::slug((string) $value);

            return $slug !== '' ? $slug : 'misc';
        }, $template) ?? $template;

        return trim(str_replace('//', '/', $path), '/');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function assertValidUpload(UploadedFile $file, array $config): void
    {
        $maxKb = (int) ($config['max_kb'] ?? 5120);
        $sizeKb = (int) ceil(($file->getSize() ?: 0) / 1024);

        if ($sizeKb > $maxKb) {
            throw new MediaException("File exceeds maximum size of {$maxKb} KB.");
        }

        $allowedMimes = $config['mimes'] ?? [];
        $allowedExt = $config['extensions'] ?? [];
        $mime = $file->getMimeType() ?: $file->getClientMimeType();
        $ext = strtolower($file->getClientOriginalExtension());

        $mimeOk = $allowedMimes === [] || ($mime && in_array($mime, $allowedMimes, true));
        $extOk = $allowedExt === [] || ($ext && in_array($ext, $allowedExt, true));

        if (! $mimeOk && ! $extOk) {
            throw new MediaException('This file type is not allowed for the selected collection.');
        }
    }

    private function uniqueFilename(string $disk, string $directory, UploadedFile $file, ?string $customName): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $base = Str::slug($customName ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $filename = $base.'.'.$extension;
        $path = trim($directory.'/'.$filename, '/');

        if (! Storage::disk($disk)->exists($path)) {
            return $filename;
        }

        return $base.'-'.Str::lower(Str::random(6)).'.'.$extension;
    }

    private function uniquePath(string $disk, string $desired, string $current): string
    {
        if ($desired === $current || ! Storage::disk($disk)->exists($desired)) {
            return $desired;
        }

        $directory = trim(dirname($desired), '.');
        $filename = basename($desired);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $suffix = Str::lower(Str::random(6));

        return ($directory === '' ? '' : $directory.'/').$name.'-'.$suffix.($ext ? '.'.$ext : '');
    }
}
