<template>
  <v-container fluid style="padding: 50px">
    <v-row justify="space-between" align="center" class="mb-4">
      <v-col>
        <h2>Logs de erros</h2>
      </v-col>
      <v-col class="text-right">
        <v-btn color="secondary" @click="clearFilters" class="mr-2">
          <v-icon left>mdi-filter-off</v-icon>
          Limpar filtros
        </v-btn>
        <v-btn color="error" @click="confirmDeleteAll">
          <v-icon left>mdi-delete-sweep</v-icon>
          Limpar todos
        </v-btn>
      </v-col>
    </v-row>

    <!-- Estatísticas -->
    <v-row v-if="statistics" class="mb-4">
      <v-col cols="12" md="2">
        <v-card>
          <v-card-text>
            <div class="text-overline">Total de Logs</div>
            <div class="text-h5">{{ statistics.total }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="2">
        <v-card>
          <v-card-text>
            <div class="text-overline">Últimas 24h</div>
            <div class="text-h5">{{ statistics.last_24h }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="2">
        <v-card>
          <v-card-text>
            <div class="text-overline">Últimos 7 dias</div>
            <div class="text-h5">{{ statistics.last_7days }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="2">
        <v-card color="warning" dark>
          <v-card-text>
            <div class="text-overline">Warnings</div>
            <div class="text-h5">{{ statistics.by_level?.warning || 0 }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="2">
        <v-card color="error" dark>
          <v-card-text>
            <div class="text-overline">Errors</div>
            <div class="text-h5">{{ statistics.by_level?.error || 0 }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="2">
        <v-card color="red" dark>
          <v-card-text>
            <div class="text-overline">Critical</div>
            <div class="text-h5">{{ statistics.by_level?.critical || 0 }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Filtros -->
    <v-row>
      <v-col cols="12" md="3">
        <v-text-field
          v-model="filters.search"
          label="Pesquisar (mensagem, arquivo, exception)"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          clearable
          density="compact"
          @update:model-value="debouncedFetchLogs"
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="filters.level"
          :items="errorLevels"
          label="Nível"
          variant="outlined"
          clearable
          density="compact"
          @update:model-value="fetchLogs"
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-text-field
          v-model="filters.date_from"
          label="Data/Hora inicial"
          type="datetime-local"
          variant="outlined"
          clearable
          density="compact"
          @update:model-value="fetchLogs"
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-text-field
          v-model="filters.date_to"
          label="Data/Hora final"
          type="datetime-local"
          variant="outlined"
          clearable
          density="compact"
          @update:model-value="fetchLogs"
        />
      </v-col>
    </v-row>

    <!-- Tabela de Logs -->
    <v-data-table
      :headers="headers"
      :items="errorLogs"
      :loading="loading"
      :items-per-page="itemsPerPage"
      class="elevation-1"
      @click:row="showLogDetails"
    >
      <template #item.level="{ item }">
        <v-chip
          :color="getLevelColor(item.level)"
          dark
          small
        >
          {{ item.level.toUpperCase() }}
        </v-chip>
      </template>

      <template #item.message="{ item }">
        <div class="text-truncate" style="max-width: 400px">
          {{ item.message }}
        </div>
      </template>

      <template #item.user="{ item }">
        {{ item.user?.name || '-' }}
      </template>

      <template #item.created_at="{ item }">
        {{ formatDateTime(item.created_at) }}
      </template>

      <template #item.actions="{ item }">
        <v-menu offset-y>
          <template #activator="{ props }">
            <v-btn icon v-bind="props" variant="text" @click.stop>
              <v-icon>mdi-dots-vertical</v-icon>
            </v-btn>
          </template>
          <v-list>
            <v-list-item @click="showLogDetails(null, { item })">
              <v-list-item-title>
                <v-icon class="me-2">mdi-eye</v-icon>
                Ver
              </v-list-item-title>
            </v-list-item>
            <v-list-item @click="deleteLog(item.id)">
              <v-list-item-title>
                <v-icon class="me-2">mdi-delete</v-icon>
                Excluir
              </v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </template>

      <template #bottom>
        <div class="text-center pt-2">
          <v-pagination
            v-model="currentPage"
            :length="totalPages"
            :total-visible="7"
            @update:model-value="fetchLogs"
          />
        </div>
      </template>
    </v-data-table>

    <!-- Dialog de Detalhes -->
    <v-dialog v-model="detailsDialog" max-width="900px" scrollable>
      <v-card v-if="selectedLog">
        <v-card-title class="d-flex justify-space-between align-center">
          <span>Detalhes do Log #{{ selectedLog.id }}</span>
          <v-btn variant="text" icon @click="detailsDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-divider />

        <v-card-text style="max-height: 600px">
          <v-row>
            <v-col cols="12" md="6">
              <div class="text-subtitle-2 mb-1">Nível:</div>
              <v-chip :color="getLevelColor(selectedLog.level)" dark>
                {{ selectedLog.level.toUpperCase() }}
              </v-chip>
            </v-col>
            <v-col cols="12" md="6">
              <div class="text-subtitle-2 mb-1">Data/Hora:</div>
              <div>{{ formatDateTime(selectedLog.created_at) }}</div>
            </v-col>
          </v-row>

          <v-row v-if="selectedLog.user">
            <v-col cols="12">
              <div class="text-subtitle-2 mb-1">Usuário:</div>
              <div>{{ selectedLog.user.name }} ({{ selectedLog.user.email }})</div>
            </v-col>
          </v-row>

          <v-row>
            <v-col cols="12">
              <div class="text-subtitle-2 mb-1">Mensagem:</div>
              <v-alert type="error" variant="tonal">
                {{ selectedLog.message }}
              </v-alert>
            </v-col>
          </v-row>

          <v-row v-if="selectedLog.exception">
            <v-col cols="12">
              <div class="text-subtitle-2 mb-1">Exception:</div>
              <div class="code-block">{{ selectedLog.exception }}</div>
            </v-col>
          </v-row>

          <v-row v-if="selectedLog.file">
            <v-col cols="12">
              <div class="text-subtitle-2 mb-1">Arquivo:</div>
              <div class="code-block">
                {{ selectedLog.file }}
                <span v-if="selectedLog.line"> : Linha {{ selectedLog.line }}</span>
              </div>
            </v-col>
          </v-row>

          <v-row v-if="selectedLog.url">
            <v-col cols="12" md="8">
              <div class="text-subtitle-2 mb-1">URL:</div>
              <div class="code-block">{{ selectedLog.url }}</div>
            </v-col>
            <v-col cols="12" md="2">
              <div class="text-subtitle-2 mb-1">Método:</div>
              <v-chip small>{{ selectedLog.method }}</v-chip>
            </v-col>
            <v-col cols="12" md="2">
              <div class="text-subtitle-2 mb-1">IP:</div>
              <div>{{ selectedLog.ip }}</div>
            </v-col>
          </v-row>

          <v-row v-if="selectedLog.request_data">
            <v-col cols="12">
              <div class="text-subtitle-2 mb-1">Dados da Requisição:</div>
              <pre class="code-block">{{ JSON.stringify(selectedLog.request_data, null, 2) }}</pre>
            </v-col>
          </v-row>

          <v-row v-if="selectedLog.trace">
            <v-col cols="12">
              <div class="text-subtitle-2 mb-1">Stack Trace:</div>
              <pre class="code-block trace-block">{{ selectedLog.trace }}</pre>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider />

        <v-card-actions>
          <v-spacer />
          <v-btn variant="outlined" color="error" @click="deleteLog(selectedLog.id)">
            <v-icon left>mdi-delete</v-icon>
            Excluir
          </v-btn>
          <v-btn variant="flat" color="primary" @click="detailsDialog = false">Fechar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <ConfirmDialog
      :show="isConfirmDialogOpen"
      :title="confirmTitle"
      :message="confirmMessage"
      @confirm="handleConfirm"
      @cancel="closeConfirm"
    />
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import type { ErrorLog, ErrorLogFilters, ErrorLogStatistics, ErrorLogLevel } from '../types/types';

export default defineComponent({
  name: 'ErrorLogsView',
  components: {
    ConfirmDialog,
  },
  setup() {
    const { showToast } = useToast();
    const {
      isConfirmDialogOpen,
      confirmTitle,
      confirmMessage,
      openConfirm,
      closeConfirm,
      handleConfirm,
    } = useConfirm();

    const loading = ref(false);
    const errorLogs = ref<ErrorLog[]>([]);
    const statistics = ref<ErrorLogStatistics | null>(null);
    const selectedLog = ref<ErrorLog | null>(null);
    const detailsDialog = ref(false);
    const currentPage = ref(1);
    const totalPages = ref(1);
    const itemsPerPage = ref(50);

    const filters = ref<ErrorLogFilters>({
      search: '',
      level: null,
      date_from: '',
      date_to: '',
      user_id: null,
    });

    const errorLevels = ref([
      { title: 'Error', value: 'error' },
      { title: 'Warning', value: 'warning' },
      { title: 'Critical', value: 'critical' },
      { title: 'Info', value: 'info' },
    ]);

    const headers = [
      { title: 'ID', value: 'id', sortable: false, width: '80px' },
      { title: 'Nível', value: 'level', sortable: false, width: '120px' },
      { title: 'Mensagem', value: 'message', sortable: false },
      { title: 'Usuário', value: 'user', sortable: false, width: '150px' },
      { title: 'Data/Hora', value: 'created_at', sortable: false, width: '180px' },
      { title: 'Ações', value: 'actions', sortable: false, width: '80px' },
    ];

    const fetchLogs = async () => {
      loading.value = true;
      try {
        const params: any = {
          page: currentPage.value,
          per_page: itemsPerPage.value,
        };

        if (filters.value.search) params.search = filters.value.search;
        if (filters.value.level) params.level = filters.value.level;
        if (filters.value.date_from) params.date_from = filters.value.date_from;
        if (filters.value.date_to) params.date_to = filters.value.date_to;
        if (filters.value.user_id) params.user_id = filters.value.user_id;

        const response = await axios.get('/api/error-logs', { params });
        errorLogs.value = response.data.data;
        totalPages.value = response.data.last_page;
      } catch (error) {
        showToast('Erro ao buscar logs de erros', 'error');
      } finally {
        loading.value = false;
      }
    };

    const fetchStatistics = async () => {
      try {
        const response = await axios.get('/api/error-logs/statistics');
        statistics.value = response.data;
      } catch (error) {
        showToast('Erro ao buscar estatísticas', 'error');
      }
    };

    let debounceTimer: number;
    const debouncedFetchLogs = () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        currentPage.value = 1;
        fetchLogs();
      }, 500);
    };

    const clearFilters = () => {
      filters.value = {
        search: '',
        level: null,
        date_from: '',
        date_to: '',
        user_id: null,
      };
      currentPage.value = 1;
      fetchLogs();
    };

    const showLogDetails = (_event: any, row: any) => {
      selectedLog.value = row.item;
      detailsDialog.value = true;
    };

    const deleteLog = async (logId: number) => {
      openConfirm(
        'Tem certeza que deseja excluir este log?',
        async () => {
          try {
            await axios.delete(`/api/error-logs/${logId}`);
            showToast('Log excluído com sucesso!', 'success');
            detailsDialog.value = false;
            fetchLogs();
            fetchStatistics();
          } catch (error) {
            showToast('Erro ao deletar log', 'error');
          }
        },
        'Excluir log'
      );
    };

    const confirmDeleteAll = () => {
      openConfirm(
        'Tem certeza que deseja excluir TODOS os logs? Esta ação não pode ser desfeita!',
        async () => {
          try {
            await axios.delete('/api/error-logs/destroy-all');
            showToast('Todos os logs foram excluídos com sucesso!', 'success');
            fetchLogs();
            fetchStatistics();
          } catch (error) {
            showToast('Erro ao deletar todos os logs', 'error');
          }
        },
        'Excluir todos os logs'
      );
    };

    const getLevelColor = (level: ErrorLogLevel): string => {
      const colors: Record<ErrorLogLevel, string> = {
        error: 'error',
        warning: 'warning',
        critical: 'purple',
        info: 'info',
      };
      return colors[level] || 'grey';
    };

    const formatDateTime = (dateString: string): string => {
      const date = new Date(dateString);
      return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      });
    };

    onMounted(() => {
      fetchLogs();
      fetchStatistics();
    });

    return {
      loading,
      errorLogs,
      statistics,
      selectedLog,
      detailsDialog,
      filters,
      errorLevels,
      headers,
      currentPage,
      totalPages,
      itemsPerPage,
      fetchLogs,
      debouncedFetchLogs,
      clearFilters,
      showLogDetails,
      deleteLog,
      confirmDeleteAll,
      getLevelColor,
      formatDateTime,
      isConfirmDialogOpen,
      confirmTitle,
      confirmMessage,
      closeConfirm,
      handleConfirm,
    };
  },
});
</script>

<style scoped>
.code-block {
  background-color: rgba(0, 0, 0, 0.05);
  color: inherit;
  padding: 12px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  font-size: 13px;
  overflow-x: auto;
  word-break: break-all;
  white-space: pre-wrap;
  border: 1px solid rgba(0, 0, 0, 0.12);
}

/* Modo escuro */
:deep(.v-theme--dark) .code-block {
  background-color: rgba(255, 255, 255, 0.05);
  color: #e0e0e0;
  border: 1px solid rgba(255, 255, 255, 0.12);
}

.trace-block {
  max-height: 300px;
  overflow-y: auto;
  white-space: pre;
}

.v-data-table >>> tbody tr {
  cursor: pointer;
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
