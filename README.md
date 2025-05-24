# Base Auth API Documentation

This document provides comprehensive documentation for the Base Auth API, which includes authentication and device management functionality.

## Table of Contents

- [Authentication](#authentication)
  - [Login](#login)
  - [Logout](#logout)
  - [Register User](#register-user)
  - [Get User Details](#get-user-details)
- [Device Management](#device-management)
  - [List All Devices](#list-all-devices)
  - [Get Device Details](#get-device-details)
  - [List User Devices](#list-user-devices)
  - [Get Device for User by Identifier](#get-device-for-user-by-identifier)
  - [Update Device Last Used](#update-device-last-used)
  - [Register Device for User (Admin)](#register-device-for-user-admin)
  - [Approve Device (Admin)](#approve-device-admin)
  - [Reject Device (Admin)](#reject-device-admin)
  - [Revoke Device (Admin)](#revoke-device-admin)
- [Response Format](#response-format)
- [Error Handling](#error-handling)

## Authentication

All authentication endpoints are located under the `/api` prefix.

### Login

Authenticates a user and returns an access token.

- **URL**: `/api/login`
- **Method**: `POST`
- **Headers**:
  - `X-Device-ID`: Device identifier (required)
- **Request Body**:
  ```json
  {
    "email": "user@example.com",
    "password": "password123",
    "device_name": "My Device"
  }
  ```

  **Note:** `device_name` is optional
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "User logged in successfully.",
    "data": {
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
      },
      "device": {
        "id": 1,
        "user_id": 1,
        "identifier": "device-identifier",
        "name": "My Device",
        "status": "pending",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
      },
      "access_token": "token_string"
    }
  }
  ```
- **Error Response**:
  ```json
  {
    "success": false,
    "message": "Invalid credentials",
    "status": 401
  }
  ```

### Logout

Logs out the user by invalidating their access token.

- **URL**: `/api/logout`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "User logged out successfully."
  }
  ```

### Register User

Registers a new user. Requires admin privileges.

- **URL**: `/api/register`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "name": "John Doe",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "User registered successfully.",
    "data": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    },
    "status": 201
  }
  ```

### Get User Details

Retrieves details for the specified user.

- **URL**: `/api/users/{user}`
- **Method**: `GET`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Success Response**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
  ```

## Device Management

All device management endpoints are located under the `/api/devices` prefix and require authentication.

### List All Devices

Lists all devices with optional status filtering and pagination.

- **URL**: `/api/devices`
- **Method**: `GET`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Query Parameters**:
  - `status`: Filter by device status (optional)
  - `perPage`: Number of items per page (optional)
- **Success Response**:
  ```json
  {
    "success": true,
    "data": {
      "data": [
        {
          "id": 1,
          "user_id": 1,
          "identifier": "device-identifier-1",
          "name": "Device 1",
          "status": "approved",
          "created_at": "2023-01-01T00:00:00.000000Z",
          "updated_at": "2023-01-01T00:00:00.000000Z"
        },
        {
          "id": 2,
          "user_id": 2,
          "identifier": "device-identifier-2",
          "name": "Device 2",
          "status": "pending",
          "created_at": "2023-01-01T00:00:00.000000Z",
          "updated_at": "2023-01-01T00:00:00.000000Z"
        }
      ],
      "links": {
        "first": "http://example.com/api/devices?page=1",
        "last": "http://example.com/api/devices?page=1",
        "prev": null,
        "next": null
      },
      "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://example.com/api/devices",
        "per_page": 15,
        "to": 2,
        "total": 2
      }
    }
  }
  ```

### Get Device Details

Retrieves details for a specific device.

- **URL**: `/api/devices/{deviceId}`
- **Method**: `GET`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Success Response**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "user_id": 1,
      "identifier": "device-identifier",
      "name": "My Device",
      "status": "approved",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
  ```
- **Error Response**:
  ```json
  {
    "success": false,
    "message": "Device not found.",
    "status": 404
  }
  ```

### List User Devices

Lists all devices belonging to a specific user.

- **URL**: `/api/devices/user/{userId}`
- **Method**: `GET`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Success Response**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "identifier": "device-identifier-1",
        "name": "Device 1",
        "status": "approved",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
      },
      {
        "id": 3,
        "user_id": 1,
        "identifier": "device-identifier-3",
        "name": "Device 3",
        "status": "pending",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
      }
    ]
  }
  ```

### Get Device for User by Identifier

Retrieves a specific device for a user by its identifier.

- **URL**: `/api/devices/user/{userId}/identifier/{identifier}`
- **Method**: `GET`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Success Response**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "user_id": 1,
      "identifier": "device-identifier",
      "name": "My Device",
      "status": "approved",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
  ```

### Update Device Last Used

Updates the last used timestamp for a device.

- **URL**: `/api/devices/{deviceId}/update-last-used`
- **Method**: `PUT`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Device last used timestamp updated successfully."
  }
  ```

### Register Device for User (Admin)

Registers a new device for a specific user by an administrator. Requires admin privileges.

- **URL**: `/api/devices/register`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "user_id": 1,
    "device_identifier": "device-identifier",
    "device_name": "My Device",
    "notes": "Registered by admin"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Device registered successfully.",
    "data": {
      "id": 1,
      "user_id": 1,
      "identifier": "device-identifier",
      "name": "My Device",
      "status": "approved",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
  ```

### Approve Device (Admin)

Approves a device for use, automatically revoking any previously approved device for the same user. Requires admin privileges.

- **URL**: `/api/devices/approve`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "device_id": 1,
    "notes": "Approved by admin"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Device approved successfully.",
    "data": {
      "id": 1,
      "user_id": 1,
      "identifier": "device-identifier",
      "name": "My Device",
      "status": "approved",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
  ```

### Reject Device (Admin)

Rejects a device, preventing it from being used. Requires admin privileges.

- **URL**: `/api/devices/reject`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "device_id": 1,
    "notes": "Rejected by admin"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "user_id": 1,
      "identifier": "device-identifier",
      "name": "My Device",
      "status": "rejected",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
  ```

### Revoke Device (Admin)

Revokes a previously approved device. Requires admin privileges.

- **URL**: `/api/devices/revoke`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "device_id": 1,
    "notes": "Revoked by admin"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "user_id": 1,
      "identifier": "device-identifier",
      "name": "My Device",
      "status": "revoked",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
  ```

## Response Format

All API responses follow a consistent format:

### Success Response

```json
{
  "success": true,
  "message": "Optional success message",
  "data": {
    "example": "response data"
  },
  "status": 200
}
```

**Note:** The `status` field is optional and defaults to 200

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "status": 400
}
```

**Note:** The `status` field contains the HTTP status code

## Error Handling

The API uses standard HTTP status codes to indicate the success or failure of a request:

- `200 OK`: The request was successful
- `201 Created`: A new resource was created successfully
- `400 Bad Request`: The request was invalid or cannot be served
- `401 Unauthorized`: Authentication failed or user doesn't have permissions
- `404 Not Found`: The requested resource does not exist
- `500 Internal Server Error`: An error occurred on the server
