<template>
  <v-navigation-drawer app>
    <v-list>
      <v-list-item v-for="(item, index) in filteredMenuItems" :key="index" @click="$router.push(item.route)">
        <v-list-item-title>{{ item.title }}</v-list-item-title>
      </v-list-item>
    </v-list>
  </v-navigation-drawer>
</template>

<script lang="ts">
import { defineComponent, computed } from 'vue';

export default defineComponent({
  name: 'SideMenu',
  props: {
    user: {
      type: Object,
      required: true,
    },
  },
  setup(props) {
    const menuItems = [
      { title: 'Chats', route: '/chats', admin: false },
      { title: 'Grupos', route: '/groups', admin: true },
      { title: 'Usuários', route: '/users', admin: true },
      { title: 'Bases de conhecimento', route: '/knowledge-bases', admin: true },
    ];

    const filteredMenuItems = computed(() => {
      return menuItems.filter(item => {
        if (item.admin) {
          return props.user && props.user.admin;
        }
        return true;
      });
    });

    return { filteredMenuItems };
  },
});
</script>