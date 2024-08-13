<!-- FrameInputInline.vue -->
<template>
  <FrameInput
    :label="label"
    :for="inputId">
    <template #control>
      <NcTextField :id="inputId" :value.sync="currentText" :error="!valid"
      @update:value="currentText = cleanInput(currentText)" />
    </template>
    <template #actions>
      <NcButton v-show="valid" v-tooltip="{ content: langActionAccept }" :aria-label="langActionAccept"
        type="tertiary" @click="$emit('accept')">
        <template #icon>
          <AcceptIcon />
        </template>
      </NcButton>
      <NcButton v-tooltip="{ content: langActionCancel }" :aria-label="langActionCancel"
        type="tertiary" @click="$emit('cancel')">
        <template #icon>
          <CancelIcon />
        </template>
      </NcButton>
    </template>
  </FrameInput>
</template>

<script>
import { NcButton, NcTextField, Tooltip } from '@nextcloud/vue'

import AcceptIcon from 'vue-material-design-icons/Check.vue'
import CancelIcon from 'vue-material-design-icons/CloseOutline.vue'

import FrameInput from './FrameInput.vue'

import { cleanFilename, cleanMail, cleanPhone, cleanUrl } from '../modules/validation.js'

/**
 * Base component.
 */
export default {
  name: 'FrameInputInline',

  components: {
    NcButton,
    NcTextField,

    AcceptIcon,
    CancelIcon,

    FrameInput,
  },
  directives: {
    Tooltip,
  },

  props: {
    inputId: {
      type: String,
      required: true,
    },
    label: {
      type: String,
      required: false,
      default: null,
    },
    value: {
      type: String,
      required: true,
    },
    valid: {
      type: Boolean,
      required: true,
    },
    clean: {
      type: String,
      required: false,
      default: '',
    },
  },

  data: () => ({
    currentText: '',
  }),
  computed: {
    langActionAccept() {
      return t('aktenschrank', 'Accept')
    },
    langActionCancel() {
      return t('aktenschrank', 'Cancel')
    },
  },
  watch: {
    currentText(newText) {
      this.$emit('update:value', newText)
    },
  },

  mounted() {
    this.currentText = this.value
  },

  methods: {
    cleanInput(text) {
      switch (this.clean) {
        case 'filename':
          return cleanFilename(text)
        case 'mail':
          return cleanMail(text)
        case 'phone':
          return cleanPhone(text)
        case 'url':
          return cleanUrl(text)
        default:
          return text
      }
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

}
</style>
