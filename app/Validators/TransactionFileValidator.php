<?php

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exceptions\ValidationException;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class TransactionFileValidator implements ValidatorInterface
{
    public function __construct(private readonly FinfoMimeTypeDetector $detector)
    {
    }

    public function validate(array $data): array
    {
        /***@var UploadedFileInterface $file */
        $file = $data['transaction'] ?? null;

        // get file if it exists
        if (! $file) {
            throw new ValidationException(['transaction' => 'Please select a csv file']);
        }

        // check upload success
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['transaction' => 'Failed to upload the csv file']);
        }


        $maxFileSize = 5 * 1024 * 1024;
        $filename    = $file->getClientFilename();
        $tmpFilePath = $file->getStream()->getMetadata('uri');
        $allowed     = ['text/csv', 'csv'];
        $contents = $file->getStream()->getContents();

        $file->getStream()->rewind();

        // size
        if ($file->getSize() > $maxFileSize) {
            throw new ValidationException(['transaction' => 'Exceeded max file size (5MB)']);
        }


        // validate name
        if (! preg_match('/^[a-zA-Z0-9_\-\.\s]+$/', $filename)) {
            throw new ValidationException(['receipt' => 'Invalid filename']);
        }

        // mime type native
        if (! in_array($file->getClientMediaType(), $allowed)) {
            throw new ValidationException(['transaction' => 'Invalid file type (Should be of type CSV)']);
        }

        // mime type and extension from content ( it falls back to extension check ) has to be over 300 bytes
        if (! in_array($this->detector->detectMimeType($tmpFilePath, $contents), $allowed)) {
            throw new ValidationException(['transaction' => 'Invalid file type (Should be of type CSV)']);
        }


        return $data;
    }

}