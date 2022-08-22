import DetailField from './components/DetailField'
import FormField from './components/FormField'
import IndexField from './components/IndexField'
import MapComponent from './components/MapComponent'
Nova.booting((app, store) => {
  app.component('wm-map', MapComponent)
  app.component('index-map-poi', IndexField)
  app.component('detail-map-poi', DetailField)
  app.component('form-map-poi', FormField)
})
