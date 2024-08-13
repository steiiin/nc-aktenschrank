<!-- AcNode.vue -->
<template>
  <div class="aktn-list--node" :class="{ 'aktn-list--node-disabled': disabled }"
    @click="clickNode(node)">
    <GroupFolderIcon v-if="isGroupFolder" />
    <FolderIcon v-else-if="isFolder" />
    <FileIcon v-else-if="isFile" />
    {{ node.name }} {{ isGroupFolder ? '('+ t('aktenschrank', 'Gruppenordner') + ')' : '' }}
  </div>
</template>

<script>

import FileIcon from 'vue-material-design-icons/File.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import GroupFolderIcon from 'vue-material-design-icons/FolderSwap.vue'

/**
 * Base component.
 */
export default {
  name: 'AcNode',

  components: {
    FileIcon,
    FolderIcon,
    GroupFolderIcon,
  },
  props: {
    node: {
      type: Object,
      required: true,
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false,
    },
  },

  computed: {
    isFolder() {
      return this.node.type === 'folder'
    },
    isGroupFolder() {
      return (this.node.type === 'folder' && this.node.isGroupfolder) ?? false
    },
    isFile() {
      return this.node.type === 'file'
    },
  },

  methods: {
    clickNode(node) {
      if (this.disabled) { return }
      this.$emit('click', node)
    },
  },
}

</script>
