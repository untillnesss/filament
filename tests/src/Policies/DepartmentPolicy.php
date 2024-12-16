<?php

namespace Filament\Tests\Policies;

use App\Models\User;
use Filament\Tests\Models\Department;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Department $department): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Department $department): bool
    {
        return false;
    }

    public function delete(User $user, Department $department): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Department $department): bool
    {
        return false;
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }
}
