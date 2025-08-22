<template>
  <v-container fluid class="pa-0 fill-height">
    <!-- Header -->
    <v-app-bar 
      elevation="1" 
      color="surface" 
      class="px-6"
      height="72"
    >
      <v-btn
        icon
        variant="text"
        @click="handleCancel"
        class="me-4"
      >
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      <v-app-bar-title class="text-h5 font-weight-bold">
        {{ isEdit ? 'Editar base de conhecimento' : 'Nova base de conhecimento' }}
      </v-app-bar-title>
      <v-spacer />
      <v-btn 
        variant="outlined" 
        @click="handleCancel"
        :disabled="isProcessing"
        class="me-3"
      >
        Cancelar
      </v-btn>
      <v-btn 
        color="primary" 
        @click="save" 
        :disabled="!isValid || isProcessing || (!isEdit && selectedFiles.length === 0)"
        :loading="isProcessing"
        variant="flat"
      >
        {{ isEdit ? 'Atualizar' : 'Criar base de conhecimento' }}
      </v-btn>
    </v-app-bar>

    <!-- Main Content -->
    <v-main class="pa-0">
      <v-container fluid class="pa-6 h-100">
        <v-row class="h-100" no-gutters>
          <!-- Left Column - Form -->
          <v-col cols="12" md="5" class="pe-md-3">
            <v-card elevation="2" class="h-100">
              <v-card-title class="text-h6 pa-6 pb-4 d-flex align-center bg-primary-lighten-5">
                <v-icon color="primary" class="me-3">mdi-information</v-icon>
                Informações básicas
              </v-card-title>
              
              <v-card-text class="pa-6">
                <v-form ref="form" v-model="isValid">
                  <v-text-field 
                    label="Nome" 
                    v-model="formData.name" 
                    :rules="nameRules" 
                    required 
                    variant="outlined"
                    class="mb-4"
                    density="comfortable"
                  />
                  
                  <v-textarea 
                    label="Descrição" 
                    v-model="formData.description" 
                    rows="4" 
                    variant="outlined"
                    density="comfortable"
                    auto-grow
                  />
                </v-form>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Right Column - Files -->
          <v-col cols="12" md="7" class="ps-md-3">
            <v-card elevation="2" class="h-100">
              <v-card-title class="text-h6 pa-6 pb-4 d-flex align-center bg-secondary-lighten-5">
                <v-icon color="secondary" class="me-3">mdi-file-multiple</v-icon>
                Arquivos da base de conhecimento
                <v-spacer />
                <v-chip 
                  v-if="selectedFiles.length > 0" 
                  color="secondary" 
                  variant="flat" 
                  size="small"
                >
                  {{ selectedFiles.length }} arquivo{{ selectedFiles.length !== 1 ? 's' : '' }}
                </v-chip>
              </v-card-title>
              
              <v-card-text class="pa-6 overflow-y-auto" style="max-height: calc(100vh - 200px);">
                <!-- Upload Section -->
                <div class="mb-6">
                  <v-file-input
                    v-model="selectedFiles"
                    label="Selecionar arquivos"
                    multiple
                    accept=".txt,.pdf"
                    variant="outlined"
                    prepend-icon="mdi-file-document"
                    :rules="fileRules"
                    class="mb-4"
                    @change="handleFileSelection"
                    density="comfortable"
                  >
                    <template #selection="{ fileNames }">
                      <div class="file-chips-container">
                        <template v-for="fileName in fileNames" :key="fileName">
                          <v-chip
                            color="primary"
                            variant="outlined"
                            size="small"
                            class="me-2 mb-2"
                          >
                            {{ fileName }}
                          </v-chip>
                        </template>
                      </div>
                    </template>
                  </v-file-input>

                  <v-alert
                    v-if="selectedFiles.length === 0 && !isEdit"
                    type="info"
                    variant="tonal"
                    class="mb-4"
                    border="start"
                    border-color="info"
                  >
                    <template #text>
                      <div class="d-flex align-center">
                        <div>
                          <strong>Selecione pelo menos um arquivo</strong> (.txt ou .pdf) para criar a base de conhecimento.
                          <br>
                          <small class="text-medium-emphasis">Tamanho máximo: 10MB por arquivo • Formatos aceitos: TXT, PDF</small>
                        </div>
                      </div>
                    </template>
                  </v-alert>
                </div>

                <!-- Progress Section -->
                <div v-if="uploadProgress.length > 0" class="mb-6">
                  <h4 class="text-h6 mb-3 d-flex align-center">
                    <v-icon color="warning" class="me-2">mdi-progress-upload</v-icon>
                    Progresso do upload
                  </h4>
                  <div
                    v-for="progress in uploadProgress"
                    :key="progress.fileName"
                    class="mb-4"
                  >
                    <div class="d-flex justify-space-between align-center mb-2">
                      <span class="text-body-1 font-weight-medium">{{ progress.fileName }}</span>
                      <v-chip
                        :color="progress.status === 'Erro' ? 'error' : progress.status === 'Concluído' ? 'success' : 'warning'"
                        size="small"
                        variant="flat"
                      >
                        {{ progress.status }}
                      </v-chip>
                    </div>
                    <v-progress-linear
                      :model-value="progress.percentage"
                      :color="progress.status === 'Erro' ? 'error' : 'primary'"
                      height="8"
                      rounded
                    />
                  </div>
                </div>

                <!-- Existing Files Section -->
                <div v-if="isEdit && sources.length > 0" class="mb-6">
                  <h4 class="text-h6 mb-3 d-flex align-center">
                    <v-icon color="success" class="me-2">mdi-check-circle</v-icon>
                    Arquivos já carregados
                  </h4>
                  <v-list class="border rounded">
                    <v-list-item
                      v-for="(source, index) in sources"
                      :key="source.id"
                      :class="{ 'border-b': index < sources.length - 1 }"
                    >
                      <template #prepend>
                        <v-icon color="primary">
                          {{ source.source_type === 'pdf' ? 'mdi-file-pdf-box' : 'mdi-file-document' }}
                        </v-icon>
                      </template>

                      <v-list-item-title class="font-weight-medium">
                        {{ source.metadata?.original_filename || source.source_identifier }}
                      </v-list-item-title>
                      
                      <v-list-item-subtitle>
                        Status: {{ getStatusText(source.status) }}
                        <span v-if="source.metadata?.file_size" class="ml-2">
                          • {{ formatFileSize(source.metadata.file_size) }}
                        </span>
                      </v-list-item-subtitle>

                      <template #append>
                        <v-chip
                          :color="getStatusColor(source.status)"
                          size="small"
                          variant="flat"
                        >
                          {{ getStatusText(source.status) }}
                        </v-chip>
                      </template>
                    </v-list-item>
                  </v-list>
                </div>

                <!-- Empty State -->
                <div v-if="!isEdit && selectedFiles.length === 0 && uploadProgress.length === 0" class="text-center py-8">
                  <div class="empty-state">
                    <v-icon size="80" color="grey-lighten-2" class="mb-4">
                      mdi-cloud-upload
                    </v-icon>
                    <h3 class="text-h6 text-grey-darken-1 mb-2">Nenhum arquivo selecionado</h3>
                    <p class="text-body-2 text-grey mb-4">
                      Arraste arquivos aqui ou use o botão acima para selecionar
                    </p>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, watch, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/auth'
import { useFileUpload } from '@/composables/useFileUpload'
import type { KnowledgeBase, KnowledgeBaseFormData, Source, ApiResponse } from '@/types/types'

export default defineComponent({
  name: 'KnowledgeBaseCreateView',
  setup() {
    const router = useRouter()
    const route = useRoute()
    const { showToast } = useToast()
    const { user } = useAuth()
    const { uploadProgress, isUploading, uploadFiles, resetProgress } = useFileUpload()
    
    const form = ref<any>(null)
    const isValid = ref(false)
    const isProcessing = ref(false)
    const selectedFiles = ref<File[]>([])
    const sources = ref<Source[]>([])

    const isEdit = computed(() => !!route.params.id)

    const formData = ref<KnowledgeBaseFormData>({
      name: '',
      description: '',
      owner_id: user.value?.id || 0
    })

    const nameRules = [
      (v: string) => !!v || 'Nome é obrigatório',
      (v: string) => (v && v.length >= 3) || 'Nome deve ter pelo menos 3 caracteres'
    ]

    const fileRules = [
      (files: File[]) => {
        if (!files || files.length === 0) return true
        return files.every(file => 
          ['text/plain', 'application/pdf'].includes(file.type) ||
          file.name.toLowerCase().endsWith('.txt') ||
          file.name.toLowerCase().endsWith('.pdf')
        ) || 'Apenas arquivos .txt e .pdf são permitidos'
      },
      (files: File[]) => {
        if (!files || files.length === 0) return true
        return files.every(file => file.size <= 10 * 1024 * 1024) || 'Arquivos devem ter no máximo 10MB'
      }
    ]

    const getStatusText = (status: string): string => {
      const statusMap: Record<string, string> = {
        'uploaded': 'Carregado',
        'processing': 'Processando',
        'chunked': 'Processado',
        'embedding': 'Gerando embeddings',
        'processed': 'Concluído',
        'failed': 'Falhou',
        'embedding_failed': 'Falha nos embeddings',
        'upsert_failed': 'Falha no armazenamento'
      }
      return statusMap[status] || status
    }

    const getStatusColor = (status: string): string => {
      const colorMap: Record<string, string> = {
        'uploaded': 'info',
        'processing': 'warning',
        'chunked': 'primary',
        'embedding': 'warning',
        'processed': 'success',
        'failed': 'error',
        'embedding_failed': 'error',
        'upsert_failed': 'error'
      }
      return colorMap[status] || 'default'
    }

    const formatFileSize = (bytes: number): string => {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }

    const handleFileSelection = () => {
      resetProgress()
    }

    const loadKnowledgeBase = async () => {
      if (!isEdit.value) return

      try {
        const response = await axios.get<ApiResponse<KnowledgeBase>>(`/api/knowledge-bases/${route.params.id}`)
        const kb = response.data.data

        if (kb) {
          formData.value = {
            id: kb.id,
            name: kb.name,
            description: kb.description || '',
            owner_id: kb.owner_id
          }

          sources.value = kb.sources || []
        }
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao carregar base de conhecimento'
        showToast(errorMsg)
        router.push('/knowledge-bases')
      }
    }

    const save = async () => {
      const validation = await form.value?.validate()
      if (!validation.valid) return

      isProcessing.value = true

      try {
        let knowledgeBaseId: string

        if (isEdit.value) {
          // Atualizar KB existente
          const response = await axios.put<ApiResponse<KnowledgeBase>>(
            `/api/knowledge-bases/${route.params.id}`,
            {
              name: formData.value.name,
              description: formData.value.description
            }
          )
          knowledgeBaseId = response.data.data?.id || route.params.id as string
          showToast('Base de conhecimento atualizada com sucesso!', 'success')
        } else {
          // Criar nova KB
          const response = await axios.post<ApiResponse<KnowledgeBase>>('/api/knowledge-bases', {
            name: formData.value.name,
            description: formData.value.description,
            owner_id: user.value?.id
          })
          knowledgeBaseId = response.data.data?.id || ''
          showToast('Base de conhecimento criada com sucesso!', 'success')
        }

        const uploadSuccess = await uploadFiles(selectedFiles.value, knowledgeBaseId)

        if (uploadSuccess || selectedFiles.value.length === 0) {
          router.push('/knowledge-bases')
        } else {
          showToast('Base de conhecimento criada, mas alguns arquivos falharam no upload', 'warning')
        }

      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao salvar a base de conhecimento'
        showToast(errorMsg)
      } finally {
        isProcessing.value = false
      }
    }

    const handleCancel = () => {
      router.push('/knowledge-bases')
    }

    onMounted(() => {
      if (isEdit.value) {
        loadKnowledgeBase()
      }
    })

    return {
      form,
      isValid,
      isProcessing,
      selectedFiles,
      sources,
      uploadProgress,
      isEdit,
      formData,
      nameRules,
      fileRules,
      getStatusText,
      getStatusColor,
      formatFileSize,
      handleFileSelection,
      save,
      handleCancel
    }
  }
})
</script>

<style scoped>
.fill-height {
  height: 100vh;
}

.h-100 {
  height: 100% !important;
}

/* Container and main layout */
.v-container {
  max-width: none !important;
}

.v-main {
  height: calc(100vh - 72px);
}

/* Card styling */
.v-card {
  border-radius: 12px;
  transition: all 0.3s ease;
  height: 100%;
}

.v-card:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12) !important;
}

/* Header styling with gradients */
.bg-primary-lighten-5 {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary-lighten-5)), rgb(var(--v-theme-primary-lighten-4))) !important;
}

.bg-secondary-lighten-5 {
  background: linear-gradient(135deg, rgb(var(--v-theme-secondary-lighten-5)), rgb(var(--v-theme-secondary-lighten-4))) !important;
}

/* File input styling */
.v-file-input :deep(.v-field__input) {
  min-height: 56px;
  padding: 12px 16px;
}

.v-file-input :deep(.v-field__overlay) {
  border-radius: 8px;
}

.v-file-input :deep(.v-field) {
  transition: all 0.3s ease;
}

.v-file-input:hover :deep(.v-field) {
  box-shadow: 0 4px 12px rgba(var(--v-theme-primary), 0.15);
  border-color: rgb(var(--v-theme-primary));
}

/* File chips container */
.file-chips-container {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  max-width: 100%;
}

/* Progress section styling */
.v-progress-linear {
  border-radius: 6px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Alert styling */
.v-alert {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* List styling */
.v-list {
  border-radius: 8px;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.v-list-item {
  padding: 16px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.v-list-item:hover {
  background-color: rgba(var(--v-theme-primary), 0.04);
}

.border-b:not(:last-child) {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

/* Chip styling */
.v-chip {
  font-weight: 500;
  border-radius: 6px;
}

/* Button styling */
.v-btn {
  border-radius: 8px;
  font-weight: 600;
  text-transform: none;
  min-height: 40px;
}

/* App bar styling */
.v-app-bar {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

/* Empty state styling */
.empty-state {
  padding: 2rem;
  border: 2px dashed rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 12px;
  background: rgba(var(--v-theme-surface), 0.5);
}

/* Responsive design */
@media (max-width: 960px) {
  .v-main {
    height: auto;
    min-height: calc(100vh - 72px);
  }
  
  .h-100 {
    height: auto !important;
    min-height: 400px !important;
  }
  
  .pe-md-3, .ps-md-3 {
    padding-left: 0 !important;
    padding-right: 0 !important;
  }
  
  .v-col:not(:last-child) {
    margin-bottom: 1rem;
  }
}

/* Scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: rgba(var(--v-theme-surface-variant), 0.3);
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-primary), 0.5);
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: rgba(var(--v-theme-primary), 0.7);
}

/* Animation for progress bars */
@keyframes pulse {
  0% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
  100% {
    opacity: 1;
  }
}

.v-progress-linear:not(.v-progress-linear--determinate) :deep(.v-progress-linear__indeterminate) {
  animation: pulse 2s ease-in-out infinite;
}

/* Form validation styling */
.v-text-field--error :deep(.v-field__outline) {
  border-color: rgb(var(--v-theme-error)) !important;
}

.v-textarea--error :deep(.v-field__outline) {
  border-color: rgb(var(--v-theme-error)) !important;
}

/* Focus styles */
.v-text-field:focus-within :deep(.v-field__outline) {
  border-color: rgb(var(--v-theme-primary)) !important;
  border-width: 2px !important;
}

.v-textarea:focus-within :deep(.v-field__outline) {
  border-color: rgb(var(--v-theme-primary)) !important;
  border-width: 2px !important;
}

/* Loading state */
.v-btn--loading {
  pointer-events: none;
}

/* Elevation transitions */
.v-card,
.v-btn,
.v-alert {
  transition: box-shadow 0.3s ease, transform 0.3s ease;
}

/* Card content max height for better scrolling */
.v-card-text.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: rgba(var(--v-theme-primary), 0.5) rgba(var(--v-theme-surface-variant), 0.3);
}
</style>
