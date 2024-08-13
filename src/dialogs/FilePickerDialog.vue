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
          <NcButton style="margin-left: .5rem" @click="beginNewFolder()">
            <template #icon>
              <AddFolderIcon />
            </template>
            {{ t('aktenschrank', 'New Folder') }}
          </NcButton>
        </template>
      </NcBreadcrumbs>

      <FrameInputInline v-if="newfolder.active"
        input-id="id-input-filepicker-newfolder"
        :label="t('aktenschrank', 'Name Of The New Folder')"
        :valid="isValidNewFolder" :value.sync="newfolder.name" clean="filename"
        @cancel="cancelNewFolder"
        @accept="acceptNewFolder" />

      <ul v-if="isInitialized && !!contentNodes" class="aktn-filepicker--list">

        <AcNode v-for="node in contentNodes"
          :key="node.node_id" :node="node" :disabled="node.type!==mode"
          @click="clickNode" />

        <template v-if="!hasChildren">
          <AcNoContent :title="t('aktenschrank', 'This Is An Empty Folder')" type="decent" />
        </template>

      </ul>

    </template>
    <template #buttons>
      <NcButton
        @click="cancel">
        {{ t('aktenschrank', 'Cancel') }}
      </NcButton>
      <NcButton type="primary" :disabled="!isValidDialog"
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

import AcNoContent from '../components/AcNoContent.vue'
import AcNode from '../components/AcNode.vue'
import BasicDialog from './BasicDialog.vue'
import FrameInputInline from '../components/FrameInputInline.vue'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { concatPaths } from '../modules/manipulations.js'

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

    AcNoContent,
    AcNode,
    BasicDialog,
    FrameInputInline,
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
    isInitialized: false,

    mode: 'file',
    selected: { name: '', path: '', hasChildren: false, hasFolders: false, isGroupfolder: false, isCabinet: false },

    currentNode: { path: '' },
    contentNodes: [],
    parentNodes: [],

    newfolder: { active: false, name: '' },

    resolvePromise: undefined,
    rejectPromise: undefined,
  }),
  computed: {

    // #region Visibility

    hasChildren() {
      return this.contentNodes.length > 0
    },

    // #endregion
    // #region Language

    langHomeFolder() {
      return t('aktenschrank', 'Home')
    },

    // #endregion

    // #region Validation

    isValidNewFolder() {
      return this.newfolder.name.trim() !== ''
    },

    isValidDialog() {
      return this.selected.path !== null && !this.isLoading && !this.newfolder.active
    },

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

      // reset picker
      this.isInitialized = false
      this.contentNodes = []
      this.parentNodes = []
      this.selected.name = null
      this.selected.path = null
      this.selected.isGroupfolder = false
      this.selected.isCabinet = false
      this.newfolder.active = false
      this.newfolder.name = ''
      this.currentNode.path = '/'
      this.navigate(opts?.path ?? '/')

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
      this.$refs.basicDialog.resolve(this.selected)
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
          if (this.newfolder.active) {
            this.acceptNewFolder()
          } else {
            this.choose()
          }
          e.stopImmediatePropagation()
          break
        case 'Escape':
          if (this.newfolder.active) {
            this.cancelNewFolder()
          } else {
            this.cancel()
          }
          e.stopImmediatePropagation()
          break
      }
    },

    // #endregion

    // #region FilePicker

    /**
     * This method navigates to a folder.
     * @param {string} to Path of the desired folder.
     * @param {boolean} create Request creation of the specified path.
     * @return {Promise} Promise that can be waited for.
     */
    async navigate(to, create = false) {
      this.isLoading = true
      this.cancelNewFolder()
      try {

        const query = { path: to, create }
        const response = (await axios.post(generateUrl('apps/aktenschrank/api/filepicker'), query)).data

        this.parentNodes = response.parentNodes
        this.contentNodes = response.contentNodes
        this.currentNode.path = response.selected.path

        if (this.mode === 'file') {

          this.selected.path = null
          this.selected.name = null

        } else if (this.mode === 'folder') {

          this.selected.path = response.selected.path
          this.selected.name = response.selected.name ?? t('aktenschrank', 'Home')
          this.selected.hasChildren = response.selected.hasChildren
          this.selected.hasFolders = response.selected.hasFolders
          this.selected.isGroupfolder = response.selected.isGroupfolder
          this.selected.isCabinet = response.selected.isCabinet

        }
        this.isInitialized = true

      } catch (error) {

        showError(t('aktenschrank', "Error while navigating to '{path}'.", { path: to }))
        console.error(error)

      } finally {

        this.isLoading = false

      }
    },

    /**
     * This method decide how to proceed with clicked node.
     * @param {object} node The node to be processed.
     * @return {undefined}
     */
    clickNode(node) {
      if (node.type === 'folder') {

        // navigate further
        this.navigate(node.path.relative)

      } else if (node.type === 'file') {

        // TODO: select file

      }
    },

    // #region NewFolder

    /**
     * Display the input to enter a new folder name.
     * @return {Promise} Promise that can be waited for.
     */
    async beginNewFolder() {
      this.newfolder.name = ''
      this.newfolder.active = true
      await this.$nextTick()
      document.getElementById('id-input-filepicker-newfolder')?.focus()
    },

    /**
     * Hide the input to enter a new folder name.
     * @return {undefined}
     */
    cancelNewFolder() {
      this.newfolder.active = false
    },

    /**
     * Navigates to the desired new folder path.
     * @return {undefined}
     */
    acceptNewFolder() {
      if (!this.isValidNewFolder) { return }
      this.cancelNewFolder()

      // create new path
      const newPath = concatPaths(this.currentNode.path, this.newfolder.name)
      this.navigate(newPath, true)
    },

    // #endregion

    // #endregion

  },
}
</script>
