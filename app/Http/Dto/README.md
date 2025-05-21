# JSON Response DTO

This directory contains Data Transfer Objects (DTOs) for standardizing JSON responses in the application.

## JsonResponseDto

The `JsonResponseDto` class provides a standardized way to structure JSON responses in the application. It ensures consistency in response format and makes it easier to create different types of responses.

### Properties

- `success`: A boolean indicating whether the operation was successful.
- `message`: An optional string with a human-readable message.
- `data`: The data to be returned in the response.
- `status`: The HTTP status code for the response.

### Static Methods

#### `success()`

Creates a success response.

```php
JsonResponseDto::success(
    data: ['user' => $user],
    message: 'User created successfully',
    status: HttpStatusCode::CREATED
);
```

#### `error()`

Creates an error response.

```php
JsonResponseDto::error(
    message: 'Invalid credentials',
    status: HttpStatusCode::UNAUTHORIZED
);
```

### Usage in Controllers

In controllers that extend the base `Controller` class, you can use the `json_response()` method with the DTO:

```php
public function login(LoginRequest $request)
{
    // ... authentication logic ...

    if (!$authenticated) {
        return $this->json_response(
            JsonResponseDto::error(
                message: 'Invalid credentials',
                status: HttpStatusCode::UNAUTHORIZED
            )
        );
    }

    return $this->json_response(
        JsonResponseDto::success(
            data: [
                'user' => $user,
                'access_token' => $token
            ],
            message: 'Logged in successfully'
        )
    );
}
```

### Usage in Other Classes

In classes that don't extend the base `Controller` class, you can use the DTO with Laravel's `response()->json()` method:

```php
return response()->json(
    JsonResponseDto::error(
        message: 'Access denied',
        status: HttpStatusCode::FORBIDDEN
    )->toArray(),
    HttpStatusCode::FORBIDDEN
);
```

### Response Format

The DTO generates responses in the following format:

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

Or for associative array data:

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "access_token": "token_string"
}
```
