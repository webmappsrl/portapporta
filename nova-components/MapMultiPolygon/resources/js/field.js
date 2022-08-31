import DetailField from './components/DetailField'
import FormField from './components/FormField'
import IndexField from './components/IndexField'
import MapComponent from './components/MapComponent'
Nova.booting((app, store) => {
  app.component('wm-map-multi-polygon', MapComponent)
  app.component('index-map-multi-polygon', IndexField)
  app.component('detail-map-multi-polygon', DetailField)
  app.component('form-map-multi-polygon', FormField)
})
