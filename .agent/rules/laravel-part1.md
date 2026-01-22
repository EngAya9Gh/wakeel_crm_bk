---
trigger: always_on
---

# Laravel Development Instructions

> **Purpose**: Guidelines for AI-assisted Laravel development. Follow these rules to produce clean, maintainable, and performant PHP applications following Laravel best practices.

---

## 1. Philosophy & Conventions

### 1.1 Laravel Way
- **Convention over Configuration**: Follow Laravel's conventions before customizing.
- **Eloquent First**: Use Eloquent ORM; raw queries only for complex optimizations.
- **Service Container**: Leverage dependency injection throughout.
- **Facades vs Injection**: Prefer injection in classes; facades acceptable in Blade views.

### 1.2 Code Style
- Follow **PSR-12** coding standards.
- Use **Laravel Pint** for automatic formatting.
- Enable strict types: `declare(strict_types=1);`

---

## 2. Clean Code Principles

> [!CAUTION]
> These principles are **MANDATORY**. Violating them produces technical debt and maintenance nightmares.

### 2.1 Anti-Patterns to AVOID

#### ❌ Fat Controllers
```php
// BAD: Controller doing everything
public function store(Request $request)
{
    $validated = $request->validate([...]);
    
    $user = User::create($validated);
    $user->profile()->create([...]);
    
    Mail::to($user)->send(new WelcomeEmail());
    
    $subscription = Stripe::createSubscription($user);
    $user->update(['stripe_id' => $subscription->id]);
    
    Log::info('User created', ['id' => $user->id]);
    
    return response()->json($user);
}

// GOOD: Thin controller, delegating to services
public function store(StoreUserRequest $request): JsonResponse
{
    $user = $this->userService->register($request->validated());
    
    return new UserResource($user);
}
```

#### ❌ God Models (Models with 1000+ lines)
```php
// BAD: Model with business logic, queries, formatters, everything
class User extends Model
{
    public function calculateMonthlyRevenue() { ... }
    public function sendPasswordReset() { ... }
    public function generateInvoicePdf() { ... }
    public function syncWithCrm() { ... }
    // 50+ more methods...
}

// GOOD: Model only for data structure, relationships, scopes
class User extends Model
{
    // Relationships, scopes, accessors only
    // Business logic in Services/Actions
}
```

#### ❌ N+1 Query Problems
```php
// BAD: Causes N+1 queries
$users = User::all();
foreach ($users as $user) {
    echo $user->posts->count(); // Query per user!
}

// GOOD: Eager load relationships
$users = User::withCount('posts')->get();
foreach ($users as $user) {
    echo $user->posts_count; // No additional queries
}
```

#### ❌ Hardcoded Values
```php
// BAD: Magic numbers and strings
if ($user->role === 'admin') { ... }
if ($order->status === 2) { ... }

// GOOD: Use constants or enums
if ($user->role === UserRole::ADMIN) { ... }
if ($order->status === OrderStatus::PROCESSING) { ... }
```

#### ❌ Massive Conditionals
```php
// BAD: Complex nested conditions
if ($user->isActive()) {
    if ($user->hasSubscription()) {
        if ($user->subscription->isValid()) {
            if ($user->subscription->plan === 'premium') {
                // Do something
            }
        }
    }
}

// GOOD: Early returns and extracted methods
if (!$user->isActive()) {
    return;
}

if (!$user->hasPremiumAccess()) {
    return;
}

// Do something
```

### 2.2 Clean Code Rules

#### Single Responsibility
```php
// Each class has ONE reason to change

// UserService: Only user CRUD operations
// UserNotificationService: Only user notifications
// UserReportService: Only user reporting
```

#### Meaningful Names
```php
// BAD
$d = User::find($id);
$arr = [];
function calc($x, $y) { ... }

// GOOD
$user = User::find($userId);
$activeSubscriptions = [];
function calculateOrderTotal(Order $order, array $discounts): float { ... }
```

#### Small Functions (Max 20 lines)
```php
// BAD: 100+ line function

// GOOD: Break into smaller, named functions
public function processOrder(Order $order): void
{
    $this->validateInventory($order);
    $this->reserveItems($order);
    $this->processPayment($order);
    $this->sendConfirmation($order);
}
```

#### Return Types & Type Hints
```php
// ALWAYS use type hints
public function findActive(int $limit = 10): Collection
{
    return User::active()->limit($limit)->get();
}
```

### 2.3 Avoid These Common Mistakes

| Mistake | Problem | Solution |
|---------|---------|----------|
| Using `$request->all()` | Mass assignment vulnerability | Use `$request->validated()` |
| Queries in Blade views | N+1, hard to debug | Prepare data in controller |
| Business logic in migrations | Migrations should be reversible | Use seeders/commands |
| Catching `Exception` | Hides real errors | Catch specific exceptions |
| Not using transactions | Data inconsistency | Wrap related writes in `DB::transaction()` |
| Storing files in `public/` | Security risk, no CDN | Use `Storage` facade |
| Hardcoded API keys | Security breach | Use `.env` and `config()` |

---

## 3. Design Patterns in Laravel

### 3.1 Repository Pattern
```php
// Interface
namespace App\Repositories\Contracts;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;
    public function findActive(): Collection;
    public function create(array $data): Product;
    public function update(Product $product, array $data): Product;
    public function delete(Product $product): bool;
}

// Implementation
namespace App\Repositories;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly Product $model
    ) {}

    public function findById(int $id): ?Product
    {
        return $this->model->find($id);
    }

    public function findActive(): Collection
    {
        return $this->model->active()->with('category')->get();
    }

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }
}

// Binding in ServiceProvider
$this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);

// Usage in Service
class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}
}
```

### 3.2 Service Pattern
```php
namespace App\Services;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PaymentService $paymentService,
        private readonly InventoryService $inventoryService,
        private readonly NotificationService $notificationService
    ) {}

    public function placeOrder(User $user, array $items): Order
    {
        return DB::transaction(function () use ($user, $items) {
            // 1. Validate inventory
            $this->inventoryService->validateAvailability($items);
            
            // 2. Create order
            $order = $this->orderRepository->create([
                'user_id' => $user->id,
                'status' => OrderStatus::PENDING,
                'total' => $this->calculateTotal($items),
            ]);
            
            // 3. Attach items
            $order->items()->createMany($items);
            
            // 4. Reserve inventory
            $this->inventoryService->reserve($items);
            
            // 5. Process payment
            $this->paymentService->charge($user, $order->total);
            
            // 6. Update status
            $order->update(['status' => OrderStatus::CONFIRMED]);
            
            // 7. Send notification (async)
            $this->notificationService->orderConfirmed($order);
            
            return $order;
        });
    }
}
```

### 3.3 Action Pattern (Single Responsibility)
```php
namespace App\Actions\Order;

class CalculateOrderTotalAction
{
    public function execute(Order $order): float
    {
        $subtotal = $order->items->sum(fn ($item) => $item->price * $item->quantity);
        $discount = $this->calculateDiscount($order);
        $tax = $this->calculateTax($subtotal - $discount);
        
        return $subtotal - $discount + $tax;
    }
    
    private function calculateDiscount(Order $order): float
    {
        // Discount logic
    }
    
    private function calculateTax(float $amount): float
    {
        return $amount * 0.1; // 10% tax
    }
}

// Usage
$total = app(CalculateOrderTotalAction::class)->execute($order);
```

### 3.4 Strategy Pattern
```php
// Interface
namespace App\Services\Payment\Contracts;

interface PaymentGatewayInterface
{
    public function charge(float $amount, array $details): PaymentResult;
    public function refund(string $transactionId, float $amount): RefundResult;
}

// Implementations
class StripeGateway implements PaymentGatewayInterface
{
    public function charge(float $amount, array $details): PaymentResult
    {
        // Stripe-specific implementation
    }
}

class PayPalGateway implements PaymentGatewayInterface
{
    public function charge(float $amount, array $details): PaymentResult
    {
        // PayPal-specific implementation
    }
}

// Factory
class PaymentGatewayFactory
{
    public function make(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'stripe' => app(StripeGateway::class),
            'paypal' => app(PayPalGateway::class),
            default => throw new InvalidArgumentException("Unknown gateway: {$gateway}"),
        };
    }
}

// Usage
$gateway = $factory->make($user->preferred_gateway);
$result = $gateway->charge($amount, $details);
```

### 3.5 Observer Pattern
```php
namespace App\Observers;

class OrderObserver
{
    public function created(Order $order): void
    {
        // Log order creation
        activity()->causedBy($order->user)
            ->performedOn($order)
            ->log('Order created');
    }
    
    public function updated(Order $order): void
    {
        if ($order->wasChanged('status')) {
            event(new OrderStatusChanged($order));
        }
    }
    
    public function deleted(Order $order): void
    {
        // Release inventory
        $order->items->each(fn ($item) => 
            $this->inventoryService->release($item->product_id, $item->quantity)
        );
    }
}

// Register in AppServiceProvider
Order::observe(OrderObserver::class);
```

### 3.6 DTO Pattern (Data Transfer Objects)
```php
namespace App\DTOs;

readonly class CreateProductDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public float $price,
        public int $categoryId,
        public bool $isActive = true,
        public ?array $metadata = null,
    ) {}
    
    public static function fromRequest(StoreProductRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description', ''),
            price: (float) $request->validated('price'),
            categoryId: (int) $request->validated('category_id'),
            isActive: $request->validated('is_active', true),
            metadata: $request->validated('metadata'),
        );
    }
    
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->categoryId,
            'is_active' => $this->isActive,
            'metadata' => $this->metadata,
        ];
    }
}

// Usage in Controller
$dto = CreateProductDTO::fromRequest($request);
$product = $this->productService->create($dto);
```
## 11. Senior Architectural & Security Standards (Added)

### 11.1 Mass Assignment Security
- **Whitelist over Blacklist**: Always prefer `protected $fillable` over `protected $guarded`. This ensures that new sensitive columns added to the database are NOT automatically mass-assignable.
- **Strict Input**: Never pass `request()->all()` to a model. Use `request()->validated()` or a filtered array.

### 11.2 Repository Purity
- **One Entity per Repository**: A Repository must only interact with its designated Model.
- **No Cross-Entity Logic**: Do not update a Client's status or log a Timeline event inside an `InvoiceRepository`. This creates High Coupling and makes the code hard to maintain.

### 11.3 Service Layer Orchestration
- **The Orchestra Conductor**: The Service layer is responsible for coordinating multiple repositories and models.
- **Atomic Transactions**: When an operation affects multiple entities (e.g., Creating an Invoice AND updating a Client), always wrap the logic in a `DB::transaction()` within the Service method. This ensures Data Consistency—either everything succeeds, or everything is rolled back.
