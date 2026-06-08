# ERP Integration & Stock Management (Scenario 1)

> **Status:** Implemented (Phase 1)
> **Driver:** Mock (Default)

This module integrates the CRM with an external ERP system. **The ERP is the single source of truth for stock management.** The CRM only displays stock levels and syncs invoices/clients to the ERP.

## 1. Configuration (config/erp.php)

You can configure the active driver and connection details in `.env`:

```ini
ERP_DRIVER=mock          # Options: mock, odoo, qoyod (future)
ERP_URL=https://api.erp.com
ERP_API_KEY=your-api-key
ERP_SYNC_INTERVAL=15     # Minutes
```

## 2. Architecture

- **Database:**
  - `products`: Contains `erp_id` and cached `stock_quantity`.
  - `erp_sync_logs`: Logs all sync attempts (success/failure).
- **Service Layer:** `App\Services\Integrations\ERP\ErpService`
- **Interface:** `App\Services\Integrations\ERP\Contracts\ErpProviderInterface`

## 3. Usage

### A. Check Stock (Real-time)
GET `/api/v1/stock/products/{id}`

- Fetches live stock from ERP.
- Updates local cache (`stock_quantity`).
- Returns JSON with stock details.

### B. Invoice Sync (Automatic)
When an invoice is created via `POST /api/v1/invoices`:

1. Invoice is created locally in CRM.
2. `ErpService::syncInvoice($invoice)` is called automatically.
3. Data is pushed to ERP.
4. If successful, `erp_id` and `erp_sync_status` are updated.
5. If failed, error is logged in `erp_sync_logs`.

### C. Mobile App Features

#### Scan Product (Barcode/SKU)
`GET /api/v1/stock/scan?sku=ABC-123`

- Searches for product by SKU or ERP ID locally.
- Fetches real-time stock from ERP.
- Returns product details + stock.

#### Validate Order Stock
`POST /api/v1/stock/validate`

**Request:**
```json
{
  "items": [
    { "sku": "PROD-1", "quantity": 10 },
    { "sku": "PROD-2", "quantity": 5 }
  ]
}
```

**Response (Success):**
```json
{ "success": true, "message": "جميع المنتجات متوفرة" }
```

**Response (Failure):**
```json
{
  "success": false,
  "message": "بعض المنتجات غير متوفرة",
  "unavailable_items": [
    { "sku": "PROD-2", "requested": 5, "available": 2, "message": "Insufficient stock" }
  ]
}
```

## 4. How to Add a Real ERP Driver?

1. Create a new class: `App\Services\Integrations\ERP\Drivers\OdooErpDriver`.
2. Implement `ErpProviderInterface`.
3. Bind it in `AppServiceProvider` based on config.
