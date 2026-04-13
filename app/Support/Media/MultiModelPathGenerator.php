<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class MultiModelPathGenerator implements PathGenerator
{
    protected function getConfig(Media $media): array
    {
        return config('media-path.' . class_basename($media->model), [
            'base' => 'others',
            'use_model_folder' => true,
        ]);
    }

    protected function getModelFolder(Media $media): string
    {
        return $media->model->slug
            ?? $media->model->id
            ?? $media->model_id;
    }

    public function getPath(Media $media): string
    {
        $config = $this->getConfig($media);

        if (!$config['use_model_folder']) {
            return "{$config['base']}/";
        }

        return "{$config['base']}/{$this->getModelFolder($media)}/";
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive/';
    }
}
