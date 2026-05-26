<?php

namespace App\Nova\Concerns;

use App\Enums\TicketType;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Panel;

trait HasTicketFormsConfigFields
{
    protected function ticketFormsConfigPanels(): array
    {
        return [
            new Panel(__('Form — Missed Collection'), $this->ticketTypeConfigFields('report')),
            new Panel(__('Form — Abandoned Waste'), $this->ticketTypeConfigFields('abandonment')),
            new Panel(__('Form — Service Booking'), $this->ticketTypeConfigFields('reservation')),
            new Panel(__('Form — Information Request'), $this->ticketTypeConfigFields('info')),
        ];
    }

    protected function ticketTypeConfigFields(string $type): array
    {
        $resolveField = function (string $key) use ($type): \Closure {
            return function ($v, $model) use ($type, $key) {
                $db = $model->ticket_forms_config;
                $default = TicketType::from($type)->config($model->name);
                $dbTypeConfig = is_array($db) ? ($db[$type] ?? []) : [];

                if ($key === 'step0label') {
                    return $dbTypeConfig['step'][0]['label'] ?? $default['step'][0]['label'] ?? null;
                }

                return $dbTypeConfig[$key] ?? $default[$key] ?? null;
            };
        };

        $fillField = function (string $key) use ($type): \Closure {
            return function ($request, $model, $attribute, $requestAttribute) use ($type, $key) {
                $current = $model->ticket_forms_config;
                if (!is_array($current) || empty($current)) {
                    $current = collect(TicketType::cases())
                        ->mapWithKeys(fn($t) => [$t->value => $t->config($model->name)])
                        ->toArray();
                }

                $value = $request->input($requestAttribute) ?: null;

                if ($key === 'step0label') {
                    $current[$type]['step'][0]['label'] = $value;
                } else {
                    $current[$type][$key] = $value;
                }

                $model->ticket_forms_config = $current;
            };
        };

        return [
            Textarea::make(__('Introductory text'), "{$type}_step0label")
                ->resolveUsing($resolveField('step0label'))
                ->fillUsing($fillField('step0label'))
                ->nullable()
                ->hideFromIndex()
                ->alwaysShow()
                ->help(__('Ticket form introductory text help')),

            Textarea::make(__('Confirmation message'), "{$type}_finalMessage")
                ->resolveUsing($resolveField('finalMessage'))
                ->fillUsing($fillField('finalMessage'))
                ->nullable()
                ->hideFromIndex()
                ->alwaysShow()
                ->help(__('Ticket form confirmation message help')),
        ];
    }
}
