<template>
  <v-navigation-drawer
  app
  :model-value="isOpen"
  :mini-variant="!isOpen"
  expand-on-hover
  :rail="rail"
  :permanent="pinned"
  
  >
    <v-list-item nav>
      <template #prepend>
        <v-avatar size="40">
          <v-img
            v-if="props.user?.avatar && props.user.avatar.length > 0"
            :src="`/api/avatars/${props.user.avatar.split('/').pop()}`"
            alt="Avatar"
          />
          <v-icon v-else>mdi-account</v-icon>
        </v-avatar>
      </template>

      <v-list-item-title>{{ props.user?.name || 'Usuário' }}</v-list-item-title>

      <template #append>
        <v-btn icon variant="text" @click.stop="togglePinned">
          <v-icon size="18">{{ isOpen ? 'mdi-chevron-right' : 'mdi-chevron-left' }}</v-icon>
        </v-btn>
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
        v-if="operationalMenuItems.length"
        value="Operacional"
        prepend-icon="mdi-cog"
      >
        <template v-slot:activator="{ props: groupProps }">
          <v-list-item v-bind="groupProps" title="Operacional"></v-list-item>
        </template>
        <v-list-item
          v-for="item in operationalMenuItems"
          :key="item.title"
          :prepend-icon="item.icon"
          :title="item.title"
          :value="item.route"
          @click="navigate(item.route)"
        ></v-list-item>
      </v-list-group>

      <v-list-group
        v-if="reportsMenuItems.length"
        value="Relatórios"
        prepend-icon="mdi-chart-bar"
      >
        <template v-slot:activator="{ props: groupProps }">
          <v-list-item v-bind="groupProps" title="Relatórios"></v-list-item>
        </template>
        <v-list-item
          v-for="item in reportsMenuItems"
          :key="item.title"
          :prepend-icon="item.icon"
          :title="item.title"
          :value="item.route"
          @click="navigate(item.route)"
        ></v-list-item>
      </v-list-group>

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

      <v-list-group
        v-if="logsMenuItems.length"
        value="Logs"
        prepend-icon="mdi-alert-circle-outline"
      >
        <template v-slot:activator="{ props: groupProps }">
          <v-list-item v-bind="groupProps" title="Logs"></v-list-item>
        </template>
        <v-list-item
          v-for="item in logsMenuItems"
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
      const pinned = ref(false)
      function togglePinned() {
        pinned.value = !pinned.value
        rail.value = !pinned.value
      }
      const router = useRouter()
      const isOpen = computed(() => pinned.value || props.drawerOpen)

    const menuItems = importedMenuItems

    const { hasPermission } = usePermissions()

    const basicMenuItems = computed(() =>
      menuItems.filter(
        item =>
          (
            !item.class ||
            item.class === 'principal'
          ) &&
          (
            !item.permission ||
            hasPermission(item.permission) ||
            props.user.admin
          )
      )
    )

    const logsMenuItems = computed(() =>
      menuItems.filter(
        item =>
          item.class === 'logs' &&
          (
            !item.permission ||
            hasPermission(item.permission) ||
            props.user.admin
          )
      )
    )

    const reportsMenuItems = computed(() =>
      menuItems.filter(
        item =>
          item.class === 'reports' &&
          (
            !item.permission ||
            hasPermission(item.permission) ||
            props.user.admin
          )
      )
    )

    const operationalMenuItems = computed(() =>
      menuItems.filter(
        item =>
          item.class === 'operational' &&
          (
            !item.permission ||
            hasPermission(item.permission) ||
            props.user.admin
          )
      )
    )

    const adminMenuItems = computed(() =>
      props.user
        ? menuItems.filter(
            item =>
              item.class === 'admin' &&
              (props.user.admin || (item.permission ? hasPermission(item.permission) : hasPermission('manage_permissions')))
          )
        : []
    )

    function navigate(route: string) {
      router.push(route)
    }

    return {
      basicMenuItems,
      operationalMenuItems,
      logsMenuItems,
      reportsMenuItems,
      adminMenuItems,
      rail,
      pinned,
      togglePinned,
      isOpen,
      props,
      navigate,
    }
  },
})
</script>

<style scoped>
/* make items inside v-list-group less indented */
::v-deep .v-list-group__items .v-list-item {
  padding-left: 25px !important;
}
</style>
