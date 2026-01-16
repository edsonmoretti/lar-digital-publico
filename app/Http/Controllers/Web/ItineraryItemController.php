<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ItineraryItemController extends Controller
{
    //
    public function store(Request $request, \App\Models\Trip $trip)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'start_time' => 'nullable',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'end_time' => 'nullable',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'category' => 'nullable|string',
        ]);

        $user = \Illuminate\Support\Facades\Auth::user();

        // Authorization (same logic as TripController)
        $isFamilyAuth = ($user->family_id && $trip->user->family_id === $user->family_id);
        if ($user->type !== 'admin' && $trip->user_id !== $user->id && !$isFamilyAuth) {
            abort(403);
        }

        $validated['trip_id'] = $trip->id;
        \App\Models\ItineraryItem::create($validated);

        return back()->with('success', 'Item adicionado ao roteiro.');
    }

    public function update(Request $request, \App\Models\ItineraryItem $itineraryItem)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $trip = $itineraryItem->trip;

        // Authorization
        $isFamilyAuth = ($user->family_id && $trip->user->family_id === $user->family_id);
        if ($user->type !== 'admin' && $trip->user_id !== $user->id && !$isFamilyAuth) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'start_time' => 'nullable',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'end_time' => 'nullable',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'category' => 'nullable|string',
            'is_completed' => 'boolean'
        ]);

        $itineraryItem->update($validated);

        return back()->with('success', 'Roteiro atualizado.');
    }

    public function destroy(\App\Models\ItineraryItem $itineraryItem)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $trip = $itineraryItem->trip;

        // Authorization
        $isFamilyAuth = ($user->family_id && $trip->user->family_id === $user->family_id);
        if ($user->type !== 'admin' && $trip->user_id !== $user->id && !$isFamilyAuth) {
            abort(403);
        }

        $itineraryItem->delete();

        return back()->with('success', 'Item removido do roteiro.');
    }
}
