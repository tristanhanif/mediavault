<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Medium;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories')->where(fn ($q) => $q->where('user_id', Auth::id())),
            ],
        ]);

        Category::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
        ]);

        return back()->with('success', "Kategori \"{$validated['name']}\" berhasil dibuat.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        abort_unless($category->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories')
                    ->where(fn ($q) => $q->where('user_id', Auth::id()))
                    ->ignore($category->id),
            ],
        ]);

        $category->update(['name' => $validated['name']]);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_unless($category->user_id === Auth::id(), 403);

        Medium::where('category_id', $category->id)->update(['category_id' => null]);
        $category->delete();

        return back()->with('success', "Kategori \"{$category->name}\" dihapus.");
    }

    public function assign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'media_id' => ['required', 'integer', 'exists:media,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $media = Medium::where('user_id', Auth::id())->findOrFail($validated['media_id']);
        $media->update(['category_id' => $validated['category_id'] ?? null]);

        return back()->with('success', 'Kategori media berhasil diperbarui.');
    }
}
