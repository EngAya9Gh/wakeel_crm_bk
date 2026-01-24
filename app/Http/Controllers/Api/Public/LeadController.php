<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Public\StoreLeadRequest;
use App\Services\Clients\ClientService;
use Illuminate\Http\JsonResponse;

/**
 * Public Lead Controller
 * 
 * This controller handles incoming leads from external website forms.
 * It does NOT require user authentication but requires a valid API Key.
 */
class LeadController extends Controller
{
    use \App\Traits\ApiResponse;

    public function __construct(
        protected ClientService $clientService
    ) {}

    /**
     * Store a new lead from website form
     * 
     * POST /api/public/v1/leads
     * 
     * This endpoint receives lead data from:
     * - Contact Form (نموذج اتصل بنا)
     * - Landing Page Form (نموذج صفحة الهبوط)
     * 
     * @param StoreLeadRequest $request
     * @return JsonResponse
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        // Extract validated data
        $data = $request->validated();
        
        // Prepare client data structure
        $clientData = [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'company' => $data['company'] ?? null,
            'address' => $data['address'] ?? null,
            
            // Set default status (usually "جديد" - New)
            'status_id' => $this->getDefaultStatusId(),
            
            // Set source based on form type
            'source_id' => $this->getSourceIdByName($data['source']),
            
            // Default values for new leads
            'priority' => 'medium',
            'lead_rating' => 'warm',
            'source_status' => 'valid',
            
            // Timestamp
            'first_contact_at' => now(),
        ];
        
        // Create the client (lead)
        $client = $this->clientService->createClient($clientData);
        
        // If there's a message/subject, add it as a comment
        if (!empty($data['message']) || !empty($data['subject'])) {
            $this->addInitialComment($client->id, $data);
        }
        
        return $this->createdResponse([
            'lead_id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'status' => 'registered'
        ], 'تم تسجيل العميل بنجاح في النظام');
    }
    
    /**
     * Get default status ID for new leads
     * 
     * @return int
     */
    private function getDefaultStatusId(): int
    {
        // Get the default "New" status
        $status = \App\Models\ClientStatus::where('is_default', true)->first();
        
        // Fallback to first status if no default is set
        return $status?->id ?? \App\Models\ClientStatus::first()->id;
    }
    
    /**
     * Get source ID by source name
     * 
     * @param string $sourceName
     * @return int|null
     */
    private function getSourceIdByName(string $sourceName): ?int
    {
        // Map source names to database records
        $sourceMap = [
            'contact_form' => 'نموذج اتصل بنا',
            'landing_page' => 'صفحة الهبوط',
            'website_form' => 'نموذج الموقع',
        ];
        
        $dbSourceName = $sourceMap[$sourceName] ?? $sourceName;
        
        $source = \App\Models\Source::where('name', $dbSourceName)
            ->orWhere('name', 'LIKE', "%{$sourceName}%")
            ->first();
        
        return $source?->id;
    }
    
    /**
     * Add initial comment with message/subject
     * 
     * @param int $clientId
     * @param array $data
     * @return void
     */
    private function addInitialComment(int $clientId, array $data): void
    {
        $content = '';
        
        if (!empty($data['subject'])) {
            $content .= "الموضوع: {$data['subject']}\n\n";
        }
        
        if (!empty($data['message'])) {
            $content .= "الرسالة: {$data['message']}";
        }
        
        if (!empty($content)) {
            \App\Models\Comment::create([
                'client_id' => $clientId,
                'user_id' => 1, // System user (you can create a dedicated "System" user)
                'content' => trim($content),
                'outcome' => 'neutral',
            ]);
        }
    }
}
