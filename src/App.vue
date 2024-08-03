<template>
  <NcContent app-name="aktenschrank">

    <!-- AppCrash -->
    <NcAppContent v-if="isCrashed" class="crash--wrapper">
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
    <NcAppContent v-else-if="isLoading" class="loading--wrapper">
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
            <NcCounterBubble v-show="timelineOpen > 0" slot="counter" type="highlighted">
              <!-- {{ timelineOpen }} -->
            </NcCounterBubble>
          </NcAppNavigationItem>
          <NcAppNavigationItem :name="t('aktenschrank', 'Inbox')" to="/inbox">
            <InboxIcon slot="icon" />
            <NcCounterBubble v-show="inboxOpen > 0" slot="counter" type="highlighted">
              <!-- {{ inboxOpen }} -->
            </NcCounterBubble>
          </NcAppNavigationItem>
          <NcAppNavigationItem :name="t('aktenschrank', 'Archive')" to="/archive">
            <ArchiveIcon slot="icon" />
          </NcAppNavigationItem>
        </template>
        <template #footer>
          <ul class="app-navigation-entry__settings">
            <NcAppNavigationItem :name="t('aktenschrank', 'Settings')" to="/settings" exact>
              <CogIcon slot="icon" />
            </NcAppNavigationItem>
          </ul>
        </template>
      </NcAppNavigation>
      <router-view />
      <!-- <ConfirmDialog style="z-index: 10000" ref="confirmDialog" /> -->

    </template>

  </NcContent>
</template>

<script>
import { NcAppContent, NcAppNavigation, NcAppNavigationItem, NcButton, NcContent, NcCounterBubble, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'

import ArchiveIcon from 'vue-material-design-icons/Archive.vue'
import CogIcon from 'vue-material-design-icons/Cog.vue'
import InboxIcon from 'vue-material-design-icons/Inbox.vue'
import TimelineIcon from 'vue-material-design-icons/TimelineClockOutline.vue'

import { useSettingsStore } from './modules/store.js'
import { mapActions /*, mapState */ } from 'pinia'

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
  },

  data() {
    return {

      isLoading: true,
      isCrashed: false,

    }
  },

  async mounted() {

    await this.loadAppSettings()

  },

  methods:
  {

    reloadApp() {
      document.location.reload()
    },

    ...mapActions(useSettingsStore, ['loadAppSettings']),

  },

}
</script>

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
