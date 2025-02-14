<template>
  <v-dialog v-model="dialog" max-width="600px">
    <v-card>
      <v-card-title>
        <span class="text-h5">{{ isEdit ? 'Editar grupo' : 'Novo grupo' }}</span>
      </v-card-title>
      <v-card-text>
        <v-form ref="form" @submit.prevent="submitForm">
          <v-text-field label="Nome do grupo" v-model="group.name" :rules="[v => !!v || 'Nome é obrigatório']"
            required>
          </v-text-field>
          <v-autocomplete
            v-model="group.user_ids"
            :items="users"
            item-text="nome"
            item-value="id"
            label="Usuários"
            multiple
            chips
            clearable
            hide-selected
            :loading="loadingUsers"
            @update:search="fetchUsers"
          >
          </v-autocomplete>
          <v-select
            v-model="group.knowledge_base_ids"
            :items="knowledgeBases"
            item-text="title"
            item-value="id"
            label="Bases de conhecimentos"
            multiple
            chips
            clearable
            hide-selected
            :loading="loadingKnowledgeBases"
          ></v-select>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn text @click="close">Cancelar</v-btn>
            <v-btn color="primary" type="submit">Salvar</v-btn>
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

export default defineComponent({
  name: 'GroupForm',
  props: {
    dialog: {
      type: Boolean,
      required: true,
    },
    groupData: {
      type: Object,
      default: null,
    },
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const form = ref(null);
    const group = ref<{ name: string; user_ids: number[]; knowledge_base_ids: number[] }>({ name: '', user_ids: [], knowledge_base_ids: [] });
    const users = ref<Array<{ id: number; title: string }>>([]);
    const loadingUsers = ref(false);
    const knowledgeBases = ref<Array<{ id: number; title: string }>>([]);
    const loadingKnowledgeBases = ref(false);

    const { showToast } = useToast();

    watch(() => props.groupData, (newData) => {
      if (newData) {
        group.value = {
          name: newData.name,
          user_ids: newData.users.map(user => user.id),
          knowledge_base_ids: newData.knowledge_bases.map(kb => kb.id),
        };
      } else {
        group.value = { 
          name: '', 
          user_ids: [],
          knowledge_base_ids: [],
        };
      }
    }, { immediate: true });

    const fetchUsers = async (search: string) => {
      if (!search) return;

      loadingUsers.value = true;
      try {
        const response = await axios.get('/api/users/search', { params: { search } });

        if (response.data)
        {
          users.value = response.data.map((user: any) => ({ id: user.id, title: user.name }));
        }
      } catch (error) {
        const errorMsg = error.response?.data?.message || 'Erro ao buscar usuários';
        showToast(errorMsg);
      } finally {
        loadingUsers.value = false;
      }
    };

    const fetchKnowledgeBases = async () => {
      loadingKnowledgeBases.value = true;

      try {
        const response = await axios.get('/api/knowledge-bases');
        knowledgeBases.value = response.data.map((kb: any) => ({ id: kb.id, title: kb.title }));
      } catch (error) {
        const errorMsg = error.response?.data?.message || 'Erro ao buscar bases de conhecimento';
        showToast(errorMsg);
      } finally {
        loadingKnowledgeBases.value = false;
      }
    };

    onMounted(() => {
      fetchKnowledgeBases();
    });

    const submitForm = async () => {
      if (form.value?.validate()) {
        try {
          const payload = {
            name: group.value.name,
            user_ids: group.value.user_ids,
            knowledge_base_ids: group.value.knowledge_base_ids,
          };

          if (props.groupData) {
            await axios.put(`/api/groups/${props.groupData.id}`, payload);
          } else {
            await axios.post('/api/groups', payload);
          }
          emit('saved');
          close();
        } catch (error) {
          const errorMsg = error.response?.data?.message || 'Erro ao salvar grupo';
          showToast(errorMsg);
        }
      }
    };

    const close = () => {
      emit('close');
    };

    return {
      form,
      group,
      users,
      loadingUsers,
      fetchUsers,
      submitForm,
      close,
      isEdit: !!props.groupData,
      knowledgeBases,
      loadingKnowledgeBases,
      fetchKnowledgeBases,
    };
  },
});
</script>