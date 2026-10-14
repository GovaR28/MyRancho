<?php

namespace App\Http\Controllers;

use App\Models\Finca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FincaController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'fincasCount' => $user->fincas()->count(),
            'activeFincasCount' => $user->fincas()->where('is_active', true)->count(),
            'animalsCount' => $user->animals()->count(),
        ]);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Fincas/Index', [
            'fincas' => $request->user()->fincas()
                ->when($request->filled('q'), function ($query) use ($request) {
                    $term = '%'.$request->input('q').'%';
                    $query->where(fn ($where) => $where
                        ->where('name', 'like', $term)
                        ->orWhere('location', 'like', $term));
                })
                ->when($request->input('estado') === 'activa', fn ($query) => $query->where('is_active', true))
                ->when($request->input('estado') === 'inactiva', fn ($query) => $query->where('is_active', false))
                ->withCount('animals')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Fincas/Form', [
            'finca' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $finca = $request->user()->fincas()->create($this->validatedData($request));

        $returnToAnimal = $request->input('return_to_animal');
        $returnsToAnimalForm = $returnToAnimal === 'create'
            || (ctype_digit((string) $returnToAnimal) && $request->user()->animals()->whereKey($returnToAnimal)->exists());
        if ($returnToAnimal === 'index') {
            return redirect()->route('animales.index')
                ->with('success', 'Finca creada correctamente.')
                ->with('created_finca_id', $finca->id);
        }

        if ($returnsToAnimalForm) {
            $destination = $returnToAnimal === 'create'
                ? route('animales.create')
                : route('animales.edit', (int) $returnToAnimal);

            return redirect()->to($destination)
                ->with('success', 'Finca creada correctamente.')
                ->with('created_finca_id', $finca->id);
        }

        return redirect()->route('fincas.index')->with('success', 'Finca creada correctamente.');
    }

    public function edit(Request $request, int $finca): Response
    {
        return Inertia::render('Fincas/Form', [
            'finca' => $request->user()->fincas()->withCount('animals')->findOrFail($finca),
        ]);
    }

    public function update(Request $request, int $finca): RedirectResponse
    {
        $record = $request->user()->fincas()->findOrFail($finca);
        $record->update($this->validatedData($request));

        return redirect()->route('fincas.index')->with('success', 'Finca actualizada correctamente.');
    }

    public function toggleStatus(Request $request, int $finca): RedirectResponse
    {
        $record = $request->user()->fincas()->findOrFail($finca);
        $record->update(['is_active' => ! $record->is_active]);

        return back()->with('success', $record->is_active ? 'Finca activada.' : 'Finca desactivada.');
    }

    public function destroy(Request $request, int $finca): RedirectResponse
    {
        $request->user()->fincas()->findOrFail($finca)->delete();

        return redirect()->route('fincas.index')->with('success', 'Finca archivada; el historial de sus animales se conservó.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'El nombre de la finca es obligatorio.',
            'area_hectares.numeric' => 'La extensión debe ser un número válido.',
            'area_hectares.min' => 'La extensión no puede ser negativa.',
        ]);
    }
}
