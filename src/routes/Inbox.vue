<!-- Inbox.vue -->
<template>
  <main id="inboxContent" class="aktn-app aktn-Inbox"
    :class="{
      'aktn-Inbox--mobile': isSizeMobile,
      'aktn-Inbox--mobile-showlist': isSizeMobile && showList,
      'aktn-Inbox--mobile-showcontent': isSizeMobile && !showList,
      'aktn-Inbox--wide': isSizeWide,
    }">
    <div class="aktn-app--header">
      <NcButton v-tooltip="langBackToNav" class="aktn-Inbox--listToggle"
        :aria-label="langBackToNav" @click="showList=true">
        <template #icon>
          <BackToListIcon />
        </template>
      </NcButton>
      Inbox
    </div>
    <div class="aktn-app--content">
      <div class="aktn-Inbox--list">

        <div class="aktn-app--toolbar">

          <div class="dynSpace" />

          <NcActions>
            <template #icon>
              <SortIcon />
            </template>
            <NcActionButton :model-value.sync="inboxSortBy" type="radio" value="abc-asc">
              <template #icon>
                <SortByAbcAscIcon />
              </template>
              {{ t('aktenschrank', 'Alphabetical (A-Z)') }}
            </NcActionButton>
            <NcActionButton :model-value.sync="inboxSortBy" type="radio" value="abc-desc">
              <template #icon>
                <SortByAbcDescIcon />
              </template>
              {{ t('aktenschrank', 'Alphabetical (Z-A)') }}
            </NcActionButton>
            <NcActionSeparator />
            <NcActionButton :model-value.sync="inboxSortBy" type="radio" value="time-desc">
              <template #icon>
                <SortByTimeDescIcon />
              </template>
              {{ t('aktenschrank', 'Added Date (Newest)') }}
            </NcActionButton>
            <NcActionButton :model-value.sync="inboxSortBy" type="radio" value="time-asc">
              <template #icon>
                <SortByTimeAscIcon />
              </template>
              {{ t('aktenschrank', 'Added Date (Oldest)') }}
            </NcActionButton>
          </NcActions>

          <NcButton v-if="!isInboxLoading"
            v-tooltip="langTbRefresh" :aria-label="langTbRefresh" type="tertiary"
            @click="updateInbox">
            <template #icon>
              <RefreshIcon />
            </template>
          </NcButton>

          <NcLoadingIcon v-if="isInboxLoading && isInboxInitialized" class="loadingIcon" />

        </div>

        <AcNoContent v-if="isInboxEmpty && isInboxInitialized"
          :title="t('aktenschrank', `Your Inbox's Empty`)" class="aktn-Inbox--nocontent" />
        <NcLoadingIcon v-if="isInboxEmpty && !isInboxInitialized" :size="48"
          class="aktn-Inbox--nocontent" />

        <div class="aktn-Inbox--list-items">

          <NcListItem v-for="inboxItem in sortedInboxItems" :key="getInboxItemId(inboxItem)"
            :name="inboxItem.file.name"
            :active="getInboxItemId(inboxItem) === currentInboxItemId"
            @click="selectItem(inboxItem)">
            <template #details>
              <NcDateTime :timestamp="getMomentFromTimestamp(inboxItem.document.timeAdded).toJSDate()" :ignore-seconds="true" relative-time="narrow" />
            </template>
            <template #subname>
              {{ getInboxMime(inboxItem.document.fileMime) }}
            </template>
          </NcListItem>

        </div>

        <div v-if="isInboxLoading" class="aktn-Inbox--disabledList">
          &nbsp; <!-- disable clicking while loading -->
        </div>

      </div>
      <div v-if="isInboxItemSelected" class="aktn-Inbox--data">
        <div class="aktn-Inbox--wrapper">
          <div class="aktn-Inbox--editor">

            <div class="aktn-app--toolbar">

              <header>
                {{ currentInboxItemName }}
              </header>

              <div class="dynSpace" />

              <NcButton v-if="!isSizeWide"
                type="primary"
                @click="showFile=true">
                {{ t('aktenschrank', 'Show Preview') }}
              </NcButton>

            </div>

            <EditorView class="aktn-Inbox--editor-view" :item="currentInboxItem" />

          </div>
          <div v-if="isSizeWide" class="aktn-Inbox--file">

            <FileView class="aktn-Inbox--file-view" />

          </div>
        </div>
      </div>
    </div>

    <NcModal :name="currentInboxItemName"
      :close-button-contained="false" size="full"
      :show.sync="showFile">
      <FileView />
    </NcModal>

    <ConfirmDialog ref="confirmDialog" :z="10000" />

  </main>
</template>

<script>
import { NcActions, NcActionButton, NcActionSeparator, NcButton, NcDateTime, NcListItem, NcLoadingIcon, NcModal, Tooltip } from '@nextcloud/vue'

import BackToListIcon from 'vue-material-design-icons/MenuOpen.vue'
import RefreshIcon from 'vue-material-design-icons/Refresh.vue'
import SortIcon from 'vue-material-design-icons/Sort.vue'
import SortByAbcAscIcon from 'vue-material-design-icons/SortAlphabeticalAscending.vue'
import SortByAbcDescIcon from 'vue-material-design-icons/SortAlphabeticalDescending.vue'
import SortByTimeAscIcon from 'vue-material-design-icons/SortClockAscendingOutline.vue'
import SortByTimeDescIcon from 'vue-material-design-icons/SortClockDescendingOutline.vue'

import AcNoContent from '../components/AcNoContent.vue'
import ConfirmDialog from '../dialogs/ConfirmDialog.vue'
import EditorView from '../views/EditorView.vue'
import FileView from '../views/FileView.vue'

import { useInboxStore, useSettingsStore } from '../modules/store.js'
import { mapActions, mapState } from 'pinia'

import ContentService from '../service/contentService.js'

/**
 * Inbox route.
 */
export default {
  name: 'Inbox',

  components: {
    NcActions,
    NcActionButton,
    NcActionSeparator,
    NcButton,
    NcDateTime,
    NcListItem,
    NcLoadingIcon,
    NcModal,

    BackToListIcon,
    RefreshIcon,
    SortIcon,
    SortByAbcAscIcon,
    SortByAbcDescIcon,
    SortByTimeAscIcon,
    SortByTimeDescIcon,

    AcNoContent,
    ConfirmDialog,
    EditorView,
    FileView,
  },
  directives: {
    Tooltip,
  },

  data() {
    return {

      contentWidth: 0,
      showList: true,
      showFile: false,

      inboxSortBy: 'time-desc',
      currentInboxItem: null,

    }
  },
  computed: {

    // #region Language

    langBackToNav() {
      return t('aktenschrank', 'Back To Inbox')
    },
    langTbRefresh() {
      return t('aktenschrank', 'Refresh Inbox')
    },

    // #endregion

    // #region Responsive

    isSizeMobile() {
      return this.contentWidth < 750 /* 300px + 450px */
    },

    isSizeWide() {
      return this.contentWidth >= 1200 /* 300px + 450px + 450px */
    },

    // #endregion

    // #region Store

    sortedInboxItems() {
      const copy = this.inboxItems.slice(0)
      if (this.inboxSortBy === 'abc-asc') {
        return copy.sort((a, b) => a.document.title.localeCompare(b.document.title))
      } else if (this.inboxSortBy === 'abc-desc') {
        return copy.sort((a, b) => b.document.title.localeCompare(a.document.title))
      } else if (this.inboxSortBy === 'time-asc') {
        return copy.sort((a, b) => a.document.timeAdded - b.document.timeAdded)
      } else if (this.inboxSortBy === 'time-desc') {
        return copy.sort((a, b) => b.document.timeAdded - a.document.timeAdded)
      } else {
        return copy
      }
    },

    isInboxItemSelected() {
      return this.currentInboxItem !== null
    },

    currentInboxItemId() {
      return this.currentInboxItem?.file.node_id ?? null
    },
    currentInboxItemName() {
      return this.getInboxItemTitle(this.currentInboxItem)
    },

    ...mapState(useInboxStore, ['isInboxLoading', 'isInboxInitialized', 'isInboxEmpty', 'inboxItems']),

    // #endregion

  },

  async mounted() {

    // responsive width
    this.resizeObserver = new ResizeObserver((entries) => {
      for (const entry of entries) {
        this.contentWidth = entry.contentRect.width
      }
    })
    this.resizeObserver.observe(document.getElementById('inboxContent'))

    // update inbox
    this.updateInbox()

    // register visibilitychange
    document.addEventListener('visibilitychange', this.updateOnVisibilityChange)
    window.addEventListener('focus', this.updateOnVisibilityChange)

  },
  unmounted() {

    // remove visibilitychange
    document.removeEventListener('visibilitychange', this.updateOnVisibilityChange)
    window.removeEventListener('focus', this.updateOnVisibilityChange)

  },

  methods: {

    // #region Inbox

    selectItem(inboxItem) {
      this.showList = false
      this.currentInboxItem = inboxItem
    },

    // #endregion

    // #region Store

    async updateInbox() {

      await this.getInbox() /* TODO: emit error */

      if (this.currentInboxItemId == null) { return }
      const currentExisting = this.inboxItems?.some((a) => this.getInboxItemId(a) === this.currentInboxItemId)
      if (!currentExisting) {

        // display list & close editor
        this.showList = true
        this.currentInboxItem = null

        // inform user
        await this.$refs.confirmDialog.open({
          title: t('aktenschrank', 'Inbox changed'),
          message: t('aktenschrank', 'Your currently selected document has been {b}removed{/b}.'),
          posText: t('aktenschrank', 'OK'),
          onlypos: true,
        })

      }
    },
    async updateOnVisibilityChange(a) {
      await new Promise(resolve => setTimeout(resolve, 500))
      if (!document.hidden) {
        this.updateInbox()
      }
    },

    getInboxItemId(inboxItem) {
      return inboxItem?.file.node_id
    },
    getInboxItemTitle(inboxItem) {
      const title = inboxItem?.document.title
      return (!title || title.trim().length < 1) ? t('aktenschrank', 'New Document') : title
    },

    ...mapActions(useInboxStore, ['getInbox']),
    ...mapActions(useSettingsStore, ['getMomentFromTimestamp']),

    // #endregion

    // #region ContentService

    getInboxMime(mimetype) {
      return ContentService.getMimeText(mimetype)
    },

    // #endregion

  },
}
</script>
<style lang="scss" scoped>

$editor-width: 450px;

.aktn-Inbox {

  display: flex;
  flex-direction: column;
  flex: 1;
  background: var(--color-main-background);
  position: relative;
  margin: 0;

  // list

  &--list {
    width: var(--navigation-width);
    overflow: hidden;
    border-right: 1px solid var(--color-border-dark);
    position: relative;

    display: flex;
    flex-direction: column;
    &-items {
      overflow-y: scroll;
    }
  }

  &--data {
    flex: 1;
    overflow: hidden;
  }

  &--listToggle {
    display: none;
    position: absolute;
    left: var(--aktn-app-header-padding);
    z-index: 10000;
    top: var(--aktn-app-header-padding);
  }

  &--disabledList {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: transparent;
    pointer-events: all;
  }

  &--mobile {

    &-showlist {

      .aktn-Inbox--list {
        width: 100%;
        flex: 1;
      }
      .aktn-Inbox--data {
        width: 0;
        flex: 0;
      }

    }

    &-showcontent {

      .aktn-Inbox--list {
        width: 0;
        flex: 0;
      }
      .aktn-Inbox--data {
        width: 100%;
        flex: 1;
      }

      & .aktn-Inbox--listToggle {
        display: flex;
      }

    }

  }

  // editor

  &--wrapper {
    display: flex;
    flex-direction: row;
    height: 100%;
  }

  &--editor {
    flex: 1;
    max-width: 100%;

    &-view {
      height: calc(100% - var(--aktn-app-header-height));
      width: 100%;
    }
  }

  &--file {
    flex: 0;

    &-view {
      height: 100%;
      width: 100%;
    }
  }

  &--wide {

    .aktn-Inbox--editor {
      flex: 0 0 $editor-width;
    }

    .aktn-Inbox--file {
      flex: 1;
    }

  }

}

// nocontent-placement

.aktn-Inbox--nocontent {
  margin-top: 33%;
}
@media only screen and (max-height: 620px) {
  .aktn-Inbox--nocontent {
    margin-top: 10%;
  }
}
@media only screen and (max-height: 330px) {
  .aktn-Inbox--nocontent {
    margin-top: 1rem;
  }
}

</style>
