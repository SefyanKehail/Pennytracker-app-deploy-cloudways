<?php

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exceptions\ValidationException;
use App\Validators\Traits\Finfo;
use League\Flysystem\Filesystem;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class ReceiptFileValidator implements ValidatorInterface
{
    public function __construct(
        private readonly FinfoMimeTypeDetector $detector,
    ) {
    }

    public function validate(array $data): array
    {
        /***@var UploadedFileInterface $file */
        $file = $data['receipt'] ?? null;


        if (! $file) {
            throw new ValidationException(['receipt' => 'Please select a receipt file']);
        }

        $filename          = $file->getClientFileName();
        $tmpFilePath       = $file->getStream()->getMetadata('uri');
        $maxFileSize       = 5 * 1024 * 1024; // in bytes
        $allowedMimeTypes  = ['image/jpeg', 'image/png', 'application/pdf'];
//        $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf'];
        $contents = $file->getStream()->getContents();

        $file->getStream()->rewind();

        // validate upload success
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['receipt' => 'Failed to upload the receipt file']);
        }


        // validate size
        if ($file->getSize() > $maxFileSize) {
            throw new ValidationException(['receipt' => 'Exceeded max file size (5MB)']);
        }

        // validate name
        if (! preg_match('/^[a-zA-Z0-9_\-\.\s]+$/', $filename)) {
            throw new ValidationException(['receipt' => 'Invalid filename']);
        }


        // validate type mime from clientSide
        if (! in_array($file->getClientMediaType(), $allowedMimeTypes)) {
            throw new ValidationException(['receipt' => 'Invalid receipt file type (should be an image or a pdf)']);
        }

        // mime type and extension from content ( it falls back to extension check ) has to be over 300 bytes
        if (! in_array($this->detector->detectMimeType($tmpFilePath, $contents), $allowedMimeTypes)) {
            throw new ValidationException(['transaction' => 'Invalid file type (Should be of type CSV)']);
        }

        return $data;
    }

}