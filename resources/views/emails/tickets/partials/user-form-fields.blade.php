@php
    /**
     * Render dynamic form fields based on the company's form_json schema and the user's form_data.
     *
     * Required: $user
     * Optional: $company, $ticket, $format ('br' default, or 'paragraph')
     */
    $format = $format ?? 'br';
    $resolvedCompany = $company ?? null;
    if (!$resolvedCompany && isset($user) && $user) {
        $resolvedCompany = \App\Models\Company::find($user->app_company_id);
    }
    $formData = [];
    if ($user) {
        $formData = $user->form_data ?? [];
        if (!is_array($formData)) {
            $formData = json_decode($formData, true) ?? [];
        }
    }
    $resolveTicketPhone = function () use ($user, $formData, $ticket) {
        if (!$user || !isset($ticket)) {
            return $ticket->phone ?? null;
        }
        $userPhone = $user->phone_number ?? ($formData['phone_number'] ?? null);
        $ticketPhone = $ticket->phone ?? null;
        if ($userPhone !== null && $userPhone !== '' && (string) $userPhone === (string) $ticketPhone) {
            return $userPhone;
        }
        return $ticketPhone;
    };
    $isPhoneField = function ($name, $label) {
        if ($name === 'phone_number' || $name === 'phone') {
            return true;
        }
        return $label !== null && stripos($label, 'telefon') !== false;
    };
    $rows = [];
    $phoneShownInRows = false;
    if ($user && $resolvedCompany && !empty($resolvedCompany->form_json)) {
        $schema = json_decode($resolvedCompany->form_json, true) ?? [];
        if (!empty($schema)) {
            $filtered = method_exists($user, 'filterFormSchemaExcludingTypes')
                ? $user->filterFormSchemaExcludingTypes($schema)
                : array_values(array_filter($schema, fn($f) => !isset($f['only_fe']) || !$f['only_fe']));
            foreach ($filtered as $field) {
                $name = $field['name'] ?? null;
                $label = $field['label'] ?? ($name ? ucwords(str_replace('_', ' ', $name)) : null);
                if ($label === null) {
                    continue;
                }
                if (($field['type'] ?? 'text') === 'password') {
                    continue;
                }
                $value = method_exists($user, 'resolveFormFieldValue')
                    ? $user->resolveFormFieldValue($field, $formData)
                    : ($formData[$field['label'] ?? ''] ?? $formData[$name ?? ''] ?? $field['value'] ?? null);
                if ($isPhoneField($name, $label)) {
                    $phoneShownInRows = true;
                    if (isset($ticket)) {
                        $value = $resolveTicketPhone();
                    }
                }
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }
    }
    if (!$phoneShownInRows && isset($ticket)) {
        $phone = $resolveTicketPhone();
        if ($phone !== null && $phone !== '') {
            $rows[] = ['label' => 'Telefono', 'value' => $phone];
        }
    }
@endphp
@foreach ($rows as $row)
    @if ($format === 'paragraph')
        <p><strong>{{ __($row['label']) }}:</strong> {{ ($row['value'] ?? '') ?: '-' }}</p>
    @elseif ($format === 'table')
        <tr>
            <td width="130" style="padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;vertical-align:top;">{{ __($row['label']) }}</td>
            <td style="padding:9px 14px;font-size:14px;color:#333333;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">{{ ($row['value'] ?? '') ?: '-' }}</td>
        </tr>
    @else
        <strong>{{ __($row['label']) }}:</strong> {{ ($row['value'] ?? '') ?: '-' }}<br>
    @endif
@endforeach
