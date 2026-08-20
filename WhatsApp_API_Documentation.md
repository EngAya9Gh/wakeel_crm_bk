# WhatsApp Platform API Documentation

Welcome to the WhatsApp Platform API. This API allows you to integrate WhatsApp messaging, chat management, and contact synchronization directly into your CRM, ERP, or custom applications.

## Base URL
All API requests must be made to your platform's base URL.
`https://provider.wakeel.cc/api/v1`

## Authentication
All endpoints require an API Key for authentication. You can generate your API Key from the **Developer Tools** section in your dashboard.

Pass the API Key in the `Authorization` header as a Bearer token:
```http
Authorization: Bearer sk_your_api_key_here
```

---

## Channels (Routing messages)
Some messaging endpoints accept an optional `channel_id` parameter. 

**What is it?** 
If your account has multiple numbers connected (e.g., a Web QR connection and a Meta Cloud API connection), the `channel_id` tells the system which number to send the message from.

**How to get it?**
You can find your available Channel IDs in the **API Keys** section of your dashboard under the "Available Channels" table.

**What if I omit it?**
If you don't pass a `channel_id` in your request, the system will automatically route the message through your default Web QR connection.

---

## 1. Messaging

### 1.1 Send Text Message
Send a plain text message to any WhatsApp number.

- **Endpoint:** `POST /message/send`
- **Headers:** `Authorization: Bearer <API_KEY>`, `Content-Type: application/json`

**Request Body:**
```json
{
  "phone": "966500000000",
  "message": "Hello! Your order #1234 has been confirmed.",
  "channel_id": "optional-channel-id"
}
```

### 1.2 Send Media via URL (PDF, Image, Video)
Send a media file using a public URL. Ideal for sending invoices, receipts, or product images.

- **Endpoint:** `POST /message/send-media`
- **Headers:** `Authorization: Bearer <API_KEY>`, `Content-Type: application/json`

**Request Body:**
```json
{
  "phone": "966500000000",
  "type": "pdf", 
  "url": "https://yourapp.com/invoices/INV-2025-001.pdf",
  "caption": "Invoice #2025-001 — Amount: 1,500 SAR",
  "channel_id": "optional-channel-id"
}
```
*(Valid types: `pdf`, `image`, `video`, `audio`)*

### 1.3 Send Interactive List
Send a menu with multiple options (up to 10 items).

- **Endpoint:** `POST /message/send-list`
- **Headers:** `Authorization: Bearer <API_KEY>`, `Content-Type: application/json`

**Request Body:**
```json
{
  "phone": "966500000000",
  "title": "Our Services",
  "body": "Please select the service you need:",
  "buttonText": "View Services",
  "sections": [
    {
      "title": "Support",
      "rows": [
        { "id": "tech", "title": "Technical Support", "description": "Help with tech issues" },
        { "id": "billing", "title": "Billing", "description": "Invoice & payments" }
      ]
    }
  ],
  "channel_id": "optional-channel-id"
}
```

### 1.4 Send Template Message
Send a pre-approved template message (Useful for Meta Cloud API).

- **Endpoint:** `POST /templates/send`
- **Headers:** `Authorization: Bearer <API_KEY>`, `Content-Type: application/json`

**Request Body:**
```json
{
  "phone": "966500000000",
  "templateId": "your-template-id",
  "variables": {
    "name": "Ahmed",
    "order_id": "ORD-7890"
  },
  "channel_id": "optional-channel-id"
}
```

---

## 2. Chat & Inbox (CRM Integration)

Use these endpoints to build a live chat window directly inside your application.

### 2.1 List Chat Threads (Conversations)
Fetch all active customer conversations.

- **Endpoint:** `GET /chat/threads`
- **Headers:** `Authorization: Bearer <API_KEY>`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "thread_abc123",
      "customerPhone": "966500000000",
      "customerName": "Ahmed Ali",
      "lastMessage": "When will my order arrive?",
      "lastMessageAt": "2025-08-20T10:00:00Z",
      "unreadCount": 2
    }
  ]
}
```

### 2.2 Get Messages in a Thread
Fetch the message history for a specific conversation.

- **Endpoint:** `GET /chat/threads/{threadId}/messages`
- **Headers:** `Authorization: Bearer <API_KEY>`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "msg_1",
      "content": "When will my order arrive?",
      "direction": "INCOMING",
      "createdAt": "2025-08-20T10:00:00Z"
    }
  ]
}
```

### 2.3 Reply to a Thread
Send a message back to the customer in a specific thread.

- **Endpoint:** `POST /chat/threads/{threadId}/messages`
- **Headers:** `Authorization: Bearer <API_KEY>`, `Content-Type: application/json`

**Request Body:**
```json
{
  "content": "Hello! Your order will arrive tomorrow between 2-5 PM.",
  "type": "text"
}
```

---

## 3. Contacts Management

Keep your CRM contacts synced with the WhatsApp platform.

### 3.1 Create a Contact
- **Endpoint:** `POST /contacts`
- **Headers:** `Authorization: Bearer <API_KEY>`, `Content-Type: application/json`

**Request Body:**
```json
{
  "name": "Sara Mohammed",
  "phone": "966501111111",
  "tags": ["new-customer", "vip"]
}
```

### 3.2 List All Contacts
- **Endpoint:** `GET /contacts`
- **Headers:** `Authorization: Bearer <API_KEY>`

---

## 4. Webhooks (Real-Time Events)

To receive incoming messages and delivery statuses in real-time, configure your Webhook URL in the dashboard.

### 4.1 Incoming Message Payload
Sent to your Webhook URL when a customer sends a message.

```json
{
  "event": "message.incoming",
  "tenantId": "tenant_abc",
  "data": {
    "messageId": "wamid.xxx",
    "from": "966500000000",
    "customerName": "Ahmed Ali",
    "type": "text",
    "content": "I need help with my order",
    "timestamp": "2025-08-20T10:30:00Z"
  }
}
```

### 4.2 Message Status Payload
Sent when a message is Delivered or Read.

```json
{
  "event": "message.status",
  "tenantId": "tenant_abc",
  "data": {
    "messageId": "wamid.yyy",
    "to": "966500000000",
    "status": "READ",
    "timestamp": "2025-08-20T10:35:00Z"
  }
}
```

### Security
We send the `X-Webhook-Key` header with every request. You should verify this key in your server to ensure the request is authentic.
