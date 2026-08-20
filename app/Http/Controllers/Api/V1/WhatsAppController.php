<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Integrations\Contracts\WhatsAppServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Clients\ClientService;
use Illuminate\Support\Facades\Validator;

class WhatsAppController extends Controller
{
    public function __construct(
        protected WhatsAppServiceInterface $whatsAppService,
        protected ClientService $clientService
    ) {}

    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required_without:phone|integer|exists:clients,id',
            'phone' => 'required_without:client_id|string',
            'type' => 'required|in:text,media,list,template',
            
            // For text
            'message' => 'required_if:type,text|string',
            
            // For media
            'url' => 'required_if:type,media|url',
            'media_type' => 'required_if:type,media|in:pdf,image,video,audio',
            'caption' => 'nullable|string',
            
            // For list
            'title' => 'required_if:type,list|string',
            'body' => 'required_if:type,list|string',
            'buttonText' => 'required_if:type,list|string',
            'sections' => 'required_if:type,list|array',
            
            // For template
            'templateId' => 'required_if:type,template|string',
            'variables' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $phone = $request->phone;
        
        if (!$phone && $request->client_id) {
            $client = $this->clientService->findById((int) $request->client_id);
            $phone = $client->phone;
        }

        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'Client has no phone number'], 400);
        }

        $success = false;

        switch ($request->type) {
            case 'text':
                $success = $this->whatsAppService->send($phone, $request->message);
                break;
            case 'media':
                $media = ['url' => $request->url, 'type' => $request->media_type];
                $success = $this->whatsAppService->send($phone, $request->caption ?? '', $media);
                break;
            case 'list':
                $success = $this->whatsAppService->sendList(
                    $phone,
                    $request->title,
                    $request->body,
                    $request->buttonText,
                    $request->sections
                );
                break;
            case 'template':
                $success = $this->whatsAppService->sendTemplate(
                    $phone,
                    $request->templateId,
                    $request->variables ?? []
                );
                break;
        }

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Message sent successfully' : 'Failed to send message'
        ], $success ? 200 : 500);
    }

    public function threads(): JsonResponse
    {
        $threads = $this->whatsAppService->getThreads();
        
        return response()->json([
            'success' => true,
            'data' => $threads
        ]);
    }

    public function threadMessages(string $threadId): JsonResponse
    {
        $messages = $this->whatsAppService->getThreadMessages($threadId);
        
        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    public function reply(Request $request, string $threadId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'type' => 'nullable|string|in:text',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $success = $this->whatsAppService->replyToThread(
            $threadId,
            $request->content,
            $request->type ?? 'text'
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Reply sent successfully' : 'Failed to send reply'
        ], $success ? 200 : 500);
    }
}
