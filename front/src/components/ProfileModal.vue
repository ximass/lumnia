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
              <v-container class="py-6">
                <v-row justify="center" class="mb-6">
                  <v-col cols="12" class="text-center">
                    <div class="position-relative d-inline-block">
                      <v-avatar 
                        size="140" 
                        class="avatar-container elevation-4"
                        :class="{ 'avatar-hover': !loading }"
                      >
                        <v-img
                          v-if="previewImage || hasAvatar(form.avatar)"
                          :src="previewImage || getAvatarUrl(form.avatar)!"
                          alt="Avatar"
                          cover
                        />
                        <span v-else class="text-h2 text-white font-weight-medium">
                          {{ getInitials(form.name) }}
                        </span>
                      </v-avatar>
                      
                      <v-btn
                        v-if="hasAvatar(form.avatar) || previewImage"
                        icon
                        size="small"
                        color="error"
                        class="avatar-delete-btn elevation-2"
                        @click="removeAvatar"
                        :disabled="loading"
                      >
                        <v-icon size="20">mdi-close</v-icon>
                      </v-btn>
                    </div>
                    
                    <div class="mt-4">
                      <v-file-input
                        ref="fileInput"
                        v-model="selectedFile"
                        accept="image/*"
                        label="Selecionar avatar"
                        prepend-icon="mdi-camera"
                        variant="outlined"
                        density="comfortable"
                        @change="onFileSelected"
                        :rules="avatarRules"
                        :disabled="loading"
                        hide-details="auto"
                        class="mx-auto"
                        style="max-width: 400px;"
                      >
                        <template #prepend-inner>
                          <v-icon color="primary">mdi-image-outline</v-icon>
                        </template>
                      </v-file-input>
                      <p class="text-caption text-medium-emphasis mt-2">
                        Tamanho máximo: 5MB. Formatos aceitos: JPG, JPEG, PNG e GIF
                      </p>
                    </div>
                  </v-col>
                </v-row>

                <v-divider class="my-6" />

                <v-row justify="center">
                  <v-col cols="12" md="8">
                    <v-text-field
                      v-model="form.name"
                      label="Nome completo"
                      placeholder="Digite seu nome"
                      variant="outlined"
                      density="comfortable"
                      :rules="nameRules"
                      :disabled="loading"
                      prepend-inner-icon="mdi-account-outline"
                      required
                      hide-details="auto"
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
                  <div class="d-flex justify-space-between align-center mb-4">
                    <h3>Persona</h3>
                    <v-switch
                      v-if="userPersona"
                      v-model="userPersonaForm.active"
                      :label="userPersonaForm.active ? 'Ativa' : 'Inativa'"
                      color="primary"
                      hide-details
                      @update:modelValue="toggleUserPersonaActive"
                    />
                  </div>

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
          :disabled="activeTab === 'general' ? !valid : !userPersonaValid"
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
  import { useAvatar } from '@/composables/useAvatar'
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
      const { getInitials, getAvatarUrl, hasAvatar } = useAvatar()

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
        active: false,
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
              active: user.value.user_persona.active,
            }
          } else {
            userPersona.value = null
            userPersonaForm.value = {
              instructions: '',
              response_format: '',
              creativity: 0.5,
              active: false,
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
        (v: string) =>
          !v || v.length <= 500 || 'Instruções deve ter no máximo 500 caracteres',
      ]

      const userPersonaResponseFormatRules = [
        (v: string) =>
          !v || v.length <= 500 || 'Formato de resposta deve ter no máximo 500 caracteres',
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
              active: user.value.user_persona.active,
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
          const payload = {
            instructions: userPersonaForm.value.instructions,
            response_format: userPersonaForm.value.response_format,
            creativity: userPersonaForm.value.creativity,
          }

          if (userPersona.value) {
            await axios.put('/api/user-persona', payload)
            showSuccess('Persona de usuário atualizada com sucesso!')
          } else {
            const response = await axios.post('/api/user-persona', payload)
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

      const toggleUserPersonaActive = async () => {
        if (!userPersona.value) return

        try {
          const response = await axios.patch('/api/user-persona/toggle-active')
          userPersona.value = response.data.data
          await fetchUser()

          const statusMessage = response.data.data.active ? 'ativada' : 'desativada'
          showSuccess(`Persona ${statusMessage} com sucesso!`)
        } catch (error: any) {
          console.error('Erro ao alternar status da persona:', error)
          if (error.response?.data?.message) {
            showError(error.response.data.message)
          } else {
            showError('Erro ao alternar status da persona. Tente novamente.')
          }

          if (user.value?.user_persona) {
            userPersonaForm.value.active = user.value.user_persona.active
          }
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

      const removeAvatar = async () => {
        if (!user.value) return

        if (previewImage.value) {
          previewImage.value = ''
          selectedFile.value = []
          return
        }

        if (!hasAvatar(form.value.avatar)) return

        loading.value = true
        try {
          await axios.delete(`/api/user/${user.value.id}/avatar`)
          form.value.avatar = ''
          previewImage.value = ''
          selectedFile.value = []

          await fetchUser()

          showSuccess('Avatar removido com sucesso!')
        } catch (error: any) {
          if (error.response?.data?.message) {
            showError(error.response.data.message)
          } else {
            showError('Erro ao remover avatar. Tente novamente.')
          }
        } finally {
          loading.value = false
        }
      }

      const updateProfile = async () => {
        if (!user.value) return

        loading.value = true
        try {
          if (valid.value) {
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
          }

          if (userPersonaValid.value && activeTab.value === 'personas') {
            const payload = {
              instructions: userPersonaForm.value.instructions,
              response_format: userPersonaForm.value.response_format,
              creativity: userPersonaForm.value.creativity,
            }

            if (userPersona.value) {
              await axios.put('/api/user-persona', payload)
            } else {
              const response = await axios.post('/api/user-persona', payload)
              userPersona.value = response.data.data
            }
          }

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
          active: false,
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
        removeAvatar,
        updateProfile,
        saveOrUpdateUserPersona,
        toggleUserPersonaActive,
        resetModalState,
        closeModal,
        getInitials,
        getAvatarUrl,
        hasAvatar,
      }
    },
  })
</script>

<style scoped>
  .avatar-container {
    border: 4px solid rgba(var(--v-theme-primary), 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgb(var(--v-theme-secondary)) 100%);
  }

  .avatar-hover:hover {
    transform: scale(1.05);
    border-color: rgba(var(--v-theme-primary), 0.3);
  }

  .avatar-delete-btn {
    position: absolute;
    top: 0;
    right: 0;
    transform: translate(25%, -25%);
  }

  .position-relative {
    position: relative;
  }

  .d-inline-block {
    display: inline-block;
  }
</style>
