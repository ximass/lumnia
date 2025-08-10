<template>
  <v-dialog v-model="dialogVisible" max-width="800px" persistent>
    <v-card>
      <v-card-title class="text-h5">Meu perfil</v-card-title>
      <v-card-text>
        <v-tabs v-model="activeTab" color="primary">
          <v-tab value="general">Informações gerais</v-tab>
          <v-tab value="personas">Personas</v-tab>
        </v-tabs>
        <v-tabs-window v-model="activeTab">
          <v-tabs-window-item value="general">
            <v-form ref="formRef" v-model="valid" @submit.prevent="updateProfile">
              <v-container>
                <v-row>
                  <v-col cols="12" md="4" class="text-center">
                    <v-avatar size="120" class="mb-4">
                      <v-img
                        v-if="previewImage || (form.avatar && form.avatar.length > 0)"
                        :src="
                          previewImage ||
                          (form.avatar && form.avatar.length > 0
                            ? `/api/avatars/${form.avatar.split('/').pop()}`
                            : '')
                        "
                        alt="Avatar"
                      />
                      <v-icon v-else size="60">mdi-account-circle</v-icon>
                    </v-avatar>
                  </v-col>
                  <v-col cols="12" md="8" class="d-flex align-center">
                    <v-file-input
                      ref="fileInput"
                      v-model="selectedFile"
                      accept="image/*"
                      label="Selecionar avatar"
                      prepend-icon="mdi-camera"
                      variant="outlined"
                      density="compact"
                      @change="onFileSelected"
                      :rules="avatarRules"
                      class="flex-grow-1"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="form.name"
                      label="Nome"
                      variant="outlined"
                      :rules="nameRules"
                      required
                    />
                  </v-col>
                </v-row>
              </v-container>
            </v-form>
          </v-tabs-window-item>
          <v-tabs-window-item value="personas">
            <v-container>
              <v-row>
                <v-col cols="12">
                  <h3 class="mb-4">Persona</h3>

                  <p class="text-body-2 mb-4">
                    Configure sua persona personalizada para interações com a IA.
                  </p>
                  <v-form ref="userPersonaFormRef" v-model="userPersonaValid">
                    <v-row>
                      <v-col cols="12">
                        <v-textarea
                          v-model="userPersonaForm.instructions"
                          label="Instruções"
                          placeholder="Descreva como a IA deve se comportar..."
                          variant="outlined"
                          rows="4"
                          :rules="userPersonaInstructionsRules"
                          required
                        />
                      </v-col>
                      <v-col cols="12">
                        <v-textarea
                          v-model="userPersonaForm.response_format"
                          label="Formato de resposta (opcional)"
                          placeholder="Especifique o formato desejado para as respostas..."
                          variant="outlined"
                          rows="3"
                          :rules="userPersonaResponseFormatRules"
                        />
                      </v-col>
                      <v-col cols="12">
                        <v-slider
                          v-model="userPersonaForm.creativity"
                          label="Criatividade"
                          min="0"
                          max="1"
                          step="0.1"
                          thumb-label
                          show-ticks="always"
                          tick-size="4"
                        >
                          <template #append>
                            <v-text-field
                              v-model="userPersonaForm.creativity"
                              type="number"
                              style="width: 80px"
                              density="compact"
                              variant="outlined"
                              min="0"
                              max="1"
                              step="0.1"
                            />
                          </template>
                        </v-slider>
                      </v-col>
                      <v-col cols="12" class="d-flex justify-end">
                        <v-btn
                          color="primary"
                          :loading="savingUserPersona"
                          :disabled="!userPersonaValid"
                          @click="saveOrUpdateUserPersona"
                        >
                          {{ userPersona ? 'Atualizar' : 'Criar' }} persona
                        </v-btn>
                      </v-col>
                    </v-row>
                  </v-form>
                </v-col>
              </v-row>
            </v-container>
          </v-tabs-window-item>
        </v-tabs-window>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn color="grey" variant="text" @click="closeModal" :disabled="loading">Cancelar</v-btn>
        <v-btn
          color="primary"
          variant="elevated"
          @click="updateProfile"
          :loading="loading"
          :disabled="!valid"
        >
          Salvar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
  import { defineComponent, ref, watch, computed, onMounted, nextTick } from 'vue'
  import axios from 'axios'
  import { useAuth } from '@/composables/auth'
  import { useToast } from '@/composables/useToast'
  import type { User, Profile, UserPersona, UserPersonaFormData } from '@/types/types'

  export default defineComponent({
    name: 'ProfileModal',
    props: {
      modelValue: {
        type: Boolean,
        default: false,
      },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
      const { user, fetchUser } = useAuth()
      const { showSuccess, showError } = useToast()

      const form = ref<Profile>({
        name: '',
        avatar: '',
      })

      const formRef = ref()
      const userPersonaFormRef = ref()
      const valid = ref(false)
      const loading = ref(false)
      const selectedFile = ref<File[]>([])
      const previewImage = ref<string>('')

      const activeTab = ref('general')

      const userPersona = ref<UserPersona | null>(null)
      const loadingUserPersona = ref(false)
      const showCreateUserPersona = ref(false)
      const userPersonaValid = ref(false)
      const savingUserPersona = ref(false)

      const userPersonaForm = ref<UserPersonaFormData>({
        instructions: '',
        response_format: '',
        creativity: 0.5,
      })

      const dialogVisible = computed({
        get: () => props.modelValue,
        set: value => emit('update:modelValue', value),
      })

      const handleUserDataLoad = async () => {
        if (!dialogVisible.value) return

        if (!user.value) {
          await fetchUser()
        }

        await nextTick()

        if (user.value) {
          form.value.name = user.value.name || ''
          form.value.avatar = user.value.avatar || ''

          previewImage.value = ''
          selectedFile.value = []

          if (user.value.user_persona) {
            userPersona.value = user.value.user_persona
            userPersonaForm.value = {
              instructions: user.value.user_persona.instructions,
              response_format: user.value.user_persona.response_format || '',
              creativity: user.value.user_persona.creativity,
            }
          } else {
            userPersona.value = null
            userPersonaForm.value = {
              instructions: '',
              response_format: '',
              creativity: 0.5,
            }
          }
        }
      }

      watch(dialogVisible, (newValue, oldValue) => {
        if (newValue && !oldValue) {
          handleUserDataLoad()
        }
      })

      watch(
        user,
        () => {
          if (dialogVisible.value && user.value) {
            handleUserDataLoad()
          }
        },
        { deep: true }
      )

      const nameRules = [
        (v: string) => !!v || 'Nome é obrigatório',
        (v: string) => v.length <= 255 || 'Nome deve ter no máximo 255 caracteres',
      ]

      const avatarRules = [
        (files: File[]) => {
          if (!files || files.length === 0) return true
          const file = files[0]
          return file.size <= 5 * 1024 * 1024 || 'Avatar deve ter no máximo 5MB'
        },
      ]

      const userPersonaInstructionsRules = [
        (v: string) => !!v || 'Instruções são obrigatórias',
        (v: string) => v.length <= 10000 || 'Instruções devem ter no máximo 10000 caracteres',
      ]

      const userPersonaResponseFormatRules = [
        (v: string) =>
          !v || v.length <= 2000 || 'Formato de resposta deve ter no máximo 2000 caracteres',
      ]

      const loadUserData = () => {
        if (user.value) {
          form.value.name = user.value.name || ''
          form.value.avatar = user.value.avatar || ''

          previewImage.value = ''
          selectedFile.value = []

          if (user.value.user_persona) {
            userPersona.value = user.value.user_persona
            userPersonaForm.value = {
              instructions: user.value.user_persona.instructions,
              response_format: user.value.user_persona.response_format || '',
              creativity: user.value.user_persona.creativity,
            }
          }
        }
      }

      onMounted(async () => {
        if (dialogVisible.value) {
          if (!user.value) {
            await fetchUser()
          }
          loadUserData()
        }
      })

      const saveOrUpdateUserPersona = async () => {
        if (!userPersonaValid.value) return

        savingUserPersona.value = true
        try {
          if (userPersona.value) {
            await axios.put('/api/user-persona', userPersonaForm.value)
            showSuccess('Persona de usuário atualizada com sucesso!')
          } else {
            const response = await axios.post('/api/user-persona', userPersonaForm.value)
            userPersona.value = response.data.data
            showSuccess('Persona de usuário criada com sucesso!')
          }

          await fetchUser()
        } catch (error: any) {
          console.error('Erro ao salvar persona de usuário:', error)
          if (error.response?.data?.message) {
            showError(error.response.data.message)
          } else {
            showError('Erro ao salvar persona de usuário. Tente novamente.')
          }
        } finally {
          savingUserPersona.value = false
        }
      }

      const onFileSelected = (files: File[]) => {
        if (files && files.length > 0) {
          const file = files[0]
          const reader = new FileReader()
          reader.onload = e => {
            if (e.target?.result) {
              previewImage.value = e.target.result as string
            }
          }
          reader.onerror = () => {
            showError('Erro ao carregar a imagem')
            previewImage.value = ''
          }
          reader.readAsDataURL(file)
        } else {
          previewImage.value = ''
        }
      }

      const updateProfile = async () => {
        if (!user.value || !valid.value) return

        loading.value = true
        try {
          const formData = new FormData()
          formData.append('name', form.value.name)

          if (selectedFile.value && selectedFile.value.length > 0) {
            formData.append('avatar', selectedFile.value[0])
          }

          const authToken = localStorage.getItem('authToken')
          await axios.post(`/api/user/${user.value.id}/profile`, formData, {
            headers: {
              Authorization: `Bearer ${authToken}`,
              'Content-Type': 'multipart/form-data',
            },
          })

          await fetchUser()
          showSuccess('Perfil atualizado com sucesso!')
          closeModal()
        } catch (error: any) {
          console.error('Erro ao atualizar perfil:', error)
          if (error.response?.data?.message) {
            showError(error.response.data.message)
          } else {
            showError('Erro ao atualizar perfil. Tente novamente.')
          }
        } finally {
          loading.value = false
        }
      }

      const resetModalState = () => {
        if (formRef.value) {
          formRef.value.reset()
          formRef.value.resetValidation()
        }

        if (userPersonaFormRef.value) {
          userPersonaFormRef.value.reset()
          userPersonaFormRef.value.resetValidation()
        }

        form.value = {
          name: '',
          avatar: '',
        }

        userPersonaForm.value = {
          instructions: '',
          response_format: '',
          creativity: 0.5,
        }

        previewImage.value = ''
        selectedFile.value = []
        activeTab.value = 'general'
        showCreateUserPersona.value = false
        valid.value = false
        userPersonaValid.value = false
      }

      const closeModal = () => {
        dialogVisible.value = false
        setTimeout(resetModalState, 300)
      }

      return {
        user,
        dialogVisible,
        form,
        formRef,
        userPersonaFormRef,
        valid,
        loading,
        selectedFile,
        previewImage,
        nameRules,
        avatarRules,
        activeTab,
        userPersona,
        loadingUserPersona,
        showCreateUserPersona,
        userPersonaValid,
        savingUserPersona,
        userPersonaForm,
        userPersonaInstructionsRules,
        userPersonaResponseFormatRules,
        onFileSelected,
        updateProfile,
        saveOrUpdateUserPersona,
        resetModalState,
        closeModal,
      }
    },
  })
</script>

<style scoped>
  .v-avatar {
    border: 2px solid rgba(0, 0, 0, 0.12);
  }
</style>
