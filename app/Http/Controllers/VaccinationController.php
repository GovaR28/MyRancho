<?php

namespace App\Http\Controllers;

use App\Models\Vaccination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VaccinationController extends Controller
{
    /**
     * Clases de vacuna disponibles para seleccionar en el formulario.
     */
    private const VACCINE_CLASSES = [
        'Viral',
        'Bacteriana',
        'Polivalente / mixta',
        'Antiparasitaria',
        'Toxoide',
        'Combinada (viral + bacteriana)',
    ];

    public function index(Request $request): Response
    {
        $vaccinations = $request->user()->vaccinations()
            ->with('lote:id,name')
            ->withCount('animals')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(fn ($where) => $where
                    ->where('vaccine_name', 'like', $term)
                    ->orWhere('vaccine_class', 'like', $term)
                    ->orWhere('vaccinator', 'like', $term)
                    ->orWhereHas('lote', fn ($lote) => $lote->where('name', 'like', $term)));
            })
            ->when($request->filled('clase'), fn ($query) => $query->where('vaccine_class', $request->input('clase')))
            ->when($request->filled('alcance'), fn ($query) => $query->where('scope', $request->input('alcance')))
            ->when($request->filled('desde'), fn ($query) => $query->whereDate('vaccination_date', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn ($query) => $query->whereDate('vaccination_date', '<=', $request->input('hasta')))
            ->orderByDesc('vaccination_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $scopeLabels = [
            'todo' => 'Todo el ganado',
            'lote' => 'Lote',
            'grupo' => 'Grupo de vacas',
            'individual' => 'Animal individual',
        ];

        $filterOptions = [
            'clase' => collect([...self::VACCINE_CLASSES, 'Otra'])->map(fn ($class) => ['value' => $class, 'label' => $class])->values(),
            'alcance' => collect($scopeLabels)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
        ];

        return Inertia::render('Vacunaciones/Index', [
            'vaccinations' => $vaccinations,
            'vaccineClasses' => [...self::VACCINE_CLASSES, 'Otra'],
            'filterOptions' => $filterOptions,
            'vaccineSuggestions' => $request->user()->vaccinations()
                ->distinct()
                ->orderBy('vaccine_name')
                ->pluck('vaccine_name'),
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        return response()->json($this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $animalIds = $this->resolveAnimalIds($request, $data);

        DB::transaction(function () use ($request, $data, $animalIds) {
            $vaccination = $request->user()->vaccinations()->create(Arr::except($data, 'animal_ids'));
            $vaccination->animals()->attach($animalIds);
        });

        return redirect()->route('vacunaciones.index')->with('success', 'Vacunación registrada correctamente.');
    }

    public function show(Request $request, int $vaccination): JsonResponse
    {
        $record = $request->user()->vaccinations()
            ->with('lote:id,name')
            ->withCount('animals')
            ->findOrFail($vaccination);

        $record->load([
            'animals' => fn ($query) => $query->withTrashed()->with(['finca:id,name,deleted_at', 'ventas:id,sale_date']),
        ]);

        return response()->json(['vaccination' => $record]);
    }

    public function edit(Request $request, int $vaccination): JsonResponse
    {
        $record = $request->user()->vaccinations()
            ->with('lote:id,name')
            ->findOrFail($vaccination);

        $record->load([
            'animals' => fn ($query) => $query->withTrashed()->orderBy('ear_tag'),
        ]);

        return response()->json([
            'vaccination' => $record,
            ...$this->formData($request, $record),
        ]);
    }

    public function update(Request $request, int $vaccination): RedirectResponse
    {
        $record = $request->user()->vaccinations()->findOrFail($vaccination);
        $data = $this->validatedData($request);
        $animalIds = $this->resolveAnimalIds($request, $data, $record);

        DB::transaction(function () use ($record, $data, $animalIds) {
            $record->update(Arr::except($data, 'animal_ids'));
            $record->animals()->sync($animalIds);
        });

        return redirect()->route('vacunaciones.index')->with('success', 'Vacunación actualizada correctamente.');
    }

    public function destroy(Request $request, int $vaccination): RedirectResponse
    {
        $record = $request->user()->vaccinations()->findOrFail($vaccination);

        DB::transaction(function () use ($record) {
            $record->animals()->detach();
            $record->delete();
        });

        return redirect()->route('vacunaciones.index')->with('success', 'Registro de vacunación eliminado.');
    }

    /**
     * Opciones del formulario: lotes, animales vacunables y clases de vacuna.
     */
    private function formData(Request $request, ?Vaccination $vaccination = null): array
    {
        $user = $request->user();

        $vaccinatedIds = $vaccination
            ? $vaccination->animals()->withTrashed()->pluck('animals.id')
            : collect();

        $animals = $user->animals()
            ->withTrashed()
            ->where(function ($query) use ($vaccinatedIds) {
                $query->whereNull('animals.deleted_at')->whereDoesntHave('ventas');
                if ($vaccinatedIds->isNotEmpty()) {
                    $query->orWhereIn('animals.id', $vaccinatedIds);
                }
            })
            ->orderBy('ear_tag')
            ->get(['id', 'ear_tag', 'name', 'sex', 'breed']);

        return [
            'vaccination' => $vaccination,
            'lotes' => $user->lotes()
                ->withCount('animals')
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'animals_count']),
            'animals' => $animals,
        ];
    }

    private function validatedData(Request $request): array
    {
        $scope = $request->input('scope');

        $data = $request->validate([
            'vaccination_date' => ['required', 'date', 'before_or_equal:today'],
            'vaccine_name' => ['required', 'string', 'max:120'],
            'vaccine_class' => ['required', 'string', 'max:60'],
            'vaccinator' => ['required', 'string', 'max:120'],
            'scope' => ['required', Rule::in(array_keys(Vaccination::SCOPES))],
            'lote_id' => [
                Rule::requiredIf($scope === 'lote'),
                'nullable',
                'integer',
                Rule::exists('lotes', 'id')->where('user_id', $request->user()->id),
            ],
            'animal_ids' => [
                Rule::requiredIf(in_array($scope, ['grupo', 'individual'], true)),
                'nullable',
                'array',
                'min:1',
            ],
            'animal_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('animals', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'vaccination_date.required' => 'La fecha de la vacunación es obligatoria.',
            'vaccination_date.before_or_equal' => 'La fecha de la vacunación no puede ser futura.',
            'vaccine_name.required' => 'Indica el tipo de vacuna aplicada.',
            'vaccine_name.max' => 'El tipo de vacuna no puede superar los 120 caracteres.',
            'vaccine_class.required' => 'Selecciona la clase de vacuna.',
            'vaccine_class.max' => 'La clase de vacuna no puede superar los 60 caracteres.',
            'vaccinator.required' => 'Indica quién aplicó la vacunación.',
            'vaccinator.max' => 'El nombre del vacunador no puede superar los 120 caracteres.',
            'scope.in' => 'Selecciona el alcance de la vacunación.',
            'lote_id.required' => 'Selecciona el lote que se vacunó.',
            'lote_id.exists' => 'El lote seleccionado no existe en tu cuenta.',
            'animal_ids.required' => 'Selecciona al menos un animal.',
            'animal_ids.min' => 'Selecciona al menos un animal.',
            'animal_ids.*.exists' => 'Uno de los animales seleccionados no está registrado en tu cuenta.',
            'animal_ids.*.distinct' => 'No repitas el mismo animal en la selección.',
            'notes.max' => 'Las notas no pueden superar los 5000 caracteres.',
        ]);

        return [
            'vaccination_date' => $data['vaccination_date'],
            'vaccine_name' => $data['vaccine_name'],
            'vaccine_class' => $data['vaccine_class'],
            'vaccinator' => $data['vaccinator'],
            'scope' => $data['scope'],
            'lote_id' => $data['lote_id'] ?? null,
            'animal_ids' => array_map('intval', $data['animal_ids'] ?? []),
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * Resuelve qué animales quedan cubiertos por la vacunación según el alcance:
     * todo el ganado, un lote, un grupo seleccionado o una sola vaca.
     *
     * @return array<int, int>
     */
    private function resolveAnimalIds(Request $request, array $data, ?Vaccination $record = null): array
    {
        $user = $request->user();

        if ($data['scope'] === 'todo') {
            $ids = $user->animals()->whereDoesntHave('ventas')->pluck('id')->all();

            if ($ids === []) {
                throw ValidationException::withMessages([
                    'scope' => 'No hay animales disponibles para vacunar.',
                ]);
            }

            return $ids;
        }

        if ($data['scope'] === 'lote') {
            $lote = $user->lotes()->findOrFail($data['lote_id']);
            $ids = $lote->animals()->whereDoesntHave('ventas')->pluck('animals.id')->all();

            if ($ids === []) {
                throw ValidationException::withMessages([
                    'lote_id' => 'El lote seleccionado no tiene animales disponibles para vacunar.',
                ]);
            }

            return $ids;
        }

        $ids = array_map('intval', $data['animal_ids'] ?? []);

        if ($data['scope'] === 'individual' && count($ids) !== 1) {
            throw ValidationException::withMessages([
                'animal_ids' => 'Selecciona una sola vaca para este registro.',
            ]);
        }

        // Al editar se conservan los animales ya incluidos en el registro,
        // aunque después hayan sido archivados o vendidos.
        $attachedIds = $record?->animals()->withTrashed()->pluck('animals.id') ?? collect();

        $available = $user->animals()
            ->withTrashed()
            ->where(function ($query) use ($attachedIds) {
                $query->whereNull('animals.deleted_at')->whereDoesntHave('ventas');
                if ($attachedIds->isNotEmpty()) {
                    $query->orWhereIn('animals.id', $attachedIds);
                }
            })
            ->whereIn('animals.id', $ids)
            ->count();

        if ($available !== count($ids)) {
            throw ValidationException::withMessages([
                'animal_ids' => 'Selecciona animales de tu cuenta que no hayan salido en una venta.',
            ]);
        }

        return $ids;
    }
}
