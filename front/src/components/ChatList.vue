<template>
  <v-list nav dense>
    <v-list-item v-for="chat in chats" :key="chat.id" @click="selectChat(chat)">
      <v-list-item-content>
        <v-list-item-title>{{ chat.name }}</v-list-item-title>
        <v-list-item-subtitle>{{ chat.lastMessage }}</v-list-item-subtitle>
      </v-list-item-content>
    </v-list-item>
  </v-list>
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
      const response = await axios.get('http://127.0.0.1:8000/api/chats');
      chats.value = response.data;
    };

    const selectChat = (chat: any) => {
      emit('chatSelected', chat);
    };

    onMounted(() => {
      fetchChats();

      echo.channel('chat')
        .listen('.MessageSent', (e: any) => {
          const chat = chats.value.find((c) => c.id === e.chat_id);
          if (chat) {
            chat.lastMessage = e.message;
          }
        });
    });

    return {
      chats,
      selectChat,
    };
  },
});
</script>