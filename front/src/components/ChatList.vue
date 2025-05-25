<template>
  <v-container fluid class="chat-list-container pa-4">
    <v-row align="center" class="mb-4" style="margin-bottom: 0;">
      <v-col cols="8">
        <h2>Chats</h2>
      </v-col>
      <v-col cols="4" class="d-flex justify-end">
        <v-btn @click="openNewChatDialog" variant="text">
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
import NewChatDialog from '@/components/NewChatDialog.vue';
import { useToast } from '@/composables/useToast';
import type { ChatWithLastMessage } from '@/types/types';

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
  emits: ['chatSelected'],  setup(props, { emit }) {
    const chats = ref<ChatWithLastMessage[]>([]);
    const currentChat = ref<ChatWithLastMessage | null>(null);
    const isNewChatDialogOpen = ref(false);

    const { showToast } = useToast();    
    
    const fetchChats = async () => {
      try {
        const response = await axios.get<ChatWithLastMessage[]>('/api/chats', {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          },
        });
        chats.value = response.data;
      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Erro ao buscar chats';
        showToast(errorMsg);
      }
    };    const selectChat = (chat: ChatWithLastMessage | null) => {
      currentChat.value = chat;
      emit('chatSelected', chat);
    };

    const deleteChat = async (chatId: number) => {
      try {
        await axios.delete(`/api/chat/${chatId}`);
        chats.value = chats.value.filter(chat => chat.id !== chatId);

        if (currentChat.value?.id === chatId) {
          selectChat(null);
        }      } catch (error: any) {
        const errorMsg = error.response?.data?.message || 'Ocorreu um erro ao excluir o chat.';
        showToast(errorMsg);
      }
    };

    const openNewChatDialog = () => {
      isNewChatDialogOpen.value = true;
    };    
    
    const handleChatCreated = (newChat: ChatWithLastMessage) => {
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
    if (this.currentChat && this.lastMessage !== undefined) {
      const chat = this.chats.find(chat => chat.id === this.currentChat?.id);
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

@media (max-width: 600px) {
  .chat-list-container {
    height: 100vh;
    padding: 8px;
  }

  .chat-list {
    margin: 0;
  }

  h2 {
    font-size: 20px;
  }
}
</style>