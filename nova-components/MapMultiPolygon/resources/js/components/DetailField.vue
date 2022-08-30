<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <wm-map-multi-polygon :field="field" :edit=false></wm-map-multi-polygon>
    </template>
  </PanelItem>
</template>

<script>
export default {
  props: ['index', 'field'],
}
</script>
