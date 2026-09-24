<?php

declare(strict_types=1);

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use Illuminate\Support\Str;

class TicketService
{
    /**
     * Create a new ticket with automatic number generation and SLA calculation.
     */
    public function createTicket(array $data): Ticket
    {
        $data['ticket_number'] = $this->generateTicketNumber();

        // Calculate SLA if a category is selected
        if (!empty($data['category_id'])) {
            $category = TicketCategory::find($data['category_id']);
            if ($category && $category->sla_hours) {
                $data['sla_due_at'] = now()->addHours($category->sla_hours);
            }
        }

        return Ticket::create($data);
    }

    /**
     * Add a message/reply to an existing ticket.
     */
    public function addMessage(Ticket $ticket, array $data): TicketMessage
    {
        $message = $ticket->messages()->create($data);

        // Update ticket status based on who replied
        if (empty($data['is_internal'])) {
            if ($data['user_id']) {
                // Agent replied
                $ticket->update(['status' => 'pending_client']);
            } else {
                // Client replied (via public link or Whatsapp)
                $ticket->update(['status' => 'open']);
            }
        }

        return $message;
    }

    /**
     * Generate a unique numeric ticket number like #10452
     */
    private function generateTicketNumber(): string
    {
        // Try to find the highest ticket number and increment it, or start from 10000
        $latestTicket = Ticket::orderBy('id', 'desc')->first();
        
        if ($latestTicket && is_numeric(str_replace('#', '', $latestTicket->ticket_number))) {
            $lastNumber = (int) str_replace('#', '', $latestTicket->ticket_number);
            return '#' . ($lastNumber + 1);
        }
        
        return '#10001';
    }
}
