<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-toolbar flat>
          <v-toolbar-title>Relatório de respostas</v-toolbar-title>
          <v-spacer />
          <v-btn color="primary" @click="exportCsv">Exportar CSV</v-btn>
        </v-toolbar>
      </v-col>

      <v-col cols="12">
        <v-card>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="filters.search"
                  label="Buscar"
                  clearable
                />
              </v-col>

              <v-col cols="12" md="3">
                <v-menu v-model="dateMenu" :close-on-content-click="false" transition="scale-transition" offset-y>
                  <template #activator="{ props }">
                    <v-text-field v-bind="props" v-model="dateRangeLabel" label="Período" readonly />
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

              <v-col cols="12" md="2">
                <v-select
                  v-model="filters.rating"
                  :items="levels"
                  label="Avaliação"
                  clearable
                />
              </v-col>

              <v-col cols="12" md="3" class="d-flex align-end justify-end">
                <v-btn color="secondary" @click="fetchData">Aplicar</v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

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

            <template #item.user="{ item }">
              {{ item.user?.name || '-' }}
            </template>

            <template #item.rating="{ item }">
              <v-chip :color="ratingColor(item.rating)" small dark>
                {{ (item.rating || '').toUpperCase() }}
              </v-chip>
            </template>

            <template #item.created_at="{ item }">
              {{ formatDateTime(item.created_at) }}
            </template>

            <template #no-data>
              <v-sheet class="pa-6" elevation="0">
                <div class="text-center grey--text">Nenhum registro encontrado</div>
              </v-sheet>
            </template>

          </v-data-table>
          <v-card-actions class="pa-4">
            <v-row align="center" justify="space-between" class="w-100">
              <div>Mostrando {{ items.length }} de {{ total }}</div>
              <v-pagination v-model="pagination.page" :length="pages" />
            </v-row>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, computed } from 'vue'
import axios from 'axios'
import type { MessageRating } from '@/types/types'

export default defineComponent({
  name: 'ReportsAnswers',
  setup() {
    const items = ref<MessageRating[]>([])
    const total = ref(0)
    const loading = ref(false)
    const filters = ref({ search: '', rating: null as MessageRating['rating'] | null })

    const levels = ref(['like', 'dislike'])

    const headers = ref([
      { title: 'id', key: 'id', value: 'id' },
      { title: 'mensagem', key: 'message', value: 'message' },
      { title: 'usuário', key: 'user', value: 'user' },
      { title: 'avaliação', key: 'rating', value: 'rating' },
      { title: 'data', key: 'created_at', value: 'created_at' },
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

    async function fetchData() {
      loading.value = true

      const params: any = {
        page: pagination.value.page,
        per_page: pagination.value.perPage,
      }

      if (filters.value.search) params.search = filters.value.search
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
      } catch (err) {
        items.value = []
        total.value = 0
      } finally {
        loading.value = false
      }
    }

    function exportCsv() {
      const rows = [
        headers.value.map(h => h.title).join(','),
        ...items.value.map((item: any) => [
          item.id,
          `"${((item.message?.text || item.message?.answer) || '').replace(/"/g, '""')}"`,
          `"${(item.user?.name || '').replace(/"/g, '""')}"`,
          item.rating,
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

    fetchData()

    function formatDateTime(value?: string) {
      if (!value) return '-'
      try {
        return new Date(value).toLocaleString()
      } catch (e) {
        return value
      }
    }

    fetchData()

    return { items, headers, filters, levels, pagination, pages, total, loading, dateMenu, dateRange, dateRangeLabel, ratingColor, fetchData, exportCsv, formatDateTime }
  },
})
</script>

<style scoped>
.v-toolbar-title { text-transform: none; }
</style>
