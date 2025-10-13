<template>
  <v-container style="padding: 50px">
    <v-row justify="space-between" align="center" class="mb-4" style="margin: 0">
      <h2>Permissões</h2>
      <v-btn color="primary" @click="openForm">Novo</v-btn>
    </v-row>

    <v-data-table :items="allPermissions" :headers="headers" class="elevation-1">
      <template #item.actions="{ item }">
        <v-menu offset-y>
          <template #activator="{ props }">
            <v-btn icon v-bind="props" variant="text"><v-icon>mdi-dots-vertical</v-icon></v-btn>
          </template>
          <v-list>
            <v-list-item @click="editPermission(item)">
              <v-list-item-title>
                <v-icon>mdi-pencil</v-icon>
                Editar
              </v-list-item-title>
            </v-list-item>
            <v-list-item @click="deletePermission(item.id)">
              <v-list-item-title>
                <v-icon>mdi-delete</v-icon>
                Excluir
              </v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </template>
    </v-data-table>

    <PermissionForm
      :dialog="isFormOpen"
      :permissionData="selectedPermission"
      @close="isFormOpen = false"
      @saved="fetchPermissions"
    />
  </v-container>
</template>

<script lang="ts">
  import { defineComponent, ref, onMounted, nextTick } from 'vue'
  import PermissionForm from '@/components/PermissionForm.vue'
  import { useToast } from '@/composables/useToast'
  import type { Permission } from '@/types/types'
  import { menuItems } from '@/constants/menu'
  import permissionService from '@/services/permission'

  export default defineComponent({
    name: 'PermissionsView',
    components: { PermissionForm },
    setup() {
      const { showToast } = useToast()
      const isFormOpen = ref(false)
    const selectedMenuOption = ref<string | null>(null)

      const allPermissions = ref<Permission[]>([])
      const selectedPermission = ref<Permission | null>(null)

      const headers = [
        { title: 'Nome', value: 'name', sortable: true },
        { title: 'Descrição', value: 'description', sortable: false },
        { title: 'Ações', value: 'actions', sortable: false },
      ]

      const fetchPermissions = async () => {
        try {
          const r = await permissionService.list()
          allPermissions.value = r.data || []
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao buscar permissões'
          showToast(errorMsg)
        }
      }

      const createPermissionFromMenu = async () => {
        if (!selectedMenuOption.value) return
        const option = menuItems.find(m => m.route === selectedMenuOption.value || m.permission === selectedMenuOption.value || m.title === selectedMenuOption.value)
        if (!option) return showToast('Opção de menu inválida')

        try {
          await permissionService.create({ name: option.permission || option.route.replace(/\//g, '_').replace(/^_/, ''), label: option.title, description: `Permissão para acessar ${option.title}` })
          showToast('Permissão criada')
          fetchPermissions()
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao criar permissão'
          showToast(errorMsg)
        }
      }

      const openForm = async () => {
        selectedPermission.value = null
        selectedMenuOption.value = null
        await nextTick()
        isFormOpen.value = true
      }

      const editPermission = (p: Permission) => {
        selectedPermission.value = p
        isFormOpen.value = true
      }

      const deletePermission = async (id: number) => {
        try {
          await permissionService.remove(id)
          fetchPermissions()
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao deletar permissão'
          showToast(errorMsg)
        }
      }

      onMounted(() => {
        fetchPermissions()
      })

      return {
        allPermissions,
        isFormOpen,
        selectedPermission,
        headers,
        menuItems,
        selectedMenuOption,
        openForm,
        editPermission,
        deletePermission,
        fetchPermissions,
        createPermissionFromMenu,
      }
    },
  })
</script>

<style scoped>
  .v-container {
    padding-top: 50px;
  }
</style>
