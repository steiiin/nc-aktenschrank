import Vue from 'vue'
import Router from 'vue-router'

import { generateUrl } from '@nextcloud/router'
import queryString from 'query-string'

import InboxRoute from './routes/Inbox.vue'

// const Timeline = () => import('./routes/Timeline')
// const Archive = () => import('./routes/Archive')
// const Settings = () => import('./routes/Settings')

Vue.use(Router)
export default new Router({

  mode: 'history',
  base: generateUrl('/apps/aktenschrank'),
  linkActiveClass: 'active',
  routes: [
    {
      path: '/inbox',
      component: InboxRoute,
    },
    // {
    //   path: '/',
    //   redirect: '/timeline',
    // },
    // {
    //   path: '/timeline',
    //   component: Timeline,
    // },
    // {
    //   path: '/archive',
    //   component: Archive,
    // },
    // {
    //   path: '/settings',
    //   component: Settings,
    // },

  ],

  // Custom stringifyQuery to prevent encoding of slashes in the url
  stringifyQuery(query) {
    const result = queryString.stringify(query).replace(/%2F/gmi, '/').replace(/%2C/gmi, ',')
    return result ? ('?' + result) : ''
  },

})
