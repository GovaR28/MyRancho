<?php

namespace App\Http\Controllers;

use App\Models\Propietario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropietarioController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Propietarios/Index', [
            'propietarios' => $request->user()->propietarios()
                ->when($request->filled('q'), function ($query) use ($request) {
                    $term = '%'.$request->input('q').'%';
                    $query->where(fn ($where) => $where
                        ->where('name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('relationship', 'like', $term));
                })
                ->when($request->input('estado') === 'activo', fn ($query) => $query->where('is_active', true))
                ->when($request->input('estado') === 'inactivo', fn ($query) => $query->where('is_active', false))
                ->withCount('animals')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Propietarios/Form', ['propietario' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $propietario = $request->user()->propietarios()->create($this->validatedData($request));

        $returnToAnimal = $request->input('return_to_animal');
        $returnsToAnimalForm = $returnToAnimal === 'create'
            || (ctype_digit((string) $returnToAnimal) && $request->user()->animals()->whereKey($returnToAnimal)->exists());
        if ($returnsToAnimalForm) {
            $destination = $returnToAnimal === 'create'
                ? route('animales.create')
                : route('animales.edit', (int) $returnToAnimal);

            return redirect()->to($destination)
                ->with('success', 'Propietario agregado correctamente.')
                ->with('created_propietario_id', $propietario->id);
        }

        return redirect()->route('propietarios.index')->with('success', 'Propietario agregado correctamente.');
    }

    public function edit(Request $request, int $propietario): Response
    {
        return Inertia::render('Propietarios/Form', [
            'propietario' => $request->user()->propietarios()->findOrFail($propietario),
        ]);
    }

    public function update(Request $request, int $propietario): RedirectResponse
    {
        $record = $request->user()->propietarios()->findOrFail($propietario);
        $record->update($this->validatedData($request));

        return redirect()->route('propietarios.index')->with('success', 'Propietario actualizado correctamente.');
    }

    public function toggleStatus(Request $request, int $propietario): RedirectResponse
    {
        $record = $request->user()->propietarios()->findOrFail($propietario);
        $record->update(['is_active' => ! $record->is_active]);

        return back()->with('success', $record->is_active ? 'Propietario activado.' : 'Propietario desactivado.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'El nombre del propietario es obligatorio.',
        ]);
    }
}
