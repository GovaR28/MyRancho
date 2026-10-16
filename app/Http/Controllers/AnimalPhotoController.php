<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnimalPhotoController extends Controller
{
    public function store(Request $request, int $animal): RedirectResponse
    {
        $record = $request->user()->animals()->findOrFail($animal);

        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'taken_at' => ['nullable', 'date', 'before_or_equal:today'],
        ], [
            'photo.required' => 'Selecciona una imagen.',
            'photo.image' => 'El archivo debe ser una imagen.',
            'photo.max' => 'La foto no puede superar los 5 MB.',
            'taken_at.before_or_equal' => 'La fecha de la foto no puede ser futura.',
        ]);

        $path = $request->file('photo')->store('animals', 'public');

        $record->addPhoto($path, $data['taken_at'] ?? null);

        return redirect()->back()->with('success', 'Foto de '.self::animalLabel($record).' actualizada.');
    }

    public function destroy(Request $request, int $animal, int $photo): RedirectResponse
    {
        $record = $request->user()->animals()->findOrFail($animal);
        $foto = $record->photos()->findOrFail($photo);

        Storage::disk('public')->delete($foto->path);
        $foto->delete();

        // Si se borró la foto actual, se toma la más reciente que quede.
        if ($record->photo_path === $foto->path) {
            $next = $record->photos()->first();
            $record->forceFill(['photo_path' => $next?->path])->saveQuietly();
        }

        $record->recalculateNextPhotoDue();

        return redirect()->back()->with('success', 'Foto eliminada del historial.');
    }

    private static function animalLabel($animal): string
    {
        return $animal->name ?: ('arete '.$animal->ear_tag);
    }
}
