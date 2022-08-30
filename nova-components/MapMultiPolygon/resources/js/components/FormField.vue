
<template>
  <default-field :field="field">
    <template #field>
      <input :id="field.name" type="file"
          :class="errorClasses"
          :placeholder="field.name"
          @change="previewFiles"
      />
      <wm-map-multi-polygon :field="field" :edit=true :attribution="attribution"></wm-map-multi-polygon>
    </template>
  </default-field>
</template>
<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova';

export default {
  mixins: [FormField, HandlesValidationErrors],
  props: ['field'],
  methods: {
    fill(formData) {
      // formData.append(this.field.attribute, this.);
    },
    previewFiles(event) {
      console.log(event.target.files);
      const reader = new FileReader();
      reader.readAsDataURL(event.target.files[0])
      reader.onload = async (event) => {
        var parser = new DOMParser();
		var doc = parser.parseFromString( event.target.result, "text/xml");
    console.log(doc);
      }
      // this.updateForm(event.target.files);
    }
  }
};
</script>
