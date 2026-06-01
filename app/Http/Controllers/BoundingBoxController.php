<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\BoundingBox;
use App\Models\Page;
use App\Models\Audio;

class BoundingBoxController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page_id' => 'required|exists:pages,id',
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg',
        ]);

        $box = BoundingBox::create([
            'page_id' => $validated['page_id'],
            'x' => $validated['x'],
            'y' => $validated['y'],
            'width' => $validated['width'],
            'height' => $validated['height'],
        ]);

        if ($request->hasFile('audio')) {
            $page = Page::find($validated['page_id']);
            $book = $page->book;
            $bookSlug = strtolower(
                str_replace(
                    [' ', ',', '.', '(', ')', '/'],
                    '_',
                    trim($book->title)
                )
            );
            $bookSlug = preg_replace('/_+/', '_', $bookSlug);
            $bookSlug = trim($bookSlug, '_');
            
            // Get original filename dan sanitize
            $originalName = $request->file('audio')->getClientOriginalName();
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $sanitizedName = pathinfo($originalName, PATHINFO_FILENAME);
            $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sanitizedName);
            $sanitizedName = preg_replace('/_+/', '_', $sanitizedName);
            $filename = trim($sanitizedName, '_') . '.' . $extension;
            
            $path = $request->file('audio')->storeAs("audios/{$bookSlug}/audio_narasi_indo", $filename, 'public');

            Audio::create([
                'bounding_box_id' => $box->id,
                'page_id' => $validated['page_id'],
                'type' => 'object',
                'file_url' => $path,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function update(Request $request, BoundingBox $box)
    {
        $validated = $request->validate([
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
        ]);

        $box->update($validated);

        return response()->json(['success' => true]);
    }

    public function delete(BoundingBox $box)
    {
        try {
            $audios = $box->audios;
            foreach ($audios as $audio) {
                if ($audio->file_url && Storage::disk('public')->exists($audio->file_url)) {
                    Storage::disk('public')->delete($audio->file_url);
                }
                $audio->delete();
            }

            $box->delete();

            return response()->json([
                'success' => true,
                'message' => 'Anotasi dan audio terkait berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus anotasi: ' . $e->getMessage()
            ], 500);
        }
    }
}
