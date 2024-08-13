<!-- FrameInput.vue -->
<template>
  <div class="aktn-input">
    <label v-if="!!label && label.length > 0">
      {{ label }}
    </label>
    <div class="aktn-input--wrapper">
      <div class="aktn-input--control">
        <slot name="control" />
      </div>
      <div v-if="hasActions" class="aktn-input--actions">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<script>

/**
 * Base component.
 */
export default {
  name: 'FrameInput',
  props: {
    label: {
      type: String,
      required: false,
      default: null,
    },
  },
  computed: {
    hasActions() {
      return !!(this.$slots.actions || [])[0]
    },
  },
}

</script>
<style scoped lang="scss">
.aktn-input {

  margin: 0.5rem 0;

  & label {
    display: block;
    font-size: 0.8rem;
    opacity: 0.75;
    text-transform: uppercase;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
  }

  &--wrapper {
    display: flex;
    gap: .5rem;
  }

  &--control {
    flex: 1;
  }

  &--actions {
    display: flex;
    flex-direction: row;
  }

}
</style>
