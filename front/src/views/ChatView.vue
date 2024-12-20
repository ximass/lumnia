<template>
  <v-container fluid>
    <v-row>
      <v-col cols="3">
        <ChatList @chatSelected="handleChatSelected" />
      </v-col>
      <v-col cols="9">
        <Chat :messages="messages" :currentChat="currentChat" />
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import axios from 'axios';
import echo from '@/plugins/echo';
import ChatList from '@/components/ChatList.vue';
import Chat from '@/components/Chat.vue';

export default defineComponent({
  name: 'ChatView',
  components: {
    ChatList,
    Chat
  },
  setup() {
    const messages = ref<Array<{ sender: object; text: string }>>([]);
    const currentChat = ref<{ id: number; name: string } | null>(null);
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    const handleChatSelected = (chat: any) => {
      currentChat.value = chat;
      loadMessages();
    };

    const loadMessages = async () => {
      if (!currentChat.value) return;

      const response = await axios.get(`api/chats/${currentChat.value.id}/messages`);
      messages.value = response.data;
    };

    

    onMounted(() => {
      echo.channel('chat')
        .listen('.MessageSent', (e: any) => {
          if (currentChat.value && e.chat_id === currentChat.value.id) {
            messages.value.push({
              sender: e.user,
              text: e.message,
            });
          }
        });
    });

    return {
      messages,
      currentChat,
      handleChatSelected,
    };
  },
});
</script>