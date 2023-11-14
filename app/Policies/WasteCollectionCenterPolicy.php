<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteCollectionCenter;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteCollectionCenterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasRole('company_admin');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WasteCollectionCenter  $wasteCollectionCenter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, WasteCollectionCenter $wasteCollectionCenter)
    {
        if ($wasteCollectionCenter->company_id == $user->companyWhereAdmin->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return !$user->hasRole('contributor');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WasteCollectionCenter  $wasteCollectionCenter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, WasteCollectionCenter $wasteCollectionCenter)
    {
        if ($wasteCollectionCenter->company_id == $user->companyWhereAdmin->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WasteCollectionCenter  $wasteCollectionCenter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, WasteCollectionCenter $wasteCollectionCenter)
    {
        if ($wasteCollectionCenter->company_id == $user->companyWhereAdmin->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WasteCollectionCenter  $wasteCollectionCenter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, WasteCollectionCenter $wasteCollectionCenter)
    {
        if ($wasteCollectionCenter->company_id == $user->companyWhereAdmin->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WasteCollectionCenter  $wasteCollectionCenter
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, WasteCollectionCenter $wasteCollectionCenter)
    {
        if ($wasteCollectionCenter->company_id == $user->companyWhereAdmin->id) {
            return true;
        }

        return false;
    }
}