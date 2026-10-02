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
     * Provider calls this to sync a contact.
     * If exists, returns client_id. If not, creates a new lead and returns client_id.
     * Body: { "phone": "099999999", "name": "WhatsApp Lead" }
     */
    public function syncContact(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'name' => 'sometimes|string'
        ]);

        $phone = $request->input('phone');
        $name = $request->input('name', 'عميل واتساب محتمل');

        // Check in ClientContacts
        $contact = ClientContact::where('phone', $phone)->first();
        if ($contact) {
            return response()->json([
                'is_new' => false,
                'client_id' => $contact->client_id,
                'contact_id' => $contact->id,
                'name' => $contact->name,
            ]);
        }

        // Check in Clients
        $client = Client::where('phone', $phone)->first();
        if ($client) {
            return response()->json([
                'is_new' => false,
                'client_id' => $client->id,
                'contact_id' => null,
                'name' => $client->name,
            ]);
        }

        // Not found -> Create New Lead
        $tenantId = 1; // Or from provider's token

        DB::beginTransaction();
        try {
            // Fetch default source and status
            $source = \App\Models\Source::where('name', 'واتساب')->first();
            $status = \App\Models\ClientStatus::where('is_default', true)->first();

            $client = Client::create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'phone' => $phone,
                'status_id' => $status ? $status->id : 1,
                'source_id' => $source ? $source->id : null,
                'priority' => 'medium',
            ]);

            $contact = ClientContact::create([
                'tenant_id' => $tenantId,
                'client_id' => $client->id,
                'name' => $name,
                'phone' => $phone,
                'is_primary' => true,
            ]);

            DB::commit();

            return response()->json([
                'is_new' => true,
                'client_id' => $client->id,
                'contact_id' => $contact->id,
                'name' => $name,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
