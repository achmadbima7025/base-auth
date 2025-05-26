<?php

namespace App\Services\Auth;

use App\Http\Resources\UserCollection;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\UnauthorizedException;

class UserService
{
    public function getAllUsers(?string $name = null, ?string $email = null, ?int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->when($name, fn ($query, $name) => $query->where('name', 'like', "%$name%"))
            ->when($email, fn ($query, $email) => $query->where('email', 'like', "%$email%"))
            ->paginate($perPage);
    }

    public function updateUser(array $data): User
    {
        $user = User::find($data['user_id']);
        if (!$user) {
            throw new ModelNotFoundException('User not found.');
        }

        if (!$user->isAdmin() && $data['role'] !== null) {
            throw new UnauthorizedException('Only admin can update role.');
        }

        $user->update($data);

        return $user->fresh();
    }
}
