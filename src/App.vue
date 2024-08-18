<!-- App.vue -->
<template>
  <NcContent app-name="aktenschrank">

    <!-- AppCrash -->
    <NcAppContent v-if="isSettingsFailed" class="crash--wrapper">
      <div class="crash--center">
        <NcNoteCard type="error">
          {{ t("aktenschrank", "Error while fetching the app configuration.") }} <br>
          {{ t("aktenschrank", "Try to reload the page and make sure there is network connectivity.") }}
        </NcNoteCard>
        <NcButton :wide="true" @click="reloadApp">
          {{ t('aktenschrank', 'Reload') }}
        </NcButton>
      </div>
    </NcAppContent>

    <!-- AppLoading -->
    <NcAppContent v-else-if="isSettingsLoading" class="loading--wrapper">
      <div class="loading--center">
        <NcLoadingIcon :size="64" />
      </div>
    </NcAppContent>

    <!-- App -->
    <template v-else>

      <NcAppNavigation>
        <template #list>
          <NcAppNavigationItem :name="t('aktenschrank', 'Timeline')" to="/timeline">
            <TimelineIcon slot="icon" />
            <!-- <NcCounterBubble v-show="timelineOpen > 0" slot="counter" type="highlighted">
              {{ timelineOpen }}
            </NcCounterBubble> -->
          </NcAppNavigationItem>
          <NcAppNavigationItem :name="t('aktenschrank', 'Inbox')" to="/inbox">
            <InboxIcon slot="icon" />
            <NcCounterBubble v-show="inboxCount > 0" slot="counter" type="highlighted">
              {{ inboxCount }}
            </NcCounterBubble>
          </NcAppNavigationItem>
          <NcAppNavigationItem :name="t('aktenschrank', 'Archive')" to="/archive">
            <ArchiveIcon slot="icon" />
          </NcAppNavigationItem>
        </template>
        <template #footer>
          <ul class="app-navigation-entry__settings">
            <NcAppNavigationItem :name="t('aktenschrank', 'Settings')"
              @click="openSettings()">
              <CogIcon slot="icon" />
            </NcAppNavigationItem>
          </ul>
        </template>
      </NcAppNavigation>
      <router-view />

      <SettingsDialog ref="settingsDialog" :z="10000" />

    </template>

  </NcContent>
</template>

<script>
import { NcAppContent, NcAppNavigation, NcAppNavigationItem, NcButton, NcContent, NcCounterBubble, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'

import ArchiveIcon from 'vue-material-design-icons/Archive.vue'
import CogIcon from 'vue-material-design-icons/Cog.vue'
import InboxIcon from 'vue-material-design-icons/Inbox.vue'
import TimelineIcon from 'vue-material-design-icons/TimelineClockOutline.vue'

import SettingsDialog from './dialogs/SettingsDialog.vue'

import { useSettingsStore, useInboxStore } from './modules/store.js'
import { mapActions, mapState } from 'pinia'

/**
 * Base component.
 */
export default {
  name: 'App',

  components:
  {
    NcAppContent,
    NcAppNavigation,
    NcAppNavigationItem,
    NcButton,
    NcContent,
    NcCounterBubble,
    NcLoadingIcon,
    NcNoteCard,

    ArchiveIcon,
    CogIcon,
    InboxIcon,
    TimelineIcon,

    SettingsDialog,
  },

  data() {
    return {

    }
  },
  computed: {
    ...mapState(useSettingsStore, ['isSettingsLoading', 'isSettingsFailed', 'isCabinetReady']),
    ...mapState(useInboxStore, ['inboxCount']),
  },

  async mounted() {
    await this.reloadAppSettings()
  },

  methods:
  {

    // #region App

    /**
     * This method reloads window and thus also the app itself.
     * @return {undefined}
     */
    reloadApp() {
      document.location.reload()
    },

    /**
     * This method loads app settings and decide if settings dialog is necessary.
     * @return {undefined}
     */
    async reloadAppSettings() {
      await this.getAppSettings()
      if (this.isCabinetReady) {

        await this.getInbox(true)

      } else {
        await this.openSettings(false)
      }
    },

    // #endregion

    // #region SettingsDialog

    async openSettings(isClosable = true) {
      const hasChanged = await this.$refs.settingsDialog.open({
        isClosable,
      })
      if (hasChanged) {
        this.reloadAppSettings()
      }
    },

    // #endregion

    // #region Store

    ...mapActions(useSettingsStore, ['getAppSettings']),
    ...mapActions(useInboxStore, ['getInbox']),

    // #endregion

  },

}
</script>
<style lang="scss">
:root {

  --aktn-app-header-padding: calc(2*var(--default-grid-baseline));
  --aktn-app-header-height: calc(var(--default-clickable-area) + 2*var(--aktn-app-header-padding));

}

.aktn-app {

  &--header {
    height: var(--aktn-app-header-height);
    padding: var(--app-navigation-padding);
    border-bottom: 1px solid var(--color-border-dark);
    display: flex;
    justify-content: center;
    align-items: center;
  }

  &--toolbar {
    min-height: var(--aktn-app-header-height);
    padding: var(--aktn-app-header-padding);
    background: var(--color-background-dark);
    display: flex;
    flex-direction: row;
    gap: .3rem;
    align-items: center;

    & header {
      font-weight: bold;
    }

    & .dynSpace {
      flex: 1;
      content: ' ';
    }

    & .loadingIcon {
      width: var(--default-clickable-area);
      height: var(--default-clickable-area);
    }

  }

  &--content {
    height: calc(100% - var(--aktn-app-header-height));
    display: flex;
  }

}
.aktn-list {

  &--ul {
    display: flex;
  }

  &--node {

    display: flex;
    height: var(--default-clickable-area);
    align-items: center;
    gap: .5rem;
    padding-left: var(--border-radius-element);
    border-radius: var(--border-radius-element);
    cursor: pointer;

    transition-property: color, border-color, background-color;
    transition-duration: 0.1s;
    transition-timing-function: linear;

    &:hover {
      background: var(--color-primary-element-light-hover);
    }

  }

  &--node-disabled {

    opacity: .2;
    pointer-events: none;

  }

  &--text-decent {

    opacity: .2;

  }

}
</style>
<style scoped lang="scss">
.app-aktenschrank {

  & .crash--wrapper {

    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;

  }

  & .loading--wrapper {

    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;

  }

}
</style>
