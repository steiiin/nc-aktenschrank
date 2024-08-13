<!-- ConfirmDialog.vue -->
<template>
  <BasicDialog ref="basicDialog"
    label="confirm" :z="z" :size="dialogSizeByMessageSize"
    @close="cancel">

    <template #content>
      <div v-html="langMessageText" /> <!-- eslint-disable-line -->
    </template>
    <template #buttons>
      <NcButton v-if="isNegButtonVisible"
        @click="cancel">
        {{ langNegButton }}
      </NcButton>
      <NcButton type="primary"
        @click="accept">
        {{ langPosButton }}
      </NcButton>
    </template>

  </BasicDialog>
</template>

<script>
import { NcButton } from '@nextcloud/vue'

import BasicDialog from './BasicDialog.vue'

import { getFormattedMessage } from '../modules/manipulations.js'

/**
 * Dialog to pick a Node in NC storage.
 */
export default {
  name: 'ConfirmDialog',

  components: {
    NcButton,

    BasicDialog,
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

  },

  data: () => ({

    dialogMessage: '',
    dialogImportant: true,
    actionNegative: { text: '', visible: true },
    actionPositive: '',

    resolvePromise: undefined,
    rejectPromise: undefined,
  }),
  computed: {

    isNegButtonVisible() {
      return this.actionNegative.visible
    },
    langNegButton() {
      return this.actionNegative.text
    },
    langPosButton() {
      return this.actionPositive
    },
    langMessageText() {
      return getFormattedMessage(this.dialogMessage)
    },

    dialogSizeByMessageSize() {
      const brCount = (this.dialogMessage.match(/{break}/g) ?? []).length
      if (brCount > 1 || this.langMessageText.length >= 150) {
        return 'normal'
      } else {
        return 'small'
      }
    },

  },

  beforeMount() {
    window.addEventListener('keydown', this.handleKeydown)
  },
  beforeDestroy() {
    window.removeEventListener('keydown', this.handleKeydown)
  },

  methods: {

    // #region Dialog

    /**
     * This method initalize the dialog and shows it async.
     * @param {object} opts Options for the dialog:
     * @param {string} opts.message The displayed dialogbox message.
     * @param {string} opts.title The title of the dialogbox message.
     * @param {string} opts.important (optional) if the dialog closes if clicked beside it.
     * @param {string} opts.onlypos If the "negative"-button is hidden.
     * @param {string} opts.posText Text on positive button.
     * @param {string} opts.negText Text on negative button.
     * @return {Promise} Promise that can be waited for.
     */
    async open(opts = {}) {

      // setup dialog
      this.dialogMessage = opts?.message
      this.dialogImportant = opts?.important ?? false
      this.actionNegative.visible = !(opts?.onlypos ?? false)
      this.actionNegative.text = opts?.negText ?? t('aktenschrank', 'Cancel')
      this.actionPositive = opts?.posText ?? t('aktenschrank', 'OK')

      // show and return Promise
      return this.$refs.basicDialog.open({
        title: opts?.title,
        subtitle: null,
        isClosable: !this.dialogImportant,
      })

    },

    /**
     * This method close the dialog and return the selected pick.
     * @return {undefined}
     */
    accept() {
      this.$refs.basicDialog.resolve(true)
    },

    /**
     * This method closes the dialog without selecting anything.
     * @return {undefined}
     */
    cancel() {
      this.$refs.basicDialog.resolve(false)
    },

    /**
     * This method handles keyboard control of the dialog.
     * @param {KeyboardEvent} e The keyevent that should be handled.
     * @return {undefined}
     */
    handleKeydown(e) {
      if (!this.$refs.basicDialog.isDialogActive) { return }
      switch (e.key) {
        case 'Enter':
          this.choose()
          e.stopImmediatePropagation()
          break
        case 'Escape':
          this.cancel()
          e.stopImmediatePropagation()
          break
      }
    },

    // #endregion

  },
}
</script>
