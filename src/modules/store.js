import { DateTime } from 'luxon'
import { defineStore } from 'pinia'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

// #region Settings

export const useSettingsStore = defineStore('settings', {
  state: () => ({

    isLoading: true,
    isFailed: false,

    localization: {
      timezone: null,
      language: null,
    },

    cabinet: {
      path: null,
      isReady: false,
      isConfigured: false,
      isExisting: false,
      isReadable: false,
      isWritable: false,
      isGroupfolder: false,
    },

  }),
  getters: {

    isSettingsLoading: (state) => state.isLoading,
    isSettingsFailed: (state) => state.isFailed,

    cabinetPath: (state) => state.cabinet.path,
    isCabinetReady: (state) => state.cabinet.isReady,
    isCabinetConfigured: (state) => state.cabinet.isConfigured,
    isCabinetExisting: (state) => state.cabinet.isExisting,
    isCabinetReadable: (state) => state.cabinet.isReadable,
    isCabinetWritable: (state) => state.cabinet.isWritable,
    isCabinetGroupfolder: (state) => state.cabinet.isGroupfolder,

    getUserTimezone: (state) => state.localization.timezone ?? undefined,
    getUserLanguage: (state) => state.localization.language ?? undefined,

  },
  actions: {

    // #region Localization

    getMomentFromTimestamp(timestamp) {
      return DateTime.fromSeconds(timestamp, { zone: this.getUserTimezone, locale: this.getUserLanguage })
    },
    getMomentFromDate(date) {
      return DateTime.fromJSDate(date, { zone: this.getUserTimezone, locale: this.getUserLanguage })
    },

    // #endregion

    async getAppSettings() {

      this.isLoading = true

      try {

        const data = (await axios.get(generateUrl('apps/aktenschrank/api/settings'))).data

        // localization
        this.localization.timezone = data.localization?.timezone ?? null
        this.localization.language = data.localization?.language ?? null

        // cabinet
        this.cabinet.path = data.cabinet?.path ?? null
        this.cabinet.isReady = data.cabinet?.isReady ?? false
        this.cabinet.isConfigured = data.cabinet?.isConfigured ?? false
        this.cabinet.isExisting = data.cabinet?.isExisting ?? false
        this.cabinet.isReadable = data.cabinet?.isReadable ?? false
        this.cabinet.isWritable = data.cabinet?.isWritable ?? false
        this.cabinet.isGroupfolder = data.cabinet?.isGroupfolder ?? false

        this.isFailed = false

      } catch (error) {

        console.error('Aktenschrank: failed to load app settings.')
        console.error(error)

        this.isFailed = true

      } finally {
        this.isLoading = false
      }

    },

  },

})

// #endregion
// #region Inbox

export const useInboxStore = defineStore('inbox', {
  state: () => ({

    isLoading: true,
    isInitialized: false,
    items: [],

  }),
  getters: {

    isInboxLoading: (state) => state.isLoading,
    isInboxInitialized: (state) => state.isInitialized,

    inboxItems: (state) => state.items ?? [],
    inboxCount: (state) => state.inboxItems.length,
    isInboxEmpty: (state) => state.inboxCount < 1,

  },
  actions: {

    async getInbox(forceGet = false) {

      if (this.isLoading && !forceGet) { return }
      this.isLoading = true

      try {

        const data = (await axios.get(generateUrl('apps/aktenschrank/api/inbox'))).data
        this.items = data.inboxItems

        this.isInitialized = true
        return true

      } catch (error) {

        console.error('Aktenschrank: failed to load inbox')
        console.error(error)
        return false

      } finally {
        this.isLoading = false
      }

    },

  },

})

// #endregion
