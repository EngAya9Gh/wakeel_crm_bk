<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Clients;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientContactController extends Controller
{
    public function index(Client $client): JsonResponse
    {
        return response()->json($client->contacts);
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'position' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
        ]);

        if ($validated['is_primary'] ?? false) {
            $client->contacts()->update(['is_primary' => false]);
        }

        $contact = $client->contacts()->create($validated);

        return response()->json(['message' => 'تم إضافة جهة الاتصال بنجاح', 'contact' => $contact], 201);
    }

    public function update(Request $request, Client $client, ClientContact $contact): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:50',
            'email' => 'nullable|email|max:255',
            'position' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
        ]);

        if ($validated['is_primary'] ?? false) {
            $client->contacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }

        $contact->update($validated);

        return response()->json(['message' => 'تم تحديث جهة الاتصال بنجاح', 'contact' => $contact]);
    }

    public function destroy(Client $client, ClientContact $contact): JsonResponse
    {
        $contact->delete();
        return response()->json(['message' => 'تم حذف جهة الاتصال بنجاح']);
    }

    /**
     * Merge a contact from one client to another
     * Example: A new lead was created by WhatsApp webhook. The agent wants to merge 
     * this lead into an existing company "Al-Wakeel".
     */
    public function mergeContact(Request $request, Client $sourceClient): JsonResponse
    {
        $request->validate([
            'target_client_id' => 'required|exists:clients,id',
            'contact_id' => 'required|exists:client_contacts,id'
        ]);

        $targetClient = Client::findOrFail($request->target_client_id);
        $contact = ClientContact::where('client_id', $sourceClient->id)
                                ->where('id', $request->contact_id)
                                ->firstOrFail();

        DB::beginTransaction();
        try {
            // 1. Move contact to target client
            $contact->update(['client_id' => $targetClient->id]);

            // 2. Move any tickets belonging to the source client to target client
            $sourceClient->tickets()->update(['client_id' => $targetClient->id]);

            // 3. Move any evaluations
            $sourceClient->evaluations()->update(['client_id' => $targetClient->id]);

            // 4. (Optional) Delete source client if it has no more contacts
            if ($sourceClient->contacts()->count() === 0) {
                $sourceClient->delete();
            }

            DB::commit();

            // Here we could optionally fire a Webhook to the Provider
            // Http::post('provider-url/webhooks/merge', ['new_client_id' => $targetClient->id, 'phone' => $contact->phone]);

            return response()->json([
                'success' => true,
                'message' => 'تم دمج جهة الاتصال بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
