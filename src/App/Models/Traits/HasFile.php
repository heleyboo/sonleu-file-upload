<?php

namespace SonLeu\File\App\Models\Traits;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Http\Message\StreamInterface;
use SonLeu\File\App\Models\File;
use SonLeu\File\App\Services\UploadImageService;

/**
 * Trait HasFile
 * @package SonLeu\File\App\Models\Traits
 * @mixin Model
 *
 * Relationships
 * @property-read Collection|File[] $files
 * @property-read Collection|File[] $attachments
 */
trait HasFile
{
    /**
     * @return MorphMany|File
     */
    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * @return mixed
     */
    public function attachments()
    {
        return $this->files()->type('attachment');
    }

    /**
     * @return string
     */
    public function getHasFileClass()
    {
        return self::class;
    }

    /**
     * @return int
     */
    public function getHasFileId()
    {
        return $this->id;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string $disk
     * @param string $type
     * @return File
     * @throws Exception
     */
    public function setFile(UploadedFile $uploadedFile, $disk = 'public', $type = 'attachment')
    {
        $old_files = $this->files()->where('type', $type)->get();

        foreach ($old_files as $old_file) {
            $old_file->delete();
        }

        $file = $this->saveFile($uploadedFile, $disk, $type);

        return $file;
    }

    /**
     * @param $uploadedFiles
     * @param string $disk
     * @param string $type
     * @return Collection
     * @throws Exception
     */
    public function attachFiles($uploadedFiles, $disk = 'public', $type = 'attachment')
    {
        $files = new Collection();

        foreach ($uploadedFiles as $uploadedFile) {
            $file = $this->saveFile($uploadedFile, $disk, $type);

            $files->push($file);
        }

        return $files;
    }

    /**
     * @param null $uploadedFiles
     * @param array|null $file_ids
     * @param string $disk
     * @param string $type
     * @return Collection
     * @throws Exception
     */
    public function syncFiles($uploadedFiles = null, array $file_ids = null, $disk = 'public', $type = 'attachment')
    {
        if (!$file_ids) {
            $file_ids = [];
        }

        $this->updateFiles($file_ids, $type);

        if ($uploadedFiles) {
            $this->attachFiles($uploadedFiles, $disk, $type);
        }

        $files = $this->files()->where('type', $type)->get();

        return $files;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param $disk
     * @param $type
     * @return File
     * @throws Exception
     */
    private function saveFile(UploadedFile $uploadedFile, $disk, $type)
    {
        $original_name = $this->convertOriginalName($uploadedFile);
        $path = $type . '/' . $original_name;

        $this->uploadFile($uploadedFile, $disk, $path);

        $file = new File([
            'disk' => $disk,
            'type' => $type,
            'path' => $path,
            'original_name' => $original_name,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'extension' => $uploadedFile->getClientOriginalExtension(),
            'size' => Storage::disk($disk)->size($path)
        ]);

        $this->files()->save($file);

        return $file;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param $disk
     * @param $path
     * @return UploadedFile|StreamInterface
     * @throws Exception
     */
    private function uploadFile(UploadedFile $uploadedFile, $disk, $path)
    {
        if (
            $uploadedFile->getSize() > config('file.image_max_filesize') &&
            in_array(strtolower($uploadedFile->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'])
        ) {
            $uploadedFile = $this->resizeImage($uploadedFile);

            Storage::disk($disk)->put($path, $uploadedFile);
        } else {
            Storage::disk($disk)->put($path, file_get_contents($uploadedFile));
        }

        return $uploadedFile;
    }

    /**
     * @param array $file_ids
     * @param $type
     */
    private function updateFiles(array $file_ids, $type)
    {
        $db_file_ids = $this->files()
            ->where('type', $type)
            ->pluck('id')
            ->toArray();

        $idsDiff = array_diff($db_file_ids, $file_ids);

        $deleteFiles = $this->files()->where('type', $type)->whereIn('id', $idsDiff)->get();

        foreach ($deleteFiles as $deleteFile) {
            $deleteFile->delete();
        }
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return string
     */
    private function convertOriginalName(UploadedFile $uploadedFile)
    {
        $filename = str_replace('.' . $uploadedFile->getClientOriginalExtension(), '', $uploadedFile->getClientOriginalName());

        return Str::slug($filename) . '-' . uniqid() . '.' . Str::slug($uploadedFile->getClientOriginalExtension());
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return StreamInterface
     * @throws Exception
     */
    private function resizeImage(UploadedFile $uploadedFile)
    {
        $uploadImageService = new UploadImageService();

        return $uploadImageService
            ->setImage($uploadedFile)
            ->setSize(config('file.image_resize_width'))
            ->toStream('resize');
    }
}
