---
trigger: always_on
---

# Database Migration & Data Integrity Guidelines

> **Purpose**: Professional guidelines for managing database schema changes and preserving data integrity in Laravel applications. This document ensures a systematic, non-destructive, and senior-level approach to evolution.

---

## 1. Migration Policy: "Append-Only Strategy"

To maintain a reliable history and ensure production safety, we follow an append-only migration strategy.

### 1.1 Never Modify Existing Migrations
- **Rule**: Once a migration has been merged/deployed, **never** edit the original file.
- **Rationale**: Modifying existing migrations breaks the state of other developers' environments and leads to `migration table` mismatches.

### 1.2 New Changes = New Migration Files
- Use descriptive names for changes:
  - Adding columns: `php artisan make:migration add_status_to_users_table --table=users`
  - Modifying columns: `php artisan make:migration change_type_in_settings_table --table=settings`
  - Adding indexes: `php artisan make:migration add_email_index_to_customers_table --table=customers`
- **Dependency**: Use the `doctrine/dbal` package if using Laravel versions older than 10 for `change()` method. (Laravel 10+ has native support).

### 1.3 Atomic Rollbacks
- Every `up()` method **must** have a corresponding `down()` method that perfectly reverses the change.
- Test rollbacks: `php artisan migrate:rollback` before committing.

---

## 2. Data Integrity & Environment Handling

We distinguish between **development/test** (volatility) and **production** (permanence).

### 2.1 Production Strategy: Data Preservation
- **No `down()` destructive actions on production**: If a migration is rolled back, ensure data is backed up or handled.
- **Data Transformation**: When renaming or splitting columns:
  1. Add the new column.
  2. Map/Copy the data from the old column to the new one (using `DB::table(...)->update(...)` within the migration).
  3. Verify data integrity.
  4. (Optional) Remove the old column in a *subsequent* separate migration.
- **Transactions**: Wrap schema changes in `DB::transaction` to ensure all-or-nothing execution where supported (PostgreSQL supports DDL transactions; MySQL supports it partially).

### 2.2 Dev/Testing Strategy: Re-seeding
- Focus on structure accuracy.
- Use `php artisan migrate:fresh --seed` to quickly align the environment with the latest structure.
- Always update **Factories** and **Seeders** immediately after a schema change to reflect the new requirements.

---

## 3. Model & Relationship Synchronization

Structural changes must be immediately reflected in the application layer.

### 3.1 Model Updates
- **Type Casting**: Update the `$casts` property in the Model if a column type changed.
- **Fillable/Guarded**: Add/Remove fields from `$fillable`.
- **Validation**: Update **FormRequests** and validation rules in controllers.

### 3.2 Relationships
- If a Foreign Key is added/modified, update the Eloquent relationship methods (`belongsTo`, `hasMany`, etc.).
- Update **Model Policies** if the structural change affects authorization logic.

---

## 4. Radical Changes (The Exception)

If a change is "Radical" (e.g., changing a Primary Key type from Integer to UUID, or merging 3 tables into 1):

1. **Step-by-Step Evolution**: Do not attempt in a single file.
2. **Phase 1**: Create new structure alongside the old.
3. **Phase 2**: Background script/Job to migrate data in chunks.
4. **Phase 3**: Switch application logic to the new structure.
5. **Phase 4**: Cleanup old structure after successful monitoring.

---

## 5. Workflow Summary

1. **Plan**: Identify the least destructive way to change the schema.
2. **Migrate**: Create a new migration file. Include `down()`.
3. **Sync**: Update Model properties (`$casts`, `$fillable`).
4. **Link**: Update Relationships in Models.
5. **Feed**: Update Factories and Seeders.
6. **Validate**: Run Feature Tests to ensure logic still holds.
7. **Production Check**: Ensure no `dropColumn` is executed without data migration/backup.