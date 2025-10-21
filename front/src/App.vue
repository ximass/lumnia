<template>
  <v-app>
  <TopMenu v-if="isAuthenticated && user" :user="user" @toggle-drawer="drawerOpen = !drawerOpen" />
    <SideMenu v-if="isAuthenticated && user" :user="user" :drawerOpen="drawerOpen" />
    <v-main><router-view /></v-main>
    <v-snackbar v-model="isToastVisible" timeout="3000" :color="toastColor">
      {{ toastMessage }}
    </v-snackbar>
  </v-app>
</template>

<script lang="ts">
  import { defineComponent, ref } from 'vue'
  import TopMenu from './components/TopMenu.vue'
  import SideMenu from './components/SideMenu.vue'
  import { useAuth } from './composables/auth'
  import { useToast } from './composables/useToast'
  import './assets/styles/global.css'

  export default defineComponent({
    name: 'App',
    components: { TopMenu, SideMenu },
    setup() {
      const { isToastVisible, toastMessage, toastColor } = useToast()
      const { fetchUser, isAuthenticated, user } = useAuth()
      const drawerOpen = ref(false)

      fetchUser()

      return {
        isToastVisible,
        toastMessage,
        toastColor,
        isAuthenticated,
        user,
        drawerOpen,
      }
    },
  })
</script>
