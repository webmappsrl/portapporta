<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <wm-map :field="field" :edit=false></wm-map>
    </template>
  </PanelItem>
</template>

<script>
export default {
  props: ['index', 'field'],
}
</script>
