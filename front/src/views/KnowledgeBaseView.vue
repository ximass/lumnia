<template>
  <v-container style="padding: 50px;">
    <v-row justify="space-between" align="center" class="mb-4" style="margin: 0;">
      <h2>Bases de conhecimento</h2>
    </v-row>
    <v-data-table :items="knowledgeBases" :headers="headers" class="elevation-1">
      <template #item.content="{ item }">
        {{ item.content }}
      </template>
      <template #item.actions="{ item }">
        <v-menu offset-y>
          <template #activator="{ props }">
            <v-btn icon v-bind="props" variant="text">
              <v-icon>mdi-dots-vertical</v-icon>
            </v-btn>
          </template>
          <v-list>
            <v-list-item @click="editKnowledgeBase(item)">
              <v-list-item-title>
                <v-icon>mdi-pencil</v-icon>
                Editar
              </v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </template>
    </v-data-table>
    <KnowledgeBaseForm :dialog="isFormOpen" :knowledgeBaseData="selectedKnowledgeBase" @close="isFormOpen = false"
      @saved="fetchKnowledgeBases" />
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import axios from 'axios';
import KnowledgeBaseForm from '@/components/KnowledgeBaseForm.vue';
import { useToast } from '@/composables/useToast';
import type { KnowledgeBase } from '@/types/types';

export default defineComponent({
  name: 'KnowledgeBaseView',
  components: { KnowledgeBaseForm },
  setup() {
    const { showToast } = useToast();
    const isFormOpen = ref(false);

    const knowledgeBases = ref<KnowledgeBase[]>([]);
    const selectedKnowledgeBase = ref<KnowledgeBase | null>(null);

    const headers = [
      { title: 'Título', value: 'title', sortable: true },
      { title: 'Conteúdo', value: 'content', sortable: false },
      { title: 'Ações', value: 'actions', sortable: false }
    ];

    const fetchKnowledgeBases = async () => {
      try {
        const response = await axios.get<KnowledgeBase[]>('/api/knowledge-bases');
        knowledgeBases.value = response.data;
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao buscar bases de conhecimento';
        showToast(errorMsg);
      }
    };

    const editKnowledgeBase = (knowledgeBase: KnowledgeBase) => {
      selectedKnowledgeBase.value = { ...knowledgeBase };
      isFormOpen.value = true;
    };
    
    onMounted(() => {
      fetchKnowledgeBases();
    });

    return {
      knowledgeBases,
      isFormOpen,
      selectedKnowledgeBase,
      headers,
      editKnowledgeBase,
      fetchKnowledgeBases,
    };
  },
});
</script>

<style scoped>
.v-container {
  padding-top: 50px;
}
</style>