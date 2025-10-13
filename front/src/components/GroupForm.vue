<template>
  <v-dialog v-model="dialog" max-width="600px">
    <v-card>
      <v-card-title>
        <span class="text-h5">{{ isEdit ? 'Editar grupo' : 'Novo grupo' }}</span>
      </v-card-title>
      <v-card-text>
        <v-form ref="form" @submit.prevent="submitForm">
          <v-text-field
            label="Nome do grupo"
            v-model="group.name"
            :rules="[v => !!v || 'Nome é obrigatório']"
            required
          ></v-text-field>

          <v-select
            v-model="group.knowledge_base_ids"
            :items="knowledgeBases"
            item-title="name"
            item-value="id"
            label="Bases de conhecimentos"
            multiple
            chips
            clearable
            hide-selected
            :loading="loadingKnowledgeBases"
          >
            <template #chip="{ props, item }">
              <v-chip v-bind="props" :text="item.title">{{ item.title }}</v-chip>
            </template>
            <template #item="{ props, item }">
              <v-list-item
                v-bind="props"
                :title="item.raw.name"
                :subtitle="item.raw.description"
              ></v-list-item>
            </template>
          </v-select>
          <v-select
            v-model="group.permission_ids"
            :items="permissions"
            item-title="label"
            item-value="id"
            label="Permissões"
            multiple
            chips
            clearable
            hide-selected
            :loading="loadingPermissions"
          />
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn @click="close">Cancelar</v-btn>
            <v-btn color="primary" type="submit">Salvar</v-btn>
          </v-card-actions>
        </v-form>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
  import { defineComponent, ref, onMounted, watch } from 'vue'
  import axios from 'axios'
  import { useToast } from '@/composables/useToast'
  import type {
    KnowledgeBase,
    GroupWithKnowledgeBases,
    GroupWithUsers,
    Permission,
  } from '@/types/types'

  export default defineComponent({
    name: 'GroupForm',
    props: {
      dialog: {
        type: Boolean,
        required: true,
      },
      groupData: {
        type: Object as () => any | null,
        default: null,
      },
    },
    emits: ['close', 'saved'],
    setup(props, { emit }) {
      const { showToast } = useToast()
      const form = ref()

      const loadingKnowledgeBases = ref(false)

      // Group no longer contains user_ids here — keep knowledge_base_ids as string|number
      const group = ref<{ id?: number; name: string; knowledge_base_ids: (string | number)[]; permission_ids?: number[] }>({
        name: '',
        knowledge_base_ids: [],
        permission_ids: [],
      })
      const knowledgeBases = ref<KnowledgeBase[]>([])
      const permissions = ref<Permission[]>([])
      const loadingPermissions = ref(false)

      watch(
        () => props.groupData,
        async (newData) => {
          if (newData && newData.id) {
            // fetch full group from API to ensure permissions are present
            try {
              const resp = await axios.get(`/api/groups/${newData.id}`)
              const d = resp.data
              group.value = {
                id: d.id,
                name: d.name,
                knowledge_base_ids: d.knowledge_bases?.map((kb: KnowledgeBase) => kb.id) || [],
                permission_ids: d.permissions?.map((p: Permission) => p.id) || [],
              }
            } catch (error: any) {
              // fallback to provided data if fetch fails
              group.value = {
                id: newData.id,
                name: newData.name,
                knowledge_base_ids: newData.knowledge_bases?.map((kb: KnowledgeBase) => kb.id) || [],
                permission_ids: newData.permissions?.map((p: any) => p.id) || [],
              }
            }
          } else {
            group.value = {
              name: '',
              knowledge_base_ids: [],
              permission_ids: [],
            }
          }
        },
        { immediate: true }
      )

      const fetchKnowledgeBases = async () => {
        loadingKnowledgeBases.value = true

        try {
          const response = await axios.get<{status: string, data: KnowledgeBase[]}>('/api/knowledge-bases')
          knowledgeBases.value = response.data.data
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao buscar bases de conhecimento'
          showToast(errorMsg)
        } finally {
          loadingKnowledgeBases.value = false
        }
      }

      const fetchPermissions = async () => {
        loadingPermissions.value = true
        try {
          const response = await axios.get('/api/permissions')
          permissions.value = response.data.data || []
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao buscar permissões'
          showToast(errorMsg)
        } finally {
          loadingPermissions.value = false
        }
      }

      onMounted(() => {
        fetchKnowledgeBases()
        fetchPermissions()
      })

      const submitForm = async () => {
        const validation = await form.value?.validate()

        if (validation.valid) {
            try {
            // payload shape for API — keep ids as they are (string|number)
            const payload: { name: string; knowledge_base_ids: (string | number)[]; permission_ids?: number[] } = {
              name: group.value.name,
              knowledge_base_ids: group.value.knowledge_base_ids,
              permission_ids: group.value.permission_ids || [],
            }

            if (group.value.id) {
              await axios.put(`/api/groups/${group.value.id}`, payload)
            } else {
              await axios.post('/api/groups', payload)
            }
            emit('saved')
            close()
          } catch (error: any) {
            const errorMsg = error.response?.data?.message || 'Erro ao salvar grupo'
            showToast(errorMsg)
          }
        }
      }

      const close = () => {
        emit('close')
      }

      return {
        form,
        group,
        submitForm,
        close,
        isEdit: !!group.value.id,
        knowledgeBases,
        loadingKnowledgeBases,
        fetchKnowledgeBases,
        permissions,
        loadingPermissions,
      }
    },
  })
</script>
