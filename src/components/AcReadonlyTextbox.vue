<!-- AcReadonlyTextbox.vue -->
<template>
  <div class="aktn-list--node aktn-ro-textbox" @click="$emit('click')">
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

  background: var(--color-primary-element-light);

  &:hover {
    background: var(--color-primary-element-light-hover);
  }

}
</style>
