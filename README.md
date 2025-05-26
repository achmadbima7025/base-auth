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
- [Role and Permission Management](#role-and-permission-management)
  - [List All Roles](#list-all-roles)
  - [Create Role](#create-role)
  - [Update Role](#update-role)
  - [Delete Role](#delete-role)
  - [Get Role Permissions](#get-role-permissions)
  - [List All Permissions](#list-all-permissions)
  - [Create Permission](#create-permission)
  - [Update Permission](#update-permission)
  - [Sync Permissions to Role](#sync-permissions-to-role)
  - [Assign Role to User](#assign-role-to-user)
  - [Remove Role from User](#remove-role-from-user)
  - [Revoke Permission from Role](#revoke-permission-from-role)
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
    "password_confirmation": "password123",
    "role": "user"
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

## Role and Permission Management

All role and permission management endpoints are located under the `/api/role-permission` prefix and require authentication with admin privileges.

### List All Roles

Lists all roles with optional name filtering and pagination.

- **URL**: `/api/role-permission`
- **Method**: `GET`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Query Parameters**:
  - `name`: Filter by role name (optional)
  - `perPage`: Number of items per page (optional)
- **Success Response**:
  ```json
  {
    "success": true,
    "data": {
      "data": [
        {
          "id": 1,
          "name": "admin",
          "guard_name": "web",
          "created_at": "2023-01-01T00:00:00.000000Z",
          "updated_at": "2023-01-01T00:00:00.000000Z"
        },
        {
          "id": 2,
          "name": "user",
          "guard_name": "web",
          "created_at": "2023-01-01T00:00:00.000000Z",
          "updated_at": "2023-01-01T00:00:00.000000Z"
        }
      ],
      "links": {
        "first": "http://example.com/api/role-permission?page=1",
        "last": "http://example.com/api/role-permission?page=1",
        "prev": null,
        "next": null
      },
      "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://example.com/api/role-permission",
        "per_page": 10,
        "to": 2,
        "total": 2
      }
    }
  }
  ```

### Create Role

Creates a new role.

- **URL**: `/api/role-permission`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "name": "editor"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Role created successfully.",
    "data": {
      "id": 3,
      "name": "editor",
      "guard_name": "web",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    },
    "status": 201
  }
  ```

### Update Role

Updates an existing role.

- **URL**: `/api/role-permission/{roleId}`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "name": "content-editor"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Role updated successfully.",
    "data": {
      "id": 3,
      "name": "content-editor",
      "guard_name": "web",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
  ```

### Delete Role

Deletes a role. The role must not be assigned to any users.

- **URL**: `/api/role-permission/{roleId}/delete`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Role deleted successfully."
  }
  ```
- **Error Response**:
  ```json
  {
    "success": false,
    "message": "Role is assigned to users."
  }
  ```

### Get Role Permissions

Retrieves all permissions assigned to a specific role.

- **URL**: `/api/role-permission/{roleId}/details`
- **Method**: `GET`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Success Response**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "name": "admin",
      "guard_name": "web",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z",
      "permissions": [
        {
          "id": 1,
          "name": "create-user",
          "guard_name": "web",
          "created_at": "2023-01-01T00:00:00.000000Z",
          "updated_at": "2023-01-01T00:00:00.000000Z"
        },
        {
          "id": 2,
          "name": "edit-user",
          "guard_name": "web",
          "created_at": "2023-01-01T00:00:00.000000Z",
          "updated_at": "2023-01-01T00:00:00.000000Z"
        }
      ]
    }
  }
  ```

### List All Permissions

Lists all permissions with optional name filtering and pagination.

- **URL**: `/api/role-permission/permissions`
- **Method**: `GET`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Query Parameters**:
  - `name`: Filter by permission name (optional)
  - `perPage`: Number of items per page (optional)
- **Success Response**:
  ```json
  {
    "success": true,
    "data": {
      "data": [
        {
          "id": 1,
          "name": "create-user",
          "guard_name": "web",
          "created_at": "2023-01-01T00:00:00.000000Z",
          "updated_at": "2023-01-01T00:00:00.000000Z"
        },
        {
          "id": 2,
          "name": "edit-user",
          "guard_name": "web",
          "created_at": "2023-01-01T00:00:00.000000Z",
          "updated_at": "2023-01-01T00:00:00.000000Z"
        }
      ],
      "links": {
        "first": "http://example.com/api/role-permission/permissions?page=1",
        "last": "http://example.com/api/role-permission/permissions?page=1",
        "prev": null,
        "next": null
      },
      "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://example.com/api/role-permission/permissions",
        "per_page": 10,
        "to": 2,
        "total": 2
      }
    }
  }
  ```

### Create Permission

Creates a new permission.

- **URL**: `/api/role-permission/permissions`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "name": "delete-user"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Permission created successfully.",
    "data": {
      "id": 3,
      "name": "delete-user",
      "guard_name": "web",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
  ```

### Update Permission

Updates an existing permission.

- **URL**: `/api/role-permission/permissions/{permissionId}`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "name": "remove-user"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Permission updated successfully.",
    "data": {
      "id": 3,
      "name": "remove-user",
      "guard_name": "web",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  }
  ```

### Sync Permissions to Role

Syncs a set of permissions to a role, replacing any existing permissions.

- **URL**: `/api/role-permission/role-sync-permissions`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "role_id": 1,
    "permissions": [1, 2, 3]
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Permissions synced successfully."
  }
  ```

### Assign Role to User

Assigns a role to a user.

- **URL**: `/api/role-permission/assign-role-user`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "user_id": 1,
    "role": "editor"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Role assigned successfully."
  }
  ```

### Remove Role from User

Removes a role from a user.

- **URL**: `/api/role-permission/remove-role-from-user`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "user_id": 1,
    "role": "editor"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Role removed user successfully."
  }
  ```

### Revoke Permission from Role

Revokes a specific permission from a role.

- **URL**: `/api/role-permission/revoke-role-from-user`
- **Method**: `POST`
- **Headers**:
  - `Authorization`: `Bearer {token}`
- **Request Body**:
  ```json
  {
    "permission": "create-user",
    "role": "editor"
  }
  ```
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Permission revoked successfully."
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
