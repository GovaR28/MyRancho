<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VentaController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Ventas/Index', [
            'ventas' => $request->user()->ventas()
                ->with(['lote:id,name', 'animals:id,ear_tag,name'])
                ->when($request->filled('q'), function ($query) use ($request) {
                    $term = '%'.$request->input('q').'%';
                    $query->where(fn ($where) => $where
                        ->where('buyer_name', 'like', $term)
                        ->orWhereHas('lote', fn ($lote) => $lote->where('name', 'like', $term))
                        ->orWhereHas('animals', fn ($animal) => $animal
                            ->where('ear_tag', 'like', $term)
                            ->orWhere('name', 'like', $term)));
                })
                ->when($request->filled('desde'), fn ($query) => $query->whereDate('sale_date', '>=', $request->input('desde')))
                ->when($request->filled('hasta'), fn ($query) => $query->whereDate('sale_date', '<=', $request->input('hasta')))
                ->latest('sale_date')
                ->latest('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lote_id' => [
                'nullable',
                'integer',
                Rule::exists('lotes', 'id')->where('user_id', $request->user()->id),
            ],
            'animal_ids' => [Rule::requiredIf(! $request->filled('lote_id')), 'array', 'min:1'],
            'animal_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('animals', 'id')->where('user_id', $request->user()->id),
            ],
            'sale_date' => ['required', 'date'],
            'total_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'buyer_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($request->filled('lote_id')) {
            $lote = $request->user()->lotes()->with('animals')->findOrFail($data['lote_id']);
            $animalIds = $lote->animals()
                ->whereDoesntHave('ventas')
                ->pluck('animals.id')
                ->all();
            if ($animalIds === []) {
                throw ValidationException::withMessages([
                    'lote_id' => 'Este lote no tiene animales disponibles para vender.',
                ]);
            }
            $loteId = $lote->id;
        } else {
            $animalIds = $data['animal_ids'];
            $loteId = null;
        }

        DB::transaction(function () use ($request, $data, $animalIds, $loteId) {
            $availableIds = $request->user()->animals()
                ->whereDoesntHave('ventas')
                ->whereIn('id', $animalIds)
                ->lockForUpdate()
                ->pluck('animals.id')
                ->all();
            if (count($availableIds) !== count($animalIds)) {
                throw ValidationException::withMessages([
                    'animal_ids' => 'Uno o más animales ya salieron en una venta o no están disponibles.',
                ]);
            }

            $venta = $request->user()->ventas()->create([
                'lote_id' => $loteId,
                'sale_date' => $data['sale_date'],
                'total_price' => $data['total_price'],
                'buyer_name' => $data['buyer_name'],
                'notes' => $data['notes'] ?? null,
            ]);
            $venta->animals()->attach($availableIds);
        });

        return redirect()->route('ventas.index')->with('success', 'Salida registrada correctamente.');
    }
}
