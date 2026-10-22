<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $guarded = [];
    protected $appends = ['logo_image_url', 'document_header_image_url', 'document_footer_image_url'];

    public function getLogoImageUrlAttribute(): ?string
    {
        return $this->logo_image ? '/storage/' . ltrim($this->logo_image, '/') : null;
    }

    public function getDocumentHeaderImageUrlAttribute(): ?string
    {
        return $this->document_header_image ? '/storage/' . ltrim($this->document_header_image, '/') : null;
    }

    public function getDocumentFooterImageUrlAttribute(): ?string
    {
        return $this->document_footer_image ? '/storage/' . ltrim($this->document_footer_image, '/') : null;
    }
}
