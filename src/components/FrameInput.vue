<!-- FrameInput.vue -->
<template>
  <div class="aktn-input">
    <label v-if="!!label && label.length > 0">
      {{ label }}
    </label>
    <div class="aktn-input--wrapper">
      <div class="aktn-input--control"
        :class="{ 'aktn-input--control-wide': wide }">
        <slot name="control" />
      </div>
      <div v-if="hasActions" class="aktn-input--actions">
        <slot name="actions" />
      </div>
    </div>
    <div v-if="error" class="aktn-input--error">
      <AlertIcon :size="15" />
      <slot name="error" />
    </div>
  </div>
</template>

<script>

import AlertIcon from 'vue-material-design-icons/AlertCircleOutline.vue'

/**
 * Base component.
 */
export default {
  name: 'FrameInput',

  components: {
    AlertIcon,
  },
  props: {
    label: {
      type: String,
      required: false,
      default: null,
    },
    error: {
      type: Boolean,
      required: false,
      default: false,
    },
    wide: {
      type: Boolean,
      required: false,
      default: false,
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
    align-items: start;
  }

  &--control {
    flex: 0;
  }

  &--control-wide {
    flex: 1;
  }

  &--actions {
    display: flex;
    flex-direction: row;
  }

  &--error {
    display: flex;
    color: var(--color-error);
    font-size: 0.75rem;
    align-content: baseline;
    gap: .2rem;
    margin-top: 4px;
  }

}
</style>
