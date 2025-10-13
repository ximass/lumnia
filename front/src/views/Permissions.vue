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
  import { defineComponent, ref, onMounted } from 'vue'
  import axios from 'axios'
  import PermissionForm from '@/components/PermissionForm.vue'
  import { useToast } from '@/composables/useToast'
  import type { Permission } from '@/types/types'

  export default defineComponent({
    name: 'PermissionsView',
    components: { PermissionForm },
    setup() {
      const { showToast } = useToast()
      const isFormOpen = ref(false)

      const allPermissions = ref<Permission[]>([])
      const selectedPermission = ref<Permission | null>(null)

      const headers = [
        { title: 'Nome', value: 'name', sortable: true },
        { title: 'Rótulo', value: 'label', sortable: true },
        { title: 'Descrição', value: 'description', sortable: false },
        { title: 'Ações', value: 'actions', sortable: false },
      ]

      const fetchPermissions = async () => {
        try {
          const response = await axios.get<Permission[]>('/api/permissions')
          // API returns { status, data }
          allPermissions.value = response.data?.data || []
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao buscar permissões'
          showToast(errorMsg)
        }
      }

      const openForm = () => {
        selectedPermission.value = null
        isFormOpen.value = true
      }

      const editPermission = (p: Permission) => {
        selectedPermission.value = p
        isFormOpen.value = true
      }

      const deletePermission = async (id: number) => {
        try {
          await axios.delete(`/api/permissions/${id}`)
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
        openForm,
        editPermission,
        deletePermission,
        fetchPermissions,
      }
    },
  })
</script>

<style scoped>
  .v-container {
    padding-top: 50px;
  }
</style>
