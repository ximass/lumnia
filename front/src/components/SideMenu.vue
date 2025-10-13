<template>
  <v-navigation-drawer
    app
    :value="drawerOpen"
    :mini-variant="!drawerOpen"
    expand-on-hover
    :rail="rail"
    permanent
  >
    <v-list-item
      :prepend-avatar="props.user?.avatar || 'mdi-account'"
      :title="props.user?.name || 'Usuário'"
      nav
    >
      <template v-slot:append>
        <v-btn
          icon="mdi-chevron-left"
          variant="text"
          @click.stop="rail = !rail"
        ></v-btn>
      </template>
    </v-list-item>

    <v-divider></v-divider>

    <v-list density="compact" nav>
      <v-list-item
        v-for="item in basicMenuItems"
        :key="item.title"
        :prepend-icon="item.icon"
        :title="item.title"
        :value="item.route"
        @click="navigate(item.route)"
      ></v-list-item>

      <v-list-group
        v-if="adminMenuItems.length"
        value="Administração"
        prepend-icon="mdi-shield-account"
      >
        <template v-slot:activator="{ props: groupProps }">
          <v-list-item v-bind="groupProps" title="Administração"></v-list-item>
        </template>
        <v-list-item
          v-for="item in adminMenuItems"
          :key="item.title"
          :prepend-icon="item.icon"
          :title="item.title"
          :value="item.route"
          @click="navigate(item.route)"
        ></v-list-item>
      </v-list-group>
    </v-list>
  </v-navigation-drawer>
</template>

<script lang="ts">
  import { defineComponent, computed, ref } from 'vue'
  import { useRouter } from 'vue-router'

  export default defineComponent({
    name: 'SideMenu',
    props: {
      user: {
        type: Object,
        required: true,
      },
      drawerOpen: {
        type: Boolean,
        required: true,
      },
    },
    setup(props) {
      const rail = ref(true)
      const router = useRouter()

      const menuItems = [
        {
          title: 'Tela inicial',
          route: '/home',
          admin: false,
          icon: 'mdi-home',
        },
        {
          title: 'Chats',
          route: '/chats',
          admin: false,
          icon: 'mdi-message-text-outline',
        },
        {
          title: 'Bases de conhecimento',
          route: '/knowledge-bases',
          admin: false,
          icon: 'mdi-book-open-variant-outline',
        },
        {
          title: 'Usuários',
          route: '/users',
          admin: true,
          icon: 'mdi-account',
        },
        {
          title: 'Grupos',
          route: '/groups',
          admin: true,
          icon: 'mdi-account-group',
        },
        {
          title: 'Personas',
          route: '/personas',
          admin: true,
          icon: 'mdi-account-tie',
        },
        {
          title: 'Permissões',
          route: '/permissions',
          admin: true,
          icon: 'mdi-shield-key',
        },
        {
          title: 'Logs de erros',
          route: '/error-logs',
          admin: true,
          icon: 'mdi-alert-circle-outline',
        },
      ]

    const basicMenuItems = computed(() =>
      menuItems.filter(
        item =>
          !item.admin
      )
    );

    function userHasPermission(name: string) {
      if (!props.user || !props.user.groups) return false;
      for (const g of props.user.groups) {
        if (!g.permissions) continue;
        if (g.permissions.some((p: any) => p.name === name)) return true;
      }
      return false;
    }

    const adminMenuItems = computed(() =>
      props.user && (props.user.admin || userHasPermission('manage_permissions')) ? menuItems.filter(item => item.admin) : []
    );

    function navigate(route: string) {
      router.push(route);
    }

    return {
      basicMenuItems,
      adminMenuItems,
      rail,
      props,
      navigate,
    };
    },
  })
</script>
