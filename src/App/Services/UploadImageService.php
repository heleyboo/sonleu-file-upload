<?php

namespace SonLeu\File\App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class UploadImageService
{
    const DEFAULT_RATIO = 0.75;

    /**
     * @var ImageManager $imageManager
     */
    private $imageManager;

    /**
     * @var UploadedFile $image
     */
    private $image;

    /**
     * @var string $disk
     */
    private $disk = 'public';

    /**
     * @var string $folder
     */
    private $folder = '';

    /**
     * Tên file khi save
     * @var string $fileName
     */
    private $fileName;

    /**
     * Tỉ lệ ảnh height/width
     * @var double $ratio
     */
    private $ratio = self::DEFAULT_RATIO;

    /**
     * @var double $resizeWidth
     */
    private $resizeWidth;

    /**
     * @var double $resizeHeight
     */
    private $resizeHeight;

    private $xCoordinate = null;

    private $yCoordinate = null;

    private $fitPosition = 'center';

    public function __construct()
    {
        $this->imageManager = new ImageManager();
    }

    /**
     * @param $image
     * @return UploadImageService
     * @throws Exception
     */
    public function setImage($image)
    {
        if ($image instanceof UploadedFile) {
            if (!in_array(strtolower($image->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'])) {
                throw new Exception('File is not an image');
            }

            $imageManager = $this->imageManager->make($image);

            $this->setSize($imageManager->width(), $imageManager->height());

            $this->ratio = $imageManager->height() / $imageManager->width();

            $this->fileName = $image->getClientOriginalName();
        } else {
            throw new Exception('$image must be an instance of Illuminate\Http\UploadedFile');
        }

        $this->image = $image;

        return $this;
    }

    /**
     * @return UploadedFile
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $disk
     * @return $this
     */
    public function setDisk(string $disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * @return string
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param string $folder
     * @return $this
     */
    public function setFolder(string $folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * @return string
     */
    public function getDisk()
    {
        return $this->disk;
    }

    /**
     * @param string $file_name
     * @return $this
     */
    public function setFileName(string $file_name)
    {
        $this->fileName = $file_name;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    public function setSize($width, $height = null)
    {
        $this->resizeWidth = $width;
        $this->resizeHeight = $height;

        /**
         * Nếu $height là null thì dùng tỉ lệ ảnh
         */
        if (is_null($height)) {
            $this->resizeHeight = ($this->resizeWidth * $this->ratio);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getSize()
    {
        return [$this->resizeWidth, $this->resizeHeight];
    }

    /**
     * @param $xCoordinate
     * @param $yCoordinate
     * @return $this
     */
    public function setCoordinates($xCoordinate, $yCoordinate)
    {
        $this->xCoordinate = $xCoordinate;
        $this->yCoordinate = $yCoordinate;

        return $this;
    }

    /**
     * @return array
     */
    public function getCoordinates()
    {
        return [$this->xCoordinate, $this->yCoordinate];
    }

    /**
     * @param string $position
     * top-left * top * top-right * left * center (mặc định) * right * bottom-right * bottom * bottom-left
     *
     * @return $this
     */
    public function setFitPosition(string $position)
    {
        $this->fitPosition = $position;

        return $this;
    }

    /**
     * @return string $fitPosition
     */
    public function getFitPosition()
    {
        return $this->fitPosition;
    }

    protected function make($type = 'fit')
    {
        $image = $this->imageManager->make($this->image);

        switch ($type) {
            case 'resize':
                $image->resize($this->resizeWidth, $this->resizeHeight);
                break;
            case 'crop':
                $image->crop($this->resizeWidth, $this->resizeHeight, $this->xCoordinate, $this->yCoordinate);
                break;
            default:
                $image->fit($this->resizeWidth, $this->resizeHeight, null, $this->fitPosition);
        }

        return $image;
    }

    public function toStream($type = 'fit')
    {
        return $this->make($type)->stream();
    }

    public function save($type = 'fit')
    {
        $path = sprintf('%s/%s', trim($this->folder, '/'), $this->fileName);

        $image = $this->make($type);

        try {
            Storage::disk($this->disk)->put($path, $image->stream());
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }

        return $path;
    }
}
