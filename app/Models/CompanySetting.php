<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    protected $guarded = [];
    protected $appends = ['logo_image_url', 'document_header_image_url', 'document_footer_image_url'];

    public function getLogoImageUrlAttribute(): ?string
    {
        return $this->logo_image ? Storage::disk('public')->url($this->logo_image) : null;
    }

    public function getDocumentHeaderImageUrlAttribute(): ?string
    {
        return $this->document_header_image ? Storage::disk('public')->url($this->document_header_image) : null;
    }

    public function getDocumentFooterImageUrlAttribute(): ?string
    {
        return $this->document_footer_image ? Storage::disk('public')->url($this->document_footer_image) : null;
    }
}
