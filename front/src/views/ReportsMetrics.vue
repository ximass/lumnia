
<template>
		<v-container fluid style="padding: 50px">
			<v-row justify="space-between" align="center" class="mb-4">
				<v-col>
					<h2>Relatório de métricas</h2>
				</v-col>
			</v-row>

			<!-- Line 1: total de amostras and total avaliadas -->
			<v-row class="mb-4">
				<v-col cols="12" md="6">
					<v-card class="pa-4 text-center">
						<div class="text-subtitle-1">Total de amostras</div>
						<div class="text-h3 mt-2">{{ totalMessages }}</div>
					</v-card>
				</v-col>
				<v-col cols="12" md="6">
					<v-card class="pa-4 text-center">
						<div class="text-subtitle-1">Total de amostras avaliadas</div>
						<div class="text-h3 mt-2">{{ total }}</div>
					</v-card>
				</v-col>
			</v-row>

			<!-- Line 2: positivas and negativas -->
			<v-row class="mb-4">
				<v-col cols="12" md="6">
					<v-card class="pa-4 text-center">
						<div class="text-subtitle-1">Positivas</div>
						<div class="text-h3 text-success mt-2">{{ positives }}</div>
					</v-card>
				</v-col>
				<v-col cols="12" md="6">
					<v-card class="pa-4 text-center">
						<div class="text-subtitle-1">Negativas</div>
						<div class="text-h3 text-error mt-2">{{ negatives }}</div>
					</v-card>
				</v-col>
			</v-row>

			<!-- Line 3: TFR and IRFC -->
			<v-row class="mb-4">
				<v-col cols="12" md="6">
					<v-card class="pa-4 text-center">
						<div class="text-subtitle-1">Taxa de fonte rastreável (TFR)</div>
						<div class="text-h3 mt-2">{{ tfr }}%</div>
					</v-card>
				</v-col>
				<v-col cols="12" md="6">
					<v-card class="pa-4 text-center">
						<div class="text-subtitle-1">Índice de rejeição por falta de contexto (IRFC)</div>
						<div class="text-h3 mt-2">{{ irfc }}%</div>
					</v-card>
				</v-col>
			</v-row>

			<!-- TAP and TAN meters occupying full width side-by-side -->
			<v-row>
				<v-col cols="12" md="6">
					<v-card class="pa-6 d-flex flex-column align-center justify-center" style="min-height:220px">
						<div class="text-subtitle-1 mb-2">Taxa de Avaliação Positivas (TAP)</div>
							<div :style="circleStyle(tap, '#4caf50')" class="metric-circle">
								<div class="inner-circle">
									<div class="text-h5">{{ tap }}%</div>
								</div>
							</div>
					</v-card>
				</v-col>

				<v-col cols="12" md="6">
					<v-card class="pa-6 d-flex flex-column align-center justify-center" style="min-height:220px">
						<div class="text-subtitle-1 mb-2">Taxa de Avaliação Negativas (TAN)</div>
							<div :style="circleStyle(tan, '#f44336')" class="metric-circle">
								<div class="inner-circle">
									<div class="text-h5">{{ tan }}%</div>
								</div>
							</div>
					</v-card>
				</v-col>
			</v-row>

			<v-row>
				<v-col cols="12">
					<v-alert type="error" v-if="error" class="mt-3">{{ error }}</v-alert>
					<v-skeleton-loader type="list-item-two-line" v-if="loading" class="mt-3" />
				</v-col>
			</v-row>
		</v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import type { MessageRating } from '@/types/types'

const props = defineProps<{ samples?: MessageRating[] }>()

const samples = ref<MessageRating[]>(props.samples ?? [])
const loading = ref(false)
const error = ref<string | null>(null)

// total based on message-ratings list (used for TAP/TAN) and totalMessages for overall messages
const total = computed(() => samples.value.length)
const totalMessages = ref<number>(0)
const withSources = ref<number>(0)
const tfr = computed(() => (totalMessages.value ? Number(((withSources.value / totalMessages.value) * 100).toFixed(2)) : 0))
// message-ratings use `rating` with values 'like' | 'dislike'
const positives = computed(() => samples.value.filter(s => (s as any).rating === 'like').length)
const negatives = computed(() => samples.value.filter(s => (s as any).rating === 'dislike').length)
const tap = computed(() => (total.value ? Number(((positives.value / total.value) * 100).toFixed(2)) : 0))
const tan = computed(() => (total.value ? Number(((negatives.value / total.value) * 100).toFixed(2)) : 0))

const negTotalStats = ref<number>(0)
const negWithoutSources = ref<number>(0)
const irfc = computed(() => (negTotalStats.value ? Number(((negWithoutSources.value / negTotalStats.value) * 100).toFixed(2)) : 0))

async function fetchSamples() {
	loading.value = true
	error.value = null
	try {
		// fetch message ratings (use same endpoint as reports answers)
		const r = await axios.get('/api/message-ratings')
		// backend may return paginator shape { data: [...] } or direct array
		if (r?.data?.data && Array.isArray(r.data.data)) samples.value = r.data.data
		else if (Array.isArray(r?.data)) samples.value = r.data
		else samples.value = []
	} catch (e: any) {
		error.value = e?.message ?? 'Erro ao buscar amostras'
	} finally {
		loading.value = false
	}
}

async function fetchTfrStats() {
	try {
		const r = await axios.get('/api/messages/with-information-sources/stats')
		if (r?.data) {
			totalMessages.value = Number(r.data.total ?? 0)
			withSources.value = Number(r.data.with_sources ?? 0)
		}
	} catch (e: any) {
		// non-blocking; show in error area
		error.value = e?.message ?? 'Erro ao buscar estatísticas de fontes'
	}
}

async function fetchIrfcStats() {
	try {
		const r = await axios.get('/api/message-ratings/dislikes/no-sources/stats')
		if (r?.data) {
			negTotalStats.value = Number(r.data.neg_total ?? 0)
			negWithoutSources.value = Number(r.data.neg_without_sources ?? 0)
		}
	} catch (e: any) {
		error.value = e?.message ?? 'Erro ao buscar estatísticas de dislikes'
	}
}

onMounted(() => {
	if (!props.samples) fetchSamples()

	fetchTfrStats()
	fetchIrfcStats()
})

watch(
	() => props.samples,
	(v) => {
		if (v) samples.value = v
	}
)

function clearFilters() {
	// same behaviour: re-fetch from backend
	error.value = null
	fetchSamples()
	fetchTfrStats()
	fetchIrfcStats()
}

function exportCsv() {
	const rows = [
		['id', 'rating', 'created_at'].join(','),
		...samples.value.map(s => {
			const id = (s as any).id ?? ''
			const rating = (s as any).rating ?? ''
			const created_at = (s as any).created_at ?? ''
			return [id, rating, `"${String(created_at).replace(/"/g, '""')}"`].join(',')
		})
	]

	const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' })
	const url = URL.createObjectURL(blob)
	const a = document.createElement('a')
	a.href = url
	a.download = 'reports-metrics.csv'
	a.click()
	URL.revokeObjectURL(url)
}

	function circleStyle(value: number, color: string) {
		const pct = Math.max(0, Math.min(100, Number(value)))
		return {
			width: '140px',
			height: '140px',
			borderRadius: '50%',
			display: 'flex',
			alignItems: 'center',
			justifyContent: 'center',
			background: `conic-gradient(${color} ${pct}%, #eee ${pct}% 100%)`
		} as Record<string, string>
	}
</script>

<style scoped>
.text-right { text-align: right; }
.metric-circle {
	margin-top: 8px;
}
.inner-circle {
	/* make inner circle larger so only the outer ring remains visible
	   and set a dark center as requested */
	width: 116px;
	height: 116px;
	border-radius: 50%;
	background: #121212;
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
}

.metric-circle { /* ensure outer ring has light background for empty portion */
  background-clip: padding-box;
}
</style>
