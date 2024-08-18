<!-- AcNoContent.vue -->
<template>
  <div class="aktn-nocontent" :class="{
    'aktn-nocontent--decent': isDecent,
    'aktn-nocontent--huge': isHuge
  }">
    <div class="aktn-nocontent--icon">
      <EmptyGeneralIcon :size="iconSize" />
    </div>
    <div class="aktn-nocontent--title">
      {{ title }}
    </div>
    <div class="aktn-nocontent--subtitle">
      {{ subtitle }}
    </div>
  </div>
</template>

<script>

import EmptyGeneralIcon from 'vue-material-design-icons/Shimmer.vue'

/**
 * Base component.
 */
export default {
  name: 'AcNoContent',

  components: {
    EmptyGeneralIcon,
  },
  props: {
    title: {
      type: String,
      required: true,
    },
    subtitle: {
      type: String,
      required: false,
      default: '',
    },
    type: {
      type: String,
      required: false,
      default: 'normal',
    },
  },

  computed: {
    isDecent() {
      return this.type === 'decent'
    },
    isNormal() {
      return this.type === 'normal'
    },
    isHuge() {
      return this.type === 'huge'
    },
    iconSize() {
      if (this.isDecent) {
        return 24
      } else if (this.isHuge) {
        return 64
      } else {
        return 36
      }
    },
  },
}

</script>
<style lang="scss" scoped>
.aktn-nocontent {

  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 1rem;
  gap: .5em;

  &--decent {

    flex-direction: row;
    opacity: .2;

  }

  &--huge {

    & .aktn-nocontent--title {
      font-weight: bold;
    }

  }

}

</style>
