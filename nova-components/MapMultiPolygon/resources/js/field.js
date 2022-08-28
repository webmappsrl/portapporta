import DetailField from './components/DetailField'
import FormField from './components/FormField'
import IndexField from './components/IndexField'
import MapComponent from './components/MapComponent'
Nova.booting((app, store) => {
  app.component('wm-map', MapComponent)
  app.component('index-map-point', IndexField)
  app.component('detail-map-point', DetailField)
  app.component('form-map-point', FormField)
})
