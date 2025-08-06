<template>
  <v-navigation-drawer 
    app 
    v-model="drawerOpen"
    :rail="rail"
    permanent
    @click="rail = false"
  >
    <v-list-item
      :title="props.user?.name || 'Usuário'"
      nav
    >
      <template v-slot:prepend>
        <v-avatar size="40">
          <v-img 
            v-if="props.user?.avatar" 
            :src="`/api/avatars/${props.user.avatar.split('/').pop()}`"
            alt="Avatar"
          />
          <v-icon v-else>mdi-account</v-icon>
        </v-avatar>
      </template>
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
        v-for="item in filteredMenuItems" 
        :key="item.title" 
        :prepend-icon="item.icon" 
        :title="item.title" 
        :value="item.route"
        @click="navigateTo(item.route)"
      >
      </v-list-item>
    </v-list>
  </v-navigation-drawer>
</template>

<script lang="ts">
import { defineComponent, computed, ref } from 'vue';
import { useRouter } from 'vue-router';

export default defineComponent({
  name: 'SideMenu',
  props: {
    user: {
      type: Object,
      required: true,
    },
    drawerOpen: {
      type: Boolean,
      required: true
    }
  },
  setup(props) {
    const rail = ref(true);
    const router = useRouter();

    const menuItems = [
      { title: 'Tela inicial', route: '/home', admin: false, icon: 'mdi-home' },
      { title: 'Chats', route: '/chats', admin: false, icon: 'mdi-message-text-outline' },
      { title: 'Bases de conhecimento', route: '/knowledge-bases', admin: true , icon: 'mdi-book-open-variant-outline' },
      { title: 'Usuários', route: '/users', admin: true, icon: 'mdi-account' },
      { title: 'Grupos', route: '/groups', admin: true, icon: 'mdi-account-group' },
      { title: 'Personas', route: '/personas', admin: true, icon: 'mdi-account-tie' },
    ];

    const filteredMenuItems = computed(() => {
      return menuItems.filter(item => {
        if (item.admin) {
          return props.user && props.user.admin;
        }
        return true;
      });
    });

    const navigateTo = (route: string) => {
      router.push(route);
    };

    return { 
      filteredMenuItems,
      rail,
      navigateTo,
      props
    };
  },
});
</script>