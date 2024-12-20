<template>
  <v-container fluid>
    <v-list>
      <v-list-item v-for="(message, index) in messages" :key="index">
        <v-list-item-content>
          <v-list-item-title>
            <strong>{{ message.user.name }}:</strong> {{ message.text }}
          </v-list-item-title>
        </v-list-item-content>
      </v-list-item>
    </v-list>

    <v-row>
      <v-col cols="12">
        <v-text-field v-model="newMessage" label="Digite sua mensagem" @keyup.enter="handleSendMessage"></v-text-field>
      </v-col>
      <v-col cols="12" class="text-right">
        <v-btn color="primary" @click="handleSendMessage">Enviar</v-btn>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';
import axios from 'axios';

export default defineComponent({
  name: 'Chat',
  props: {
    messages: {
      type: Array,
      required: false,
    },
    currentChat: {
      type: Object,
      required: true,
    },
  },
  emits: ['sendMessage'],
  setup(props, { emit }) {
    const newMessage = ref('');

    const handleSendMessage = () => {
      if (newMessage.value.trim() === '') return;

      axios.post(`api/chat/${props.currentChat.id}`, {
          text: newMessage.value
        },
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          }
        }
      );

      props.messages.push({
        user: JSON.parse(localStorage.getItem('user') || '{}'),
        text: newMessage.value,
      });

      emit('sendMessage', newMessage.value);
      newMessage.value = '';
    };

    return {
      newMessage,
      handleSendMessage,
    };
  },
});
</script>