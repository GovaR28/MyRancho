<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoteController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Lotes/Index', [
            'lotes' => $request->user()->lotes()
                ->when($request->filled('q'), function ($query) use ($request) {
                    $term = '%'.$request->input('q').'%';
                    $query->where(fn ($where) => $where
                        ->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term));
                })
                ->withCount('animals')
                ->withCount('ventas')
                ->latest()
                ->get(),
            'animals' => $this->availableAnimals($request)->get(['id', 'ear_tag', 'name', 'sex', 'breed']),
        ]);
    }

    public function show(Request $request, int $lote): Response
    {
        $record = $request->user()->lotes()
            ->with(['animals.finca', 'animals.ventas', 'ventas'])
            ->findOrFail($lote);

        return Inertia::render('Lotes/Show', [
            'lote' => $record,
            'animals' => $this->availableAnimals($request)
                ->whereDoesntHave('lotes', fn ($query) => $query->whereKey($record->id))
                ->get(['id', 'ear_tag', 'name', 'sex', 'breed']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'animal_ids' => ['required', 'array', 'min:1'],
            'animal_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('animals', 'id')->where('user_id', $request->user()->id),
            ],
        ]);
        DB::transaction(function () use ($request, $data) {
            $this->ensureAvailableAnimals($request, $data['animal_ids']);
            $lote = $request->user()->lotes()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);
            $lote->animals()->attach($data['animal_ids']);
        });

        return redirect()->route('lotes.index')->with('success', 'Lote creado correctamente.');
    }

    public function addAnimals(Request $request, int $lote): RedirectResponse
    {
        $record = $request->user()->lotes()->findOrFail($lote);
        $data = $request->validate([
            'animal_ids' => ['required', 'array', 'min:1'],
            'animal_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('animals', 'id')->where('user_id', $request->user()->id),
            ],
        ]);
        DB::transaction(function () use ($request, $data, $record) {
            $this->ensureAvailableAnimals($request, $data['animal_ids']);
            $record->animals()->syncWithoutDetaching($data['animal_ids']);
        });

        return back()->with('success', 'Animales agregados al lote.');
    }

    public function removeAnimal(Request $request, int $lote, int $animal): RedirectResponse
    {
        $record = $request->user()->lotes()->findOrFail($lote);
        $member = $record->animals()->whereKey($animal)->whereDoesntHave('ventas')->firstOrFail();
        $record->animals()->detach($member->id);

        return back()->with('success', 'Animal retirado del lote.');
    }

    private function availableAnimals(Request $request)
    {
        return $request->user()->animals()
            ->whereDoesntHave('ventas')
            ->whereDoesntHave('lotes')
            ->orderBy('ear_tag');
    }

    private function ensureAvailableAnimals(Request $request, array $animalIds): void
    {
        $availableCount = $this->availableAnimals($request)->whereIn('id', $animalIds)->count();
        if ($availableCount !== count($animalIds)) {
            throw ValidationException::withMessages([
                'animal_ids' => 'Selecciona animales disponibles que no estén en otro lote ni hayan salido en una venta.',
            ]);
        }
    }
}
