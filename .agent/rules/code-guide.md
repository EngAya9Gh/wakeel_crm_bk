---
trigger: always_on
---

# Senior Code Guidelines: Clean Code, SOLID & Pragmatic Design

> **Purpose**: A manifesto for writing high-quality, maintainable code without falling into the trap of over-engineering. We value **clarity** over cleverness and **pragmatism** over dogmatic pattern adherence.

---

## 1. Core Philosophy: The Pragmatic Senior

### 1.1 KISS (Keep It Simple, Stupid) & YAGNI (You Aren't Gonna Need It)
- **Rule**: Do not build for a future that might never happen. Solve the current problem with the cleanest possible code.
- **Anti-Pattern**: Creating a generic `AbstractBaseService` with 5 interfaces for a simple CRUD operation.
- **Goal**: Code should be boringly strictly typed and easy to read.

### 1.2 Cognitive Load Management
- Code is read 10x more than it is written.
- Start reading a function; if you have to jump to 3 other files to understand what it does, it's too complex.
- **Abstraction Rule**: Abstract only when logic is duplicated or complex. Don't hide simple logic behind a layer of abstraction just to "look professional."

---

## 2. SOLID Principles (The Pragmatic Way)

### 2.1 Single Responsibility (SRP)
- **Practice**: A class/method should have one reason to change.
- **Pragmatism**:
  - *Good*: Extracting invoice calculation logic from `OrderController` to `InvoiceCalculator`.
  - *Over-kill*: Splitting `InvoiceCalculator` into `TaxCalculator`, `SubtotalCalculator`, `DiscountCalculator` if the logic is just `a + b - c`.

### 2.2 Open/Closed (OCP)
- **Practice**: Extend behavior without modifying existing code.
- **Pragmatism**:
  - Use **Strategy Pattern** only when you have 3+ variations of logic (e.g., Payment Gateways: Stripe, PayPal, Cash).
  - Use a simple `switch`/`match` statement if you only have 2 simple cases. Don't create a standardized Interface system for a simple boolean toggle.

### 2.3 Liskov Substitution (LSP)
- **Practice**: Subtypes must be substitutable for their base types.
- **Pragmatism**: Don't inherit if you find yourself throwing `NotImplementedException` in the child class. Use Composition instead.

### 2.4 Interface Segregation (ISP)
- **Practice**: Clients shouldn't depend on methods they don't use.
- **Pragmatism**: Don't force every class to implement an Interface if there is only one implementation. Interfaces are for **polymorphism** or **mocking in tests**, not just for "structure."

### 2.5 Dependency Inversion (DIP)
- **Practice**: Depend on abstractions, not concretions.
- **Pragmatism**: Inject your Service classes. BUT, standard Laravel logic (like Eloquent Models) doesn't always need to be wrapped in a Repository Interface unless you plan to swap MySQL for Mongo (which you probably won't).

---

## 3. Clean Code Rules

### 3.1 Naming is Everything
- **Variables**: `$daysUntilExpiration` (Good) vs `$d` (Bad).
- **Booleans**: `$isActive`, `$hasAccess`. Avoid negative names like `$isNotDisabled`.
- **Functions**: `Verb` + `Noun`. `calculateTotal()`, `fetchUser()`.

### 3.2 The Golden Rule of Returns
- **Avoid Else**: Use Guard Clauses (Early Returns) to flatten nesting.
  ```php
  // Bad
  if ($user) {
      if ($user->isActive) {
          return true;
      } else {
          return false;
      }
  }
  
  // Good (Senior)
  if (!$user || !$user->isActive) {
      return false;
  }
  return true;
  ```

### 3.3 Method Size
- A method should ideally fit on one screen.
- If a method has sections commented `// Validation`, `// Calculation`, `// Saving`, extract them into private methods: `validate()`, `calculate()`, `save()`.

---

## 4. Design Patterns (Use With Caution)

### 4.1 Strategy Pattern
- **Use when**: You have multiple distinct algorithms for a task (e.g., Export to PDF, CSV, Excel).
- **Avoid when**: You just have a simple `if ($type == 'pdf')` check.

### 4.2 Factory Pattern
- **Use when**: object creation logic is complex or conditional.
- **Avoid when**: `new Button()` is sufficient.

### 4.3 DTO (Data Transfer Objects)
- **Use when**: Passing strict data structures between layers (Controller -> Service).
- **Benefit**: array keys are vague; DTO properties with types (`public string $email`) are strict and auto-documented.

### 4.4 Repository Pattern (Laravel Context)
- **Use when**: Complex queries need to be reused across multiple services, or testing requires mocking database calls strictly.
- **Avoid when**: `User::find($id)` is perfectly fine. Don't wrap it in `UserRepository->find($id)` just to wrap it. That's useless noise.

---

## 5. Refactoring Checklist

Before merging code, ask:
1.  **Can I delete this code?** (Less code = fewer bugs).
2.  **Is this understandable to a Junior dev?** If not, simplify or comment the *Why*, not the *How*.
3.  **Did I handle edge cases?** (Nulls, empty arrays, exceptions).
4.  **Is it over-engineered?** Did I build a Ferrari to go to the grocery store?

---

## 6. Implementation Example

**Scenario**: Processing a Refund.

**Junior/Med (Messy)**:
Logic inside Controller. Mixed validation, DB commands, and email sending in one function.

**Over-Engineered (Too Much)**:
`RefundController` -> `RefundServiceInterface` -> `RefundService` -> `RefundRepositoryInterface` -> `RefundRepository` -> `PaymentGatewayFactory` -> `StripeAdapter`...

**Senior (Pragmatic)**:
`RefundController` validates request -> Calls `RefundAction` (or Service).
`RefundAction`:
1. Checks logic (can be refunded?).
2. Calls Payment Gateway (injected service).
3. Updates Database.
4. Returns result.
*Clean, testable, direct.*