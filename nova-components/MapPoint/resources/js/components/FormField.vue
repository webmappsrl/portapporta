
<template>
  <default-field :field="field">
    <template #field>
      <wm-map :field="field" :edit=true @latlng="updateForm" :attribution="attribution"></wm-map>
    </template>
  </default-field>
</template>
<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova';

export default {
  mixins: [FormField, HandlesValidationErrors],
  props: ['field'],
  methods: {
    updateForm(value) {
      this.latlng = value;
    },
    fill(formData) {
      formData.append(this.field.attribute, this.latlng);
    }
  }
};
</script>
