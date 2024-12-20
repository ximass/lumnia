<template>
  <v-container fluid class="chat-list-container pa-4">
    <v-list class="chat-list">
      <v-list-item
        v-for="chat in chats"
        :key="chat.id"
        @click="selectChat(chat)"
        class="chat-item"
      >
        <v-list-item-content>
          <v-row>
            <v-col cols="10">
              <v-list-item-title>{{ chat.name }}</v-list-item-title>
              <v-list-item-subtitle>{{ chat.lastMessage }}</v-list-item-subtitle>
            </v-col>
            <v-col cols="2" class="d-flex justify-end">
              <v-list-item-action>
                <v-icon class="delete-icon">mdi-close</v-icon>
              </v-list-item-action>
            </v-col>
          </v-row>
        </v-list-item-content>
        <v-hover v-slot:default="{ isHovering }">
          <v-list-item-action>
            <v-icon
              v-if="isHovering"
              @click.stop="deleteChat(chat.id)"
              class="delete-icon"
            >
              mdi-close
            </v-icon>
          </v-list-item-action>
        </v-hover>
      </v-list-item>
    </v-list>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import axios from 'axios';
import echo from '@/plugins/echo';

export default defineComponent({
  name: 'ChatList',
  emits: ['chatSelected'],
  setup(props, { emit }) {
    const chats = ref<Array<{ id: number; name: string; lastMessage: string }>>([]);

    const fetchChats = async () => {
      const response = await axios.get('/api/chats', {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('authToken')}`,
        },
      });
      chats.value = response.data;
    };

    const selectChat = (chat: any) => {
      emit('chatSelected', chat);
    };

    const deleteChat = async (chatId: number) => {
      try {
        await axios.delete(`/api/chat/${chatId}`);
        chats.value = chats.value.filter(chat => chat.id !== chatId);
      } catch (error) {
        console.error('Error deleting chat:', error);
      }
    };

    onMounted(() => {
      fetchChats();
    });

    return {
      chats,
      selectChat,
      deleteChat,
    };
  },
});
</script>

<style scoped>
.chat-list-container {
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.chat-list {
  flex: 1;
  overflow-y: auto;
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
</style>