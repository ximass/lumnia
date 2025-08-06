<template>
  <v-dialog v-model="dialogVisible" max-width="800px" persistent>
    <v-card>
      <v-card-title class="text-h5">
        Meu perfil
      </v-card-title>
      
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
                        :src="previewImage || (form.avatar && form.avatar.length > 0 ? `/api/avatars/${form.avatar.split('/').pop()}` : '')"
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
                  <h3 class="mb-4">Persona padrão</h3>
                  <v-select
                    v-model="form.default_persona_id"
                    :items="personas"
                    item-title="name"
                    item-value="id"
                    label="Selecionar persona padrão"
                    variant="outlined"
                    clearable
                    :loading="loadingPersonas"
                  >
                    <template v-slot:item="{ item, props }">
                      <v-list-item v-bind="props">
                        <v-list-item-title>{{ item.raw.name }}</v-list-item-title>
                        <v-list-item-subtitle>{{ item.raw.description }}</v-list-item-subtitle>
                      </v-list-item>
                    </template>
                  </v-select>
                </v-col>

                <v-col cols="12">
                  <v-divider class="my-4" />
                  <div class="d-flex justify-space-between align-center mb-4">
                    <h3>Criar nova persona</h3>
                    <v-btn
                      color="primary"
                      @click="showCreatePersona = !showCreatePersona"
                      :prepend-icon="showCreatePersona ? 'mdi-chevron-up' : 'mdi-chevron-down'"
                      variant="text"
                    >
                      {{ showCreatePersona ? 'Ocultar' : 'Mostrar' }} formulário
                    </v-btn>
                  </div>

                  <v-expand-transition>
                    <v-card v-show="showCreatePersona" variant="outlined" class="pa-4">
                      <v-form ref="personaFormRef" v-model="personaValid">
                        <v-row>
                          <v-col cols="12">
                            <v-text-field
                              v-model="newPersona.name"
                              label="Nome da persona"
                              variant="outlined"
                              :rules="personaNameRules"
                              required
                            />
                          </v-col>

                          <v-col cols="12">
                            <v-textarea
                              v-model="newPersona.description"
                              label="Descrição"
                              variant="outlined"
                              :rules="personaDescriptionRules"
                              required
                            />
                          </v-col>

                          <v-col cols="12">
                            <v-textarea
                              v-model="newPersona.instructions"
                              label="Instruções"
                              variant="outlined"
                              :rules="personaInstructionsRules"
                              required
                            />
                          </v-col>

                          <v-col cols="12">
                            <v-textarea
                              v-model="newPersona.response_format"
                              label="Formato de resposta"
                              variant="outlined"
                              rows="3"
                              placeholder="Defina o formato desejado para as respostas (opcional)"
                            />
                          </v-col>

                          <v-col cols="12" sm="6">
                            <v-slider
                              v-model="newPersona.creativity"
                              label="Criatividade"
                              min="0"
                              max="1"
                              step="0.1"
                              thumb-label
                              :thumb-size="24"
                            />
                            <div class="text-caption text-center">
                              0% = Muito preciso | 100% = Muito criativo
                            </div>
                          </v-col>

                          <v-col cols="12">
                            <v-btn
                              color="primary"
                              @click="createPersona"
                              :loading="creatingPersona"
                              :disabled="!personaValid"
                              block
                            >
                              Criar persona
                            </v-btn>
                          </v-col>
                        </v-row>
                      </v-form>
                    </v-card>
                  </v-expand-transition>
                </v-col>
              </v-row>
            </v-container>
          </v-tabs-window-item>
        </v-tabs-window>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn
          color="grey"
          variant="text"
          @click="closeModal"
          :disabled="loading"
        >
          Cancelar
        </v-btn>
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
import { defineComponent, ref, watch, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { useAuth } from '@/composables/auth';
import { useToast } from '@/composables/useToast';
import type { User, Profile, Persona, PersonaFormData } from '@/types/types';

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
    const { user, fetchUser } = useAuth();
    const { showSuccess, showError } = useToast();
    
    const form = ref<Profile>({
      name: '',
      avatar: '',
      default_persona_id: undefined,
    });
    
    const formRef = ref();
    const personaFormRef = ref();
    const valid = ref(false);
    const loading = ref(false);
    const selectedFile = ref<File[]>([]);
    const previewImage = ref<string>('');

    const activeTab = ref('general');

    const personas = ref<Persona[]>([]);
    const loadingPersonas = ref(false);
    const showCreatePersona = ref(false);
    const personaValid = ref(false);
    const creatingPersona = ref(false);

    const newPersona = ref<PersonaFormData>({
      name: '',
      description: '',
      instructions: '',
      response_format: '',
      creativity: 0.5,
    });

    const dialogVisible = computed({
      get: () => props.modelValue,
      set: (value) => emit('update:modelValue', value),
    });

    const handleUserDataLoad = async () => {
      if (!dialogVisible.value) return;
      
      if (!user.value) {
        await fetchUser();
      }
      
      await nextTick();
      
      if (user.value) {
        form.value.name = user.value.name || '';
        form.value.default_persona_id = user.value.default_persona_id || undefined;
        form.value.avatar = user.value.avatar || '';
        
        previewImage.value = '';
        selectedFile.value = [];
      }
      
      if (personas.value.length === 0) {
        loadPersonas();
      }
    };

    watch(dialogVisible, (newValue, oldValue) => {
      if (newValue && !oldValue) {
        handleUserDataLoad();
      }
    });

    watch(user, () => {
      if (dialogVisible.value && user.value) {
        handleUserDataLoad();
      }
    }, { deep: true });

    const nameRules = [
      (v: string) => !!v || 'Nome é obrigatório',
      (v: string) => v.length <= 255 || 'Nome deve ter no máximo 255 caracteres',
    ];

    const avatarRules = [
      (files: File[]) => {
        if (!files || files.length === 0) return true;
        const file = files[0];
        return file.size <= 5 * 1024 * 1024 || 'Avatar deve ter no máximo 5MB';
      },
    ];

    const personaNameRules = [
      (v: string) => !!v || 'Nome é obrigatório',
      (v: string) => v.length <= 255 || 'Nome deve ter no máximo 255 caracteres',
    ];

    const personaDescriptionRules = [
      (v: string) => !!v || 'Descrição é obrigatória',
      (v: string) => v.length <= 1000 || 'Descrição deve ter no máximo 1000 caracteres',
    ];

    const personaInstructionsRules = [
      (v: string) => !!v || 'Instruções são obrigatórias',
      (v: string) => v.length <= 10000 || 'Instruções devem ter no máximo 10000 caracteres',
    ];

    const loadUserData = () => {
      if (user.value) {
        form.value.name = user.value.name || '';
        form.value.default_persona_id = user.value.default_persona_id || undefined;
        form.value.avatar = user.value.avatar || '';

        previewImage.value = '';
        selectedFile.value = [];
      }
    };

    onMounted(async () => {
      if (dialogVisible.value) {
        if (!user.value) {
          await fetchUser();
        }
        loadUserData();
        loadPersonas();
      }
    });

    const loadPersonas = async () => {
      loadingPersonas.value = true;
      try {
        const authToken = localStorage.getItem('authToken');
        const response = await axios.get('/api/personas', {
          headers: {
            Authorization: `Bearer ${authToken}`,
          },
        });
        personas.value = response.data;
      } catch (error) {
        console.error('Erro ao carregar personas:', error);
        showError('Erro ao carregar personas');
      } finally {
        loadingPersonas.value = false;
      }
    };

    const onFileSelected = (files: File[]) => {
      if (files && files.length > 0) {
        const file = files[0];
        const reader = new FileReader();
        reader.onload = (e) => {
          if (e.target?.result) {
            previewImage.value = e.target.result as string;
          }
        };
        reader.onerror = () => {
          showError('Erro ao carregar a imagem');
          previewImage.value = '';
        };
        reader.readAsDataURL(file);
      } else {
        previewImage.value = '';
      }
    };

    const updateProfile = async () => {
      if (!user.value || !valid.value) return;

      loading.value = true;
      try {
        const formData = new FormData();
        formData.append('name', form.value.name);

        if (form.value.default_persona_id) {
          formData.append('default_persona_id', form.value.default_persona_id.toString());
        }

        if (selectedFile.value && selectedFile.value.length > 0) {
          formData.append('avatar', selectedFile.value[0]);
        }

        const authToken = localStorage.getItem('authToken');
        await axios.post(`/api/user/${user.value.id}/profile`, formData, {
          headers: {
            Authorization: `Bearer ${authToken}`,
            'Content-Type': 'multipart/form-data',
          },
        });

        await fetchUser();
        showSuccess('Perfil atualizado com sucesso!');
        closeModal();
      } catch (error: any) {
        console.error('Erro ao atualizar perfil:', error);
        if (error.response?.data?.message) {
          showError(error.response.data.message);
        } else {
          showError('Erro ao atualizar perfil. Tente novamente.');
        }
      } finally {
        loading.value = false;
      }
    };

    const createPersona = async () => {
      if (!personaValid.value) return;

      creatingPersona.value = true;
      try {
        const authToken = localStorage.getItem('authToken');
        const response = await axios.post('/api/personas', newPersona.value, {
          headers: {
            Authorization: `Bearer ${authToken}`,
            'Content-Type': 'application/json',
          },
        });

        if (response.data.status === 'success') {
          showSuccess('Persona criada com sucesso!');
          newPersona.value = {
            name: '',
            description: '',
            instructions: '',
            response_format: '',
            creativity: 0.5,
          };
          showCreatePersona.value = false;
          await loadPersonas();
        }
      } catch (error: any) {
        console.error('Erro ao criar persona:', error);
        if (error.response?.data?.message) {
          showError(error.response.data.message);
        } else {
          showError('Erro ao criar persona. Tente novamente.');
        }
      } finally {
        creatingPersona.value = false;
      }
    };

    const resetModalState = () => {
      if (formRef.value) {
        formRef.value.reset();
        formRef.value.resetValidation();
      }
      
      if (personaFormRef.value) {
        personaFormRef.value.reset();
        personaFormRef.value.resetValidation();
      }
      
      form.value = {
        name: '',
        avatar: '',
        default_persona_id: undefined,
      };
      previewImage.value = '';
      selectedFile.value = [];
      activeTab.value = 'general';
      showCreatePersona.value = false;
      valid.value = false;
      personaValid.value = false;
      
      newPersona.value = {
        name: '',
        description: '',
        instructions: '',
        response_format: '',
        creativity: 0.5,
      };
    };

    const closeModal = () => {
      dialogVisible.value = false;
      setTimeout(resetModalState, 300);
    };

    return {
      user,
      dialogVisible,
      form,
      formRef,
      personaFormRef,
      valid,
      loading,
      selectedFile,
      previewImage,
      nameRules,
      avatarRules,
      activeTab,
      personas,
      loadingPersonas,
      showCreatePersona,
      personaValid,
      creatingPersona,
      newPersona,
      personaNameRules,
      personaDescriptionRules,
      personaInstructionsRules,
      onFileSelected,
      updateProfile,
      createPersona,
      resetModalState,
      closeModal,
    };
  },
});
</script>

<style scoped>
.v-avatar {
  border: 2px solid rgba(0, 0, 0, 0.12);
}
</style>
