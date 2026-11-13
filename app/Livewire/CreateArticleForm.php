<?php

namespace App\Livewire;

use App\Models\Article;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Category;
use App\Jobs\ResizeImage;
use App\Jobs\RemoveFaces;
use App\Jobs\GoogleVisionSafeSearch;
use App\Jobs\GoogleVisionLabelImage;



class CreateArticleForm extends Component
{
    use WithFileUploads;

    public $title = '';
    public $description = '';
    public $price = '';
    public $category = '';
    public $showSuccess = false;
    public $images = [];
    public $temporary_images;

    protected function rules()
{
    return [
        'title' => 'required|min:3',
        'description' => 'required|min:10',
        'price' => 'required|numeric',
        'category' => 'required',
        'temporary_images.*' => 'image|max:2048',
        'images.*' => 'image|max:2048',
    ];
}

    public function store()
{
    $this->validate();
    $this->article = Article::create([
        'title' => $this->title,
        'description' => $this->description,
        'price' => $this->price,
        'category_id' => $this->category,
        'user_id' => Auth::id()
    ]);
    if (count($this->images) > 0) {
        foreach ($this->images as $image) {
            $newFileName = "articles/{$this->article->id}";
            $newImage = $this->article->images()->create(['path' => $image->store($newFileName, 'public')]);
            // dispatch(new ResizeImage($newImage->path, 300, 300));
            // dispatch(new GoogleVisionSafeSearch($newImage->id));
            // dispatch(new GoogleVisionLabelImage($newImage->id));
            RemoveFaces::withChain([
                new ResizeImage($newImage->path, 300, 300),
                new GoogleVisionSafeSearch($newImage->id),
                new GoogleVisionLabelImage($newImage->id),
            ])->dispatch($newImage->id);
        }
        File::deleteDirectory(storage_path('/app/livewire-tmp'));
    }
        session()->flash('success', __('ui.articleCreated'));
        return $this->redirectRoute('home');

    $this->cleanForm();
}

    protected function cleanForm()
    {
        $this->title = '';
        $this->description = '';
        $this->category = '';
        $this->price = '';
        $this->images = [];
    }

    public function render()
    {
        $categories = Category::all();
        return view('livewire.create-article-form', compact('categories'));
    }

    public function updatedTemporaryImages()
    {
        $this->validate([
            'temporary_images' => 'nullable|array',
            'temporary_images.*' => 'image|max:1024',
        ]);

        foreach ($this->temporary_images as $image) {
            $this->images[] = $image;
        }
    }

    public function removeImage($key)
    {
        if (in_array($key, array_keys($this->images))) {
            unset($this->images[$key]);
        }
    }
}