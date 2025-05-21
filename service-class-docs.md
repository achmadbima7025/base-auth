# Service Class Documentation

This document provides comprehensive documentation for the service classes in the application.

## Table of Contents
- [Auth Service](#auth-service)
  - [register](#register)
  - [login](#login)
  - [logout](#logout)
  - [getUserDetails](#getuserdetails)
- [Device Service](#device-service)
  - [getDeviceForUserByIdentifier](#getdeviceforuserbyidentifier)
  - [listUserDevices](#listuserdevices)
  - [listAllDevicesFiltered](#listalldevicesfiltered)
  - [getDeviceDetails](#getdevicedetails)
  - [approveDevice](#approvedevice)
  - [rejectDevice](#rejectdevice)
  - [revokeDevice](#revokedevice)
  - [registerDeviceForUserByAdmin](#registerdeviceforuserbyAdmin)
  - [updateDeviceLastUsed](#updatedevicelastused)

## Auth Service

The `AuthService` class handles user authentication operations including registration, login, logout, and retrieving user details.

### register

Registers a new user with the provided data.

**Signature:**
```php
public function register(array $data): array
```

**Parameters:**
- `$data` (array): User data including 'name' and 'email'

**Returns:**
- array: Response with success status, message, and user data if successful

**Example:**
```php
$authService = new AuthService();
$result = $authService->register([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### login

Authenticates a user with the provided credentials and handles device registration/verification.

**Signature:**
```php
public function login(array $credentials, string $deviceIdentifier, ?string $deviceName, string $ipAddress): array
```

**Parameters:**
- `$credentials` (array): User credentials including 'email' and 'password'
- `$deviceIdentifier` (string): Unique identifier for the device
- `$deviceName` (string|null): Name of the device, defaults to "Unknown Device" if not provided
- `$ipAddress` (string): IP address of the login request

**Returns:**
- array: Response with success status, user data, device data, and access token if successful

**Example:**
```php
$authService = new AuthService();
$result = $authService->login(
    ['email' => 'john@example.com', 'password' => 'password123'],
    'device-uuid-123',
    'John\'s iPhone',
    '192.168.1.1'
);
```

### logout

Logs out a user by deleting all their tokens.

**Signature:**
```php
public function logout(User $user): array
```

**Parameters:**
- `$user` (User): The user to log out

**Returns:**
- array: Response with success status and message

**Example:**
```php
$authService = new AuthService();
$result = $authService->logout($user);
```

### getUserDetails

Retrieves details for a user.

**Signature:**
```php
public function getUserDetails(User $user): array
```

**Parameters:**
- `$user` (User): The user to get details for

**Returns:**
- array: Response with success status and user data

**Example:**
```php
$authService = new AuthService();
$result = $authService->getUserDetails($user);
```

## Device Service

The `DeviceService` class handles device management operations including listing, approving, rejecting, and revoking devices.

### getDeviceForUserByIdentifier

Retrieves a device for a specific user by its identifier.

**Signature:**
```php
public function getDeviceForUserByIdentifier(User $user, string $deviceIdentifier): ?Device
```

**Parameters:**
- `$user` (User): The user who owns the device
- `$deviceIdentifier` (string): Unique identifier for the device

**Returns:**
- Device|null: The device if found, null otherwise

**Example:**
```php
$deviceService = new DeviceService();
$device = $deviceService->getDeviceForUserByIdentifier($user, 'device-uuid-123');
```

### listUserDevices

Lists all devices for a user.

**Signature:**
```php
public function listUserDevices(User $user): Collection
```

**Parameters:**
- `$user` (User): The user whose devices to list

**Returns:**
- Collection: Collection of devices belonging to the user

**Example:**
```php
$deviceService = new DeviceService();
$devices = $deviceService->listUserDevices($user);
```

### listAllDevicesFiltered

Lists all devices with optional status filtering and pagination.

**Signature:**
```php
public function listAllDevicesFiltered(?string $status, int $perPage = 10): LengthAwarePaginator
```

**Parameters:**
- `$status` (string|null): Filter devices by status (approved, pending, rejected, revoked)
- `$perPage` (int): Number of devices per page, defaults to 10

**Returns:**
- LengthAwarePaginator: Paginated list of devices

**Example:**
```php
$deviceService = new DeviceService();
$devices = $deviceService->listAllDevicesFiltered('approved', 20);
```

### getDeviceDetails

Gets details for a specific device.

**Signature:**
```php
public function getDeviceDetails(Device $device): array
```

**Parameters:**
- `$device` (Device): The device to get details for

**Returns:**
- array: Response with success status and device data

**Example:**
```php
$deviceService = new DeviceService();
$result = $deviceService->getDeviceDetails($device);
```

### approveDevice

Approves a device for use, automatically revoking any previously approved device for the same user.

**Signature:**
```php
public function approveDevice(Device $device, User $admin, ?string $notes): array
```

**Parameters:**
- `$device` (Device): The device to approve
- `$admin` (User): The admin user performing the approval
- `$notes` (string|null): Optional notes about the approval

**Returns:**
- array: Response with success status, message, and device data if successful

**Example:**
```php
$deviceService = new DeviceService();
$result = $deviceService->approveDevice($device, $adminUser, 'Approved after verification');
```

### rejectDevice

Rejects a device.

**Signature:**
```php
public function rejectDevice(Device $device, User $admin, string $notes): array
```

**Parameters:**
- `$device` (Device): The device to reject
- `$admin` (User): The admin user performing the rejection
- `$notes` (string): Notes explaining the rejection reason

**Returns:**
- array: Response with success status, message, and device data if successful

**Example:**
```php
$deviceService = new DeviceService();
$result = $deviceService->rejectDevice($device, $adminUser, 'Suspicious login pattern');
```

### revokeDevice

Revokes access for a device.

**Signature:**
```php
public function revokeDevice(Device $device, User $admin, ?string $notes): array
```

**Parameters:**
- `$device` (Device): The device to revoke
- `$admin` (User): The admin user performing the revocation
- `$notes` (string|null): Optional notes about the revocation

**Returns:**
- array: Response with success status, message, and device data if successful

**Example:**
```php
$deviceService = new DeviceService();
$result = $deviceService->revokeDevice($device, $adminUser, 'User reported device lost');
```

### registerDeviceForUserByAdmin

Registers a device for a user by an admin, automatically revoking any previously approved device.

**Signature:**
```php
public function registerDeviceForUserByAdmin(
    User $user,
    string $deviceIdentifier,
    string $deviceName,
    User $admin,
    ?string $notes
): array
```

**Parameters:**
- `$user` (User): The user to register the device for
- `$deviceIdentifier` (string): Unique identifier for the device
- `$deviceName` (string): Name of the device
- `$admin` (User): The admin user performing the registration
- `$notes` (string|null): Optional notes about the registration

**Returns:**
- array: Response with success status, message, and device data if successful

**Example:**
```php
$deviceService = new DeviceService();
$result = $deviceService->registerDeviceForUserByAdmin(
    $user,
    'device-uuid-123',
    'Office Laptop',
    $adminUser,
    'Registered for new employee'
);
```

### updateDeviceLastUsed

Updates the last used timestamp for a device.

**Signature:**
```php
public function updateDeviceLastUsed(Device $device): void
```

**Parameters:**
- `$device` (Device): The device to update

**Returns:**
- void

**Example:**
```php
$deviceService = new DeviceService();
$deviceService->updateDeviceLastUsed($device);
```
