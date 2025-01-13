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
            v-model="selectedKnowledgeBases" 
            label="Bases de conhecimento" 
            multiple
            chips 
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

export default defineComponent({
  name: 'NewChatDialog',
  emits: ['chatCreated', 'update:modelValue'],
  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
  },
  setup(props, { emit }) {
    const dialog = ref(props.modelValue);
    const chatName = ref<string | null>(null);
    const knowledgeBases = ref<Array<{ id: number; name: string }>>([]);
    const selectedKnowledgeBases = ref<number[]>([]);
    const form = ref(null);

    watch(() => props.modelValue, (newVal) => {
      dialog.value = newVal;
    });

    watch(dialog, (newVal) => {
      emit('update:modelValue', newVal);
    });

    const fetchKnowledgeBases = async () => {
      try {
        const response = await axios.get('/api/knowledge-bases');

        knowledgeBases.value = response.data;
      } catch (error) {
        console.error('Erro ao buscar bases de conhecimento:', error);
      }
    };

    const submitForm = () => {
      if (form.value?.validate()) {
        createChat();
      }
    };

    const createChat = async () => {
      try {
        const response = await axios.post(
          '/api/chat',
          {
            name: chatName.value,
            knowledge_base_ids: selectedKnowledgeBases.value,
          },
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem('authToken')}`,
            },
          }
        );

        emit('chatCreated', response.data);
        dialog.value = false;
      } catch (error) {
        console.error('Erro ao criar chat:', error);
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
      selectedKnowledgeBases,
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