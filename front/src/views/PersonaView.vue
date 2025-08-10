<template>
  <v-container style="padding: 50px">
    <v-row justify="space-between" align="center" class="mb-4" style="margin: 0">
      <h2>Personas</h2>
      <v-btn color="primary" @click="openForm">Nova</v-btn>
    </v-row>
    <v-data-table :items="personas" :headers="headers" class="elevation-1">
      <template #item.active="{ item }">
        <v-chip :color="item.active ? 'success' : 'error'" size="small">
          {{ item.active ? 'Ativa' : 'Inativa' }}
        </v-chip>
      </template>
      <template #item.creativity="{ item }">{{ (item.creativity * 100).toFixed(0) }}%</template>
      <template #item.keywords="{ item }">
        <div v-if="item.keywords && item.keywords.length > 0">
          <v-chip
            v-for="keyword in item.keywords.slice(0, 3)"
            :key="keyword"
            size="x-small"
            class="ma-1"
          >
            {{ keyword }}
          </v-chip>
          <span v-if="item.keywords.length > 3" class="text-caption">
            +{{ item.keywords.length - 3 }} mais
          </span>
        </div>
        <span v-else class="text-grey">Nenhuma</span>
      </template>
      <template #item.description="{ item }">
        <div class="text-truncate" style="max-width: 300px" :title="item.description">
          {{ item.description }}
        </div>
      </template>
      <template #item.actions="{ item }">
        <v-menu offset-y>
          <template #activator="{ props }">
            <v-btn icon v-bind="props" variant="text"><v-icon>mdi-dots-vertical</v-icon></v-btn>
          </template>
          <v-list>
            <v-list-item @click="editPersona(item)">
              <v-list-item-title>
                <v-icon>mdi-pencil</v-icon>
                Editar
              </v-list-item-title>
            </v-list-item>
            <v-list-item @click="deletePersona(item)">
              <v-list-item-title>
                <v-icon>mdi-delete</v-icon>
                Excluir
              </v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </template>
    </v-data-table>
    <PersonaForm
      :dialog="isFormOpen"
      :personaData="selectedPersona"
      @close="isFormOpen = false"
      @saved="handlePersonaSaved"
    />
  </v-container>
</template>

<script lang="ts">
  import { defineComponent, ref, onMounted } from 'vue'
  import axios from 'axios'
  import PersonaForm from '@/components/PersonaForm.vue'
  import { useToast } from '@/composables/useToast'
  import type { Persona } from '@/types/types'

  export default defineComponent({
    name: 'PersonaView',
    components: { PersonaForm },
    setup() {
      const { showToast } = useToast()
      const isFormOpen = ref(false)

      const personas = ref<Persona[]>([])
      const selectedPersona = ref<Persona | null>(null)

      const headers = [
        { title: 'Nome', value: 'name', sortable: true },
        { title: 'Descrição', value: 'description', sortable: true },
        { title: 'Palavras-chave', value: 'keywords', sortable: false },
        { title: 'Criatividade', value: 'creativity', sortable: true },
        { title: 'Status', value: 'active', sortable: true },
        { title: 'Ações', value: 'actions', sortable: false },
      ]

      const fetchPersonas = async () => {
        try {
          const response = await axios.get<Persona[]>('/api/personas')
          personas.value = response.data
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao buscar personas'
          showToast(errorMsg)
        }
      }

      const editPersona = (persona: Persona) => {
        selectedPersona.value = { ...persona }
        isFormOpen.value = true
      }

      const openForm = () => {
        selectedPersona.value = null
        isFormOpen.value = true
      }

      const deletePersona = async (persona: Persona) => {
        if (confirm(`Tem certeza que deseja excluir a persona "${persona.name}"?`)) {
          try {
            await axios.delete(`/api/personas/${persona.id}`)
            showToast('Persona excluída com sucesso!', 'success')
            fetchPersonas()
          } catch (error: any) {
            const errorMsg = error.response?.data?.message || 'Erro ao excluir persona'
            showToast(errorMsg)
          }
        }
      }

      const handlePersonaSaved = () => {
        isFormOpen.value = false
        fetchPersonas()
      }

      onMounted(() => {
        fetchPersonas()
      })

      return {
        personas,
        isFormOpen,
        selectedPersona,
        headers,
        editPersona,
        openForm,
        deletePersona,
        fetchPersonas,
        handlePersonaSaved,
        showToast,
      }
    },
  })
</script>

<style scoped>
  .v-container {
    padding-top: 50px;
  }
</style>
