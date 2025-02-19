<template>
    <v-navigation-drawer app>
      <v-list>
        <v-list-item
          v-for="(item, index) in menuItems"
          :key="index"
          @click="$router.push(item.route)"
        >
          <v-list-item-title>{{ item.title }}</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>
</template>
  
<script lang="ts">
  import { defineComponent } from 'vue';
  
  export default defineComponent({
    name: 'SideMenu',
    data() {
      return {
        menuItems: [
          { title: 'Chats', route: '/chats' },
          { title: 'Grupos', route: '/groups' },
          { title: 'Usuários', route: '/users' },
          { title: 'Bases de conhecimento', route: '/knowledge-bases' },
        ],
      };
    },
  });
</script>