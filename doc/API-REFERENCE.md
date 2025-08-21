# API Reference - Dependency Injection Components

**Version:** 2.5.37+  
**Last Updated:** 2025-01-20  
**Author:** Mobility Trailblazers - Nicolas Estrem

## Table of Contents

1. [Container API](#container-api)
2. [Service Provider API](#service-provider-api)
3. [Service Interfaces](#service-interfaces)
4. [Repository Interfaces](#repository-interfaces)
5. [AJAX Base Classes](#ajax-base-classes)
6. [Service Classes](#service-classes)
7. [Repository Classes](#repository-classes)
8. [AJAX Layer Updates](#ajax-layer-updates)
9. [Backward Compatibility API](#backward-compatibility-api)
10. [Utility Classes](#utility-classes)
11. [Error Handling](#error-handling)
12. [Usage Examples](#usage-examples)

## Container API

### MT_Container

The main dependency injection container class.

#### Class: `MobilityTrailblazers\Core\MT_Container`

**Namespace:** `MobilityTrailblazers\Core`  
**Since:** 2.5.37

#### Static Methods

##### `get_instance(): MT_Container`

Returns the singleton container instance.

```php
$container = MT_Container::get_instance();
```

**Returns:** `MT_Container` - The container instance

#### Instance Methods

##### `bind(string $abstract, mixed $concrete = null, bool $shared = false): void`

Bind an abstract to a concrete implementation.

**Parameters:**
- `$abstract` (string) - Abstract class or interface name
- `$concrete` (mixed) - Concrete implementation (class name or closure)
- `$shared` (bool) - Whether to share the instance (singleton)

```php
// Basic binding
$container->bind('ServiceInterface', 'ServiceImplementation');

// With factory closure
$container->bind('Service', function($container) {
    return new Service($container->make('Dependency'));
});

// Shared instance
$container->bind('Service', 'ServiceClass', true);
```

##### `singleton(string $abstract, mixed $concrete = null): void`

Register a shared binding (singleton).

**Parameters:**
- `$abstract` (string) - Abstract class or interface name
- `$concrete` (mixed) - Concrete implementation

```php
$container->singleton('ServiceInterface', 'ServiceImplementation');

$container->singleton('Service', function($container) {
    return new Service($container->make('Dependency'));
});
```

##### `make(string $abstract): mixed`

Resolve a service from the container.

**Parameters:**
- `$abstract` (string) - Abstract to resolve

**Returns:** `mixed` - Resolved instance

**Throws:** `Exception` - If unable to resolve

```php
$service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
```

##### `get(string $abstract): mixed`

Alias for `make()` method.

**Parameters:**
- `$abstract` (string) - Abstract to resolve

**Returns:** `mixed` - Resolved instance

```php
$service = $container->get('ServiceClass');
```

##### `has(string $abstract): bool`

Check if a binding exists.

**Parameters:**
- `$abstract` (string) - Abstract to check

**Returns:** `bool` - True if binding exists

```php
if ($container->has('OptionalService')) {
    $service = $container->make('OptionalService');
}
```

##### `register_provider(MT_Service_Provider|string $provider): void`

Register a service provider.

**Parameters:**
- `$provider` (MT_Service_Provider|string) - Service provider instance or class name

```php
$container->register_provider(new MT_Custom_Provider($container));
$container->register_provider('MobilityTrailblazers\Providers\MT_Custom_Provider');
```

##### `flush(): void`

Clear all bindings and instances. Useful for testing.

```php
$container->flush();
```

#### Private Methods

##### `build(string $abstract): mixed`

Build an instance from the container.

##### `instantiate(string $class): object`

Instantiate a class with automatic dependency injection.

##### `resolve_parameter(ReflectionParameter $parameter): mixed`

Resolve a constructor parameter.

##### `is_shared(string $abstract): bool`

Check if a binding is shared (singleton).

## Service Provider API

### MT_Service_Provider

Abstract base class for all service providers.

#### Class: `MobilityTrailblazers\Core\MT_Service_Provider`

**Namespace:** `MobilityTrailblazers\Core`  
**Since:** 2.5.37  
**Abstract:** Yes

#### Constructor

##### `__construct(MT_Container $container)`

**Parameters:**
- `$container` (MT_Container) - Container instance

#### Abstract Methods

##### `register(): void`

Register services with the container. Must be implemented by child classes.

```php
public function register() {
    $this->singleton('ServiceInterface', 'ServiceImplementation');
}
```

#### Virtual Methods

##### `boot(): void`

Bootstrap services after registration. Can be overridden by child classes.

```php
public function boot() {
    // Post-registration initialization
    $service = $this->container->make('SomeService');
    $service->initialize();
}
```

#### Protected Methods

##### `bind(string $abstract, mixed $concrete = null, bool $singleton = false): void`

Helper method to bind a service.

**Parameters:**
- `$abstract` (string) - Abstract name
- `$concrete` (mixed) - Concrete implementation
- `$singleton` (bool) - Whether to register as singleton

```php
$this->bind('ServiceInterface', 'ServiceClass', true);
```

##### `singleton(string $abstract, mixed $concrete = null): void`

Helper method to register a singleton.

**Parameters:**
- `$abstract` (string) - Abstract name
- `$concrete` (mixed) - Concrete implementation

```php
$this->singleton('Service', function($container) {
    return new Service($container->make('Dependency'));
});
```

### MT_Repository_Provider

Registers all repository classes with the container.

#### Class: `MobilityTrailblazers\Providers\MT_Repository_Provider`

**Namespace:** `MobilityTrailblazers\Providers`  
**Extends:** `MT_Service_Provider`  
**Since:** 2.5.37

#### Methods

##### `register(): void`

Register repository services as singletons.

**Registered Services:**
- `MobilityTrailblazers\Repositories\MT_Evaluation_Repository`
- `MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface`
- `MobilityTrailblazers\Repositories\MT_Assignment_Repository`
- `MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface`
- `MobilityTrailblazers\Repositories\MT_Candidate_Repository`
- `MobilityTrailblazers\Interfaces\MT_Candidate_Repository_Interface`
- `MobilityTrailblazers\Repositories\MT_Audit_Log_Repository`
- `MobilityTrailblazers\Interfaces\MT_Audit_Log_Repository_Interface`

### MT_Services_Provider

Registers all service classes with the container.

#### Class: `MobilityTrailblazers\Providers\MT_Services_Provider`

**Namespace:** `MobilityTrailblazers\Providers`  
**Extends:** `MT_Service_Provider`  
**Since:** 2.5.37

#### Methods

##### `register(): void`

Register services with proper dependency injection.

**Registered Services:**
- `MobilityTrailblazers\Services\MT_Evaluation_Service`
- `MobilityTrailblazers\Interfaces\MT_Evaluation_Service_Interface`
- `MobilityTrailblazers\Services\MT_Assignment_Service`
- `MobilityTrailblazers\Interfaces\MT_Assignment_Service_Interface`
- `MobilityTrailblazers\Services\MT_Candidate_Import_Service`
- `MobilityTrailblazers\Services\MT_Diagnostic_Service`

## Service Interfaces

### MT_Service_Interface

Base interface for all service classes.

#### Interface: `MobilityTrailblazers\Interfaces\MT_Service_Interface`

**Namespace:** `MobilityTrailblazers\Interfaces`  
**Since:** 2.0.0

#### Methods

##### `process(array $data): mixed`

Process the main action.

**Parameters:**
- `$data` (array) - Input data

**Returns:** `mixed` - Result of the operation

##### `validate(array $data): bool`

Validate input data.

**Parameters:**
- `$data` (array) - Input data to validate

**Returns:** `bool` - True if valid, false otherwise

##### `get_errors(): array`

Get validation errors.

**Returns:** `array` - Array of error messages

### MT_Evaluation_Service_Interface

Interface for evaluation service operations.

#### Interface: `MobilityTrailblazers\Interfaces\MT_Evaluation_Service_Interface`

**Namespace:** `MobilityTrailblazers\Interfaces`  
**Extends:** `MT_Service_Interface`  
**Since:** 2.5.37

#### Additional Methods

##### `save_draft(array $data): int|false`

Save evaluation as draft.

**Parameters:**
- `$data` (array) - Evaluation data

**Returns:** `int|false` - Evaluation ID on success, false on failure

##### `submit_final(array $data): int|false`

Submit final evaluation.

**Parameters:**
- `$data` (array) - Evaluation data

**Returns:** `int|false` - Evaluation ID on success, false on failure

##### `get_criteria(): array`

Get evaluation criteria configuration.

**Returns:** `array` - Criteria configuration

##### `get_jury_progress(int $jury_member_id): array`

Get jury member's evaluation progress.

**Parameters:**
- `$jury_member_id` (int) - Jury member ID

**Returns:** `array` - Progress data

### MT_Assignment_Service_Interface

Interface for assignment service operations.

#### Interface: `MobilityTrailblazers\Interfaces\MT_Assignment_Service_Interface`

**Namespace:** `MobilityTrailblazers\Interfaces`  
**Extends:** `MT_Service_Interface`  
**Since:** 2.5.37

#### Additional Methods

##### `auto_assign(string $method, int $candidates_per_jury): bool`

Auto assign candidates to jury members.

**Parameters:**
- `$method` (string) - Assignment method ('balanced' or 'random')
- `$candidates_per_jury` (int) - Number of candidates per jury member

**Returns:** `bool` - True on success

##### `remove_assignment(int $jury_member_id, int $candidate_id): bool`

Remove specific assignment.

**Parameters:**
- `$jury_member_id` (int) - Jury member ID
- `$candidate_id` (int) - Candidate ID

**Returns:** `bool` - True on success

### MT_Diagnostic_Service_Interface

Interface for system diagnostic operations.

#### Interface: `MobilityTrailblazers\Interfaces\MT_Diagnostic_Service_Interface`

**Namespace:** `MobilityTrailblazers\Interfaces`  
**Extends:** `MT_Service_Interface`  
**Since:** 2.5.37

#### Methods

##### `run_health_check(): array`

Run comprehensive system health check.

**Returns:** `array` - Health check results

##### `check_database_integrity(): array`

Check database table integrity.

**Returns:** `array` - Database integrity results

##### `validate_data_consistency(): array`

Validate data consistency across tables.

**Returns:** `array` - Consistency validation results

### MT_Candidate_Import_Service_Interface

Interface for candidate import operations.

#### Interface: `MobilityTrailblazers\Interfaces\MT_Candidate_Import_Service_Interface`

**Namespace:** `MobilityTrailblazers\Interfaces`  
**Extends:** `MT_Service_Interface`  
**Since:** 2.5.37

#### Methods

##### `import_from_csv(string $file_path, array $options = []): array`

Import candidates from CSV file.

**Parameters:**
- `$file_path` (string) - Path to CSV file
- `$options` (array) - Import options

**Returns:** `array` - Import results

##### `import_from_excel(string $file_path, array $options = []): array`

Import candidates from Excel file.

**Parameters:**
- `$file_path` (string) - Path to Excel file
- `$options` (array) - Import options

**Returns:** `array` - Import results

##### `validate_import_data(array $data): array`

Validate import data before processing.

**Parameters:**
- `$data` (array) - Import data

**Returns:** `array` - Validation results

## Repository Interfaces

### MT_Repository_Interface

Base interface for all repository classes.

#### Interface: `MobilityTrailblazers\Interfaces\MT_Repository_Interface`

**Namespace:** `MobilityTrailblazers\Interfaces`  
**Since:** 2.0.0

#### Methods

##### `find(int $id): object|null`

Find a single record by ID.

**Parameters:**
- `$id` (int) - Record ID

**Returns:** `object|null` - Record object or null if not found

##### `find_all(array $args = []): array`

Find all records matching criteria.

**Parameters:**
- `$args` (array) - Query arguments

**Returns:** `array` - Array of record objects

##### `create(array $data): int|false`

Create a new record.

**Parameters:**
- `$data` (array) - Record data

**Returns:** `int|false` - Insert ID on success, false on failure

##### `update(int $id, array $data): bool`

Update an existing record.

**Parameters:**
- `$id` (int) - Record ID
- `$data` (array) - Updated data

**Returns:** `bool` - True on success, false on failure

##### `delete(int $id): bool`

Delete a record.

**Parameters:**
- `$id` (int) - Record ID

**Returns:** `bool` - True on success, false on failure

### MT_Evaluation_Repository_Interface

Extended interface for evaluation repository.

#### Interface: `MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface`

**Namespace:** `MobilityTrailblazers\Interfaces`  
**Extends:** `MT_Repository_Interface`  
**Since:** 2.5.37

#### Additional Methods

##### `find_by_jury_and_candidate(int $jury_member_id, int $candidate_id): object|null`

Find evaluation by jury member and candidate.

**Parameters:**
- `$jury_member_id` (int) - Jury member ID
- `$candidate_id` (int) - Candidate ID

**Returns:** `object|null` - Evaluation object or null

##### `get_by_jury_member(int $jury_member_id): array`

Get all evaluations by jury member.

**Parameters:**
- `$jury_member_id` (int) - Jury member ID

**Returns:** `array` - Array of evaluation objects

##### `save(array $data): int|WP_Error`

Save evaluation with validation.

**Parameters:**
- `$data` (array) - Evaluation data

**Returns:** `int|WP_Error` - Evaluation ID or error

### MT_Assignment_Repository_Interface

Extended interface for assignment repository.

#### Interface: `MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface`

**Namespace:** `MobilityTrailblazers\Interfaces`  
**Extends:** `MT_Repository_Interface`  
**Since:** 2.5.37

#### Additional Methods

##### `exists(int $jury_member_id, int $candidate_id): bool`

Check if assignment exists.

**Parameters:**
- `$jury_member_id` (int) - Jury member ID
- `$candidate_id` (int) - Candidate ID

**Returns:** `bool` - True if assignment exists

##### `get_by_jury_member(int $jury_member_id): array`

Get assignments for jury member.

**Parameters:**
- `$jury_member_id` (int) - Jury member ID

**Returns:** `array` - Array of assignment objects

##### `bulk_create(array $assignments): int`

Bulk create assignments.

**Parameters:**
- `$assignments` (array) - Array of assignment data

**Returns:** `int` - Number of assignments created

### MT_Candidate_Repository_Interface

Interface for candidate repository operations.

#### Interface: `MobilityTrailblazers\Interfaces\MT_Candidate_Repository_Interface`

**Namespace:** `MobilityTrailblazers\Interfaces`  
**Extends:** `MT_Repository_Interface`  
**Since:** 2.5.37

#### Additional Methods

##### `find_by_email(string $email): object|null`

Find candidate by email address.

**Parameters:**
- `$email` (string) - Email address

**Returns:** `object|null` - Candidate object or null

##### `get_published(): array`

Get all published candidates.

**Returns:** `array` - Array of published candidate objects

##### `search(string $query, array $filters = []): array`

Search candidates by query and filters.

**Parameters:**
- `$query` (string) - Search query
- `$filters` (array) - Additional filters

**Returns:** `array` - Array of matching candidates

### MT_Audit_Log_Repository_Interface

Interface for audit log repository operations.

#### Interface: `MobilityTrailblazers\Interfaces\MT_Audit_Log_Repository_Interface`

**Namespace:** `MobilityTrailblazers\Interfaces`  
**Extends:** `MT_Repository_Interface`  
**Since:** 2.5.37

#### Additional Methods

##### `log_action(string $action, array $data = []): int|false`

Log an action to the audit trail.

**Parameters:**
- `$action` (string) - Action performed
- `$data` (array) - Additional data

**Returns:** `int|false` - Log ID on success, false on failure

##### `get_by_user(int $user_id, int $limit = 50): array`

Get audit logs for a specific user.

**Parameters:**
- `$user_id` (int) - User ID
- `$limit` (int) - Maximum number of logs to return

**Returns:** `array` - Array of audit log objects

##### `get_by_action(string $action, int $limit = 50): array`

Get audit logs for a specific action type.

**Parameters:**
- `$action` (string) - Action type
- `$limit` (int) - Maximum number of logs to return

**Returns:** `array` - Array of audit log objects

## AJAX Base Classes

### MT_Base_Ajax

Abstract base class for all AJAX handlers providing security and utility methods.

#### Class: `MobilityTrailblazers\Ajax\MT_Base_Ajax`

**Namespace:** `MobilityTrailblazers\Ajax`  
**Since:** 2.0.0  
**Abstract:** Yes

#### Abstract Methods

##### `init(): void`

Initialize the AJAX handler. Must be implemented by child classes.

```php
public function init() {
    add_action('wp_ajax_my_action', [$this, 'handle_my_action']);
    add_action('wp_ajax_nopriv_my_action', [$this, 'handle_my_action']);
}
```

#### Security Methods

##### `verify_nonce(string $nonce_name = 'mt_ajax_nonce'): bool`

Verify WordPress nonce for AJAX requests.

**Parameters:**
- `$nonce_name` (string) - Nonce action name (default: 'mt_ajax_nonce')

**Returns:** `bool` - True if nonce is valid, false otherwise

```php
if (!$this->verify_nonce()) {
    return; // Error response already sent
}
```

##### `check_permission(string $capability): bool`

Check user capabilities for the requested action.

**Parameters:**
- `$capability` (string) - Required WordPress capability

**Returns:** `bool` - True if user has permission, false otherwise

```php
if (!$this->check_permission('mt_submit_evaluations')) {
    return; // Error response already sent
}
```

#### Response Methods

##### `success(mixed $data = null, string $message = ''): void`

Send JSON success response and terminate execution.

**Parameters:**
- `$data` (mixed) - Response data
- `$message` (string) - Success message

```php
$this->success([
    'evaluation_id' => $evaluation_id,
    'status' => 'submitted'
], __('Evaluation saved successfully.', 'mobility-trailblazers'));
```

##### `error(string $message = '', mixed $data = null): void`

Send JSON error response and terminate execution.

**Parameters:**
- `$message` (string) - Error message
- `$data` (mixed) - Additional error data

```php
$this->error(__('Validation failed.', 'mobility-trailblazers'), [
    'validation_errors' => $errors
]);
```

##### `send_json_success(mixed $data = null): void`

Alias for WordPress `wp_send_json_success()`.

##### `send_json_error(string $message = ''): void`

Alias for WordPress `wp_send_json_error()`.

#### Parameter Helper Methods

##### `get_param(string $key, mixed $default = null): mixed`

Get and sanitize a request parameter.

**Parameters:**
- `$key` (string) - Parameter key
- `$default` (mixed) - Default value if parameter not found

**Returns:** `mixed` - Sanitized parameter value

##### `get_text_param(string $key, string $default = ''): string`

Get and sanitize a text parameter using `sanitize_text_field()`.

**Parameters:**
- `$key` (string) - Parameter key
- `$default` (string) - Default value

**Returns:** `string` - Sanitized text value

##### `get_int_param(string $key, int $default = 0): int`

Get and convert parameter to integer.

**Parameters:**
- `$key` (string) - Parameter key
- `$default` (int) - Default value

**Returns:** `int` - Integer value

##### `get_float_param(string $key, float $default = 0.0): float`

Get and convert parameter to float.

**Parameters:**
- `$key` (string) - Parameter key
- `$default` (float) - Default value

**Returns:** `float` - Float value

##### `get_textarea_param(string $key, string $default = ''): string`

Get and sanitize textarea parameter using `sanitize_textarea_field()`.

**Parameters:**
- `$key` (string) - Parameter key
- `$default` (string) - Default value

**Returns:** `string` - Sanitized textarea content

##### `get_array_param(string $key, array $default = []): array`

Get array parameter with validation.

**Parameters:**
- `$key` (string) - Parameter key
- `$default` (array) - Default value

**Returns:** `array` - Array value or default

#### Validation Methods

##### `validate_required_params(array $required_params): bool`

Validate that all required parameters are present and not empty.

**Parameters:**
- `$required_params` (array) - Array of required parameter names

**Returns:** `bool` - True if all parameters present, false otherwise

```php
if (!$this->validate_required_params(['jury_member_id', 'candidate_id'])) {
    return; // Error response already sent
}
```

##### `validate_upload(array $file, array $allowed_types = ['csv'], int $max_size = null): bool|string`

Validate uploaded file for security and type compliance.

**Parameters:**
- `$file` (array) - $_FILES array element
- `$allowed_types` (array) - Allowed file extensions
- `$max_size` (int|null) - Maximum file size in bytes (default: 10MB)

**Returns:** `bool|string` - True if valid, error message string if invalid

```php
$validation_result = $this->validate_upload($_FILES['import_file'], ['csv', 'xlsx']);
if ($validation_result !== true) {
    $this->error($validation_result);
    return;
}
```

#### Exception Handling

##### `handle_exception(\Exception $e, string $context = ''): void`

Handle exceptions in AJAX methods with proper logging and user-friendly error response.

**Parameters:**
- `$e` (\Exception) - Exception object
- `$context` (string) - Context description for logging

```php
try {
    // Your AJAX logic here
} catch (\Exception $e) {
    $this->handle_exception($e, 'evaluation submission');
}
```

## Service Classes

### MT_Evaluation_Service

Service for handling evaluation business logic.

#### Class: `MobilityTrailblazers\Services\MT_Evaluation_Service`

**Namespace:** `MobilityTrailblazers\Services`  
**Implements:** `MT_Service_Interface`  
**Since:** 2.0.0  
**Updated:** 2.5.37 (Added DI support)

#### Constructor

##### `__construct(MT_Evaluation_Repository_Interface $evaluation_repository = null, MT_Assignment_Repository_Interface $assignment_repository = null)`

Constructor with dependency injection support and backward compatibility.

**Parameters:**
- `$evaluation_repository` (MT_Evaluation_Repository_Interface|null) - Optional evaluation repository
- `$assignment_repository` (MT_Assignment_Repository_Interface|null) - Optional assignment repository

#### Methods

##### `process(array $data): int|false`

Process evaluation submission.

**Parameters:**
- `$data` (array) - Evaluation data

**Returns:** `int|false` - Evaluation ID on success, false on failure

##### `save_draft(array $data): int|false`

Save evaluation as draft.

**Parameters:**
- `$data` (array) - Evaluation data

**Returns:** `int|false` - Evaluation ID on success, false on failure

##### `submit_final(array $data): int|false`

Submit final evaluation.

**Parameters:**
- `$data` (array) - Evaluation data

**Returns:** `int|false` - Evaluation ID on success, false on failure

##### `save_evaluation(array $data): int|WP_Error`

Save or update evaluation with validation.

**Parameters:**
- `$data` (array) - Evaluation data

**Returns:** `int|WP_Error` - Evaluation ID or error

##### `validate(array $data): bool`

Validate evaluation data.

**Parameters:**
- `$data` (array) - Input data

**Returns:** `bool` - True if valid, false otherwise

##### `get_errors(): array`

Get validation errors.

**Returns:** `array` - Array of error messages

##### `get_criteria(): array`

Get evaluation criteria configuration.

**Returns:** `array` - Criteria configuration

##### `get_jury_progress(int $jury_member_id): array`

Get jury member's evaluation progress.

**Parameters:**
- `$jury_member_id` (int) - Jury member ID

**Returns:** `array` - Progress data

### MT_Assignment_Service

Service for handling assignment business logic.

#### Class: `MobilityTrailblazers\Services\MT_Assignment_Service`

**Namespace:** `MobilityTrailblazers\Services`  
**Implements:** `MT_Service_Interface`  
**Since:** 2.0.0  
**Updated:** 2.5.37 (Added DI support)

#### Constructor

##### `__construct(MT_Assignment_Repository_Interface $repository = null)`

Constructor with dependency injection support.

**Parameters:**
- `$repository` (MT_Assignment_Repository_Interface|null) - Optional repository dependency

#### Methods

##### `process(array $data): mixed`

Process assignment request.

**Parameters:**
- `$data` (array) - Assignment data

**Returns:** `mixed` - Result based on assignment type

##### `remove_by_id(int $assignment_id): bool`

Remove assignment by ID (efficient method).

**Parameters:**
- `$assignment_id` (int) - Assignment ID

**Returns:** `bool` - True on success

##### `remove_assignment(int $jury_member_id, int $candidate_id): bool`

Remove assignment by jury member and candidate IDs (legacy method).

**Parameters:**
- `$jury_member_id` (int) - Jury member ID
- `$candidate_id` (int) - Candidate ID

**Returns:** `bool` - True on success

**Deprecated:** Use `remove_by_id()` for better performance

##### `validate(array $data): bool`

Validate manual assignment data.

**Parameters:**
- `$data` (array) - Input data

**Returns:** `bool` - True if valid

##### `get_errors(): array`

Get validation errors.

**Returns:** `array` - Array of error messages

##### `auto_assign(string $method, int $candidates_per_jury): bool`

Auto assign candidates to jury members.

**Parameters:**
- `$method` (string) - Assignment method ('balanced' or 'random')
- `$candidates_per_jury` (int) - Number of candidates per jury member

**Returns:** `bool` - True on success

## Repository Classes

### MT_Evaluation_Repository

Repository for evaluation data access.

#### Class: `MobilityTrailblazers\Repositories\MT_Evaluation_Repository`

**Namespace:** `MobilityTrailblazers\Repositories`  
**Implements:** `MT_Evaluation_Repository_Interface`  
**Since:** 2.0.0

#### Methods

See `MT_Evaluation_Repository_Interface` for method signatures.

### MT_Assignment_Repository

Repository for assignment data access.

#### Class: `MobilityTrailblazers\Repositories\MT_Assignment_Repository`

**Namespace:** `MobilityTrailblazers\Repositories`  
**Implements:** `MT_Assignment_Repository_Interface`  
**Since:** 2.0.0

#### Methods

See `MT_Assignment_Repository_Interface` for method signatures.

## AJAX Layer Updates

### MT_Evaluation_Ajax

AJAX handler for evaluation operations.

#### Class: `MobilityTrailblazers\Ajax\MT_Evaluation_Ajax`

**Namespace:** `MobilityTrailblazers\Ajax`  
**Extends:** `MT_Base_Ajax`  
**Since:** 2.0.0  
**Updated:** 2.5.37 (Added DI support)

#### Private Methods

##### `get_evaluation_repository(): MT_Evaluation_Repository_Interface`

Get evaluation repository from container.

**Returns:** `MT_Evaluation_Repository_Interface` - Repository instance

```php
private function get_evaluation_repository() {
    $container = MT_Plugin::container();
    return $container->make('MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface');
}
```

##### `get_assignment_repository(): MT_Assignment_Repository_Interface`

Get assignment repository from container.

**Returns:** `MT_Assignment_Repository_Interface` - Repository instance

```php
private function get_assignment_repository() {
    $container = MT_Plugin::container();
    return $container->make('MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface');
}
```

#### AJAX Actions

##### `submit_evaluation()`

Handle evaluation submission.

**AJAX Action:** `mt_submit_evaluation`  
**Permission:** `mt_submit_evaluations`  
**Nonce:** `mt_ajax_nonce`

##### `get_evaluation()`

Get evaluation data.

**AJAX Action:** `mt_get_evaluation`  
**Permission:** `mt_submit_evaluations`  
**Nonce:** `mt_ajax_nonce`

##### `save_inline_evaluation()`

Save inline evaluation from rankings grid.

**AJAX Action:** `mt_save_inline_evaluation`  
**Permission:** `mt_submit_evaluations`  
**Nonce:** `mt_ajax_nonce`

## Backward Compatibility API

### MT_Backward_Compatibility

Facade for accessing services the old way during migration.

#### Class: `MobilityTrailblazers\Legacy\MT_Backward_Compatibility`

**Namespace:** `MobilityTrailblazers\Legacy`  
**Since:** 2.5.37

#### Static Methods

##### `get_evaluation_service(): MT_Evaluation_Service`

Get Evaluation Service instance.

**Returns:** `MT_Evaluation_Service` - Service instance

```php
$service = MT_Backward_Compatibility::get_evaluation_service();
```

##### `get_assignment_service(): MT_Assignment_Service`

Get Assignment Service instance.

**Returns:** `MT_Assignment_Service` - Service instance

##### `get_evaluation_repository(): MT_Evaluation_Repository`

Get Evaluation Repository instance.

**Returns:** `MT_Evaluation_Repository` - Repository instance

##### `get_assignment_repository(): MT_Assignment_Repository`

Get Assignment Repository instance.

**Returns:** `MT_Assignment_Repository` - Repository instance

##### `get_candidate_repository(): MT_Candidate_Repository`

Get Candidate Repository instance.

**Returns:** `MT_Candidate_Repository` - Repository instance

##### `get_audit_log_repository(): MT_Audit_Log_Repository`

Get Audit Log Repository instance.

**Returns:** `MT_Audit_Log_Repository` - Repository instance

##### `get_diagnostic_service(): MT_Diagnostic_Service`

Get Diagnostic Service instance.

**Returns:** `MT_Diagnostic_Service` - Service instance

##### `get_candidate_import_service(): MT_Candidate_Import_Service`

Get Candidate Import Service instance.

**Returns:** `MT_Candidate_Import_Service` - Service instance

## Utility Classes

### MT_Logger

Centralized logging system with multiple log levels.

#### Class: `MobilityTrailblazers\Core\MT_Logger`

**Namespace:** `MobilityTrailblazers\Core`  
**Since:** 2.0.11

#### Log Level Constants

- `LEVEL_DEBUG` - Debug level logging
- `LEVEL_INFO` - Informational logging
- `LEVEL_WARNING` - Warning level logging
- `LEVEL_ERROR` - Error level logging
- `LEVEL_CRITICAL` - Critical error logging

#### Static Methods

##### `debug(string $message, array $context = []): void`

Log debug message (only in development environment).

**Parameters:**
- `$message` (string) - Log message
- `$context` (array) - Additional context data

```php
MT_Logger::debug('Processing evaluation', [
    'evaluation_id' => $evaluation_id,
    'user_id' => get_current_user_id()
]);
```

##### `info(string $message, array $context = []): void`

Log informational message.

**Parameters:**
- `$message` (string) - Log message
- `$context` (array) - Additional context data

```php
MT_Logger::info('Candidate imported successfully', [
    'candidate_id' => $candidate_id,
    'source' => 'csv_import'
]);
```

##### `warning(string $message, array $context = []): void`

Log warning message.

**Parameters:**
- `$message` (string) - Log message
- `$context` (array) - Additional context data

```php
MT_Logger::warning('Duplicate candidate detected', [
    'email' => $email,
    'existing_id' => $existing_candidate_id
]);
```

##### `error(string $message, array $context = []): void`

Log error message.

**Parameters:**
- `$message` (string) - Log message
- `$context` (array) - Additional context data

```php
MT_Logger::error('Database query failed', [
    'query' => $query,
    'error' => $wpdb->last_error
]);
```

##### `critical(string $message, array $context = []): void`

Log critical error message.

**Parameters:**
- `$message` (string) - Log message
- `$context` (array) - Additional context data

```php
MT_Logger::critical('Plugin initialization failed', [
    'exception' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

#### Specialized Logging Methods

##### `security_event(string $message, array $context = []): void`

Log security-related events.

**Parameters:**
- `$message` (string) - Security event description
- `$context` (array) - Event context data

```php
MT_Logger::security_event('Unauthorized access attempt', [
    'user_id' => get_current_user_id(),
    'requested_action' => $_REQUEST['action'],
    'ip_address' => $_SERVER['REMOTE_ADDR']
]);
```

##### `ajax_error(string $action, string $message, array $context = []): void`

Log AJAX-specific errors.

**Parameters:**
- `$action` (string) - AJAX action name
- `$message` (string) - Error message
- `$context` (array) - Additional context

```php
MT_Logger::ajax_error('mt_submit_evaluation', 'Validation failed', [
    'user_id' => get_current_user_id(),
    'validation_errors' => $errors
]);
```

### MT_Audit_Logger

Specialized logger for audit trail functionality.

#### Class: `MobilityTrailblazers\Core\MT_Audit_Logger`

**Namespace:** `MobilityTrailblazers\Core`  
**Since:** 2.0.0

#### Static Methods

##### `log_evaluation_action(string $action, int $evaluation_id, array $data = []): void`

Log evaluation-related actions.

**Parameters:**
- `$action` (string) - Action performed ('created', 'updated', 'submitted', 'deleted')
- `$evaluation_id` (int) - Evaluation ID
- `$data` (array) - Additional action data

```php
MT_Audit_Logger::log_evaluation_action('submitted', $evaluation_id, [
    'jury_member_id' => $jury_member_id,
    'candidate_id' => $candidate_id,
    'total_score' => $total_score
]);
```

##### `log_assignment_action(string $action, int $assignment_id, array $data = []): void`

Log assignment-related actions.

**Parameters:**
- `$action` (string) - Action performed ('created', 'removed', 'bulk_created')
- `$assignment_id` (int) - Assignment ID
- `$data` (array) - Additional action data

```php
MT_Audit_Logger::log_assignment_action('created', $assignment_id, [
    'jury_member_id' => $jury_member_id,
    'candidate_id' => $candidate_id,
    'assigned_by' => get_current_user_id()
]);
```

##### `log_import_action(string $action, array $data = []): void`

Log import-related actions.

**Parameters:**
- `$action` (string) - Action performed ('started', 'completed', 'failed')
- `$data` (array) - Import data and statistics

```php
MT_Audit_Logger::log_import_action('completed', [
    'source_file' => $filename,
    'candidates_imported' => $imported_count,
    'candidates_updated' => $updated_count,
    'duration' => $duration
]);
```

## Error Handling

### Container Exceptions

#### Resolution Errors

```php
try {
    $service = $container->make('NonExistentService');
} catch (Exception $e) {
    // Handle service resolution failure
    MT_Logger::error('Service resolution failed', [
        'service' => 'NonExistentService',
        'error' => $e->getMessage()
    ]);
}
```

#### Common Exception Messages

- `"Unable to resolve {abstract} from container"` - Service not registered
- `"Class {class} does not exist"` - Class file not found or not autoloaded
- `"Unable to resolve parameter {parameter} of type {type}"` - Dependency cannot be resolved

### Service Validation Errors

Services that implement `MT_Service_Interface` provide validation:

```php
$service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');

if (!$service->validate($data)) {
    $errors = $service->get_errors();
    foreach ($errors as $error) {
        // Handle validation error
    }
}
```

### Repository Errors

Repositories return `WP_Error` objects for validation failures:

```php
$repository = $container->make('MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface');
$result = $repository->save($data);

if (is_wp_error($result)) {
    $error_message = $result->get_error_message();
    // Handle error
}
```

## Usage Examples

### Basic Service Resolution

```php
// Get container
$container = MT_Plugin::container();

// Resolve service (dependencies automatically injected)
$evaluation_service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');

// Use service
$result = $evaluation_service->process($evaluation_data);
```

### Custom Service Registration

```php
// In a service provider
class MT_Custom_Provider extends MT_Service_Provider {
    public function register() {
        $this->singleton('MyCustomService', function($container) {
            return new MyCustomService(
                $container->make('RequiredDependency')
            );
        });
    }
}

// Register provider
$container->register_provider(new MT_Custom_Provider($container));

// Use service
$service = $container->make('MyCustomService');
```

### Testing with Mocks

```php
// Create test container
$container = new MT_Container();

// Register mock
$mock_repository = $this->createMock(MT_Repository_Interface::class);
$container->singleton('RepositoryInterface', function() use ($mock_repository) {
    return $mock_repository;
});

// Test service with mock dependency
$service = $container->make('ServiceClass');
```

This API reference provides comprehensive documentation for all classes, interfaces, and methods in the new dependency injection architecture of the Mobility Trailblazers plugin.