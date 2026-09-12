<?php

namespace Plugins\MegaMenu\src\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Plugins\Catalog\src\Models\Category;

class MegaMenuItem extends Model
{
    protected $table = 'mega_menu_items';

    protected $fillable = [
        'parent_id', 'title', 'type', 'url', 'category_id', 'badge', 'icon',
        'columns', 'html', 'is_mega', 'open_in_new', 'is_active', 'sort_order',
        'image_url', 'bg_image_url', 'description', 'animation', 'effect', 'panel_width',
        'show_search', 'search_placeholder', 'is_tabbed', 'tab_label',
        'form_type', 'form_html', 'accent_color', 'css_class',
        'icon_image_url', 'font_family', 'title_color', 'link_color', 'hover_color',
        'text_color', 'panel_bg_color', 'panel_radius', 'icon_size', 'open_mode', 'panel_align',
    ];

    protected function casts(): array
    {
        return [
            'is_mega' => 'boolean',
            'open_in_new' => 'boolean',
            'is_active' => 'boolean',
            'show_search' => 'boolean',
            'is_tabbed' => 'boolean',
            'columns' => 'integer',
            'sort_order' => 'integer',
            'panel_radius' => 'integer',
            'icon_size' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function href(): string
    {
        if ($this->type === 'category' && $this->category) {
            return url('/category/'.$this->category->slug);
        }
        $url = $this->url ?: '#';
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    }

    public function panelClasses(): string
    {
        $parts = [
            'mega-panel',
            'anim-'.($this->animation ?: 'fade'),
            'fx-'.($this->effect ?: 'shadow'),
        ];

        if ($this->is_mega) {
            $parts[] = 'is-mega-panel';
            $parts[] = 'w-'.($this->panel_width ?: 'wide');
            $parts[] = 'align-'.($this->panel_align ?: 'right');
        } else {
            $parts[] = 'is-dropdown';
            $parts[] = 'w-auto';
            $parts[] = 'align-right';
        }

        if ($this->is_tabbed) {
            $parts[] = 'is-tabbed';
        }
        if ($this->show_search) {
            $parts[] = 'has-search';
        }
        if ($this->css_class) {
            $parts[] = $this->css_class;
        }

        return implode(' ', $parts);
    }

    public function inlineStyle(): string
    {
        $vars = [];
        if ($this->accent_color) {
            $vars[] = '--mega-accent:'.$this->accent_color;
        }
        if ($this->title_color) {
            $vars[] = '--mega-title:'.$this->title_color;
        }
        if ($this->link_color) {
            $vars[] = '--mega-link:'.$this->link_color;
        }
        if ($this->hover_color) {
            $vars[] = '--mega-hover:'.$this->hover_color;
        }
        if ($this->text_color) {
            $vars[] = '--mega-text:'.$this->text_color;
        }
        if ($this->panel_bg_color) {
            $vars[] = '--mega-panel-bg:'.$this->panel_bg_color;
        }
        if ($this->font_family) {
            $vars[] = '--mega-font:'.(str_contains($this->font_family, ',') ? $this->font_family : "'{$this->font_family}', Vazirmatn, sans-serif");
        }
        if ($this->panel_radius) {
            $vars[] = '--mega-radius:'.((int) $this->panel_radius).'px';
        }
        if ($this->icon_size) {
            $vars[] = '--mega-icon-size:'.((int) $this->icon_size).'px';
        }
        $vars[] = '--mega-cols:'.max(1, (int) ($this->columns ?: 3));

        return implode(';', $vars);
    }

    /** @return \Illuminate\Support\Collection<int, self> */
    public static function tree()
    {
        // سه سطح زیر‌منو برای مگامنو/آبشاری (عمیق‌تر از قبل)
        return static::query()
            ->with([
                'category',
                'activeChildren.category',
                'activeChildren.activeChildren.category',
                'activeChildren.activeChildren.activeChildren.category',
            ])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
