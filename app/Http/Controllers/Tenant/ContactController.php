<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CreateContactRequest;
use App\Http\Requests\Tenant\UpdateContactRequest;
use App\Http\Resources\Tenant\ContactResource;
use App\Models\Tenant\Contact;

class ContactController extends Controller
{
    /**
     * Get all contacts
     */
    public function index(Request $request)
    {
        $contacts = Contact::with('creator')->paginate(15);
        
        return response()->json([
            'data' => ContactResource::collection($contacts),
            'message' => 'Contacts retrieved successfully'
        ]);
    }

    /**
     * Create a new contact
     */
    public function store(CreateContactRequest $request)
    {
        $jwt = $request->attributes->get('jwt');
        
        $contact = Contact::create([
            ...$request->validated(),
            'created_by' => $jwt['sub'],
        ]);

        return response()->json([
            'data' => new ContactResource($contact->load('creator')),
            'message' => 'Contact created successfully'
        ], 201);
    }

    /**
     * Get a specific contact
     */
    public function show(Contact $contact)
    {
        return response()->json([
            'data' => new ContactResource($contact->load('creator')),
            'message' => 'Contact retrieved successfully'
        ]);
    }

    /**
     * Update a contact
     */
    public function update(Request $request, Contact $contact)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $contact->update($request->all());

        return response()->json([
            'data' => $contact->load('creator'),
            'message' => 'Contact updated successfully'
        ]);
    }

    /**
     * Delete a contact
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->json([
            'message' => 'Contact deleted successfully'
        ]);
    }
}
