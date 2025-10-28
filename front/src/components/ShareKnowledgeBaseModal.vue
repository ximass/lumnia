<template>
  <v-dialog v-model="shareDialog" :max-width="$vuetify.display.xs ? '90%' : '500'">
    <v-card>
      <v-card-title class="text-subtitle-1 text-sm-h5 d-flex align-center pa-4 pa-sm-6">
        <v-icon color="info" class="me-2 me-sm-3" :size="$vuetify.display.xs ? 20 : 24">mdi-share-variant</v-icon>
        Compartilhar base de conhecimento
      </v-card-title>
      <v-card-text class="pa-4 pa-sm-6">
        <p class="text-body-2 text-sm-body-1">Selecione os grupos com os quais deseja compartilhar a base de conhecimento <strong>"{{ knowledgeBase?.name }}"</strong>.</p>
        <v-select
          v-model="selectedGroups"
          :items="groups"
          item-title="name"
          item-value="id"
          label="Grupos"
          multiple
          outlined
          dense
        />
      </v-card-text>
      <v-card-actions class="pa-4 pa-sm-6">
        <v-spacer />
        <v-btn
          variant="flat"
          color="primary"
          @click="$emit('close')"
          :size="$vuetify.display.xs ? 'small' : 'default'"
        >
          Cancelar
        </v-btn>
        <v-btn
          variant="outlined"
          @click="shareKnowledgeBase"
          :loading="sharing"
          :size="$vuetify.display.xs ? 'small' : 'default'"
        >
          Compartilhar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import { defineComponent, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';
import type { KnowledgeBase, ApiResponse, Group } from '@/types/types';

export default defineComponent({
  name: 'ShareKnowledgeBaseModal',
  props: {
    show: {
      type: Boolean,
      required: true
    },
    knowledgeBase: {
      type: Object as () => KnowledgeBase,
      required: true
    }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const { showToast } = useToast();
    const shareDialog = ref(props.show);
    const sharing = ref(false);
    const selectedGroups = ref<number[]>([]);
    const groups = ref<Group[]>([]);
    const loadingGroups = ref(false);

    watch(
      () => props.show,
      async (newVal) => {
        if (newVal) {
          shareDialog.value = true;
          await fetchGroups();
        } else {
          shareDialog.value = false;
        }
      }
    );

    onMounted(async () => {
      if (groups.value.length === 0) {
        await fetchGroups();
      }
    });

    onBeforeUnmount(() => {
      // Ensure any observers or listeners are cleaned up
      shareDialog.value = false;
    });

    const fetchGroups = async () => {
      loadingGroups.value = true;
      try {
        const response = await axios.get<ApiResponse<Group[]>>('/api/groups');
        if (response.data && Array.isArray(response.data)) {
          groups.value = response.data.map(group => ({ id: group.id, name: group.name }));
        } else {
          showToast('Nenhum grupo encontrado', 'warning');
        }

        // Fetch groups already associated with the knowledge base
        const associatedResponse = await axios.get<ApiResponse<Group[]>>(
          `/api/knowledge-bases/${props.knowledgeBase.id}/groups`
        );

        if (associatedResponse.data && Array.isArray(associatedResponse.data)) {
          selectedGroups.value = associatedResponse.data.map(group => group.id);
        } else {
          selectedGroups.value = [];
        }
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao buscar grupos';
        showToast(errorMsg, 'error');
      } finally {
        loadingGroups.value = false;
      }
    };

    const shareKnowledgeBase = async () => {
      if (!props.knowledgeBase) {
        showToast('Base de conhecimento não encontrada', 'warning');
        return;
      }

      sharing.value = true;
      try {
        // Fetch the current associated groups
        const associatedResponse = await axios.get<ApiResponse<Group[]>>(
          `/api/knowledge-bases/${props.knowledgeBase.id}/groups`
        );
        const associatedGroups = associatedResponse.data;

        if (!associatedGroups || !Array.isArray(associatedGroups)) {
          showToast('Erro ao buscar grupos associados', 'error');
          return;
        }

        const currentlyAssociatedGroups: number[] = associatedGroups.map((group: Group) => group.id);

        // Determine groups to add and remove
        const groupsToAdd: number[] = selectedGroups.value.filter(
          (groupId: number) => !currentlyAssociatedGroups.includes(groupId)
        );
        const groupsToRemove: number[] = currentlyAssociatedGroups.filter(
          (groupId: number) => !selectedGroups.value.includes(groupId)
        );

        // Add new groups
        if (groupsToAdd.length > 0) {
          const addPayload = {
            knowledge_base_id: props.knowledgeBase.id,
            group_ids: groupsToAdd,
          };
          try {
            const addResponse = await axios.post('/api/groups/add-knowledge-base', addPayload);
          } catch (error: any) {
            showToast('Erro ao adicionar grupos', 'error');
          }
        }

        // Remove deselected groups
        for (const groupId of groupsToRemove) {
          try {
            await axios.delete(`/api/groups/${groupId}/knowledge-base/${props.knowledgeBase.id}`);
          } catch (error: any) {
            showToast(`Erro ao descompartilhar com o grupo ${groupId}`, 'error');
          }
        }

        if (groupsToAdd.length === 0 && groupsToRemove.length === 0) {
          showToast('Nenhuma alteração foi feita', 'info');
        } else {
          showToast('Base de conhecimento atualizada com sucesso', 'success');
        }

        emit('close');
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao atualizar compartilhamento';
        showToast(errorMsg, 'error');
      } finally {
        sharing.value = false;
      }
    };

    return {
      shareDialog,
      sharing,
      selectedGroups,
      groups,
      loadingGroups,
      fetchGroups,
      shareKnowledgeBase,
    };
  }
});
</script>