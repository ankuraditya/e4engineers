<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class MediaResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'url' => $this->url, 'filename' => $this->filename, 'original_name' => $this->original_name, 'mime_type' => $this->mime_type, 'extension' => $this->extension, 'size' => $this->size, 'width' => $this->width, 'height' => $this->height, 'alt_text' => $this->alt_text, 'title' => $this->title, 'caption' => $this->caption, 'created_at' => $this->created_at];
    }
}
