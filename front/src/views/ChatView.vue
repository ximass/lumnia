<template>
  <v-container fluid>
    <v-row>
      <v-col cols="3">
        <ChatList @chatSelected="handleChatSelected" />
      </v-col>
      <v-col cols="9">
        <v-list>
          <v-list-item v-for="(message, index) in messages" :key="index">
            <v-list-item-content>
              <v-list-item-title>
                <strong>{{ message.sender }}:</strong> {{ message.text }}
              </v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list>

        <v-row>
          <v-col cols="9">
            <v-text-field v-model="newMessage" label="Digite sua mensagem" @keyup.enter="sendMessage"></v-text-field>
          </v-col>
          <v-col cols="3" class="text-right">
            <v-btn color="primary" @click="sendMessage">Enviar</v-btn>
          </v-col>
        </v-row>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue';
import axios from 'axios';
import echo from '@/plugins/echo';
import ChatList from '@/components/ChatList.vue';

export default defineComponent({
  name: 'ChatView',
  components: {
    ChatList,
  },
  setup() {
    const messages = ref<Array<{ sender: string; text: string }>>([]);
    const newMessage = ref('');
    const currentChat = ref<{ id: number; name: string } | null>(null);

    const sendMessage = async () => {
      if (newMessage.value.trim() === '' || !currentChat.value) return;

      messages.value.push({
        sender: 'Você',
        text: newMessage.value,
      });

      await axios.post(`http://127.0.0.1:8000/api/chats/${currentChat.value.id}/messages`, {
        text: newMessage.value,
      });

      newMessage.value = '';
    };

    const handleChatSelected = (chat: any) => {
      currentChat.value = chat;
      loadMessages();
    };

    const loadMessages = async () => {
      if (!currentChat.value) return;
      const response = await axios.get(`http://127.0.0.1:8000/api/chats/${currentChat.value.id}/messages`);
      messages.value = response.data;
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
      newMessage,
      sendMessage,
      handleChatSelected,
    };
  },
});
</script>