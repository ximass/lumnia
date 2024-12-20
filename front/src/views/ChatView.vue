<template>
  <v-container fluid>
    <v-row>
      <v-col cols="3">
        <ChatList @chatSelected="handleChatSelected" />
      </v-col>
      <v-col cols="9">
        <Chat :messages="messages" :currentChat="currentChat" @sendMessage="sendMessage" />
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
    const messages = ref<Array<{ sender: string; text: string }>>([]);
    const currentChat = ref<{ id: number; name: string } | null>(null);

    const handleChatSelected = (chat: any) => {
      currentChat.value = chat;
      loadMessages();
    };

    const loadMessages = async () => {
      if (!currentChat.value) return;
      const response = await axios.get(`api/chats/${currentChat.value.id}/messages`);
      messages.value = response.data;
    };

    const sendMessage = async (text: string) => {
      if (!currentChat.value) return;
      messages.value.push({
        sender: 'Você',
        text,
      });

      await axios.post(`api/chats/${currentChat.value.id}/messages`, {
        text,
      });
    };

    onMounted(() => {
      echo.channel('chat')
        .listen('.MessageSent', (e: any) => {
          if (currentChat.value && e.chat_id === currentChat.value.id) {
            messages.value.push({
              sender: e.sender,
              text: e.message,
            });
          }
        });
    });

    return {
      messages,
      currentChat,
      handleChatSelected,
      sendMessage,
    };
  },
});
</script>