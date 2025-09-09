<template>
  <v-dialog v-model="dialog" max-width="500px">
    <v-card>
      <v-card-title>
        <span class="text-h5">{{ user.id ? 'Editar usuário' : 'Novo usuário' }}</span>
      </v-card-title>
      <v-card-text>
        <v-form ref="form" @submit.prevent="submitForm">
          <v-text-field
            label="Nome"
            v-model="user.name"
            :rules="[v => !!v || 'Nome é obrigatório']"
            required
          ></v-text-field>
          <v-text-field
            label="Email"
            v-model="user.email"
            :rules="[
              v => !!v || 'Email é obrigatório',
              v => /.+@.+\..+/.test(v) || 'Email deve ser válido',
            ]"
            type="email"
            required
          ></v-text-field>
          <v-text-field
            label="Senha"
            v-model="user.password"
            :disabled="!!user.id"
            type="password"
          ></v-text-field>
            <v-autocomplete
              v-model="user.groups_ids"
              :items="groups"
              item-title="name"
              item-value="id"
              label="Grupos"
              multiple
              chips
              clearable
              hide-selected
              :loading="loadingGroups"
            ></v-autocomplete>
          <v-switch v-model="user.admin" label="Administrador"></v-switch>
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="close">Cancelar</v-btn>
        <v-btn color="primary" @click="submitForm">Salvar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
  import { defineComponent, ref, onMounted, watch } from 'vue'
  import axios from 'axios'
  import { useToast } from '@/composables/useToast'
  import type { Group, User, UserFormData, UserWithGroups } from '@/types/types'

  export default defineComponent({
    name: 'UserForm',
    props: {
      dialog: { 
        type: Boolean, 
        required: true 
      },
      userData: {
        type: Object as () => UserWithGroups | null,
        default: null,
      },
    },
    emits: ['close', 'saved'],
    setup(props, { emit }) {
      const { showToast } = useToast()
      const form = ref()

      const loadingGroups = ref(false)

      const user = ref<UserFormData>({ 
        name: '', 
        email: '', 
        password: '', 
        groups_ids: [], 
        admin: false 
      })

      const groups = ref<Group[]>([])

      // Estado auxiliar para guardar os grupos_ids caso os grupos ainda não estejam carregados
      watch(
        () => props.userData,
        newData => {
          if (newData) {
            user.value = {
              id: newData.id,
              name: newData.name,
              email: newData.email,
              password: '',
              groups_ids: newData.groups?.map((group: Group) => group.id) || [],
              admin: newData.admin,
            }
          } else {
            user.value = { 
              name: '', 
              email: '', 
              password: '', 
              groups_ids: [], 
              admin: false 
            }
          }
        },
        { immediate: true }
      )

      const fetchGroups = async () => {
        loadingGroups.value = true
        try {
          const response = await axios.get<Group[]>('/api/groups')
          groups.value = response.data
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao buscar grupos'
          showToast(errorMsg)
        } finally {
          loadingGroups.value = false
        }
      }

      onMounted(() => {
        fetchGroups()
      })

      const submitForm = async () => {
        const validation = await form.value?.validate()

        if (!validation.valid) {
          return
        }

        try {
          const payload: Omit<UserFormData, 'id'> = {
            name: user.value.name,
            email: user.value.email,
            password: user.value.password,
            groups_ids: user.value.groups_ids,
            admin: user.value.admin,
          }

          if (user.value.id) {
            await axios.put<User>(`/api/user/${user.value.id}`, payload)
          } else {
            await axios.post<User>('/api/users', payload)
          }

          emit('saved')
          close()
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao salvar usuário'
          showToast(errorMsg)
        }
      }

      const close = () => {
        emit('close')
      }

      return { user, loadingGroups, fetchGroups, form, close, submitForm, showToast, groups }
    },
  })
</script>
