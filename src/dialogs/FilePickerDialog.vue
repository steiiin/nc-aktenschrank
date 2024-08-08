<!-- FilePickerDialog.vue -->
<template>
  <BasicDialog ref="basicDialog"
    label="filepicker" :z="z" size="large"
    :is-loading="isLoading"
    @close="cancel">

    <template #content>

      <NcBreadcrumbs>
        <NcBreadcrumb name="Home" :title="langHomeFolder" @click="navigate('/')">
          <template #icon>
            <HomeIcon />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb v-for="{ name, path } in parentNodes"
          :key="path" :name="name"
          @click="navigate(path)" />
        <template #actions>
          <NcButton @click="startNewFolder()">
            <template #icon>
              <AddFolderIcon />
            </template>
            {{ t("aktenschrank", "New") }}
          </NcButton>
        </template>
      </NcBreadcrumbs>

    </template>
    <template #buttons>
      <NcButton
        @click="cancel">
        {{ t('aktenschrank', 'Cancel') }}
      </NcButton>
      <NcButton type="primary"
        @click="choose">
        {{ t('aktenschrank', 'Choose') }}
      </NcButton>
    </template>

  </BasicDialog>
</template>

<script>
import { NcBreadcrumb, NcBreadcrumbs, NcButton } from '@nextcloud/vue'

import AddFolderIcon from 'vue-material-design-icons/FolderPlusOutline.vue'
import HomeIcon from 'vue-material-design-icons/Home.vue'

import BasicDialog from './BasicDialog.vue'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'

/**
 * Dialog to pick a Node in NC storage.
 */
export default {
  name: 'FilePickerDialog',

  components: {
    NcBreadcrumb,
    NcBreadcrumbs,
    NcButton,

    AddFolderIcon,
    HomeIcon,

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
    isLoading: true,

    mode: 'file',
    selected: { name: '', path: '' },

    currentNodes: [],
    parentNodes: [],

    resolvePromise: undefined,
    rejectPromise: undefined,
  }),
  computed: {

    // #region Visibility

    // #endregion
    // #region Language

    langHomeFolder() {
      return t('aktenschrank', 'Home')
    },

    // #endregion

    // #region Store

    // #endregion

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
     * @param {boolean} opts.isClosable If dialog could closed or canceled without saving.
     * @return {Promise} Promise that can be waited for.
     */
    async open(opts = {}) {

      // setup dialog
      this.mode = opts?.mode ?? 'folder'
      const dialogTitle = {
        file: t('aktenschrank', 'Choose a file'),
        folder: t('aktenschrank', 'Choose a folder'),
      }[this.mode]

      this.isLoading = false

      // show and return Promise
      return this.$refs.basicDialog.open({
        title: dialogTitle,
        subtitle: opts?.description ?? null,
        isClosable: true,
      })

    },

    /**
     * This method close the dialog and return the selected pick.
     * @return {undefined}
     */
    async choose() {
      // TODO: implement choosing
      this.$refs.basicDialog.resolve(true)
    },

    /**
     * This method closes the dialog without selecting anything.
     * @return {undefined}
     */
    cancel() {
      this.$refs.basicDialog.resolve(null)
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

    // #region FilePicker

    async navigate(to) {
      this.isLoading = true
      try {

        const query = { path: to }
        const response = (await axios.post(generateUrl('apps/aktenschrank/api/filepicker'), query)).data

        this.parentNodes = response.parentNodes
        this.currentNodes = response.currentNodes

        if (this.mode === 'file') {

          this.selected.path = null
          this.selected.name = null

        } else if (this.mode === 'folder') {

          this.selected.path = response.path
          this.selected.name = response.name ?? t('aktenschrank', 'Home')

        }

        debugger

      } catch (error) {

        showError(t('aktenschrank', 'Error while navigating to \'{path}\'.', { path: to }))
        console.error(error)

      } finally {

        this.isLoading = false

      }
    },

    // #endregion

  },
}
</script>
