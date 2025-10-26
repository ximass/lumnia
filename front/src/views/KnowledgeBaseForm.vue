<template>
  <v-container fluid class="pa-0 fill-height">
    <!-- Header -->
    <v-app-bar 
      elevation="1" 
      color="surface" 
      class="px-3 px-sm-6"
      :height="$vuetify.display.smAndDown ? 64 : 72"
    >
      <v-btn
        icon
        variant="text"
        @click="handleCancel"
        :class="$vuetify.display.smAndDown ? 'me-2' : 'me-4'"
        size="small"
      >
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      <v-app-bar-title :class="$vuetify.display.smAndDown ? 'text-subtitle-1' : 'text-h5'" class="font-weight-bold">
        {{ isEdit ? 'Editar base de conhecimento' : 'Nova base de conhecimento' }}
      </v-app-bar-title>
      <v-spacer />
      <v-btn 
        v-if="!$vuetify.display.xs"
        variant="outlined" 
        @click="handleCancel"
        :disabled="isProcessing"
        class="me-3"
        :size="$vuetify.display.smAndDown ? 'small' : 'default'"
      >
        Cancelar
      </v-btn>
      <v-btn 
        color="primary" 
        @click="save" 
        :disabled="!isValid || isProcessing || (!isEdit && selectedFiles.length === 0)"
        :loading="isProcessing"
        variant="flat"
        :size="$vuetify.display.smAndDown ? 'small' : 'default'"
      >
        {{ $vuetify.display.xs ? (isEdit ? 'Atualizar' : 'Criar') : (isEdit ? 'Atualizar' : 'Criar base de conhecimento') }}
      </v-btn>
    </v-app-bar>

    <!-- Main Content -->
    <v-main class="pa-0">
      <v-container fluid class="pa-3 pa-sm-6 h-100">
        <v-row class="h-100" no-gutters>
          <!-- Left Column - Form -->
          <v-col cols="12" md="5" class="pe-md-3 mb-3 mb-md-0">
            <v-card elevation="2" class="h-100">
              <v-card-title :class="$vuetify.display.smAndDown ? 'text-subtitle-1 pa-4 pb-3' : 'text-h6 pa-6 pb-4'" class="d-flex align-center bg-primary-lighten-5">
                <v-icon color="primary" :class="$vuetify.display.smAndDown ? 'me-2' : 'me-3'" :size="$vuetify.display.smAndDown ? 'default' : 'large'">mdi-information</v-icon>
                Informações básicas
              </v-card-title>
              
              <v-card-text :class="$vuetify.display.smAndDown ? 'pa-4' : 'pa-6'">
                <v-form ref="form" v-model="isValid">
                  <v-text-field 
                    label="Nome" 
                    v-model="formData.name" 
                    :rules="nameRules" 
                    required 
                    variant="outlined"
                    class="mb-4"
                    :density="$vuetify.display.smAndDown ? 'compact' : 'comfortable'"
                  />
                  
                  <v-textarea 
                    label="Descrição" 
                    v-model="formData.description" 
                    :rows="$vuetify.display.smAndDown ? 3 : 4" 
                    variant="outlined"
                    :density="$vuetify.display.smAndDown ? 'compact' : 'comfortable'"
                    auto-grow
                  />
                </v-form>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Right Column - Files -->
          <v-col cols="12" md="7" class="ps-md-3">
            <v-card elevation="2" class="h-100">
              <v-card-title :class="$vuetify.display.smAndDown ? 'text-subtitle-1 pa-4 pb-3' : 'text-h6 pa-6 pb-4'" class="d-flex align-center bg-secondary-lighten-5">
                <v-icon color="secondary" :class="$vuetify.display.smAndDown ? 'me-2' : 'me-3'" :size="$vuetify.display.smAndDown ? 'default' : 'large'">mdi-file-multiple</v-icon>
                <span class="text-truncate">Arquivos da base de conhecimento</span>
                <v-spacer />
                <v-chip 
                  v-if="selectedFiles.length > 0" 
                  color="secondary" 
                  variant="flat" 
                  size="small"
                >
                  {{ selectedFiles.length }}
                </v-chip>
              </v-card-title>
              
              <v-card-text :class="$vuetify.display.smAndDown ? 'pa-4' : 'pa-6'" class="overflow-y-auto" :style="{ maxHeight: $vuetify.display.smAndDown ? 'calc(100vh - 300px)' : 'calc(100vh - 200px)' }">
                <!-- Upload Section -->
                <div class="mb-6">
                  <v-file-input
                    v-model="selectedFiles"
                    label="Selecionar arquivos"
                    multiple
                    accept=".txt,.pdf,.csv,.xlsx,.doc,.docx,.odt"
                    variant="outlined"
                    prepend-icon="mdi-file-document"
                    :rules="fileRules"
                    class="mb-4"
                    @change="handleFileSelection"
                    :density="$vuetify.display.smAndDown ? 'compact' : 'comfortable'"
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
                    :density="$vuetify.display.smAndDown ? 'compact' : 'default'"
                  >
                    <template #text>
                      <div class="d-flex align-center">
                        <div>
                          <strong>Selecione pelo menos um arquivo</strong> para criar a base de conhecimento.
                          <br v-if="!$vuetify.display.xs">
                          <small class="text-medium-emphasis" :class="$vuetify.display.xs ? 'd-block mt-1' : ''">Tamanho máximo: 10MB por arquivo • Formatos aceitos: TXT, PDF, CSV, XLSX, DOC, DOCX, ODT, JSON, JSONL</small>
                        </div>
                      </div>
                    </template>
                  </v-alert>
                </div>

                <!-- Progress Section -->
                <div v-if="uploadProgress.length > 0" class="mb-6">
                  <h4 :class="$vuetify.display.smAndDown ? 'text-subtitle-1 mb-2' : 'text-h6 mb-3'" class="d-flex align-center">
                    <v-icon color="warning" class="me-2">mdi-progress-upload</v-icon>
                    Progresso do upload
                  </h4>
                  <div
                    v-for="progress in uploadProgress"
                    :key="progress.fileName"
                    :class="$vuetify.display.smAndDown ? 'mb-3' : 'mb-4'"
                  >
                    <div class="d-flex justify-space-between align-center mb-2">
                      <span :class="$vuetify.display.smAndDown ? 'text-body-2' : 'text-body-1'" class="font-weight-medium text-truncate me-2">{{ progress.fileName }}</span>
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
                      :height="$vuetify.display.smAndDown ? 6 : 8"
                      rounded
                    />
                  </div>
                </div>

                <!-- Existing Files Section -->
                <div v-if="isEdit && sources.length > 0" class="mb-6">
                  <h4 :class="$vuetify.display.smAndDown ? 'text-subtitle-1 mb-2' : 'text-h6 mb-3'" class="d-flex align-center">
                    <v-icon color="success" class="me-2">mdi-check-circle</v-icon>
                    Arquivos já carregados
                  </h4>
                  <v-list class="border rounded" :density="$vuetify.display.smAndDown ? 'compact' : 'default'">
                    <v-list-item
                      v-for="(source, index) in sources"
                      :key="source.id"
                      :class="{ 'border-b': index < sources.length - 1 }"
                    >
                      <template #prepend>
                        <v-icon color="primary" :size="$vuetify.display.smAndDown ? 'small' : 'default'">
                          {{ getFileIcon(source.source_type) }}
                        </v-icon>
                      </template>

                      <v-list-item-title :class="$vuetify.display.smAndDown ? 'text-body-2' : ''" class="font-weight-medium">
                        {{ source.metadata?.original_filename || source.source_identifier }}
                      </v-list-item-title>
                      
                      <v-list-item-subtitle :class="$vuetify.display.smAndDown ? 'text-caption' : ''">
                        Status: {{ getStatusText(source.status) }}
                        <span v-if="source.metadata?.file_size && !$vuetify.display.xs" class="ml-2">
                          • {{ formatFileSize(source.metadata.file_size) }}
                        </span>
                      </v-list-item-subtitle>

                      <template #append>
                        <div :class="$vuetify.display.smAndDown ? 'd-flex flex-column align-end ga-1' : 'd-flex align-center ga-2'">
                          <v-chip
                            :color="getStatusColor(source.status)"
                            size="small"
                            variant="flat"
                          >
                            {{ getStatusText(source.status) }}
                          </v-chip>
                          <v-btn
                            icon
                            size="small"
                            variant="text"
                            color="primary"
                            @click="previewSource(source)"
                            :disabled="!canPreview(source)"
                          >
                            <v-icon size="20">mdi-eye</v-icon>
                            <v-tooltip activator="parent" location="top">
                              {{ canPreview(source) ? 'Visualizar arquivo' : 'Preview não disponível para este tipo de arquivo' }}
                            </v-tooltip>
                          </v-btn>
                          <v-btn
                            icon
                            size="small"
                            variant="text"
                            color="secondary"
                            @click="downloadSource(source)"
                            :disabled="isProcessing"
                          >
                            <v-icon size="20">mdi-download</v-icon>
                            <v-tooltip activator="parent" location="top">
                              Baixar arquivo
                            </v-tooltip>
                          </v-btn>
                          <v-btn
                            icon
                            size="small"
                            variant="text"
                            color="error"
                            @click="deleteSource(source)"
                            :disabled="isProcessing"
                          >
                            <v-icon size="20">mdi-delete</v-icon>
                            <v-tooltip activator="parent" location="top">
                              Excluir arquivo
                            </v-tooltip>
                          </v-btn>
                        </div>
                      </template>
                    </v-list-item>
                  </v-list>
                </div>

                <!-- Empty State -->
                <div v-if="!isEdit && selectedFiles.length === 0 && uploadProgress.length === 0" class="text-center py-8">
                  <div class="empty-state">
                    <v-icon :size="$vuetify.display.smAndDown ? 60 : 80" color="grey-lighten-2" :class="$vuetify.display.smAndDown ? 'mb-3' : 'mb-4'">
                      mdi-cloud-upload
                    </v-icon>
                    <h3 :class="$vuetify.display.smAndDown ? 'text-subtitle-1' : 'text-h6'" class="text-grey-darken-1 mb-2">Nenhum arquivo selecionado</h3>
                    <p :class="$vuetify.display.smAndDown ? 'text-caption' : 'text-body-2'" class="text-grey mb-4">
                      {{ $vuetify.display.smAndDown ? 'Use o botão acima para selecionar' : 'Arraste arquivos aqui ou use o botão acima para selecionar' }}
                    </p>
                  </div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Preview Dialog -->
    <v-dialog v-model="previewDialog.show" :max-width="$vuetify.display.mdAndUp ? '900' : '95vw'" :fullscreen="$vuetify.display.smAndDown" scrollable>
      <v-card>
        <v-card-title :class="$vuetify.display.smAndDown ? 'text-subtitle-1 pa-4' : 'text-h6 pa-6'" class="d-flex align-center border-b">
          <v-icon :class="$vuetify.display.smAndDown ? 'me-2' : 'me-3'" color="primary">
            {{ previewDialog.source ? getFileIcon(previewDialog.source.source_type) : 'mdi-file' }}
          </v-icon>
          <span class="text-truncate flex-grow-1">{{ previewDialog.originalFilename }}</span>
          <v-btn
            icon
            variant="text"
            size="small"
            @click="closePreview"
            :class="$vuetify.display.smAndDown ? 'ms-2' : 'ms-3'"
          >
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        
        <v-card-text :class="$vuetify.display.smAndDown ? 'pa-4' : 'pa-6'" style="min-height: 400px; max-height: 70vh;">
          <div v-if="previewDialog.loading" class="d-flex flex-column align-center justify-center" style="min-height: 400px;">
            <v-progress-circular indeterminate color="primary" :size="$vuetify.display.smAndDown ? 50 : 70" />
            <p :class="$vuetify.display.smAndDown ? 'text-body-2 mt-3' : 'text-body-1 mt-4'" class="text-grey">Carregando preview...</p>
          </div>

          <v-alert v-else-if="previewDialog.error" type="error" variant="tonal" class="mb-0">
            {{ previewDialog.error }}
          </v-alert>

          <div v-else-if="previewDialog.previewType === 'text' && previewDialog.content" class="preview-content">
            <div class="d-flex justify-space-between align-items-center mb-4">
              <div>
                <div :class="$vuetify.display.smAndDown ? 'text-caption' : 'text-body-2'" class="text-grey">
                  <strong>Tamanho:</strong> {{ formatFileSize(previewDialog.fileSize) }}
                </div>
                <div :class="$vuetify.display.smAndDown ? 'text-caption' : 'text-body-2'" class="text-grey">
                  <strong>Tipo:</strong> {{ previewDialog.source?.source_type.toUpperCase() }}
                </div>
              </div>
            </div>

            <v-divider class="mb-4" />

            <div class="preview-text-container">
              <pre class="preview-text"><code>{{ previewDialog.content }}</code></pre>
            </div>
          </div>

          <div v-else-if="previewDialog.previewType === 'binary' && previewDialog.downloadUrl" class="preview-content">
            <div class="d-flex justify-space-between align-items-center mb-4">
              <div>
                <div :class="$vuetify.display.smAndDown ? 'text-caption' : 'text-body-2'" class="text-grey">
                  <strong>Tamanho:</strong> {{ formatFileSize(previewDialog.fileSize) }}
                </div>
                <div :class="$vuetify.display.smAndDown ? 'text-caption' : 'text-body-2'" class="text-grey">
                  <strong>Tipo:</strong> {{ previewDialog.source?.source_type.toUpperCase() }}
                </div>
              </div>
            </div>

            <v-divider class="mb-4" />

            <div class="preview-document-container">
              <iframe 
                v-if="previewDialog.source?.source_type.toLowerCase() === 'pdf'"
                :src="previewDialog.downloadUrl" 
                class="preview-iframe"
                frameborder="0"
              ></iframe>
              <div v-else class="preview-document-message">
                <v-icon size="80" color="grey-lighten-1" class="mb-4">
                  {{ getFileIcon(previewDialog.source?.source_type || '') }}
                </v-icon>
                <h3 class="text-h6 mb-4">Preview não disponível</h3>
                <p class="text-body-2 text-grey mb-6">
                  Arquivos {{ previewDialog.source?.source_type.toUpperCase() }} não podem ser visualizados diretamente no navegador.
                  Faça o download do arquivo para visualizá-lo.
                </p>
                <v-btn 
                  color="primary" 
                  variant="flat"
                  :href="previewDialog.downloadUrl"
                  download
                  prepend-icon="mdi-download"
                >
                  Baixar arquivo
                </v-btn>
              </div>
            </div>
          </div>
        </v-card-text>

        <v-card-actions :class="$vuetify.display.smAndDown ? 'px-4 pb-4' : 'px-6 pb-6'" class="border-t">
          <v-btn
            v-if="previewDialog.downloadUrl"
            variant="text"
            color="primary"
            :href="previewDialog.downloadUrl"
            download
            prepend-icon="mdi-download"
            :size="$vuetify.display.smAndDown ? 'small' : 'default'"
          >
            Baixar
          </v-btn>
          <v-spacer />
          <v-btn
            variant="outlined"
            @click="closePreview"
            :size="$vuetify.display.smAndDown ? 'small' : 'default'"
          >
            Fechar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog.show" :max-width="$vuetify.display.smAndDown ? '90vw' : '500'" :fullscreen="$vuetify.display.xs">
      <v-card>
        <v-card-title :class="$vuetify.display.smAndDown ? 'text-subtitle-1 pa-4' : 'text-h6 pa-6'" class="d-flex align-center">
          <v-icon color="info" class="me-3">mdi-alert-circle</v-icon>
          Confirmar exclusão
        </v-card-title>
        
        <v-card-text :class="$vuetify.display.smAndDown ? 'pa-4' : 'pa-6'">
          <p class="mb-3" :class="$vuetify.display.smAndDown ? 'text-body-2' : ''">
            Tem certeza que deseja excluir o arquivo 
            <strong>"{{ deleteDialog.source?.metadata?.original_filename || deleteDialog.source?.source_identifier }}"</strong>?
          </p>
          <v-alert  class="mb-0" :density="$vuetify.display.smAndDown ? 'compact' : 'default'">
            <strong>Atenção:</strong> Esta ação não pode ser desfeita. O arquivo e todos os seus dados associados serão permanentemente removidos.
          </v-alert>
        </v-card-text>

        <v-card-actions :class="$vuetify.display.smAndDown ? 'px-4 pb-4' : 'px-6 pb-6'">
          <v-spacer />
          <v-btn 
            variant="flat" 
            color="primary"
            @click="deleteDialog.show = false"
            :disabled="deleteDialog.loading"
            :size="$vuetify.display.smAndDown ? 'small' : 'default'"
          >
            Cancelar
          </v-btn>
          <v-btn 
            variant="outlined"
            @click="confirmDeleteSource"
            :loading="deleteDialog.loading"
            :size="$vuetify.display.smAndDown ? 'small' : 'default'"
          >
            Excluir arquivo
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, watch, onMounted, onBeforeUnmount, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/auth'
import { useFileUpload } from '@/composables/useFileUpload'
import type { KnowledgeBase, KnowledgeBaseFormData, Source, ApiResponse, SourcePreview } from '@/types/types'

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
    const statusUpdateInterval = ref<number | null>(null)

    const deleteDialog = ref({
      show: false,
      source: null as Source | null,
      loading: false
    })

    const previewDialog = ref({
      show: false,
      source: null as Source | null,
      content: '',
      fileSize: 0,
      originalFilename: '',
      loading: false,
      error: '',
      previewType: 'text' as 'text' | 'binary',
      downloadUrl: ''
    })

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
          [
            'text/plain', 
            'application/pdf', 
            'text/csv', 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.oasis.opendocument.text',
            'application/json',
            'application/jsonl',
            'application/x-jsonlines'
          ].includes(file.type) ||
          file.name.toLowerCase().endsWith('.txt') ||
          file.name.toLowerCase().endsWith('.pdf') ||
          file.name.toLowerCase().endsWith('.csv') ||
          file.name.toLowerCase().endsWith('.xlsx') ||
          file.name.toLowerCase().endsWith('.doc') ||
          file.name.toLowerCase().endsWith('.docx') ||
          file.name.toLowerCase().endsWith('.odt') ||
          file.name.toLowerCase().endsWith('.json') ||
          file.name.toLowerCase().endsWith('.jsonl')
        ) || 'Apenas arquivos .txt, .pdf, .csv, .xlsx, .doc, .docx, .odt, .json e .jsonl são permitidos'
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

    const getFileIcon = (sourceType: string): string => {
      const iconMap: Record<string, string> = {
        'pdf': 'mdi-file-pdf-box',
        'txt': 'mdi-file-document',
        'text': 'mdi-file-document',
        'csv': 'mdi-file-delimited',
        'xlsx': 'mdi-file-excel',
        'doc': 'mdi-file-word',
        'docx': 'mdi-file-word',
        'odt': 'mdi-file-document-outline',
        'json': 'mdi-code-json',
        'jsonl': 'mdi-code-json'
      }
      return iconMap[sourceType] || 'mdi-file-document'
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

    const updateSourcesStatus = async () => {
      if (!isEdit.value || !route.params.id) return

      const hasProcessing = sources.value.some(source => 
        ['uploaded', 'processing', 'chunked', 'embedding'].includes(source.status)
      )

      if (!hasProcessing) return

      try {
        const response = await axios.get<ApiResponse<KnowledgeBase>>(`/api/knowledge-bases/${route.params.id}`)
        const kb = response.data.data

        if (kb && kb.sources) {
          sources.value = kb.sources
        }
      } catch (error: any) {
        console.error('Erro ao atualizar status dos arquivos:', error)
      }
    }

    const startStatusPolling = () => {
      if (statusUpdateInterval.value) {
        clearInterval(statusUpdateInterval.value)
      }

      statusUpdateInterval.value = window.setInterval(() => {
        updateSourcesStatus()
      }, 5000)
    }

    const stopStatusPolling = () => {
      if (statusUpdateInterval.value) {
        clearInterval(statusUpdateInterval.value)
        statusUpdateInterval.value = null
      }
    }

    const save = async () => {
      const validation = await form.value?.validate()
      if (!validation.valid) return

      isProcessing.value = true

      try {
        let knowledgeBaseId: string

        if (isEdit.value) {
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

    const deleteSource = (source: Source) => {
      deleteDialog.value.source = source
      deleteDialog.value.show = true
    }

    const confirmDeleteSource = async () => {
      if (!deleteDialog.value.source) return

      deleteDialog.value.loading = true

      try {
        await axios.delete(`/api/sources/${deleteDialog.value.source.id}`)
        
        sources.value = sources.value.filter(s => s.id !== deleteDialog.value.source?.id)
        
        showToast('Arquivo excluído com sucesso!', 'success')
        deleteDialog.value.show = false
        deleteDialog.value.source = null
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao excluir o arquivo'
        showToast(errorMsg, 'error')
      } finally {
        deleteDialog.value.loading = false
      }
    }

    const handleCancel = () => {
      router.push('/knowledge-bases')
    }

    const downloadSource = async (source: Source) => {
      try {
        const response = await axios.get<ApiResponse<{ download_url: string }>>(`/api/sources/${source.id}/download-url`)
        
        if (response.data.status === 'success' && response.data.data?.download_url) {
          const downloadUrl = response.data.data.download_url
          const filename = source.metadata?.original_filename || `file.${source.source_type}`
          
          const link = document.createElement('a')
          link.href = downloadUrl
          link.download = filename
          link.setAttribute('target', '_blank')
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
          
          showToast('Download iniciado com sucesso!', 'success')
        }
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao baixar o arquivo'
        showToast(errorMsg, 'error')
      }
    }

    const canPreview = (source: Source): boolean => {
      const previewableTypes = ['txt', 'json', 'jsonl', 'csv', 'text', 'pdf', 'doc', 'docx', 'odt']
      return previewableTypes.includes(source.source_type.toLowerCase())
    }

    const previewSource = async (source: Source) => {
      if (!canPreview(source)) {
        showToast('Preview não disponível para este tipo de arquivo', 'warning')
        return
      }

      previewDialog.value.show = true
      previewDialog.value.source = source
      previewDialog.value.originalFilename = source.metadata?.original_filename || source.source_identifier
      previewDialog.value.loading = true
      previewDialog.value.error = ''
      previewDialog.value.content = ''
      previewDialog.value.downloadUrl = ''

      try {
        const response = await axios.get<ApiResponse<SourcePreview>>(`/api/sources/${source.id}/preview`)
        
        if (response.data.status === 'success' && response.data.data) {
          const data = response.data.data
          previewDialog.value.fileSize = data.file_size
          previewDialog.value.originalFilename = data.original_filename
          previewDialog.value.previewType = data.preview_type
          
          if (data.preview_type === 'text') {
            previewDialog.value.content = data.content || ''
          } else if (data.preview_type === 'binary') {
            previewDialog.value.downloadUrl = data.download_url || ''
          }
        }
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao carregar preview do arquivo'
        previewDialog.value.error = errorMsg
        showToast(errorMsg, 'error')
      } finally {
        previewDialog.value.loading = false
      }
    }

    const closePreview = () => {
      previewDialog.value.show = false
      previewDialog.value.source = null
      previewDialog.value.content = ''
      previewDialog.value.fileSize = 0
      previewDialog.value.originalFilename = ''
      previewDialog.value.loading = false
      previewDialog.value.error = ''
      previewDialog.value.previewType = 'text'
      previewDialog.value.downloadUrl = ''
    }

    onMounted(() => {
      if (isEdit.value) {
        loadKnowledgeBase()
        startStatusPolling()
      }
    })

    onBeforeUnmount(() => {
      stopStatusPolling()
    })

    return {
      form,
      isValid,
      isProcessing,
      selectedFiles,
      sources,
      uploadProgress,
      deleteDialog,
      previewDialog,
      isEdit,
      formData,
      nameRules,
      fileRules,
      getStatusText,
      getStatusColor,
      formatFileSize,
      getFileIcon,
      handleFileSelection,
      save,
      deleteSource,
      confirmDeleteSource,
      handleCancel,
      downloadSource,
      canPreview,
      previewSource,
      closePreview
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

/* Mobile card adjustments */
@media (max-width: 600px) {
  .v-card {
    border-radius: 8px;
  }
  
  .v-card-title {
    padding: 12px 16px !important;
  }
  
  .v-card-text {
    padding: 12px 16px !important;
  }
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

/* Mobile file input */
@media (max-width: 600px) {
  .v-file-input :deep(.v-field__input) {
    min-height: 48px;
    padding: 8px 12px;
    font-size: 0.875rem;
  }
  
  .v-file-input :deep(.v-field__prepend-inner) {
    padding-inline-end: 8px;
  }
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

/* Mobile app bar */
@media (max-width: 600px) {
  .v-app-bar {
    padding-left: 8px !important;
    padding-right: 8px !important;
  }
  
  .v-app-bar .v-btn {
    min-width: auto;
  }
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
    min-height: calc(100vh - 64px);
  }
  
  .h-100 {
    height: auto !important;
    min-height: 300px !important;
  }
  
  .pe-md-3, .ps-md-3 {
    padding-left: 0 !important;
    padding-right: 0 !important;
  }
}

@media (max-width: 600px) {
  /* Mobile specific adjustments */
  .v-app-bar-title {
    font-size: 0.9rem !important;
    line-height: 1.2;
  }
  
  .v-card-title {
    font-size: 1rem !important;
    flex-wrap: nowrap;
  }
  
  .v-card-title .text-truncate {
    max-width: calc(100vw - 180px);
  }
  
  /* Reduce spacing on mobile */
  .mb-6 {
    margin-bottom: 1rem !important;
  }
  
  .mb-4 {
    margin-bottom: 0.75rem !important;
  }
  
  .mb-3 {
    margin-bottom: 0.5rem !important;
  }
  
  /* Adjust empty state padding */
  .empty-state {
    padding: 1.5rem 1rem;
  }
  
  /* File chips in file input */
  .file-chips-container {
    max-width: 100%;
  }
  
  .file-chips-container .v-chip {
    max-width: 100%;
    font-size: 0.75rem;
  }
  
  /* List items on mobile */
  .v-list-item {
    padding: 12px 8px;
  }
  
  .v-list-item-title {
    font-size: 0.875rem !important;
  }
  
  .v-list-item-subtitle {
    font-size: 0.75rem !important;
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

/* Mobile scrollbar */
@media (max-width: 600px) {
  .overflow-y-auto::-webkit-scrollbar {
    width: 4px;
  }
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

/* Preview content styling */
.preview-content {
  width: 100%;
}

.preview-text-container {
  background-color: rgb(var(--v-theme-surface-variant));
  border-radius: 8px;
  padding: 16px;
  overflow-x: auto;
  max-height: calc(70vh - 200px);
}

.preview-text {
  margin: 0;
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
  font-size: 13px;
  line-height: 1.6;
  white-space: pre-wrap;
  word-wrap: break-word;
  color: rgb(var(--v-theme-on-surface));
}

.preview-text code {
  font-family: inherit;
  background: transparent;
  padding: 0;
}

/* Mobile preview adjustments */
@media (max-width: 600px) {
  .preview-text-container {
    padding: 12px;
    max-height: calc(70vh - 180px);
  }
  
  .preview-text {
    font-size: 12px;
    line-height: 1.5;
  }
}

/* Preview scrollbar */
.preview-text-container::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.preview-text-container::-webkit-scrollbar-track {
  background: rgba(var(--v-theme-surface), 0.3);
  border-radius: 4px;
}

.preview-text-container::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-primary), 0.5);
  border-radius: 4px;
}

.preview-text-container::-webkit-scrollbar-thumb:hover {
  background: rgba(var(--v-theme-primary), 0.7);
}

/* Preview document styling */
.preview-document-container {
  width: 100%;
  height: 100%;
  min-height: 500px;
  background-color: rgb(var(--v-theme-surface-variant));
  border-radius: 8px;
  overflow: hidden;
}

.preview-iframe {
  width: 100%;
  height: 600px;
  border: none;
  border-radius: 8px;
}

.preview-document-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  text-align: center;
  min-height: 500px;
}

/* Mobile preview document adjustments */
@media (max-width: 600px) {
  .preview-document-container {
    min-height: 400px;
  }
  
  .preview-iframe {
    height: 400px;
  }
  
  .preview-document-message {
    padding: 2rem 1rem;
    min-height: 400px;
  }
}

</style>
