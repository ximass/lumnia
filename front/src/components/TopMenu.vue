<template>
  <v-app-bar app fixed>
    <v-toolbar-title>Rugcore ??</v-toolbar-title>
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
        <v-divider></v-divider>
        <v-list-item>
          <v-row>
            <v-col>
              <v-list-item-title>Modo escuro</v-list-item-title>
            </v-col>
            <v-col style="margin-top: -15px;">
                <v-switch v-model="isDark" @change="toggleDarkMode"></v-switch>
            </v-col>
          </v-row>
        </v-list-item>
      </v-list>
    </v-menu>
  </v-app-bar>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import { useAuth } from '@/composables/auth';
import { useRouter } from 'vue-router';
import { useTheme } from 'vuetify';

export default defineComponent({
  name: 'TopMenu',
  setup() {
    const { isAuthenticated, clearAuth } = useAuth();
    const router = useRouter();
    const menu = ref(false);
    const theme = useTheme();
    const isDark = ref(theme.global.name.value === 'dark');

    const logout = () => {
      clearAuth();
      router.push('/login');
    };

    const goToProfile = () => {
      router.push('/profile');
    };

    const toggleDarkMode = () => {
      if (isDark.value) {
        theme.global.name.value = 'dark';
        localStorage.setItem('theme', 'dark');
      } else {
        theme.global.name.value = 'light';
        localStorage.setItem('theme', 'light');
      }

      document.documentElement.setAttribute('data-theme', theme.global.name.value);
    };

    onMounted(() => {
      const savedTheme = localStorage.getItem('theme');

      if (savedTheme) {
        theme.global.name.value = savedTheme;
        isDark.value = savedTheme === 'dark';
      }
    });

    return {
      isAuthenticated,
      logout,
      goToProfile,
      menu,
      isDark,
      toggleDarkMode,
    };
  },
});
</script>

<style scoped>
</style>