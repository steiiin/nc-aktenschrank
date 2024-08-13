<!-- BasicDialog.vue -->
<template>
  <NcModal :label-id="'modalId-'+label" :size="size" :style="'z-index:'+z"
    :can-close="isClosable && !isLoading"
    :show.sync="isVisible" @close="resolve(false)">
    <div class="aktn-dialog">
      <div class="aktn-dialog--title">
        {{ dialogTitle }}
      </div>
      <div class="aktn-dialog--subtitle">
        {{ dialogSubtitle }}
      </div>
      <div class="aktn-dialog--wrapper">
        <div class="aktn-dialog--content">
          <slot name="content" />
        </div>
        <div class="aktn-dialog--buttons">
          <slot name="buttons">
            <NcButton type="primary" :wide="true" @click="resolve(true)">
              {{ t('aktenschrank', 'OK') }}
            </NcButton>
          </slot>
        </div>
        <div v-if="isLoading" class="aktn-dialog--loading">
          <NcLoadingIcon :size="36" />
        </div>
      </div>
    </div>

    <slot />

  </NcModal>
</template>

<script>
import { NcModal, NcButton, NcLoadingIcon } from '@nextcloud/vue'

/**
 * Dialog to change app settings.
 */
export default {
  name: 'BasicDialog',

  components: {
    NcModal,
    NcButton,
    NcLoadingIcon,
  },

  props: {

    /**
     * Set 'z-index' of the modal component of the dialog.
     */
    z: {
      type: Number,
      required: false,
      default: 10000,
    },

    /**
     * Set 'size' of the modal component of the dialog.
     */
     size: {
      type: String,
      required: false,
      default: 'normal',
    },

    /**
     * Used to create a labelId for the dialog.
     */
     label: {
      type: String,
      required: true,
    },

    /**
     * If the dialog shows a loading indicator.
     */
     isLoading: {
      type: Boolean,
      required: false,
      default: false,
    },

  },

  data: () => ({
    isVisible: false,
    isClosable: true,

    dialogTitle: '',
    dialogSubtitle: '',

    resolvePromise: undefined,
    rejectPromise: undefined,
  }),
  computed: {
    isDialogActive() {
      return this.isVisible
    },
  },

  methods: {

    // #region Dialog

    /**
     * This method makes the dialog visible.
     * @return {undefined}
     */
    show() {
      this.isVisible = true
    },

    /**
     * This method hides the dialog.
     * @return {undefined}
     */
    hide() {
      this.isVisible = false
    },

    /**
     * This method initalize the dialog and shows it async.
     * @param {object} opts Options for the dialog:
     * @param {boolean} opts.isClosable If dialog could closed or canceled without saving.
     * @return {Promise} Promise that can be waited for.
     */
    async open(opts = {}) {

      // setup dialog
      this.dialogTitle = opts?.title ?? ''
      this.dialogSubtitle = opts?.subtitle ?? ''
      this.isClosable = opts?.isClosable ?? true

      // show and return Promise
      this.show()
      return new Promise((resolve, reject) => {
        this.resolvePromise = resolve
        this.rejectPromise = reject
      })

    },

    /**
     * This method closes the dialog and return result to caller.
     * @param {object} opts Desired result
     * @return {undefined}
     */
    resolve(opts = false) {
      this.hide()
      this.resolvePromise(opts)
    },

    // #endregion

  },
}
</script>
<style scoped lang="scss">
.aktn-dialog {

  padding: .75rem;

  &--title {
    font-size: 1.2rem;
    font-weight: bold;
  }
  &--subtitle {
    line-height: 1.1;
    opacity: .75;
  }

  &--wrapper {
    position: relative;
    margin-top: 1rem;
  }

  &--content {
    margin-bottom: 1rem;
  }

  &--loading {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    background: var(--color-main-background-blur);
  }

  &--buttons {
    display: flex;
    flex-wrap: nowrap;
    justify-content: space-between;
    margin: -0.75rem;
    padding: 0.75rem;
    border-top: 1px solid var(--color-background-dark);
  }

}
</style>
