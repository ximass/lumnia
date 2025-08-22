<template>
  <v-container fluid class="pa-6">
    <!-- Header -->
    <v-row class="mb-6">
      <v-col cols="12">
        <div class="d-flex align-center justify-space-between">
          <div>
            <h1 class="text-h4 font-weight-bold mb-2">Bases de conhecimento</h1>
            <p class="text-body-1 text-medium-emphasis">
              Gerencie suas bases de conhecimento e faça upload de documentos
            </p>
          </div>
          <v-btn 
            color="primary" 
            size="large"
            @click="createKnowledgeBase"
            prepend-icon="mdi-plus"
          >
            Nova base de conhecimento
          </v-btn>
        </div>
      </v-col>
    </v-row>

    <!-- Statistics Cards -->
    <v-row class="mb-6">
      <v-col cols="12" sm="6" md="3">
        <v-card class="text-center pa-4" color="primary-lighten-5">
          <v-icon size="40" color="primary" class="mb-2">mdi-database</v-icon>
          <div class="text-h5 font-weight-bold">{{ knowledgeBases.length }}</div>
          <div class="text-body-2 text-medium-emphasis">Total de bases</div>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <v-card class="text-center pa-4" color="success-lighten-5">
          <v-icon size="40" color="success" class="mb-2">mdi-file-multiple</v-icon>
          <div class="text-h5 font-weight-bold">{{ totalSources }}</div>
          <div class="text-body-2 text-medium-emphasis">Total de arquivos</div>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <v-card class="text-center pa-4" color="warning-lighten-5">
          <v-icon size="40" color="warning" class="mb-2">mdi-cog</v-icon>
          <div class="text-h5 font-weight-bold">{{ processingCount }}</div>
          <div class="text-body-2 text-medium-emphasis">Processando</div>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="3">
        <v-card class="text-center pa-4" color="info-lighten-5">
          <v-icon size="40" color="info" class="mb-2">mdi-check-circle</v-icon>
          <div class="text-h5 font-weight-bold">{{ completedCount }}</div>
          <div class="text-body-2 text-medium-emphasis">Concluídas</div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Data Table -->
    <v-card elevation="2">
      <v-card-title class="pa-6 pb-4">
        <div class="d-flex align-center justify-space-between w-100">
          <div class="d-flex align-center">
            <v-icon color="primary" class="me-3">mdi-table</v-icon>
            <span class="text-h6">Lista de bases de conhecimento</span>
          </div>
          <div class="d-flex align-center gap-3">
            <v-text-field
              v-model="search"
              placeholder="Buscar bases..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              hide-details
              clearable
              style="max-width: 300px;"
            />
            <v-btn
              icon
              variant="text"
              @click="fetchKnowledgeBases"
              :loading="loading"
            >
              <v-icon>mdi-refresh</v-icon>
            </v-btn>
          </div>
        </div>
      </v-card-title>

      <v-divider />

      <v-data-table 
        :items="filteredKnowledgeBases" 
        :headers="headers" 
        :loading="loading"
        :items-per-page="10"
        class="elevation-0"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center">
            <v-icon color="primary" class="me-3">mdi-database</v-icon>
            <div>
              <div class="font-weight-medium">{{ item.name }}</div>
              <div class="text-caption text-medium-emphasis">
                ID: {{ item.id }}
              </div>
            </div>
          </div>
        </template>

        <template #item.description="{ item }">
          <div v-if="item.description" class="text-body-2">
            {{ item.description.length > 100 ? item.description.substring(0, 100) + '...' : item.description }}
          </div>
          <div v-else class="text-caption text-medium-emphasis">
            Sem descrição
          </div>
        </template>

        <template #item.sources="{ item }">
          <div class="d-flex align-center gap-2">
            <v-chip 
              v-if="item.sources && item.sources.length > 0"
              color="primary" 
              variant="tonal" 
              size="small"
            >
              <v-icon start>mdi-file-multiple</v-icon>
              {{ item.sources.length }} arquivo{{ item.sources.length !== 1 ? 's' : '' }}
            </v-chip>
            <v-chip 
              v-else
              color="grey" 
              variant="tonal" 
              size="small"
            >
              <v-icon start>mdi-file-off</v-icon>
              Nenhum arquivo
            </v-chip>
          </div>
        </template>

        <template #item.status="{ item }">
          <v-chip
            :color="getOverallStatusColor(item)"
            variant="flat"
            size="small"
          >
            <v-icon start>{{ getOverallStatusIcon(item) }}</v-icon>
            {{ getOverallStatusText(item) }}
          </v-chip>
        </template>

        <template #item.created_at="{ item }">
          <div class="text-body-2">
            {{ formatDate(item.created_at) }}
          </div>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center gap-1">
            <v-btn
              icon
              variant="text"
              size="small"
              @click="viewDetails(item)"
              color="info"
            >
              <v-icon>mdi-eye</v-icon>
              <v-tooltip activator="parent">Ver detalhes</v-tooltip>
            </v-btn>
            <v-btn
              icon
              variant="text"
              size="small"
              @click="editKnowledgeBase(item)"
              color="primary"
            >
              <v-icon>mdi-pencil</v-icon>
              <v-tooltip activator="parent">Editar</v-tooltip>
            </v-btn>
            <v-btn
              icon
              variant="text"
              size="small"
              @click="confirmDelete(item)"
              color="error"
            >
              <v-icon>mdi-delete</v-icon>
              <v-tooltip activator="parent">Excluir</v-tooltip>
            </v-btn>
          </div>
        </template>

        <template #no-data>
          <div class="text-center py-8">
            <v-icon size="64" color="grey-lighten-1" class="mb-4">
              mdi-database-off
            </v-icon>
            <h3 class="text-h6 text-grey-darken-1 mb-2">Nenhuma base de conhecimento encontrada</h3>
            <p class="text-body-2 text-grey mb-4">
              Crie sua primeira base de conhecimento para começar
            </p>
            <v-btn 
              color="primary" 
              @click="createKnowledgeBase"
              prepend-icon="mdi-plus"
            >
              Criar primeira base
            </v-btn>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500">
      <v-card>
        <v-card-title class="text-h5 d-flex align-center">
          <v-icon color="error" class="me-3">mdi-alert-circle</v-icon>
          Confirmar exclusão
        </v-card-title>
        <v-card-text>
          <p>Tem certeza que deseja excluir a base de conhecimento <strong>"{{ selectedKnowledgeBase?.name }}"</strong>?</p>
          <p class="text-caption text-error mt-2">
            <v-icon size="small" class="me-1">mdi-warning</v-icon>
            Esta ação não pode ser desfeita. Todos os arquivos e dados associados serão removidos permanentemente.
          </p>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn 
            variant="text" 
            @click="deleteDialog = false"
          >
            Cancelar
          </v-btn>
          <v-btn 
            color="error" 
            @click="deleteKnowledgeBase"
            :loading="deleting"
          >
            Excluir
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from '@/composables/useToast'
import type { KnowledgeBase, ApiResponse } from '@/types/types'

export default defineComponent({
  name: 'KnowledgeBaseView',
  setup() {
    const router = useRouter()
    const { showToast } = useToast()

    const knowledgeBases = ref<KnowledgeBase[]>([])
    const selectedKnowledgeBase = ref<KnowledgeBase | null>(null)
    const loading = ref(false)
    const deleting = ref(false)
    const deleteDialog = ref(false)
    const search = ref('')

    const headers = [
      { title: 'Nome', value: 'name', sortable: true },
      { title: 'Descrição', value: 'description', sortable: false },
      { title: 'Arquivos', value: 'sources', sortable: false },
      { title: 'Status', value: 'status', sortable: false },
      { title: 'Criada em', value: 'created_at', sortable: true },
      { title: 'Ações', value: 'actions', sortable: false },
    ]

    // Computed properties
    const filteredKnowledgeBases = computed(() => {
      if (!search.value) return knowledgeBases.value
      return knowledgeBases.value.filter(kb => 
        kb.name.toLowerCase().includes(search.value.toLowerCase()) ||
        (kb.description && kb.description.toLowerCase().includes(search.value.toLowerCase()))
      )
    })

    const totalSources = computed(() => {
      return knowledgeBases.value.reduce((total, kb) => 
        total + (kb.sources?.length || 0), 0
      )
    })

    const processingCount = computed(() => {
      return knowledgeBases.value.filter(kb => {
        if (!kb.sources || kb.sources.length === 0) return false
        return kb.sources.some(source => 
          ['uploaded', 'processing', 'embedding'].includes(source.status)
        )
      }).length
    })

    const completedCount = computed(() => {
      return knowledgeBases.value.filter(kb => {
        if (!kb.sources || kb.sources.length === 0) return false
        return kb.sources.every(source => source.status === 'processed')
      }).length
    })

    // Methods
    const fetchKnowledgeBases = async () => {
      loading.value = true
      try {
        const response = await axios.get<ApiResponse<KnowledgeBase[]>>('/api/knowledge-bases')
        knowledgeBases.value = response.data.data || []
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao buscar bases de conhecimento'
        showToast(errorMsg)
      } finally {
        loading.value = false
      }
    }

    const createKnowledgeBase = () => {
      router.push('/knowledge-bases/create')
    }

    const editKnowledgeBase = (knowledgeBase: KnowledgeBase) => {
      router.push(`/knowledge-bases/${knowledgeBase.id}/edit`)
    }

    const viewDetails = (knowledgeBase: KnowledgeBase) => {
      // For now, redirect to edit view as details view
      router.push(`/knowledge-bases/${knowledgeBase.id}/edit`)
    }

    const confirmDelete = (knowledgeBase: KnowledgeBase) => {
      selectedKnowledgeBase.value = knowledgeBase
      deleteDialog.value = true
    }

    const deleteKnowledgeBase = async () => {
      if (!selectedKnowledgeBase.value) return

      deleting.value = true
      try {
        await axios.delete(`/api/knowledge-bases/${selectedKnowledgeBase.value.id}`)
        showToast('Base de conhecimento excluída com sucesso!', 'success')
        deleteDialog.value = false
        selectedKnowledgeBase.value = null
        fetchKnowledgeBases()
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao excluir base de conhecimento'
        showToast(errorMsg)
      } finally {
        deleting.value = false
      }
    }

    const getOverallStatusText = (knowledgeBase: KnowledgeBase): string => {
      if (!knowledgeBase.sources || knowledgeBase.sources.length === 0) {
        return 'Sem arquivos'
      }

      const statuses = knowledgeBase.sources.map(s => s.status)

      console.log(statuses);
      
      if (statuses.every(s => s === 'processed')) {
        return 'Concluída'
      } else if (statuses.some(s => ['failed', 'embedding_failed', 'upsert_failed'].includes(s))) {
        return 'Com erros'
      } else if (statuses.some(s => ['uploaded', 'processing', 'embedding'].includes(s))) {
        return 'Processando'
      } else {
        return 'Pendente'
      }
    }

    const getOverallStatusColor = (knowledgeBase: KnowledgeBase): string => {
      if (!knowledgeBase.sources || knowledgeBase.sources.length === 0) {
        return 'grey'
      }

      const statuses = knowledgeBase.sources.map(s => s.status)
      
      if (statuses.every(s => s === 'processed')) {
        return 'success'
      } else if (statuses.some(s => ['failed', 'embedding_failed', 'upsert_failed'].includes(s))) {
        return 'error'
      } else if (statuses.some(s => ['uploaded', 'processing', 'embedding'].includes(s))) {
        return 'warning'
      } else {
        return 'info'
      }
    }

    const getOverallStatusIcon = (knowledgeBase: KnowledgeBase): string => {
      if (!knowledgeBase.sources || knowledgeBase.sources.length === 0) {
        return 'mdi-file-off'
      }

      const statuses = knowledgeBase.sources.map(s => s.status)
      
      if (statuses.every(s => s === 'processed')) {
        return 'mdi-check-circle'
      } else if (statuses.some(s => ['failed', 'embedding_failed', 'upsert_failed'].includes(s))) {
        return 'mdi-alert-circle'
      } else if (statuses.some(s => ['uploaded', 'processing', 'embedding'].includes(s))) {
        return 'mdi-cog'
      } else {
        return 'mdi-clock'
      }
    }

    const formatDate = (dateString?: string): string => {
      if (!dateString) return 'N/A'
      
      try {
        const date = new Date(dateString)
        return new Intl.DateTimeFormat('pt-BR', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        }).format(date)
      } catch {
        return 'Data inválida'
      }
    }

    onMounted(() => {
      fetchKnowledgeBases()
    })

    return {
      knowledgeBases,
      selectedKnowledgeBase,
      loading,
      deleting,
      deleteDialog,
      search,
      headers,
      filteredKnowledgeBases,
      totalSources,
      processingCount,
      completedCount,
      fetchKnowledgeBases,
      createKnowledgeBase,
      editKnowledgeBase,
      viewDetails,
      confirmDelete,
      deleteKnowledgeBase,
      getOverallStatusText,
      getOverallStatusColor,
      getOverallStatusIcon,
      formatDate
    }
  }
})
</script>

<style scoped>
/* Container styling */
.v-container {
  max-width: none !important;
}

/* Card hover effects */
.v-card {
  transition: all 0.3s ease;
  border-radius: 12px;
}

.v-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

/* Statistics cards special styling */
.v-card.text-center {
  border: 1px solid rgba(var(--v-border-color), 0.12);
}

/* Data table styling */
.v-data-table {
  border-radius: 0 0 12px 12px;
}

.v-data-table :deep(.v-data-table__td) {
  padding: 16px 12px;
}

.v-data-table :deep(.v-data-table__th) {
  padding: 16px 12px;
  font-weight: 600;
  color: rgb(var(--v-theme-primary));
}

/* Search field styling */
.v-text-field--outlined :deep(.v-field__outline) {
  border-radius: 8px;
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
}

/* Icon button styling */
.v-btn--icon {
  border-radius: 50%;
}

/* Tooltip styling */
.v-tooltip :deep(.v-overlay__content) {
  background: rgba(0, 0, 0, 0.8) !important;
  border-radius: 6px;
  font-size: 12px;
  padding: 6px 12px;
}

/* Dialog styling */
.v-dialog :deep(.v-overlay__content) {
  border-radius: 16px;
  box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
}

/* Empty state styling */
.v-data-table :deep(.v-data-table__empty-wrapper) {
  padding: 48px 24px;
}

/* Status chip colors */
.v-chip--color-success {
  background: rgba(76, 175, 80, 0.1) !important;
  color: rgb(76, 175, 80) !important;
}

.v-chip--color-warning {
  background: rgba(255, 152, 0, 0.1) !important;
  color: rgb(255, 152, 0) !important;
}

.v-chip--color-error {
  background: rgba(244, 67, 54, 0.1) !important;
  color: rgb(244, 67, 54) !important;
}

.v-chip--color-info {
  background: rgba(33, 150, 243, 0.1) !important;
  color: rgb(33, 150, 243) !important;
}

.v-chip--color-grey {
  background: rgba(158, 158, 158, 0.1) !important;
  color: rgb(158, 158, 158) !important;
}

/* Loading states */
.v-btn--loading {
  pointer-events: none;
}

.v-data-table--loading :deep(.v-data-table__td) {
  opacity: 0.6;
}

/* Responsive adjustments */
@media (max-width: 960px) {
  .v-container {
    padding: 16px !important;
  }
  
  .text-h4 {
    font-size: 1.75rem !important;
  }
  
  .v-data-table :deep(.v-data-table__td),
  .v-data-table :deep(.v-data-table__th) {
    padding: 8px 6px;
    font-size: 0.875rem;
  }
  
  .d-flex.gap-3 {
    flex-direction: column;
    gap: 12px !important;
  }
}

/* Animation for statistics cards */
@keyframes countUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.text-h5 {
  animation: countUp 0.6s ease-out;
}

/* Hover effects for action buttons */
.v-btn--icon:hover {
  background-color: rgba(var(--v-theme-primary), 0.08);
}

/* Focus states */
.v-text-field:focus-within :deep(.v-field__outline) {
  border-color: rgb(var(--v-theme-primary)) !important;
  border-width: 2px !important;
}

/* Card titles with icons */
.v-card-title {
  font-weight: 600;
  letter-spacing: 0.025em;
}

/* Divider styling */
.v-divider {
  border-color: rgba(var(--v-border-color), 0.12);
}

/* Statistics card icons */
.v-icon {
  transition: transform 0.3s ease;
}

.v-card:hover .v-icon {
  transform: scale(1.1);
}

/* Progress and loading indicators */
.v-progress-circular {
  color: rgb(var(--v-theme-primary)) !important;
}

/* Text emphasis */
.text-medium-emphasis {
  color: rgba(var(--v-theme-on-surface), 0.6) !important;
}

/* Border radius consistency */
.rounded {
  border-radius: 8px !important;
}

/* Box shadow consistency */
.elevation-1 {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}

.elevation-2 {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12) !important;
}
</style>
