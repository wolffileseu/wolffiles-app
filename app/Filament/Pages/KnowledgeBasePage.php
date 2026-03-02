<?php

namespace App\Filament\Pages;

use App\Models\KnowledgeBase;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class KnowledgeBasePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Knowledge Base';
    protected static ?int $navigationSort = 12;
    protected static string $view = 'filament.pages.knowledge-base';

    public ?string $selectedCategory = null;
    public ?int $selectedArticle = null;
    public bool $editing = false;
    public bool $creating = false;
    public string $search = '';

    // Editor fields
    public string $editTitle = '';
    public string $editSlug = '';
    public string $editCategory = 'system';
    public string $editIcon = '📄';
    public string $editContent = '';
    public bool $editPinned = false;
    public int $editSortOrder = 0;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function getArticles()
    {
        $query = KnowledgeBase::orderByDesc('is_pinned')->orderBy('sort_order')->orderBy('title');

        if ($this->selectedCategory) {
            $query->where('category', $this->selectedCategory);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        return $query->get();
    }

    public function getArticle(): ?KnowledgeBase
    {
        if (!$this->selectedArticle) return null;
        return KnowledgeBase::find($this->selectedArticle);
    }

    public function selectArticle(int $id): void
    {
        $this->selectedArticle = $id;
        $this->editing = false;
        $this->creating = false;
    }

    public function startEdit(): void
    {
        $article = $this->getArticle();
        if (!$article) return;

        $this->editTitle = $article->title;
        $this->editSlug = $article->slug;
        $this->editCategory = $article->category;
        $this->editIcon = $article->icon;
        $this->editContent = $article->content;
        $this->editPinned = $article->is_pinned;
        $this->editSortOrder = $article->sort_order;
        $this->editing = true;
        $this->creating = false;
    }

    public function startCreate(): void
    {
        $this->editTitle = '';
        $this->editSlug = '';
        $this->editCategory = $this->selectedCategory ?: 'system';
        $this->editIcon = '📄';
        $this->editContent = '';
        $this->editPinned = false;
        $this->editSortOrder = 0;
        $this->creating = true;
        $this->editing = false;
        $this->selectedArticle = null;
    }

    public function save(): void
    {
        if (empty($this->editTitle)) {
            Notification::make()->title('Title is required')->danger()->send();
            return;
        }

        if (empty($this->editSlug)) {
            $this->editSlug = \Illuminate\Support\Str::slug($this->editTitle);
        }

        $data = [
            'title' => $this->editTitle,
            'slug' => $this->editSlug,
            'category' => $this->editCategory,
            'icon' => $this->editIcon,
            'content' => $this->editContent,
            'is_pinned' => $this->editPinned,
            'sort_order' => $this->editSortOrder,
            'updated_by' => auth()->id(),
        ];

        if ($this->creating) {
            $article = KnowledgeBase::create($data);
            $this->selectedArticle = $article->id;
            $this->creating = false;
            Notification::make()->title('Article created!')->success()->send();
        } else {
            KnowledgeBase::find($this->selectedArticle)->update($data);
            Notification::make()->title('Article saved!')->success()->send();
        }

        $this->editing = false;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->creating = false;
    }

    public function deleteArticle(): void
    {
        if ($this->selectedArticle) {
            KnowledgeBase::find($this->selectedArticle)?->delete();
            $this->selectedArticle = null;
            Notification::make()->title('Article deleted')->success()->send();
        }
    }
}
