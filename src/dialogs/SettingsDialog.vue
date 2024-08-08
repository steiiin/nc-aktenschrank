<!-- SettingsDialog.vue -->
<template>
  <BasicDialog ref="basicDialog"
    label="settings" :z="z" size="large"
    :is-loading="isLoading"
    @close="cancelSettings">

    <template #content>

      <FrameGroup :title="t('aktenschrank', 'Working Directory')">

        <NcNoteCard v-if="showReady" type="success">
          {{ t('aktenschrank', 'You\'ve properly set up the filing cabinet.') }}
        </NcNoteCard>
        <NcNoteCard v-if="showPrepSetup" type="success">
          <template #icon>
            <CogIcon />
          </template>
          {{ t('aktenschrank', 'You must first set up my working directory before you can use the filing cabinet.') }}
        </NcNoteCard>

        <FrameInput
          :label="t('aktenschrank', 'Path to the folder, where I store your documents and data')"
          for="id-input-cabinetpath">
          <template #control>
            <AcReadonlyTextbox :text.sync="cabinet.selected.path" @click="chooseCabinet" />
          </template>
          <template #actions>
            <NcButton @click="chooseCabinet">
              {{ t('aktenschrank', 'Choose') }}
            </NcButton>
          </template>
        </FrameInput>

        <NcNoteCard v-if="showPermIssue" type="error">
          {{ t('aktenschrank', 'The filing cabinet exists, but you do not have write access. Please check the folder permissions.') }}
        </NcNoteCard>
        <NcNoteCard v-if="showCabMissing" type="error">
          {{ t('aktenschrank', 'The filing cabinet is missing. You set it up before - did you delete it by mistake?') }}
        </NcNoteCard>

        <NcNoteCard v-if="showPrepPermIssue" type="warning">
          {{ t('aktenschrank', 'The folder you\'ve selected exists, but you do not have write access. Please check the folder permissions or select another folder.') }}
        </NcNoteCard>
        <NcNoteCard v-if="showPrepExisting" type="warning">
          {{ t('aktenschrank', 'The folder you\'ve selected isn\'t empty. Please ensure it is safe to store new data here and that no important files will be overwritten.') }}
        </NcNoteCard>
        <NcNoteCard v-if="showPrepAvailable" type="info">
          {{ t('aktenschrank', 'The folder you\'ve selected doesn\'t exist. I\'ll create it for you.') }}
        </NcNoteCard>

      </FrameGroup>

    </template>
    <template #buttons>
      <NcButton v-if="isClosable"
        @click="cancelSettings">
        {{ t('aktenschrank', 'Cancel') }}
      </NcButton>
      <NcButton type="primary" :wide="!isClosable"
        @click="saveSettings">
        {{ t('aktenschrank', 'Save') }}
      </NcButton>
    </template>

    <FilePickerDialog ref="filePickerDialog" :z="10010" />

  </BasicDialog>
</template>

<script>
import { NcButton, NcNoteCard } from '@nextcloud/vue'

import CogIcon from 'vue-material-design-icons/Cog.vue'

import AcReadonlyTextbox from '../components/AcReadonlyTextbox.vue'
import BasicDialog from './BasicDialog.vue'
import FilePickerDialog from './FilePickerDialog.vue'
import FrameGroup from '../components/FrameGroup.vue'
import FrameInput from '../components/FrameInput.vue'

import { useSettingsStore } from '../modules/store.js'
import { mapState } from 'pinia'

/**
 * Dialog to change app settings.
 */
export default {
  name: 'SettingsDialog',

  components: {
    NcButton,
    NcNoteCard,

    CogIcon,

    AcReadonlyTextbox,
    BasicDialog,
    FilePickerDialog,
    FrameGroup,
    FrameInput,
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
    isVisible: false,
    isClosable: true,
    isLoading: true,

    cabinet: {
      path: '',
      selected: { path: '', isExisting: false, isWritable: false },
    },

    resolvePromise: undefined,
    rejectPromise: undefined,
  }),
  computed: {

    // #region Visibility

    isSettingCabinetChanged() {
      return this.cabinet.path !== this.cabinet.selected.path
    },

    showReady() {
      return this.isCabinetConfigured && this.isCabinetWritable
    },
    showPermIssue() {
      return !this.isSettingCabinetChanged && this.isCabinetConfigured && this.isCabinetExisting && !this.isCabinetWritable
    },
    showCabMissing() {
      return !this.isSettingCabinetChanged && this.isCabinetConfigured && !this.isCabinetWritable
    },

    showPrepSetup() {
      return !this.isCabinetConfigured
    },
    showPrepPermIssue() {
      return !this.isCabinetConfigured && this.cabinet.selected.isExisting && !this.cabinet.selected.isWritable
    },
    showPrepExisting() {
      return !this.isCabinetConfigured && this.cabinet.selected.isExisting && this.cabinet.selected.isWritable
    },
    showPrepAvailable() {
      return !this.isCabinetConfigured && !this.cabinet.selected.isExisting
    },

    // #endregion
    // #region Language

    langCabinetPathPlaceholder() {
      return this.cabinet.path ?? t('aktenschrank', 'Working Folder')
    },

    // #endregion

    // #region Store

    ...mapState(useSettingsStore, [
      'cabinetPath',
      'isCabinetReady',
      'isCabinetConfigured',
      'isCabinetExisting',
      'isCabinetWritable',
    ]),

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
      this.isClosable = opts?.isClosable ?? true

      this.cabinet.path = this.cabinetPath
      this.cabinet.selected.path = this.cabinetPath
      this.cabinet.selected.isExisting = this.isCabinetExisting
      this.cabinet.selected.isWritable = this.isCabinetWritable

      this.isLoading = false

      // show and return Promise
      return this.$refs.basicDialog.open({
        title: t('aktenschrank', 'Settings'),
        subtitle: t('aktenschrank', 'Configure your preferences and app settings.'),
        isClosable: this.isClosable,
      })

    },

    /**
     * This method saves the settings and closes the dialog.
     * @return {undefined}
     */
    async saveSettings() {
      this.$refs.basicDialog.resolve(true)
    },

    /**
     * This method closes the dialog without saving.
     * @return {undefined}
     */
    cancelSettings() {
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
          this.saveSettings()
          e.stopImmediatePropagation()
          break
        case 'Escape':
          this.cancelSettings()
          e.stopImmediatePropagation()
          break
      }
    },

    // #endregion

    // #region Cabinet

    /**
     * This method open a folder picker dialog and set cabinet selected data accordingly.
     * @return {undefined}
     */
    async chooseCabinet() {
      const selectedFolder = await this.$refs.filePickerDialog.open({
        mode: 'folder',
        description: t('aktenschrank', 'Pick a working folder for the filing cabinet.'),
      })
      if (selectedFolder) {
        console.log('changed')
      }
    },

    // #endregion

  },
}
</script>
