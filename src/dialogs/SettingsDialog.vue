<!-- SettingsDialog.vue -->
<template>
  <BasicDialog ref="basicDialog"
    label="settings" :z="z" size="large"
    :is-loading="isLoading"
    @close="cancelSettings">

    <template #content>

      <FrameGroup :title="t('aktenschrank', 'Working Directory')">

        <AcInfoCard v-if="showReady" type="success"
          :message="t('aktenschrank', `You've properly set up the filing cabinet.`)" />
        <AcInfoCard v-if="showPrepSetup" type="success"
          :message="t('aktenschrank', 'You must first set up my working directory before you can use the filing cabinet.')">
          <template #icon>
            <CogIcon />
          </template>
        </AcInfoCard>

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

        <AcInfoCard v-if="!isValidCabinetPath && !showCabMissing" type="error"
          :message="t('aktenschrank', 'You must provide a valid path.')" />
        <AcInfoCard v-if="showPermReadonly" type="info"
          :message="t('aktenschrank', 'You only have {b}read-only{/b} access.')" />
        <AcInfoCard v-if="showPermIssue" type="error"
          :message="t('aktenschrank', 'The filing cabinet exists, but you do not have write access. Please check the folder permissions.')" />
        <AcInfoCard v-if="showCabMissing" type="error"
          :message="t('aktenschrank', 'The filing cabinet is missing. You set it up before - {b}did you delete it by mistake?{/b}')" />

        <AcInfoCard v-if="showPrepHasChildren" type="warning"
          :message="t('aktenschrank', `The folder you've selected {b}isn't empty{/b}.{break}Normally, an empty folder should be choosed unless you're sure that the selected one is already registered as a {b}Filing Cabinet{/b} by another user.`)" />
        <AcInfoCard v-if="showPrepAlreadyCabinet" type="info"
          :message="t('aktenschrank', `The folder you've selected {b}is already a filing cabinet{/b}.{break}You'll switch to this one.`)" />
        <AcInfoCard v-if="showPrepAvailable" type="info"
          :message="t('aktenschrank', `The folder you've selected doesn't exist.{break}I'll create it for you.`)" />
        <AcInfoCard v-if="showGroupfolder" type="info"
          :message="t('aktenschrank', `The folder you've selected is a {b}group folder{/b}.{break}The filing cabinet is {b}shared by every user{/b} who can access it.`)" />

      </FrameGroup>

    </template>
    <template #buttons>
      <NcButton v-if="isClosable"
        @click="cancelSettings">
        {{ t('aktenschrank', 'Cancel') }}
      </NcButton>
      <NcButton type="primary" :wide="!isClosable" :disabled="!isValidDialog"
        @click="saveSettings">
        {{ t('aktenschrank', 'Save') }}
      </NcButton>
    </template>

    <ConfirmDialog ref="confirmDialog" :z="10010" />
    <FilePickerDialog ref="filePickerDialog" :z="10010" />

  </BasicDialog>
</template>

<script>
import { NcButton } from '@nextcloud/vue'

import CogIcon from 'vue-material-design-icons/Cog.vue'

import AcInfoCard from '../components/AcInfoCard.vue'
import AcReadonlyTextbox from '../components/AcReadonlyTextbox.vue'
import BasicDialog from './BasicDialog.vue'
import ConfirmDialog from './ConfirmDialog.vue'
import FilePickerDialog from './FilePickerDialog.vue'
import FrameGroup from '../components/FrameGroup.vue'
import FrameInput from '../components/FrameInput.vue'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { isValidPathValue } from '../modules/validation.js'
import { useSettingsStore } from '../modules/store.js'
import { mapState } from 'pinia'

/**
 * Dialog to change app settings.
 */
export default {
  name: 'SettingsDialog',

  components: {
    NcButton,

    CogIcon,

    AcInfoCard,
    AcReadonlyTextbox,
    BasicDialog,
    ConfirmDialog,
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
      selected: { path: '', isExisting: false, hasChildren: false, isGroupfolder: false, isCabinet: false },
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
      return !this.isSettingCabinetChanged && this.isCabinetConfigured && this.isCabinetWritable
    },
    showPermIssue() {
      return !this.isSettingCabinetChanged && this.isCabinetConfigured && this.isCabinetExisting && !this.isCabinetReadable
    },
    showPermReadonly() {
      return !this.isSettingCabinetChanged && this.isCabinetConfigured && this.isCabinetExisting && this.isCabinetReadable && !this.isCabinetWritable
    },
    showCabMissing() {
      return !this.isSettingCabinetChanged && this.isCabinetConfigured && !this.isCabinetExisting
    },

    showPrepSetup() {
      return !this.isCabinetConfigured
    },
    showPrepHasChildren() {
      return this.isSettingCabinetChanged && this.cabinet.selected.isExisting && !this.cabinet.selected.isCabinet && this.cabinet.selected.hasChildren
    },
    showPrepAlreadyCabinet() {
      return this.isSettingCabinetChanged && this.cabinet.selected.isExisting && this.cabinet.selected.isCabinet
    },
    showPrepAvailable() {
      return this.isSettingCabinetChanged && !this.cabinet.selected.isExisting && !this.cabinet.selected.isCabinet
    },
    showGroupfolder() {
      return this.isSettingCabinetChanged && this.cabinet.selected.isGroupfolder
    },

    // #endregion
    // #region Language

    langCabinetPathPlaceholder() {
      return this.cabinet.path ?? t('aktenschrank', 'Working Folder')
    },

    // #endregion
    // #region Validation

    isValidCabinetPath() {
      return isValidPathValue(this.cabinet.selected.path)
    },

    hasAnythingChanged() {
      return true
    },
    isValidDialog() {
      return this.hasAnythingChanged && this.isValidCabinetPath
    },

    // #endregion

    // #region Store

    ...mapState(useSettingsStore, [
      'cabinetPath',
      'isCabinetReady',
      'isCabinetConfigured',
      'isCabinetExisting',
      'isCabinetReadable',
      'isCabinetWritable',
      'isCabinetGroupfolder',
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

      // ask user about action
      let moveCab = false
      if (this.isCabinetConfigured && this.isSettingCabinetChanged) {

        if (this.isCabinetGroupfolder || this.cabinet.selected.isGroupfolder || this.cabinet.selected.isCabinet) {

          let textGrFo = ''
          if (this.isCabinetGroupfolder && this.cabinet.selected.isGroupfolder) {
            textGrFo = t('aktenschrank', 'Both your current working folder and the selected one are group folders, and you {b}cannot move{/b} either of them.')
          } else if (this.isCabinetGroupfolder) {
            textGrFo = t('aktenschrank', 'Your current working folder is a group folder and {b}cannot be moved{/b} to a new location.')
          } else if (this.cabinet.selected.isGroupfolder) {
            textGrFo = t('aktenschrank', 'The selected folder is a group folder, and you {b}cannot move{/b} your current data there.')
          }

          let textSwit = ''
          let textBtn = ''
          if (this.cabinet.selected.isCabinet) {
            textSwit = t('aktenschrank', 'The selected location already contains a {b}Filing Cabinet{/b}. {break}You can only {b}settle in{/b} this location.')
            textBtn = t('aktenschrank', 'Settle In')
          } else {
            textSwit = t('aktenschrank', 'You can only create a {b}new, empty Filing Cabinet{/b} in the selected location.')
            textBtn = t('aktenschrank', 'Create New')
          }

          // no moving allowed
          const proceed = await this.$refs.confirmDialog.open({
            title: t('aktenschrank', 'Change Filing Cabinet?'),
            message: t('aktenschrank', "You've already configured a Filing Cabinet.{break}" + textGrFo + '{break}' + textSwit),
            negText: t('aktenschrank', 'Cancel'),
            posText: textBtn,
            important: false,
          })
          if (!proceed) {
            return
          }

        } else {

          // moving allowed
          moveCab = await this.$refs.confirmDialog.open({
            title: t('aktenschrank', 'Move Filing Cabinet?'),
            message: t('aktenschrank', "You've already configured a Filing Cabinet.{break}Would you like to {b}move your data{/b} to the new location or {b}create a new{/b} Filing Cabinet there?"),
            negText: t('aktenschrank', 'Create New'),
            posText: t('aktenschrank', 'Move My Cabinet'),
            important: true,
          })

        }

      }

      this.isLoading = true
      try {

        // POST Settings
        const query = { cabinet: { path: this.cabinet.selected.path, moveExisting: moveCab ?? false } }
        const response = (await axios.post(generateUrl('apps/aktenschrank/api/settings'), query)).data
        if (!(response?.success ?? false)) { throw new Error('no success transmitted') }

        // close dialog
        this.$refs.basicDialog.resolve(true)

      } catch (error) {

        showError(t('aktenschrank', 'Error while updating the settings.'))
        console.error(error)

      } finally {

        this.isLoading = false

      }

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

      // skip, if not active
      if (!this.$refs.basicDialog.isDialogActive) { return }

      // send keys to active children
      if (this.$refs.filePickerDialog.$refs.basicDialog.isDialogActive) {
        this.$refs.filePickerDialog.handleKeydown(e)
        return
      }

      // catch keys
      switch (e.key) {
        case 'Enter':
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
        this.cabinet.selected.path = selectedFolder.path
        this.cabinet.selected.isExisting = true
        this.cabinet.selected.hasChildren = selectedFolder.hasChildren
        this.cabinet.selected.isGroupfolder = selectedFolder.isGroupfolder
        this.cabinet.selected.isCabinet = selectedFolder.isCabinet
      }
    },

    // #endregion

  },
}
</script>
