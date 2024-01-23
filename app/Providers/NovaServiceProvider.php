<?php

namespace App\Providers;

use App\Nova\Calendar;
use App\Nova\CalendarItem;
use App\Nova\Ticket;
use App\Nova\PushNotification;
use App\Nova\TrashType;
use App\Nova\UserType;
use App\Nova\Waste;
use App\Nova\WasteCollectionCenter;
use App\Nova\Zone;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Illuminate\Http\Request;
use Laravel\Nova\Menu\Menu;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;

class NovaServiceProvider extends NovaApplicationServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        Nova::mainMenu(function (Request $request, Menu $menu) {
            if (!empty($request->user()->companyWhereAdmin)) {
                return [
                    MenuSection::make(__('Communication'), [
                        MenuItem::resource(Ticket::class),
                        MenuItem::resource(PushNotification::class),
                    ])->icon('user')->collapsable(),
                    MenuSection::make(__('Calendar'),  [
                        MenuItem::resource(Calendar::class),
                        MenuItem::resource(CalendarItem::class),
                        MenuItem::resource(Zone::class),
                        MenuItem::resource(UserType::class),
                    ])->icon('calendar')->collapsable(),
                    MenuSection::make(__('trash'), [
                        MenuItem::resource(TrashType::class),
                        MenuItem::resource(Waste::class),
                        MenuItem::resource(WasteCollectionCenter::class),
                    ])->icon('trash')->collapsable(),
                ];
            } else return $menu;
        });
        Nova::userMenu(function (Request $request, Menu $menu) {

            if (!empty($request->user()->companyWhereAdmin)) {
                $menu->append(
                    MenuItem::make(
                        'Profile (company: ' . $request->user()->companyWhereAdmin->name . ')',
                        "/resources/users/{$request->user()->id} . '/edit'"
                    )
                );
            }

            return $menu;
        });
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return $user->hasRole('super_admin') || $user->hasRole('company_admin');
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new \App\Nova\Dashboards\Main,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [
            \Vyuldashev\NovaPermission\NovaPermissionTool::make()->canSee(function ($request) {
                return $request->user()->hasRole('super_admin');
            }),
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
