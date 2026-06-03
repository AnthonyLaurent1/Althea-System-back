<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final class ImageUploadService
{
    private const MAX_SIZE = 5 * 1024 * 1024;

    /** @var array<string, string> */
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%env(default::UPLOAD_BASE_URL)%')]
        private readonly string $baseUrl = '',
    ) {
    }

    /**
     * Valide et stocke une image, puis retourne son URL publique.
     *
     * @return array{url: string, fileName: string}
     */
    public function upload(?UploadedFile $file): array
    {
        if ($file === null) {
            throw new \InvalidArgumentException('Aucun fichier reçu (champ "file").');
        }

        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Fichier invalide.');
        }

        if ($file->getSize() > self::MAX_SIZE) {
            throw new \InvalidArgumentException('Le fichier dépasse la taille maximale de 5 Mo.');
        }

        $mimeType = (string) $file->getMimeType();
        if (!isset(self::ALLOWED_MIME[$mimeType])) {
            throw new \InvalidArgumentException('Type de fichier non autorisé (JPEG, PNG ou WebP uniquement).');
        }

        $fileName = Uuid::v4()->toRfc4122().'.'.self::ALLOWED_MIME[$mimeType];
        $directory = $this->projectDir.'/public/uploads';

        try {
            $file->move($directory, $fileName);
        } catch (FileException $exception) {
            throw new \RuntimeException('Impossible de stocker le fichier.', previous: $exception);
        }

        $relativeUrl = '/uploads/'.$fileName;

        return [
            'url' => rtrim($this->baseUrl, '/').$relativeUrl,
            'fileName' => $fileName,
        ];
    }
}
