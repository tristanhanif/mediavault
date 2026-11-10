<?php

namespace App\Services;

use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Asset\DeliveryType;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class CloudinaryService
{
    protected ?Cloudinary $client = null;

    protected function client(): Cloudinary
    {
        if ($this->client === null) {
            if (! config('cloudinary.cloud_name') || ! config('cloudinary.api_key') || ! config('cloudinary.api_secret')) {
                throw new \RuntimeException(
                    'Cloudinary belum dikonfigurasi. Isi CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, dan CLOUDINARY_API_SECRET pada file .env.'
                );
            }

            $config = Configuration::instance([
                'cloud' => [
                    'cloud_name' => config('cloudinary.cloud_name'),
                    'api_key' => config('cloudinary.api_key'),
                    'api_secret' => config('cloudinary.api_secret'),
                ],
                'url' => [
                    'secure' => true,
                ],
            ]);

            $this->client = new Cloudinary($config);
        }

        return $this->client;
    }

    /**
     * Unggah file ke Cloudinary di folder terisolasi milik user.
     *
     * @return array{public_id: string, resource_type: string, bytes: int, format: string}
     *
     * @throws ApiError
     */
    public function upload(UploadedFile $file, int $userId): array
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = $this->sanitizeName($originalName);

        $publicId = sprintf(
            '%s/users/%d/%s_%s_%s',
            config('cloudinary.upload_prefix', 'mediavault'),
            $userId,
            time(),
            Str::lower(Str::random(8)),
            $sanitizedName,
        );

        $resourceType = $this->resolveResourceType($file);

        if ($resourceType === 'raw') {
            $publicId .= '.'.Str::lower($file->getClientOriginalExtension());
        }

        $result = $this->client()->uploadApi()->upload($file->getRealPath(), [
            'public_id' => $publicId,
            'resource_type' => $resourceType,
            'use_filename' => false,
            'unique_filename' => false,
            'overwrite' => false,
            'filename' => basename($publicId),
            'invalidate' => true,
        ]);

        return [
            'public_id' => $result['public_id'],
            'resource_type' => $result['resource_type'],
            'bytes' => (int) $result['bytes'],
            'format' => $result['format'] ?? $file->getClientOriginalExtension(),
        ];
    }

    /**
     * Hapus file dari Cloudinary (dipakai untuk rollback maupun permanent delete).
     */
    public function delete(string $publicId, string $resourceType): bool
    {
        $result = $this->client()->uploadApi()->destroy($publicId, [
            'resource_type' => $resourceType,
            'invalidate' => true,
        ]);

        return ($result['result'] ?? null) === 'ok'
            || ($result['result'] ?? null) === 'not found';
    }

    /**
     * Ambil konten file dari Cloudinary (server-side proxy untuk akses privat).
     */
    public function download(string $publicId, string $resourceType): string
    {
        $url = $this->signedUrl($publicId, $resourceType);

        $content = file_get_contents($url);

        if ($content === false) {
            throw new RuntimeException('Gagal mengambil file dari Cloudinary.');
        }

        return $content;
    }

    /**
     * Bangun URL privat (signed) untuk streaming/preview.
     */
    public function signedUrl(string $publicId, string $resourceType, array $options = []): string
    {
        $asset = match ($resourceType) {
            'image' => $this->client()->image($publicId),
            'video' => $this->client()->video($publicId),
            default => $this->client()->raw($publicId),
        };

        $asset->setAssetProperty('deliveryType', $options['type'] ?? DeliveryType::UPLOAD);
        $asset->setUrlConfig('sign_url', true);

        return (string) $asset->toUrl();
    }

    public function resolveResourceType(UploadedFile $file): string
    {
        $mime = $file->getMimeType() ?? '';

        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            default => 'raw',
        };
    }

    protected function sanitizeName(string $name): string
    {
        $name = preg_replace('/[^\p{L}\p{N}_\-]+/u', '_', $name) ?: 'file';

        return trim($name, '_-') ?: 'file';
    }
}
