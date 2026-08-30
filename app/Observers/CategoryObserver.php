<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    public function saved(): void
    {
        $this->flush();
    }

    public function deleted(): void
    {
        $this->flush();
    }

    protected function flush(): void
    {
        Cache::forget('nav.root_categories');
    }
}
