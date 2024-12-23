<template>
  <v-container fluid class="chat-container pa-4">
    <v-card class="pa-2" style="display: flex; flex-direction: column; height: 100%;">
      <v-card-title>
        <v-text-field
          v-model="chatName"
          @blur="updateChatName"
          hide-details
          flat
          solo
          class="w-100"
          variant="underlined"
        ></v-text-field>
      </v-card-title>
      <v-card-text class="message-container ma-2" style="flex: 1; overflow-y: auto;">
        <v-list>
          <v-list-item v-for="(message, index) in messages" :key="index">
              <v-list-item-content>
                <v-list-item-title>
                  <div class="d-flex flex-column">
                    <div class="sent-message">
                      <span>{{ formatDate(message.updated_at) }}</span> - <strong>{{ message.user.name }}:</strong> {{ message.text }}
                    </div>
                    <div v-if="message.answer" class="received-message mt-2">
                      <span>{{ formatDate(message.updated_at) }}</span> - <strong>Admin:</strong> {{ message.answer }}
                    </div>
                  </div>
                </v-list-item-title>
              </v-list-item-content>
            </v-list-item>
        </v-list>
      </v-card-text>
      <v-card-actions class="message-input pa-2" style="position: sticky; bottom: 0;">
        <v-textarea
          v-model="newMessage"
          label="Digite sua mensagem"
          @keyup.enter="handleSendMessage"
          append-icon=""
          hide-details
          class="w-100"
        ></v-textarea>
      </v-card-actions>
    </v-card>
  </v-container>
</template>

<script lang="ts">
import { defineComponent, ref, watch } from 'vue';
import axios from 'axios';
import { format } from 'date-fns';

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
  emits: ['sendMessage', 'updateChatName'],
  setup(props, { emit }) {
    const newMessage = ref('');
    const chatName = ref(props.currentChat.name);

    watch(() => props.currentChat, (newChat) => {
      chatName.value = newChat.name;
    });

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
        updated_at: new Date().toISOString(),
      });

      emit('sendMessage', newMessage.value);
      newMessage.value = '';
    };

    const updateChatName = async () => {
      try {
        await axios.put(`api/chat/${props.currentChat.id}`, {
          name: chatName.value,
        });
        emit('updateChatName', chatName.value);
      } catch (err) {
        // Handle error
      }
    };

    return {
      newMessage,
      handleSendMessage,
      chatName,
      updateChatName,
    };
  },
  methods: {
    formatDate(dateString: string): string {
      return format(new Date(dateString), 'dd/MM HH:mm');
    }
  },
});
</script>

<style scoped>
.chat-container {
  height: 100vh;
  display: flex;
  flex-direction: column;
  padding: 16px;
}

.pa-4 {
  padding: 16px;
}

.pa-2 {
  padding: 8px;
}

.ma-2 {
  margin: 8px;
}

.message-container {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.message-input {
  position: sticky;
  bottom: 0;
  background-color: var(--vt-c-white-soft);
}

.v-card {
  height: 100%;
}

.v-text-field {
  background-color: var(--vt-c-white);
  border-radius: 4px;
}

.sent-message {
  align-self: flex-end;
  background-color: #e0f7fa;
  padding: 8px;
  border-radius: 8px;
  max-width: 70%;
  text-align: right;
}

.received-message {
  align-self: flex-start;
  background-color: #f1f8e9;
  padding: 8px;
  border-radius: 8px;
  max-width: 70%;
  text-align: left;
}
</style>