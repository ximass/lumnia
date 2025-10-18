<template>
  <v-container fluid style="padding: 50px">
    <v-row justify="space-between" align="center" class="mb-4">
      <v-col>
        <h2>Relatório de respostas</h2>
      </v-col>
      <v-col class="text-right">
        <v-btn color="secondary" @click="clearFilters" class="mr-2">
          <v-icon left>mdi-filter-off</v-icon>
          Limpar filtros
        </v-btn>
        <v-btn color="primary" @click="exportCsv">
          <v-icon left>mdi-download</v-icon>
          Exportar CSV
        </v-btn>
      </v-col>
    </v-row>

  <v-row>
      <!-- Filters -->
      <v-col cols="12">
        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="filters.search"
              label="Pesquisar (usuário)"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              clearable
              density="compact"
              @update:model-value="debouncedFetch"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-menu v-model="dateMenu" :close-on-content-click="false" transition="scale-transition" offset-y>
              <template #activator="{ props }">
                <v-text-field v-bind="props" v-model="dateRangeLabel" label="Período" readonly variant="outlined" density="compact" />
              </template>

              <v-card>
                <v-date-picker v-model="dateRange" range scrollable>
                  <v-spacer />
                  <v-card-actions>
                    <v-btn variant="text" @click="dateMenu = false">Fechar</v-btn>
                  </v-card-actions>
                </v-date-picker>
              </v-card>
            </v-menu>
          </v-col>

          <v-col cols="12" md="4">
            <v-select
              v-model="filters.rating"
              :items="levels"
              item-title="title"
              item-value="value"
              label="Avaliação"
              variant="outlined"
              clearable
              density="compact"
              @update:model-value="onFilterChange"
            />
          </v-col>          
        </v-row>
      </v-col>

      <!-- Dashboard cards -->
      <v-col cols="12">
        <v-row>
          <v-col cols="12" md="4">
            <v-card class="pa-4">
              <div class="text-h6">Total de respostas</div>
              <div class="text-h4">{{ stats.total }}</div>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="pa-4">
              <div class="text-h6">Positivas</div>
              <div class="text-h4 text-success">{{ stats.likes }}</div>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="pa-4">
              <div class="text-h6">Negativas</div>
              <div class="text-h4 text-error">{{ stats.dislikes }}</div>
            </v-card>
          </v-col>
        </v-row>
      </v-col>

      <!-- Charts -->
      <v-col cols="12">
        <v-row>
          <v-col cols="12" md="4">
            <v-card>
              <v-card-title>Distribuição</v-card-title>
              <v-card-text>
                <canvas ref="pieCanvas" style="max-height:240px" />
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card>
              <v-card-title>Respostas por dia</v-card-title>
              <v-card-text>
                <canvas ref="barCanvas" style="max-height:240px" />
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card>
              <v-card-title>Tendência (últimos 7 dias)</v-card-title>
              <v-card-text>
                <canvas ref="lineCanvas" style="max-height:240px" />
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-col>

      <!-- Table -->
      <v-col cols="12">
        <v-card>
          <v-data-table
            :headers="headers"
            :items="items"
            :page.sync="pagination.page"
            :items-per-page="pagination.perPage"
            :server-items-length="total"
            :loading="loading"
            class="elevation-1"
          >
            <template #item.message="{ item }">
              <div class="text-truncate" style="max-width: 400px">
                {{ item.message?.text || item.message?.answer || '-' }}
              </div>
            </template>

            <template #item.chat_id="{ item }">
              {{ item.message?.chat_id ?? '-' }}
            </template>

            <template #item.message_id="{ item }">
              <v-btn variant="text" icon @click.stop="showMessageDetails(item)">
                <v-icon>mdi-eye</v-icon>
              </v-btn>
              <span class="ms-2">{{ item.message?.id ?? item.id ?? '-' }}</span>
            </template>

            <template #item.user="{ item }">
              {{ item.user?.name || '-' }}
            </template>

            <template #item.rating="{ item }">
              <v-chip :color="ratingColor(item.rating)" small dark>
                {{ ratingLabel(item.rating) }}
              </v-chip>
            </template>

            <template #item.created_at="{ item }">
              {{ formatDateTime(item.created_at) }}
            </template>

            <template #no-data>
              <v-sheet class="pa-6" elevation="0">
                <div class="text-center grey--Text">Nenhum registro encontrado</div>
              </v-sheet>
            </template>

          </v-data-table>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="messageDialog" max-width="800px">
      <v-card v-if="selectedMessage">
        <v-card-title class="d-flex justify-space-between align-center">
          <span>Mensagem #{{ selectedMessage.message?.id || selectedMessage.id }}</span>
          <v-btn variant="text" icon @click="messageDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-divider />

        <v-card-text>
          <v-row>
            <v-col cols="12">
              <div class="text-subtitle-2 mb-1">Pergunta</div>
              <v-sheet class="square-box">
                {{ selectedMessage.message?.text || '-' }}
              </v-sheet>
            </v-col>
            <v-col cols="12">
              <div class="text-subtitle-2 mb-1">Resposta</div>
              <v-sheet class="square-box">
                {{ selectedMessage.message?.answer || '-' }}
              </v-sheet>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider />

        <v-card-actions>
          <v-spacer />
          <v-btn variant="flat" color="primary" @click="messageDialog = false">Fechar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, computed, onMounted, watch, nextTick } from 'vue'
import axios from 'axios'
import type { MessageRating } from '@/types/types'
import { Chart, PieController, ArcElement, BarController, BarElement, LineController, LineElement, CategoryScale, LinearScale, PointElement, Tooltip, Legend, TimeScale } from 'chart.js'

Chart.register(PieController, ArcElement, BarController, BarElement, LineController, LineElement, CategoryScale, LinearScale, PointElement, Tooltip, Legend, TimeScale)

export default defineComponent({
  name: 'ReportsAnswers',
  setup() {
    const items = ref<MessageRating[]>([])
    const total = ref(0)
    const loading = ref(false)
    const filters = ref({ search: '', rating: null as MessageRating['rating'] | null })

    const ratingLabels = {
      like: 'Positiva',
      dislike: 'Negativa'
    } as Record<string, string>

    const levels = ref([
      { title: 'Positivas', value: 'like' },
      { title: 'Negativas', value: 'dislike' },
    ])

    const headers = ref([
      { title: 'Chat', key: 'chat_id', value: 'chat_id' },
      { title: 'Mensagem', key: 'message_id', value: 'message_id' },
      { title: 'Usuário', key: 'user', value: 'user' },
      { title: 'Avaliação', key: 'rating', value: 'rating' },
      { title: 'Data', key: 'created_at', value: 'created_at' },
    ])

    const pagination = ref({ page: 1, perPage: 10 })

    const pages = computed(() => Math.max(1, Math.ceil(total.value / pagination.value.perPage)))

    const dateMenu = ref(false)
    const dateRange = ref<string[] | null>(null)
    const dateRangeLabel = computed(() => {
      if (!dateRange.value || dateRange.value.length === 0) return ''
      return `${dateRange.value[0]} • ${dateRange.value[1] || ''}`
    })

    function ratingColor(r: MessageRating['rating'] | undefined) {
      return r === 'like' ? 'green' : r === 'dislike' ? 'red' : 'grey'
    }

    function ratingLabel(r: MessageRating['rating'] | undefined) {
      if (!r) return ''
      return ratingLabels[r] || r
    }

    // Stats for dashboard
    const stats = ref({ total: 0, likes: 0, dislikes: 0 })

    // Chart refs and instances
    const pieCanvas = ref<HTMLCanvasElement | null>(null)
    const barCanvas = ref<HTMLCanvasElement | null>(null)
    const lineCanvas = ref<HTMLCanvasElement | null>(null)
    let pieChart: Chart | null = null
    let barChart: Chart | null = null
    let lineChart: Chart | null = null

    // message details dialog
    const messageDialog = ref(false)
    const selectedMessage = ref<MessageRating | null>(null)

    function showMessageDetails(item: MessageRating) {
      selectedMessage.value = item
      messageDialog.value = true
    }

    function computeStats(list: MessageRating[]) {
      const s = { total: list.length, likes: 0, dislikes: 0 }
      for (const it of list) {
        if (it.rating === 'like') s.likes++
        if (it.rating === 'dislike') s.dislikes++
      }
      stats.value = s
    }

    function buildPie(list: MessageRating[]) {
      const data = [list.filter(i => i.rating === 'like').length, list.filter(i => i.rating === 'dislike').length]
      if (!pieCanvas.value) return
      if (pieChart) pieChart.destroy()
      pieChart = new Chart(pieCanvas.value.getContext('2d') as CanvasRenderingContext2D, {
        type: 'pie',
        data: {
          labels: [ratingLabels.like + 's', ratingLabels.dislike + 's'],
          datasets: [{ data, backgroundColor: ['#4caf50', '#f44336'] }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      })
    }

    function buildBar(list: MessageRating[]) {
      // group by day
      const map = new Map<string, number>()
      for (const it of list) {
        const day = it.created_at ? it.created_at.split('T')[0] : 'unknown'
        map.set(day, (map.get(day) || 0) + 1)
      }
      const labels = Array.from(map.keys()).sort()
      const data = labels.map(l => map.get(l) || 0)

      if (!barCanvas.value) return
      if (barChart) barChart.destroy()
      barChart = new Chart(barCanvas.value.getContext('2d') as CanvasRenderingContext2D, {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Respostas', data, backgroundColor: '#1976d2' }] },
        options: { responsive: true, maintainAspectRatio: false }
      })
    }

    function buildLine(list: MessageRating[]) {
      // last 7 days
      const days: string[] = []
      const now = new Date()
      for (let i = 6; i >= 0; i--) {
        const d = new Date(now)
        d.setDate(now.getDate() - i)
        days.push(d.toISOString().split('T')[0])
      }

      const map = new Map(days.map(d => [d, 0]))
      for (const it of list) {
        const day = it.created_at ? it.created_at.split('T')[0] : null
        if (day && map.has(day)) map.set(day, (map.get(day) || 0) + 1)
      }

      const labels = days
      const data = labels.map(l => map.get(l) || 0)

      if (!lineCanvas.value) return
      if (lineChart) lineChart.destroy()
      lineChart = new Chart(lineCanvas.value.getContext('2d') as CanvasRenderingContext2D, {
        type: 'line',
        data: { labels, datasets: [{ label: 'Respostas', data, borderColor: '#1565c0', backgroundColor: 'rgba(21,101,192,0.1)', fill: true }] },
        options: { responsive: true, maintainAspectRatio: false }
      })
    }

    async function fetchData() {
      loading.value = true

      const params: any = {
        page: pagination.value.page,
        per_page: pagination.value.perPage,
      }

  if (filters.value.search) params.user = filters.value.search
      if (filters.value.rating) params.rating = filters.value.rating
      if (dateRange.value && dateRange.value.length === 2) {
        params.date_from = dateRange.value[0]
        params.date_to = dateRange.value[1]
      }

      try {
        const resp = await axios.get('/api/message-ratings', { params })
        const data = resp.data
        // Laravel paginator shape: data, total, per_page, current_page
        items.value = data.data || []
        total.value = data.total || 0
        computeStats(items.value)

        // rebuild charts after DOM updates
        await nextTick()
        buildPie(items.value)
        buildBar(items.value)
        buildLine(items.value)
      } catch (err) {
        items.value = []
        total.value = 0
        computeStats([])
      } finally {
        loading.value = false
      }
    }

    function onFilterChange() {
      pagination.value.page = 1
      fetchData()
    }

    // debounce for search input
    let debounceTimer: number | undefined
    function debouncedFetch() {
      clearTimeout(debounceTimer)
      debounceTimer = window.setTimeout(() => {
        pagination.value.page = 1
        fetchData()
      }, 500)
    }

    function clearFilters() {
      filters.value = { search: '', rating: null }
      dateRange.value = null
      pagination.value.page = 1
      fetchData()
    }

    // watchers so filters apply immediately
    watch(() => filters.value.rating, () => {
      onFilterChange()
    })

    watch(() => dateRange.value, () => {
      onFilterChange()
    })

    watch(() => pagination.value.page, () => {
      fetchData()
    })

    function exportCsv() {
      const rows = [
        headers.value.map(h => h.title).join(','),
        ...items.value.map((item: any) => [
          item.message?.chat_id || '',
          item.message?.id || item.id || '',
          `"${(item.message?.metadata ? JSON.stringify(item.message.metadata) : '').replace(/"/g, '""')}"`,
          `"${((item.message?.text || item.message?.answer) || '').replace(/"/g, '""')}"`,
          `"${(item.user?.name || '').replace(/"/g, '""')}"`,
          `"${(ratingLabel(item.rating) || '').replace(/"/g, '""')}"`,
          item.created_at || ''
        ].join(','))
      ]

      const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = 'reports-answers.csv'
      a.click()
      URL.revokeObjectURL(url)
    }

    function formatDateTime(value?: string) {
      if (!value) return '-'
      try {
        return new Date(value).toLocaleString()
      } catch (e) {
        return value
      }
    }

    onMounted(() => {
      fetchData()
    })

    // cleanup charts on unmount
    function destroyCharts() {
      if (pieChart) { pieChart.destroy(); pieChart = null }
      if (barChart) { barChart.destroy(); barChart = null }
      if (lineChart) { lineChart.destroy(); lineChart = null }
    }

    return { items, headers, filters, levels, pagination, pages, total, loading, dateMenu, dateRange, dateRangeLabel, ratingColor, ratingLabel, fetchData, exportCsv, formatDateTime, stats, pieCanvas, barCanvas, lineCanvas, onFilterChange, clearFilters, debouncedFetch, messageDialog, selectedMessage, showMessageDetails }
  },
})
</script>

<style scoped>
.v-toolbar-title { text-transform: none; }
  
.square-box {
  min-height: 120px;
  padding: 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  background: rgba(0,0,0,0.02);
  overflow: auto;
  white-space: pre-wrap;
}
</style>
