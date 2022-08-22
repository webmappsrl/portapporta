<?php

namespace Wm\MapPoi;

use Laravel\Nova\Fields\Field;
use Illuminate\Support\Facades\DB;

class MapPoi extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'map-poi';
}
