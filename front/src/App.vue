<template>
  <v-app>
    <SideMenu />
    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import SideMenu from './components/SideMenu.vue';

export default defineComponent({
  name: 'App',
  components: {
    SideMenu,
  },
});
</script>