<?php

namespace App\Http\Controllers;

use App\Models\Medium;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('tags')->where(fn ($q) => $q->where('user_id', Auth::id())),
            ],
        ]);

        Tag::create([
            'user_id' => Auth::id(),
            'name' => Str::trim($validated['name']),
        ]);

        return back()->with('success', "Tag \"{$validated['name']}\" berhasil dibuat.");
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        abort_unless($tag->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('tags')
                    ->where(fn ($q) => $q->where('user_id', Auth::id()))
                    ->ignore($tag->id),
            ],
        ]);

        $tag->update(['name' => Str::trim($validated['name'])]);

        return back()->with('success', 'Tag berhasil diperbarui.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        abort_unless($tag->user_id === Auth::id(), 403);

        $tag->delete();

        return back()->with('success', "Tag \"{$tag->name}\" dihapus.");
    }
}
