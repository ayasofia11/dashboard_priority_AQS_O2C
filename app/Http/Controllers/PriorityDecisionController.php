<?php

namespace App\Http\Controllers;

use App\Models\{OrderPriorityEvaluation, OrderPriorityDecision};
use Illuminate\Http\Request;

class PriorityDecisionController extends Controller
{
    public function store(Request $request, OrderPriorityEvaluation $evaluation)
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:accepted,overridden'],
            'final_priority_level' => ['required_if:decision,overridden', 'nullable', 'in:critique,urgente,prioritaire,normale,bloquee'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $decision = OrderPriorityDecision::updateOrCreate(
            ['evaluation_id' => $evaluation->id],
            [
                'decision' => $validated['decision'],
                'final_priority_level' => $validated['decision'] === 'overridden' ? $validated['final_priority_level'] : null,
                'comment' => $validated['comment'] ?? null,
                'decided_by' => auth()->id(),
                'decided_at' => now(),
            ]
        );

        return response()->json($decision);
    }
}
