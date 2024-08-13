<!-- AcInfoCard.vue -->
<template>
  <NcNoteCard :type="type">
    <template #icon>
      <slot name="icon" />
    </template>
    <div v-html="langMessageText" /> <!-- eslint-disable-line -->
  </NcNoteCard>
</template>

<script>
import { NcNoteCard } from '@nextcloud/vue'

import { getFormattedMessage } from '../modules/manipulations.js'

/**
 * Base component.
 */
export default {
  name: 'AcInfoCard',

  components: {
    NcNoteCard,
  },
  props: {
    type: {
      type: String,
      required: false,
      default: 'info',
    },
    message: {
      type: String,
      required: true,
    },
  },

  computed: {
    langMessageText() {
      return getFormattedMessage(this.message)
    },
  },
}
</script>
