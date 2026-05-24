<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    private function flush(): void
    {
        Cache::forget('api.uploader.tree.v1');
        Cache::forget('api.categories.v2');
    }

    public function created(Category $c): void   { $this->flush(); }
    public function updated(Category $c): void   { $this->flush(); }
    public function deleted(Category $c): void   { $this->flush(); }
    public function restored(Category $c): void  { $this->flush(); }
}
