<?php

namespace Modules\Whatsapp\Entities;

use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplateDefinition extends Model
{
    protected $table = 'whatsapp_template_definitions';
    
    protected $guarded = ['id'];

    protected $casts = [
        'sample_variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get template JSON as array.
     */
    public function getTemplateJsonArray(): array
    {
        return json_decode($this->template_json, true) ?? [];
    }

    /**
     * Scope to get active definitions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}

