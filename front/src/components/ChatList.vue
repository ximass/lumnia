<template>
  <v-container fluid class="chat-list-container pa-4">
    <v-row align="center" class="mb-4">
      <v-col cols="8">
        <h2>Chats</h2>
      </v-col>
      <v-col cols="4" class="d-flex justify-end">
        <v-btn icon @click="openNewChatDialog">
          <v-icon>mdi-plus</v-icon>
        </v-btn>
      </v-col>
    </v-row>
    <v-list class="chat-list">
      <v-list-item
        v-for="chat in chats"
        :key="chat.id"
        @click="selectChat(chat)"
        class="chat-item"
      >
        <v-row>
          <v-col cols="10">
            <v-list-item-title>{{ chat.name }}</v-list-item-title>
            <v-list-item-subtitle>{{ chat.lastMessage }}</v-list-item-subtitle>
          </v-col>
          <v-col cols="2" class="d-flex justify-end">
            <v-list-item-action>
              <v-icon class="delete-icon" @click.stop="deleteChat(chat.id)">mdi-close</v-icon>
            </v-list-item-action>
          </v-col>
        </v-row>
      </v-list-item>
    </v-list>
    <NewChatDialog
      v-model="isNewChatDialogOpen"
      @chatCreated="handleChatCreated"
    />
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import axios from 'axios';
import echo from '@/plugins/echo';
import NewChatDialog from '@/components/NewChatDialog.vue';
import { useToast } from '@/composables/useToast';

export default defineComponent({
  name: 'ChatList',
  props: {
    lastMessage: {
      type: String,
      required: false,
    },
  },
  components: {
    NewChatDialog,
  },
  emits: ['chatSelected'],
  setup(props, { emit }) {
    const chats = ref<Array<{ id: number; name: string; lastMessage: string }>>([]);
    const currentChat = ref<{ id: number; name: string } | null>(null);
    const isNewChatDialogOpen = ref(false);

    const { showToast } = useToast();

    const fetchChats = async () => {
      const response = await axios.get('/api/chats', {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('authToken')}`,
        },
      });
      chats.value = response.data;
    };

    const selectChat = (chat: any) => {
      currentChat.value = chat;

      emit('chatSelected', chat);
    };

    const deleteChat = async (chatId: number) => {
      try {
        await axios.delete(`/api/chat/${chatId}`);
        chats.value = chats.value.filter(chat => chat.id !== chatId);
      } catch (error) {
        const errorMsg = error.response?.data?.message || 'Ocorreu um erro ao excluir o chat.';
        showToast(errorMsg);
      }
    };

    const openNewChatDialog = () => {
      isNewChatDialogOpen.value = true;
    };

    const handleChatCreated = (newChat: any) => {
      chats.value = [newChat, ...chats.value];
      isNewChatDialogOpen.value = false;
    };

    onMounted(() => {
      fetchChats();
    });

    return {
      chats,
      currentChat,
      selectChat,
      deleteChat,
      isNewChatDialogOpen,
      openNewChatDialog,
      handleChatCreated,
    };
  },
  updated() {
    if (this.currentChat) {
      const chat = this.chats.find(chat => chat.id === this.currentChat.id);

      if (chat && chat.lastMessage !== this.lastMessage) {
        chat.lastMessage = this.lastMessage;
      }
    }
  },
});
</script>

<style scoped>
.chat-list-container {
  height: 90vh;
  display: flex;
  flex-direction: column;
}

.chat-list {
  flex: 1;
  overflow-y: auto;
  border-radius: 5px;
}

.delete-icon {
  cursor: pointer;
  color: black;
  opacity: 0;
  transition: opacity 0.3s;
}

.chat-item:hover .delete-icon {
  opacity: 1;
}

.mb-4 {
  margin-bottom: 16px;
  display: flex;
  flex: 0;
}
</style>