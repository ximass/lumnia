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
import { usePermissions } from '@/composables/usePermissions'
import { menuItems as importedMenuItems } from '@/constants/menu'

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

    const menuItems = importedMenuItems

    const { hasPermission } = usePermissions()

    const basicMenuItems = computed(() =>
      menuItems.filter(item => !item.admin && (!item.permission || hasPermission(item.permission) || props.user.admin))
    )

    const adminMenuItems = computed(() =>
      props.user
        ? menuItems.filter(
            item =>
              item.admin &&
              (props.user.admin || (item.permission ? hasPermission(item.permission) : hasPermission('manage_permissions')))
          )
        : []
    )

    function navigate(route: string) {
      router.push(route)
    }

    return {
      basicMenuItems,
      adminMenuItems,
      rail,
      props,
      navigate,
    }
  },
})
</script>
