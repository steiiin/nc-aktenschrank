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

  }),
  getters: {

    getUserLanguage: (state) => state.localization.language ?? undefined,
    getUserTimezone: (state) => state.localization.timezone ?? undefined,

  },
  actions: {

    async loadAppSettings() {

      this.isLoading = true

      try {

        const data = (await axios.get(generateUrl('apps/aktenschrank/settings'))).data
        debugger
        this.config.timezone = data.timezone
        this.config.language = data.language

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
