<!-- AcReadonlyTextbox.vue -->
<template>
  <div class="aktn-ro-textbox" @click="$emit('click')">
    {{ text }}
  </div>
</template>

<script>

/**
 * Base component.
 */
export default {
  name: 'AcReadonlyTextbox',
  props: {
    text: {
      type: String,
      required: true,
    },
  },
}

</script>
<style scoped lang="scss">
.aktn-ro-textbox {
  height: var(--default-clickable-area);
  display: flex;
  align-items: center;
  background: var(--color-primary-element-light);
  border-radius: var(--border-radius-element);
  padding-left: var(--border-radius-element);
  cursor: pointer;

  transition-property: color, border-color, background-color;
  transition-duration: 0.1s;
  transition-timing-function: linear;

  &:hover {
    background: var(--color-primary-element-light-hover);
  }
}
</style>
