
<template>
    <default-field :field="field">
    <template #field>
      <wm-map  :field="field" :position="position" :edit="true"></wm-map>
    </template>
    </default-field>
</template>
<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'

export default {
  mixins: [FormField, HandlesValidationErrors],
  props: ['resourceName', 'resourceId', 'field'],
};
</script>
