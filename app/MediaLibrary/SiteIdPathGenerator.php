<?php

namespace App\MediaLibrary;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class SiteIdPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media).'/responsive-images/';
    }

    protected function getBasePath(Media $media): string
    {
        $prefix = config('media-library.prefix', '');
        $mediaKeyBase = (string) $media->getKey();

        $siteId = null;
        try {
            $relatedModel = $media->model; // morph relation
            if ($relatedModel && isset($relatedModel->site_id)) {
                $siteId = (int) $relatedModel->site_id;
            }
        } catch (\Throwable) {
            // If the model can't be resolved for some reason, fall back to the default behaviour.
        }

        // Candidate paths (same structure as DefaultPathGenerator, but with optional site_id prefix).
        $withoutSite = $prefix !== '' ? $prefix.'/'.$mediaKeyBase : $mediaKeyBase;
        $withSite = $siteId !== null ? ($prefix !== '' ? $prefix.'/'.$siteId.'/'.$mediaKeyBase : $siteId.'/'.$mediaKeyBase) : $withoutSite;

        // Backward compatibility: if the file already exists in the old location, keep using it.
        try {
            $disk = Storage::disk($media->disk);
            $fileName = (string) $media->file_name;

            if ($disk->exists($withSite.'/'.$fileName)) {
                return $withSite;
            }

            if ($disk->exists($withoutSite.'/'.$fileName)) {
                return $withoutSite;
            }
        } catch (\Throwable) {
            // Ignore disk checks; we'll return the "new" path by default if possible.
        }

        return $withSite;
    }
}
