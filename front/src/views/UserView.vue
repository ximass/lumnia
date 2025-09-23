<template>
  <v-container style="padding: 50px">
    <v-row justify="space-between" align="center" class="mb-4" style="margin: 0">
      <h2>Usuários</h2>
      <v-btn color="primary" @click="openForm">Novo</v-btn>
    </v-row>
    <v-data-table :items="users" :headers="headers" class="elevation-1">
      <template #item.admin="{ item }">{{ item.admin ? 'Sim' : 'Não' }}</template>
      <template #item.actions="{ item }">
        <v-menu offset-y>
          <template #activator="{ props }">
            <v-btn icon v-bind="props" variant="text"><v-icon>mdi-dots-vertical</v-icon></v-btn>
          </template>
          <v-list>
            <v-list-item @click="editUser(item)">
              <v-list-item-title>
                <v-icon>mdi-pencil</v-icon>
                Editar
              </v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </template>
    </v-data-table>
    <UserForm
      :dialog="isFormOpen"
      :userData="selectedUser"
      @close="isFormOpen = false"
      @saved="handleUserSaved"
    />
  </v-container>
</template>

<script lang="ts">
  import { defineComponent, ref, onMounted } from 'vue'
  import axios from 'axios'
  import UserForm from '@/components/UserForm.vue'
  import { useToast } from '@/composables/useToast'
  import type { UserWithGroups } from '@/types/types'

  export default defineComponent({
    name: 'UserView',
    components: { UserForm },
    setup() {
      const { showToast } = useToast()
      const isFormOpen = ref(false)

      const users = ref<UserWithGroups[]>([])
      const selectedUser = ref<UserWithGroups | null>(null)

      const headers = [
        { title: 'Nome', value: 'name', sortable: true },
        { title: 'Email', value: 'email', sortable: true },
        { title: 'Administrador', value: 'admin', sortable: true },
        { title: 'Ações', value: 'actions', sortable: false },
      ]

      const fetchUsers = async () => {
        try {
          const response = await axios.get<UserWithGroups[]>('/api/users')
          users.value = response.data
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao buscar usuários'
          showToast(errorMsg)
        }
      }

      const editUser = (user: UserWithGroups) => {
        selectedUser.value = { ...user }
        isFormOpen.value = true
      }

      const openForm = () => {
        selectedUser.value = null
        isFormOpen.value = true
      }

      const handleUserSaved = () => {
        isFormOpen.value = false
        fetchUsers()
      }

      onMounted(() => {
        fetchUsers()
      })

      return {
        users,
        isFormOpen,
        selectedUser,
        headers,
        editUser,
        openForm,
        fetchUsers,
        handleUserSaved,
        showToast,
      }
    },
  })
</script>

<style scoped>
  .v-container {
    padding-top: 50px;
  }
</style>
