<?php

namespace App\Policies;

use App\Models\CalendarItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CalendarItemPolicy
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
        return $user->can('manage_calendars');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CalendarItem  $calendar_item
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, CalendarItem $calendar_item)
    {
        if ($calendar_item->calendar->company_id == $user->company->id) {
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
     * @param  \App\Models\CalendarItem  $calendar_item
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, CalendarItem $calendar_item)
    {
        if ($calendar_item->calendar->company->id == $user->company->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CalendarItem  $calendar_item
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, CalendarItem $calendar_item)
    {
        if ($calendar_item->calendar->company_id == $user->company->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CalendarItem  $calendar_item
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, CalendarItem $calendar_item)
    {
        if ($calendar_item->calendar->company_id == $user->company->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\CalendarItem  $calendar_item
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, CalendarItem $calendar_item)
    {
        if ($calendar_item->calendar->company_id == $user->company->id) {
            return true;
        }

        return false;
    }
}
