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
