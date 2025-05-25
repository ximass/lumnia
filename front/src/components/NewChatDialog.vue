<template>
  <v-dialog v-model="dialog" max-width="500px">
    <v-card>
      <v-card-title>
        <span class="text-h5">Novo chat</span>
      </v-card-title>
      <v-card-text>
        <v-form ref="form" @submit.prevent="submitForm">
          <v-text-field 
            label="Nome" 
            v-model="chatName" 
            :rules="[v => !!v || 'Nome é obrigatório']"
            required>
          </v-text-field>
          <v-select 
            :items="knowledgeBases" 
            item-value="id"
            item-text="title"
            v-model="selectedKnowledgeBase" 
            :rules="[v => !!v || 'Base é obrigatória']"
            label="Base de conhecimento" 
            required>
          </v-select>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn @click="close" variant="tonal">Cancelar</v-btn>
            <v-btn color="primary" type="submit" variant="tonal">Criar</v-btn>
          </v-card-actions>
        </v-form>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
import type { KnowledgeBase, ChatWithLastMessage } from '@/types/types';

export default defineComponent({
  name: 'NewChatDialog',
  emits: ['chatCreated', 'update:modelValue'],
  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
  },
  setup(props, { emit }) {    const dialog = ref(props.modelValue);
    const { showToast } = useToast();
    const form = ref<any>(null);
    const chatName = ref<string>('');

    const knowledgeBases = ref<KnowledgeBase[]>([]);
    const selectedKnowledgeBase = ref<number | null>(null);

    watch(() => props.modelValue, (newVal) => {
      dialog.value = newVal;
    });

    watch(dialog, (newVal) => {
      emit('update:modelValue', newVal);
    });    
    
    const fetchKnowledgeBases = async () => {
      try {
        const response = await axios.get<KnowledgeBase[]>('/api/knowledge-bases');
        knowledgeBases.value = response.data;
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao buscar bases de conhecimento';
        showToast(errorMsg);
      }
    };

    const submitForm = () => {
      if (form.value?.validate()) {
        createChat();
      }
    };    
    
    const createChat = async () => {
      try {
        const response = await axios.post<ChatWithLastMessage>(
          '/api/chat',
          {
            name: chatName.value,
            knowledge_base_id: selectedKnowledgeBase.value,
          },
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem('authToken')}`,
            },
          }
        );

        emit('chatCreated', response.data);
        dialog.value = false;
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao criar chat';
        showToast(errorMsg);
      }
    };

    const close = () => {
      dialog.value = false;
    };

    onMounted(() => {
      fetchKnowledgeBases();
    });

    return {
      dialog,
      chatName,
      knowledgeBases,
      selectedKnowledgeBase,
      form,
      submitForm,
      close,
    };
  },
});
</script>

<style scoped>
.v-dialog {
  overflow-y: auto;
}
</style>