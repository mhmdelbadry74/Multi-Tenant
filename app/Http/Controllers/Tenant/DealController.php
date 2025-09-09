<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CreateDealRequest;
use App\Http\Requests\Tenant\UpdateDealRequest;
use App\Http\Resources\Tenant\DealResource;
use App\Models\Tenant\Deal;
use App\Models\Tenant\Contact;

class DealController extends Controller
{
    /**
     * Get all deals
     */
    public function index(Request $request)
    {
        $deals = Deal::with(['contact', 'assignedUser'])->paginate(15);
        
        return response()->json([
            'data' => DealResource::collection($deals),
            'message' => 'Deals retrieved successfully'
        ]);
    }

    /**
     * Create a new deal
     */
    public function store(CreateDealRequest $request)
    {
        $deal = Deal::create($request->validated());

        return response()->json([
            'data' => new DealResource($deal->load(['contact', 'assignedUser'])),
            'message' => 'Deal created successfully'
        ], 201);
    }

    /**
     * Get a specific deal
     */
    public function show(Deal $deal)
    {
        return response()->json([
            'data' => new DealResource($deal->load(['contact', 'assignedUser', 'activities'])),
            'message' => 'Deal retrieved successfully'
        ]);
    }

    /**
     * Update a deal
     */
    public function update(Request $request, Deal $deal)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:open,won,lost',
            'contact_id' => 'sometimes|exists:contacts,id',
            'assigned_to' => 'sometimes|exists:users,id',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $deal->update($request->all());

        return response()->json([
            'data' => $deal->load(['contact', 'assignedUser']),
            'message' => 'Deal updated successfully'
        ]);
    }

    /**
     * Mark deal as won
     */
    public function markWon(Deal $deal)
    {
        $deal->markAsWon();

        return response()->json([
            'data' => $deal->load(['contact', 'assignedUser']),
            'message' => 'Deal marked as won'
        ]);
    }

    /**
     * Mark deal as lost
     */
    public function markLost(Deal $deal)
    {
        $deal->markAsLost();

        return response()->json([
            'data' => $deal->load(['contact', 'assignedUser']),
            'message' => 'Deal marked as lost'
        ]);
    }

    /**
     * Delete a deal
     */
    public function destroy(Deal $deal)
    {
        $deal->delete();

        return response()->json([
            'message' => 'Deal deleted successfully'
        ]);
    }
}
