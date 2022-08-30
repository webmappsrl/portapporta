
<template>
  <default-field :field="field">
    <template #field>
      <input :id="field.name" type="file" :class="errorClasses" :placeholder="field.name" @change="previewFiles"
        accept=".geojson,.gpx,.kml" />
      <p v-if="hasError" class="my-2 text-danger">
        {{  firstError  }}
      </p>
      <wm-map-multi-polygon :field="field" :attribution="attribution" :geojson="geojson">
      </wm-map-multi-polygon>
    </template>
  </default-field>
</template>
<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova';
import * as t from '@mapbox/togeojson'

export default {
  mixins: [FormField, HandlesValidationErrors],
  props: ['field'],
  methods: {
    fill(formData) {
      const geometry = this.geojson.features[0].geometry;
      if (geometry.type === 'Polygon') {
        geometry.type = 'MultiPolygon'
        geometry.coordinates = [geometry.coordinates]
      }
      console.log(geometry)
      formData.append(this.field.attribute, JSON.stringify(geometry))
    },
    previewFiles(event) {
      var reader = new FileReader();
      let fileName = event.target.files[0].name;
      reader.onload = (event) => {
        let res = event.target.result;
        if (fileName.indexOf('gpx') > -1) {
          const parser = new DOMParser().parseFromString(res, 'text/xml')
          res = t.gpx(parser);
        } else
          if (fileName.indexOf('kml') > -1) {
            const parser = new DOMParser().parseFromString(res, 'text/xml')
            res = t.kml(parser);
          } else {
            res = JSON.parse(res);
          }
        this.geojson = res;
      };
      reader.readAsText(event.target.files[0]);
    }
  },
  data() {
    return { geojson: null }
  }
};
</script>
