<?php

namespace App\Http\Controllers;

use App\Models\{PriorityModel, PriorityFactor};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriorityConfigController extends Controller
{
    public function index()
    {
        return response()->json(PriorityModel::with('factors')->get());
    }

    public function show(PriorityModel $model)
    {
        return response()->json($model->load('factors'));
    }

    public function updateThresholds(Request $request, PriorityModel $model)
    {
        $validated = $request->validate([
            'thresholds.critique' => ['required', 'numeric', 'between:0,100'],
            'thresholds.urgente' => ['required', 'numeric', 'between:0,100'],
            'thresholds.prioritaire' => ['required', 'numeric', 'between:0,100'],
        ]);

        $t = $validated['thresholds'];
        if (! ($t['critique'] > $t['urgente'] && $t['urgente'] > $t['prioritaire'])) {
            return response()->json([
                'message' => 'Les seuils doivent être strictement décroissants : critique > urgente > prioritaire.',
            ], 422);
        }

        $model->update(['thresholds' => $t]);

        return response()->json($model->fresh());
    }

    public function updateWeights(Request $request, PriorityModel $model)
    {
        $validated = $request->validate([
            'weights' => ['required', 'array', 'min:1'],
            'weights.*.factor_id' => ['required', 'integer'],
            'weights.*.weight' => ['required', 'numeric', 'between:0,100'],
        ]);

        $factorIds = collect($validated['weights'])->pluck('factor_id');
        $validFactorIds = $model->factors()->pluck('id');

        if ($factorIds->diff($validFactorIds)->isNotEmpty()) {
            return response()->json(['message' => 'Un ou plusieurs facteurs n\'appartiennent pas à ce modèle.'], 422);
        }

        if ($factorIds->count() !== $validFactorIds->count()) {
            return response()->json(['message' => 'Tous les facteurs du modele doivent etre fournis.'], 422);
        }

        $sum = collect($validated['weights'])->sum('weight');
        if (abs($sum - 100) > 0.01) {
            return response()->json(['message' => "La somme des poids doit etre egale a 100 (actuellement : {$sum})."], 422);
        }

        DB::transaction(function () use ($validated) {
            foreach ($validated['weights'] as $w) {
                PriorityFactor::where('id', $w['factor_id'])->update(['weight' => $w['weight']]);
            }
        });

        return response()->json($model->fresh('factors'));
    }

    public function updateFactorConfig(Request $request, PriorityFactor $factor)
    {
        $validated = $request->validate([
            'config_json' => ['nullable', 'array'],
        ]);

        $factor->update(['config_json' => $validated['config_json'] ?? null]);

        return response()->json($factor->fresh());
    }

    public function activate(PriorityModel $model)
    {
        DB::transaction(function () use ($model) {
            PriorityModel::where('id', '!=', $model->id)->update(['is_active' => false]);
            $model->update(['is_active' => true]);
        });

        return response()->json($model->fresh());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:20'],
            'based_on' => ['required', 'integer', 'exists:priority_models,id'],
        ]);

        $source = PriorityModel::with('factors')->findOrFail($validated['based_on']);

        $newModel = DB::transaction(function () use ($validated, $source) {
            $model = PriorityModel::create([
                'name' => $validated['name'],
                'version' => $validated['version'],
                'is_active' => false,
                'thresholds' => $source->thresholds,
            ]);

            foreach ($source->factors as $factor) {
                PriorityFactor::create([
                    'priority_model_id' => $model->id,
                    'code' => $factor->code,
                    'weight' => $factor->weight,
                    'config_json' => $factor->config_json,
                ]);
            }

            return $model;
        });

        return response()->json($newModel->fresh('factors'), 201);
    }
}
