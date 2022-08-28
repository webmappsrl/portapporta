import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
  app.component('index-map-multi-polygon', IndexField)
  app.component('detail-map-multi-polygon', DetailField)
  app.component('form-map-multi-polygon', FormField)
})
