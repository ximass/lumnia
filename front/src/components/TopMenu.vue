<template>
  <v-app-bar app fixed>
    <v-toolbar-title>Rugcore??</v-toolbar-title>
    <v-spacer></v-spacer>
    <v-menu v-model="menu" offset-y>
      <template #activator="{ props }">
        <v-btn icon v-bind="props">
          <v-avatar>
            <v-icon>mdi-account</v-icon>
          </v-avatar>
        </v-btn>
      </template>
      <v-list>
        <v-list-item @click="goToProfile">
          <v-list-item-title>Meu perfil</v-list-item-title>
        </v-list-item>
        <v-list-item @click="logout">
          <v-list-item-title>Sair</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
  </v-app-bar>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';
import { useAuth } from '@/composables/auth';
import { useRouter } from 'vue-router';

export default defineComponent({
  name: 'TopMenu',
  setup() {
    const { isAuthenticated, clearAuth } = useAuth();
    const router = useRouter();
    const menu = ref(false);

    const logout = () => {
      clearAuth();
      router.push('/login');
    };

    const goToProfile = () => {
      router.push('/profile');
    };

    return {
      isAuthenticated,
      logout,
      goToProfile,
      menu,
    };
  },
});
</script>

<style scoped></style>