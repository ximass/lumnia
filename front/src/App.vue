<template>
  <v-app>
    <TopMenu v-if="isAuthenticated" :user/>
    <SideMenu v-if="isAuthenticated" :user/>
    <v-main>
      <router-view />
    </v-main>
    <v-snackbar v-model="isToastVisible" timeout="3000" color="error">
      {{ toastMessage }}
    </v-snackbar>
  </v-app>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import TopMenu from './components/TopMenu.vue';
import SideMenu from './components/SideMenu.vue';
import { useAuth } from './composables/auth';
import { useToast } from './composables/useToast';
import './assets/styles/global.css';

export default defineComponent({
  name: 'App',
  components: {
    TopMenu,
    SideMenu,
  },
  setup() {
    const { fetchUser, isAuthenticated, user } = useAuth();
    const { isToastVisible, toastMessage } = useToast();
    
    fetchUser();

    return {
      isAuthenticated,
      isToastVisible,
      toastMessage,
      user
    };
  },
});
</script>