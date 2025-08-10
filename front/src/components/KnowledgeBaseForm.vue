<template>
  <v-dialog v-model="internalDialog" max-width="600px">
    <v-card>
      <v-card-title>
        {{ formData.id ? 'Editar Base de Conhecimento' : 'Nova Base de Conhecimento' }}
      </v-card-title>
      <v-card-text>
        <v-form ref="form" v-model="isValid">
          <v-text-field label="Título" v-model="formData.title" :rules="titleRules" required />
          <v-textarea label="Conteúdo" v-model="formData.content" :rules="contentRules" required />
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="handleClose">Cancelar</v-btn>
        <v-btn color="primary" @click="save" :disabled="!isValid">Salvar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
  import { defineComponent, ref, watch } from 'vue'
  import axios from 'axios'
  import { useToast } from '@/composables/useToast'
  import type { KnowledgeBase, KnowledgeBaseFormData } from '@/types/types'

  export default defineComponent({
    name: 'KnowledgeBaseForm',
    props: {
      dialog: {
        type: Boolean,
        required: true,
      },
      knowledgeBaseData: {
        type: Object as () => KnowledgeBase | null,
        default: () => null,
      },
    },
    emits: ['close', 'saved'],
    setup(props, { emit }) {
      const { showToast } = useToast()
      const form = ref<any>(null)
      const isValid = ref(false)
      const internalDialog = ref(props.dialog)

      const formData = ref<KnowledgeBaseFormData>({ title: '', content: '' })

      watch(
        () => props.dialog,
        newVal => {
          internalDialog.value = newVal
        }
      )

      watch(internalDialog, val => {
        if (!val) {
          emit('close')
        }
      })

      watch(
        () => props.knowledgeBaseData,
        (newVal: KnowledgeBase | null) => {
          if (newVal) {
            formData.value = {
              id: newVal.id,
              title: newVal.title,
              content: newVal.content,
            }
          } else {
            formData.value = { title: '', content: '' }
          }
        },
        { immediate: true }
      )

      const titleRules = [(v: string) => !!v || 'Título é obrigatório']
      const contentRules = [(v: string) => !!v || 'Conteúdo é obrigatório']

      const save = async () => {
        const validation = await form.value?.validate()

        if (!validation.valid) {
          return
        }

        try {
          const payload: Omit<KnowledgeBaseFormData, 'id'> = {
            title: formData.value.title,
            content: formData.value.content,
          }

          if (formData.value.id) {
            await axios.put<KnowledgeBase>(`/api/knowledge-base/${formData.value.id}`, payload)
            showToast('Base de conhecimento atualizada com sucesso!', 'success')
          } else {
            await axios.post<KnowledgeBase>('/api/knowledge-bases', payload)
            showToast('Base de conhecimento criada com sucesso!', 'success')
          }
          internalDialog.value = false
          emit('saved')
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao salvar a base de conhecimento'
          showToast(errorMsg)
        }
      }

      const handleClose = () => {
        internalDialog.value = false
      }

      return {
        internalDialog,
        formData,
        form,
        isValid,
        titleRules,
        contentRules,
        save,
        handleClose,
      }
    },
  })
</script>

<style scoped></style>
