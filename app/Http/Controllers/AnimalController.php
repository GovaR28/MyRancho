<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AnimalController extends Controller
{
    public function index(Request $request): Response
    {
        $animals = $request->user()->animals()
            ->with(['finca:id,name,deleted_at', 'propietario:id,name,deleted_at', 'mother:id,ear_tag,name', 'father:id,ear_tag,name', 'ventas:id,sale_date,buyer_name,total_price'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(fn ($where) => $where
                    ->where('ear_tag', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('breed', 'like', $term)
                    ->orWhere('color', 'like', $term));
            })
            ->when($request->filled('finca_id'), fn ($query) => $query->where('finca_id', $request->integer('finca_id')))
            ->when($request->filled('sex'), fn ($query) => $query->where('sex', $request->input('sex')))
            ->when($request->filled('acquisition_type'), fn ($query) => $query->where('acquisition_type', $request->input('acquisition_type')))
            ->when($request->input('estado') === 'todos', fn ($query) => $query->withTrashed())
            ->when($request->input('estado') === 'baja', fn ($query) => $query->onlyTrashed())
            ->when($request->input('estado') === 'vendido', fn ($query) => $query->whereHas('ventas'))
            ->when($request->input('estado') === 'activo', fn ($query) => $query->whereDoesntHave('ventas')->whereNull('deleted_at'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $filterOptions = [
            'fincas' => $request->user()->fincas()->orderBy('name')->get(['id', 'name'])->map(fn ($finca) => [
                'value' => $finca->id,
                'label' => $finca->name,
            ]),
        ];

        return Inertia::render('Animales/Index', [
            'animals' => $animals,
            'filterOptions' => $filterOptions,
        ]);
    }

    private function ownerOptions(User $user, ?Animal $animal = null): \Illuminate\Database\Eloquent\Collection
    {
        return $user->propietarios()
            ->withTrashed()
            ->where(function ($query) use ($animal) {
                $query->whereNull('deleted_at')->where('is_active', true);
                if ($animal?->propietario_id) {
                    $query->orWhere('id', $animal->propietario_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'relationship', 'is_active', 'deleted_at']);
    }

    public function create(Request $request): Response|JsonResponse
    {
        $data = $this->formData($request);

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Animales/Form', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->validatePedigree($request, $data);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('animals', 'public');
        }

        $offspring = $request->only([
            'offspring_sex',
            'offspring_name',
            'offspring_birth_date',
            'offspring_weight_kg',
        ]);
        DB::transaction(function () use ($request, $data, $offspring) {
            $data['ear_tag'] = empty($data['ear_tag'])
                ? Animal::nextEarTag($request->user())
                : $data['ear_tag'];
            $animal = $request->user()->animals()->create($data);

            if ($request->boolean('came_with_offspring')) {
                $request->user()->animals()->create([
                    'finca_id' => $animal->finca_id,
                    'propietario_id' => $animal->propietario_id,
                    'mother_id' => $animal->id,
                    'ear_tag' => Animal::nextEarTag($request->user()),
                    'name' => $offspring['offspring_name'] ?? null,
                    'sex' => $offspring['offspring_sex'],
                    'birth_date' => $offspring['offspring_birth_date'] ?? null,
                    'birth_weight_kg' => $offspring['offspring_weight_kg'] ?? null,
                    'acquisition_type' => 'purchased',
                    'purchase_source' => $animal->purchase_source,
                    'purchase_date' => $animal->purchase_date,
                    'seller_name' => $animal->seller_name,
                    'came_with_mother' => true,
                    'record_type' => 'comercial',
                    'notes' => 'Cría registrada junto con la compra de su madre.',
                ]);
            }
        });

        $message = $request->boolean('came_with_offspring')
            ? 'Vaca y cría registradas correctamente.'
            : 'Animal registrado correctamente.';

        return redirect()->route('animales.index')->with('success', $message);
    }

    public function edit(Request $request, int $animal): Response|JsonResponse
    {
        $record = $request->user()->animals()->with(['mother', 'father'])->findOrFail($animal);

        $data = [
            ...$this->formData($request, $record),
            'animal' => $record,
        ];

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Animales/Form', $data);
    }

    public function update(Request $request, int $animal): RedirectResponse
    {
        $record = $request->user()->animals()->findOrFail($animal);
        $data = $this->validatedData($request, $record);
        $this->validatePedigree($request, $data, $record);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('animals', 'public');
            if ($record->photo_path) {
                Storage::disk('public')->delete($record->photo_path);
            }
        }

        $record->update($data);

        return redirect()->route('animales.index')->with('success', 'Animal actualizado correctamente.');
    }

    public function darDeBaja(Request $request, int $animal): RedirectResponse
    {
        $record = $request->user()->animals()->findOrFail($animal);

        if ($record->ventas()->exists()) {
            throw ValidationException::withMessages([
                'exit_reason' => 'Este animal ya figura como vendido, así que no se puede dar de baja.',
            ]);
        }

        $data = $request->validate([
            'exit_date' => ['required', 'date', 'before_or_equal:today'],
            'exit_reason' => ['required', 'string', 'max:120'],
            'exit_notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'exit_date.required' => 'Indica la fecha de la baja.',
            'exit_date.before_or_equal' => 'La fecha de baja no puede ser futura.',
            'exit_reason.required' => 'Indica el motivo de la baja (muerte, robo, pérdida…).',
        ]);

        $record->update($data);
        $record->delete();

        return redirect()->route('animales.index')->with('success', 'Animal dado de baja; se conservaron sus vínculos y su número de arete.');
    }

    public function reactivar(Request $request, int $animal): RedirectResponse
    {
        $record = $request->user()->animals()->onlyTrashed()->findOrFail($animal);

        $record->restore();
        $record->update([
            'exit_date' => null,
            'exit_reason' => null,
            'exit_notes' => null,
        ]);

        return redirect()->route('animales.index')->with('success', 'Animal reactivado; vuelve al listado de activos.');
    }

    private function formData(Request $request, ?Animal $animal = null): array
    {
        $user = $request->user();
        $fincas = $user->fincas()
            ->withTrashed()
            ->where(function ($query) use ($animal) {
                $query->where('is_active', true);
                if ($animal) {
                    $query->orWhere('id', $animal->finca_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'is_active', 'deleted_at']);
        $mothers = $user->animals()->withTrashed()->where('sex', 'hembra')
            ->where(function ($query) use ($animal) {
                $query->whereNull('deleted_at');
                if ($animal?->mother_id) {
                    $query->orWhere('id', $animal->mother_id);
                }
            })
            ->when($animal, fn ($query) => $query->where('id', '!=', $animal->id))
            ->orderBy('ear_tag')->get(['id', 'ear_tag', 'name', 'deleted_at']);
        $fathers = $user->animals()->withTrashed()->where('sex', 'macho')
            ->where(function ($query) use ($animal) {
                $query->whereNull('deleted_at');
                if ($animal?->father_id) {
                    $query->orWhere('id', $animal->father_id);
                }
            })
            ->when($animal, fn ($query) => $query->where('id', '!=', $animal->id))
            ->orderBy('ear_tag')->get(['id', 'ear_tag', 'name', 'deleted_at']);

        return [
            'animal' => $animal,
            'fincas' => $fincas,
            'mothers' => $mothers,
            'fathers' => $fathers,
            'propietarios' => $this->ownerOptions($user, $animal),
            'nextEarTag' => Animal::nextEarTag($user),
            'nextOffspringEarTag' => Animal::nextEarTag($user, 1),
        ];
    }

    private function validatedData(Request $request, ?Animal $animal = null): array
    {
        $data = $request->validate([
            'propietario_id' => [
                'nullable',
                Rule::exists('propietarios', 'id')->where(function ($query) use ($request, $animal) {
                    $query->where('user_id', $request->user()->id)
                        ->where(function ($status) use ($animal) {
                            $status->whereNull('deleted_at')->where('is_active', true);
                            if ($animal?->propietario_id) {
                                $status->orWhere('id', $animal->propietario_id);
                            }
                        });
                }),
            ],
            'finca_id' => [
                'required',
                Rule::exists('fincas', 'id')->where(function ($query) use ($request, $animal) {
                    $query->where('user_id', $request->user()->id)
                        ->where(function ($status) use ($animal) {
                            $status->whereNull('deleted_at')->where('is_active', true);
                            if ($animal) {
                                $status->orWhere('id', $animal->finca_id);
                            }
                        });
                }),
            ],
            'ear_tag' => [
                // En el alta el arete se asigna automáticamente (consecutivo); al editar sí se permite cambiarlo.
                $animal ? 'required' : 'nullable',
                'string',
                'max:100',
                Rule::unique('animals', 'ear_tag')->where(fn ($query) => $query->where('user_id', $request->user()->id))
                    ->ignore($animal?->id),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'sex' => ['required', Rule::in(['hembra', 'macho'])],
            'acquisition_type' => [$animal ? 'nullable' : 'required', Rule::in(['born', 'purchased'])],
            'purchase_source' => ['nullable', 'required_if:acquisition_type,purchased', Rule::in(['hacienda', 'feria', 'particular'])],
            'breed' => ['nullable', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:120'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'reproduction_method' => ['nullable', Rule::in(['natural', 'insemination', 'embryo_transfer', 'other'])],
            'reproduction_method_other' => ['nullable', 'string', 'max:120', 'required_if:reproduction_method,other'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'purchase_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
            'seller_name' => ['nullable', 'string', 'max:255'],
            'age_at_purchase_months' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'came_with_mother' => ['nullable', 'boolean'],
            'came_with_offspring' => ['nullable', 'boolean'],
            'offspring_sex' => ['nullable', Rule::in(['hembra', 'macho']), 'required_if:came_with_offspring,1'],
            'offspring_name' => ['nullable', 'string', 'max:255'],
            'offspring_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'offspring_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'purchased_breed' => ['nullable', 'string', 'max:120'],
            'record_type' => ['required', Rule::in(['comercial', 'puro', 'asociacion'])],
            'registration_number' => ['nullable', 'string', 'max:120'],
            'mother_id' => [
                'nullable',
                Rule::exists('animals', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id)->where('sex', 'hembra')),
            ],
            'father_id' => [
                'nullable',
                Rule::exists('animals', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id)->where('sex', 'macho')),
            ],
            'mother_unknown' => ['nullable', 'boolean'],
            'father_unknown' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'acquisition_type.required' => 'Indica si el animal nació en la finca o fue comprado.',
            'finca_id.required' => 'Selecciona una finca activa.',
            'propietario_id.exists' => 'Selecciona un propietario activo asociado a tu cuenta.',
            'ear_tag.required' => 'El número de arete es obligatorio.',
            'ear_tag.unique' => 'Ya existe un animal con este arete en tu cuenta.',
            'sex.in' => 'Selecciona hembra o macho.',
            'purchase_source.required_if' => 'Indica dónde compraste el animal: hacienda, feria o particular.',
            'purchase_source.in' => 'Selecciona una fuente de compra válida.',
            'birth_date.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'birth_weight_kg.numeric' => 'El peso al nacer debe ser un número.',
            'reproduction_method_other.required' => 'Describe el método de reproducción.',
            'purchase_date.before_or_equal' => 'La fecha de compra no puede ser futura.',
            'mother_id.exists' => 'La madre debe ser una hembra registrada en tu cuenta.',
            'father_id.exists' => 'El padre debe ser un macho registrado en tu cuenta.',
            'offspring_ear_tag.unique' => 'Ya existe un animal con este arete en tu cuenta.',
            'offspring_ear_tag.different' => 'La cría debe tener un arete diferente al de la vaca.',
            'offspring_birth_date.before_or_equal' => 'La fecha de nacimiento de la cría no puede ser futura.',
            'photo.image' => 'El archivo debe ser una imagen.',
            'photo.max' => 'La foto no puede superar los 5 MB.',
            'offspring_ear_tag.required_if' => 'El arete de la cría es obligatorio.',
            'offspring_sex.required_if' => 'Selecciona el sexo de la cría.',
            'reproduction_method_other.required_if' => 'Describe el método de reproducción.',
        ]);

        if (
            $request->boolean('came_with_offspring')
            && ($animal || $data['acquisition_type'] !== 'purchased' || $data['sex'] !== 'hembra')
        ) {
            throw ValidationException::withMessages([
                'came_with_offspring' => 'Registra una cría en este paso solo al comprar una hembra.',
            ]);
        }

        $data = Arr::except($data, [
            'came_with_offspring',
            'offspring_ear_tag',
            'offspring_sex',
            'offspring_name',
            'offspring_birth_date',
            'offspring_weight_kg',
        ]);

        if (! empty($data['mother_unknown'])) {
            $data['mother_id'] = null;
        }
        if (! empty($data['father_unknown'])) {
            $data['father_id'] = null;
        }

        $acquisitionType = $data['acquisition_type'] ?? $animal?->acquisition_type;
        if ($acquisitionType === 'purchased') {
            $data['birth_date'] = null;
        } elseif ($acquisitionType === 'born') {
            $data['purchase_date'] = null;
        }

        return $data;
    }

    private function validatePedigree(Request $request, array $data, ?Animal $animal = null): void
    {
        if (! empty($data['mother_id']) && $data['mother_id'] === ($data['father_id'] ?? null)) {
            throw ValidationException::withMessages([
                'father_id' => 'La madre y el padre deben ser animales distintos.',
            ]);
        }

        if (! $animal) {
            return;
        }

        foreach (['mother_id', 'father_id'] as $parentField) {
            $parentId = $data[$parentField] ?? null;
            $pending = $parentId ? [$parentId] : [];
            $visited = [];
            $ancestors = $request->user()->animals()->withTrashed()
                ->get(['id', 'mother_id', 'father_id'])
                ->keyBy('id');

            while ($pending !== []) {
                $currentId = array_pop($pending);
                if ((int) $currentId === $animal->id) {
                    throw ValidationException::withMessages([
                        $parentField => 'Este parentesco crearía un ciclo en la genealogía.',
                    ]);
                }
                if (isset($visited[$currentId])) {
                    continue;
                }
                $visited[$currentId] = true;
                $ancestor = $ancestors->get($currentId);
                if ($ancestor) {
                    if ($ancestor->mother_id) {
                        $pending[] = $ancestor->mother_id;
                    }
                    if ($ancestor->father_id) {
                        $pending[] = $ancestor->father_id;
                    }
                }
            }
        }
    }
}
