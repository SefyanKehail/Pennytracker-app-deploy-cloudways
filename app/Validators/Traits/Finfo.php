<?php

namespace App\Validators\Traits;


// to be avoided since finfo doesn't give proper handling, use  a library instead ( flysystem )
trait Finfo
{
    public function getExtension(string $filePath): string
    {
        $extension = (new \finfo(FILEINFO_EXTENSION))->file($filePath) ?: '';

        if (str_contains($extension, '/')) {
            return explode('/', $extension)[0];
        }

        return $extension;
    }

    public function getMimeType(string $filePath): string
    {
        return (new \finfo(FILEINFO_MIME_TYPE))->file($filePath) ?: '';
    }
}