---
trigger: always_on
---

### 3.7 Event Sourcing Pattern (Simplified)
```php
// Events
class OrderPlaced
{
    public function __construct(
        public readonly Order $order,
        public readonly User $user,
    ) {}
}

class OrderStatusChanged
{
    public function __construct(
        public readonly Order $order,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}

// Listeners
class SendOrderConfirmationEmail
{
    public function handle(OrderPlaced $event): void
    {
        Mail::to($event->user)->queue(new OrderConfirmationMail($event->order));
    }
}

class UpdateInventoryOnOrder
{
    public function handle(OrderPlaced $event): void
    {
        foreach ($event->order->items as $item) {
            InventoryService::decrement($item->product_id, $item->quantity);
        }
    }
}

// EventServiceProvider
protected $listen = [
    OrderPlaced::class => [
        SendOrderConfirmationEmail::class,
        UpdateInventoryOnOrder::class,
        NotifyAdminOfNewOrder::class,
    ],
];

// Dispatch
event(new OrderPlaced($order, auth()->user()));
```

### 3.8 Factory Pattern
```php
namespace App\Factories;

class NotificationFactory
{
    public function create(string $type, array $data): Notification
    {
        return match ($type) {
            'email' => new EmailNotification($data),
            'sms' => new SmsNotification($data),
            'push' => new PushNotification($data),
            'slack' => new SlackNotification($data),
            default => throw new InvalidArgumentException("Unknown notification type: {$type}"),
        };
    }
    
    public function createFromPreference(User $user, array $data): array
    {
        return collect($user->notification_preferences)
            ->map(fn ($type) => $this->create($type, $data))
            ->toArray();
    }
}
```

---

## 4. Project Structure


```
/laravel-app/
├── app/
│   ├── Console/Commands/        # Artisan commands
│   ├── Exceptions/              # Custom exceptions
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   ├── Requests/            # Form requests (validation)
│   │   └── Resources/           # API resources
│   ├── Models/                  # Eloquent models
│   ├── Policies/                # Authorization policies
│   ├── Providers/               # Service providers
│   ├── Services/                # Business logic
│   ├── Repositories/            # Data access layer (optional)
│   └── Actions/                 # Single-action classes
├── config/                      # Configuration files
├── database/
│   ├── factories/               # Model factories
│   ├── migrations/              # Database migrations
│   └── seeders/                 # Database seeders
├── resources/
│   ├── views/                   # Blade templates
│   ├── css/                     # Stylesheets
│   └── js/                      # JavaScript
├── routes/
│   ├── web.php                  # Web routes
│   ├── api.php                  # API routes
│   └── console.php              # Console routes
├── tests/
│   ├── Feature/                 # Feature tests
│   └── Unit/                    # Unit tests
└── .env                         # Environment config
```

---

## 5. Artisan Commands

### 3.1 Common Generation
```bash
# Model with migration, factory, seeder, controller, form requests
php artisan make:model Product -mfsc --requests

# API Resource Controller
php artisan make:controller Api/ProductController --api --model=Product

# Form Request
php artisan make:request StoreProductRequest

# Policy
php artisan make:policy ProductPolicy --model=Product

# Service Class (custom)
php artisan make:class Services/ProductService
```

### 3.2 Database Commands
```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration with seeders
php artisan migrate:fresh --seed

# Create migration
php artisan make:migration add_status_to_products_table
```

---

## 6. Eloquent Best Practices

### 4.1 Model Definition
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Accessors
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn () => '$' . number_format($this->price, 2));
    }
}
```

### 4.2 Query Optimization
```php
// Eager loading (prevent N+1)
$products = Product::with(['category', 'orderItems'])->get();

// Lazy eager loading
$products->load('reviews');

// Chunking for large datasets
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        // Process
    }
});

// Cursor for memory efficiency
foreach (Product::cursor() as $product) {
    // Process one at a time
}

// Specific columns
$products = Product::select(['id', 'name', 'price'])->get();
```

---

## 7. Controllers & Requests

### 5.1 Resource Controller
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $products = Product::with('category')
            ->active()
            ->paginate(20);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return response()->json([
            'message' => 'Product created successfully',
            'data' => new ProductResource($product)
        ], 201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category'));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product = $this->productService->update($product, $request->validated());

        return new ProductResource($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);
        $product->delete();

        return response()->json(['message' => 'Product deleted'], 200);
    }
}
```

### 5.2 Form Request Validation
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'category_id' => ['required', 'exists:categories,id'],
            'is_active' => ['boolean'],
            'sku' => ['required', 'string', Rule::unique('products', 'sku')],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }
}
```

---

## 8. API Resources

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'is_active' => $this->is_active,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

---

## 9. Services & Actions

### 7.1 Service Class
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($data);
            
            // Additional logic (e.g., sync inventory, notify)
            
            return $product;
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($data);
            
            return $product->fresh();
        });
    }
}
```

### 7.2 Single Action Class
```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Order;
use App\Notifications\OrderShippedNotification;

class ShipOrderAction
{
    public function execute(Order $order, string $trackingNumber): Order
    {
        $order->update([
            'status' => 'shipped',
            'tracking_number' => $trackingNumber,
            'shipped_at' => now(),
        ]);

        $order->user->notify(new OrderShippedNotification($order));

        return $order;
    }
}
```

---

## 10. Authentication & Authorization

### 8.1 Sanctum API Auth
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Login endpoint
public function login(LoginRequest $request): JsonResponse
{
    if (!Auth::attempt($request->validated())) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $token = $request->user()->createToken('api-token')->plainTextToken;

    return response()->json(['token' => $token]);
}
```

### 8.2 Policies
```php
<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->user_id || $user->isAdmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
```