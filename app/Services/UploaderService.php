<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class UploaderService
{

    public function __construct(
        public ?string $driver = 'public'
    )
    {
    }

    /**
     * @param $file
     * @param $path
     * @param $filename
     * @return string
     */
    public function upload($file, $path, $filename = null): string
    {
        $extension = $file->getClientOriginalExtension();

        $filename = $filename ? "{$filename}.{$extension}" : $file->getClientOriginalName();

        $filename = preg_replace("/\.{$extension}$/i", '', $filename) . ".{$extension}";

        return Storage::disk($this->driver)
            ->putFileAs(
                $path,
                $file,
                $filename
            );
    }
}
