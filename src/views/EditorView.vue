<!-- EditorView.vue -->
<template>
  <div v-if="!!currentItem" class="aktn-EditorView">

    <FrameGroup :title="t('aktenschrank', 'General Informations')">

        <FrameInput
          :label="t('aktenschrank', 'Title')" :wide="true"
          for="id-input-editor-title" :error="!isValidTitle">
          <template #control>
            <NcTextField id="id-input-editor-title"
              :value.sync="currentItem.document.title" :placeholder="undo.title"
              :error="!isValidTitle" :label-outside="true"
              @update:value="currentItem.document.title=cleanFilename(currentItem.document.title)" />
          </template>
          <template #error>
            {{ t('aktenschrank', 'You must provide a title.') }}
          </template>
        </FrameInput>

        <FrameInput
          :label="t('aktenschrank', 'Date mentioned in this document')"
          for="id-input-editor-timementioned" :error="!isValidTimeMentioned">
          <template #control>
            <NcDateTimePickerNative id="id-input-editor-timementioned"
              :value="getMomentFromTimestamp(currentItem.document.timeMentioned).toJSDate()"
              type="date" :hide-label="true"
              @input="updateTimeMentioned" />
          </template>
          <template #error>
            {{ t('aktenschrank', 'You must provide a date that is mentioned in the document.') }}
          </template>
        </FrameInput>

    </FrameGroup>

    <FrameGroup :title="t('aktenschrank', 'Mailbox')">

        <FrameInput
          :label="t('aktenschrank', 'Recipient')"
          for="id-input-editor-recipient" :error="!isValidRecipient">
          <template #control>
            <NcSelect v-model="currentItem.document.recipientId" input-id="id-input-editor-recipient"
              :options="recipients.options" :selectable="(o) => !o.isGroupheader" label="name" :label-outside="true"
              :reduce="(o) => option.id" :loading="recipients.isLoading" />
          </template>
          <template #actions>
            <NcButton v-tooltip="{ content: langActionEditRecipients }" :aria-label="langActionEditRecipients"
              type="secondary" @click="$emit('accept')">
              <template #icon>
                <EditPropsIcon />
              </template>
            </NcButton>
          </template>
          <template #error>
            {{ t('aktenschrank', 'You must select a recipient.') }}
          </template>
        </FrameInput>

    </FrameGroup>

    <NcButton @click="debug">
      DEBUG
    </NcButton>

  </div>
</template>

<script>
import { NcButton, NcDateTimePickerNative, NcSelect, NcTextField, Tooltip } from '@nextcloud/vue'

import FrameGroup from '../components/FrameGroup.vue'
import FrameInput from '../components/FrameInput.vue'

import EditPropsIcon from 'vue-material-design-icons/DatabaseCogOutline.vue'

import { useSettingsStore } from '../modules/store.js'
import { mapActions } from 'pinia'

import { cleanFilename } from '../modules/validation.js'
// import { DateTime } from 'luxon'

/**
 * EditorView.
 */
export default {
  name: 'EditorView',

  components: {
    NcButton,
    NcDateTimePickerNative,
    NcSelect,
    NcTextField,

    FrameGroup,
    FrameInput,

    EditPropsIcon,
  },
  directives: {
    Tooltip,
  },
  props: {
    item: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      isLoading: true,
      currentItem: null,
      undo: { title: '' },

      recipients: { isLoading: true, options: [] },
    }
  },
  computed: {

    // #region Language

    langActionEditRecipients() {
      return t('aktenschrank', 'Edit Recipients')
    },

    // #endregion

    // #region Validation

    // #region General Information

    isValidTitle() {
      return this.currentItem?.document.title.trim().length > 0 ?? false
    },

    isValidTimeMentioned() {
      return !!this.currentItem?.document.timeMentioned
    },

    // #endregion

    // #region Mailbox

    isValidRecipient() {
      return !!this.currentItem?.document.recipientId
    },

    // #endregion

    isValid() {
      return this.isValidTitle
        && this.isValidTimeMentioned
    },

    // #endregion

    // #region Store

    // #endregion

  },
  watch: {

    'item'(newItem, oldItem) {

      if (!newItem) {
        this.currentItem = null
        return
      }
      // TODO: this.loadProps()
      this.loadItem()

    },

  },

  async mounted() {

    this.loadItem()

  },

  methods: {

    // #region Load Data

    async loadItem() {
      this.currentItem = this.item ?? null
      this.undo.title = (' ' + this.item?.document.title ?? '').slice(1)
      // TODO: setEditorSnapshot(this.documentSnapshot, true)
    },

    // #endregion

    // #region Validation

    updateTimeMentioned(date) {
      const datetime = this.getMomentFromDate(date)
      this.currentItem.document.timeMentioned = datetime.toSeconds()
    },

    cleanFilename,

    // #endregion

    // #region Store

    ...mapActions(useSettingsStore, ['getMomentFromTimestamp', 'getMomentFromDate']),

    // #endregion

    debug() {
      debugger
    },

  },
}
</script>
<style lang="scss">
#id-input-editor-timementioned {
  border: var(--border-width-input, 2px) solid var(--color-border-maxcontrast);
}
</style>
<style lang="scss" scoped>
.aktn-EditorView {
  padding: .5rem;
}

</style>
