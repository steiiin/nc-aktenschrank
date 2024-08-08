<!-- FrameGroup.vue -->
<template>
  <div class="aktn-group">
    <div class="aktn-group--title">
      {{ title }}
    </div>
    <div class="aktn-group--content">
      <slot />
    </div>
  </div>
</template>

<script>

/**
 * Base component.
 */
export default {
  name: 'FrameGroup',
  props: {
    title: {
      type: String,
      required: true,
    },
  },
}

</script>
<style scoped lang="scss">
.aktn-group {

  &--title {
    background: var(--color-background-dark);
    padding: .5rem;
    font-weight: bold;
    border-radius: var(--border-radius-small);
  }

  &--content {
    padding: .5rem;
  }

}
</style>
