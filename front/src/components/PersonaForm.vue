<template>
  <v-dialog v-model="internalDialog" max-width="800px">
    <v-card>
      <v-card-title>
        <span class="text-h5">{{ persona.id ? 'Editar persona' : 'Nova persona' }}</span>
      </v-card-title>
      <v-card-text>
        <v-form ref="form" @submit.prevent="submitForm">
          <v-row>
            <v-col cols="12">
              <v-text-field
                label="Nome"
                v-model="persona.name"
                :rules="[v => !!v || 'Nome é obrigatório']"
                required
              />
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12">
              <v-textarea
                label="Descrição"
                v-model="persona.description"
                :rules="[v => !!v || 'Descrição é obrigatória']"
                rows="2"
                required
              />
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12">
              <v-textarea
                label="Instruções"
                v-model="persona.instructions"
                :rules="[v => !!v || 'Instruções são obrigatórias']"
                rows="6"
                counter="10000"
                hint="Instruções detalhadas sobre como o LLM deve se comportar"
                required
              />
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12">
              <v-textarea
                label="Formato de resposta"
                v-model="persona.response_format"
                rows="3"
                hint="Descreva o formato esperado das respostas (opcional)"
              />
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="12" md="6">
              <v-combobox
                v-model="persona.keywords"
                label="Palavras-chave"
                multiple
                chips
                closable-chips
                hint="Pressione Enter para adicionar uma palavra-chave"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-slider
                v-model="persona.creativity"
                label="Criatividade"
                :min="0"
                :max="1"
                :step="0.1"
                thumb-label="always"
                :thumb-size="24"
                track-color="grey"
                track-fill-color="primary"
              >
                <template #thumb-label="{ modelValue }">
                  {{ Math.round(modelValue * 100) }}%
                </template>
              </v-slider>
              <div class="text-caption text-center mt-2">
                0% = Muito Preciso | 100% = Muito Criativo
              </div>
            </v-col>
            <v-col cols="12" md="2">
              <v-switch v-model="persona.active" label="Ativa" color="success" />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="close">Cancelar</v-btn>
        <v-btn color="primary" @click="submitForm" :loading="loading">Salvar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
  import { defineComponent, ref, watch } from 'vue'
  import axios from 'axios'
  import { useToast } from '@/composables/useToast'
  import type { Persona, PersonaFormData } from '@/types/types'

  export default defineComponent({
    name: 'PersonaForm',
    props: {
      dialog: { type: Boolean, required: true },
      personaData: {
        type: Object as () => Persona | null,
        default: null,
      },
    },
    emits: ['close', 'saved'],
    setup(props, { emit }) {
      const { showToast } = useToast()
      const internalDialog = ref(props.dialog)
      const form = ref()
      const loading = ref(false)

      const persona = ref<PersonaFormData>({
        name: '',
        description: '',
        instructions: '',
        response_format: '',
        keywords: [],
        creativity: 0.7,
        active: true,
      })

      watch(
        () => props.dialog,
        val => {
          internalDialog.value = val
        }
      )

      watch(
        () => props.personaData,
        (newData: Persona | null) => {
          if (newData) {
            persona.value = {
              id: newData.id,
              name: newData.name,
              description: newData.description,
              instructions: newData.instructions,
              response_format: newData.response_format || '',
              keywords: newData.keywords || [],
              creativity: newData.creativity,
              active: newData.active,
            }
          } else {
            persona.value = {
              name: '',
              description: '',
              instructions: '',
              response_format: '',
              keywords: [],
              creativity: 0.7,
              active: true,
            }
          }
        },
        { immediate: true }
      )

      const close = () => {
        emit('close')
      }

      const submitForm = async () => {
        const validation = await form.value?.validate()

        if (!validation.valid) {
          return
        }

        loading.value = true

        try {
          const payload: Omit<PersonaFormData, 'id'> = {
            name: persona.value.name,
            description: persona.value.description,
            instructions: persona.value.instructions,
            response_format: persona.value.response_format || undefined,
            keywords:
              persona.value.keywords && persona.value.keywords.length > 0
                ? persona.value.keywords
                : undefined,
            creativity: persona.value.creativity,
            active: persona.value.active,
          }

          if (persona.value.id) {
            await axios.put<Persona>(`/api/personas/${persona.value.id}`, payload)
            showToast('Persona atualizada com sucesso!', 'success')
          } else {
            await axios.post<Persona>('/api/personas', payload)
            showToast('Persona criada com sucesso!', 'success')
          }

          emit('saved')
          close()
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao salvar persona'
          showToast(errorMsg)
        } finally {
          loading.value = false
        }
      }

      return {
        internalDialog,
        persona,
        form,
        loading,
        close,
        submitForm,
        showToast,
      }
    },
  })
</script>

<style scoped>
  .v-slider {
    padding-top: 16px;
  }
</style>
