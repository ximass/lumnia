<template>
  <v-app>
    <SideMenu v-if="isAuthenticated" />
    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>

<script lang="ts">
import { defineComponent, computed } from 'vue';
import SideMenu from './components/SideMenu.vue';
import { useAuth } from './composables/auth';

export default defineComponent({
  name: 'App',
  components: {
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