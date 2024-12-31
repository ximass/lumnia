<template>
  <v-app>
    <TopMenu v-if="isAuthenticated" />
    <SideMenu v-if="isAuthenticated" />
    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import TopMenu from './components/TopMenu.vue';
import SideMenu from './components/SideMenu.vue';
import { useAuth } from './composables/auth';
import './assets/styles/global.css';

export default defineComponent({
  name: 'App',
  components: {
    TopMenu,
    SideMenu,
  },
  setup() {
    const { isAuthenticated } = useAuth();

    return {
      isAuthenticated,
    };
  },
});
</script>