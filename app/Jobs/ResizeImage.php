<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Image\Enums\CropPosition;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;
use Spatie\Image\Enums\Unit;


class ResizeImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $path;
    public $w;
    public $h;
    public $fileName;

    public function __construct($filePath, $w, $h)
    {
        $this->path = $filePath;
        $this->w = $w;
        $this->h = $h;
    }

    public function handle(): void
    {
        $srcPath = storage_path('app/public/' . $this->path);

        $destPath = storage_path('app/public/' . dirname($this->path) . '/crop_' . $this->w . 'x' . $this->h . '_' . basename($this->path));

        Image::useImageDriver(ImageDriver::Gd)
            ->load($srcPath)
            ->crop($this->w, $this->h, CropPosition::Center)
            ->watermark(base_path('resources/img/watermark.png'))
            ->save($destPath);
    }
}