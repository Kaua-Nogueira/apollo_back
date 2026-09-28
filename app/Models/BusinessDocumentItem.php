<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessDocumentItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function document() { return $this->belongsTo(BusinessDocument::class, 'business_document_id'); }
}
