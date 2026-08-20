<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// WhatsApp Inbox Channel (All new messages)
Broadcast::channel('whatsapp.inbox', function ($user) {
    // Only authorized employees can listen to the general inbox
    return $user !== null;
});

// Specific Chat Thread Channel
Broadcast::channel('chat.{threadId}', function ($user, $threadId) {
    // Check if user has permission to view this specific thread
    return $user !== null;
});
