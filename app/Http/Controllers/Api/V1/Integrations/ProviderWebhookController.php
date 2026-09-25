<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderWebhookController extends Controller
{
    /**
     * Provider calls this to check if a phone number exists in CRM
     * Body: { "phone": "099999999" }
     */
    public function checkContact(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string']);
        $phone = $request->input('phone');

        // Check in ClientContacts
        $contact = ClientContact::where('phone', $phone)->first();

        if ($contact) {
            return response()->json([
                'exists' => true,
                'client_id' => $contact->client_id,
                'contact_id' => $contact->id,
                'name' => $contact->name,
            ]);
        }

        // Check in Clients (if phone is still directly on client)
        $client = Client::where('phone', $phone)->first();
        if ($client) {
            return response()->json([
                'exists' => true,
                'client_id' => $client->id,
                'contact_id' => null,
                'name' => $client->name,
            ]);
        }

        return response()->json([
            'exists' => false
        ]);
    }

    /**
     * Provider calls this to create a NEW lead/client if checkContact returns false
     * Body: { "phone": "099999999", "name": "WhatsApp Lead" }
     */
    public function createLead(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'name' => 'required|string'
        ]);

        $tenantId = 1; // Or from provider's token

        DB::beginTransaction();
        try {
            // Create a dummy client
            $client = Client::create([
                'tenant_id' => $tenantId,
                'name' => $request->name,
                'phone' => $request->phone,
                'status_id' => 1, // Default status 'New'
                'source_id' => 1, // Optional: 'WhatsApp'
            ]);

            // Create contact for it
            $contact = ClientContact::create([
                'tenant_id' => $tenantId,
                'client_id' => $client->id,
                'name' => $request->name,
                'phone' => $request->phone,
                'is_primary' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'client_id' => $client->id,
                'contact_id' => $contact->id,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
