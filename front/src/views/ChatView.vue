<template>
  <v-container fluid class="chat-view-container">
    <v-row>
      <v-col cols="12" md="3"><ChatList @chatSelected="handleChatSelected" :lastMessage /></v-col>
      <v-col cols="12" md="9" class="chat-area">
        <Chat
          v-if="currentChat"
          :messages="messages"
          :currentChat="currentChat"
          @updateChatName="handleUpdateChatName"
          @sendMessage="handleLastMessage"
        />
        <v-alert v-else type="info" class="mt-5">
          Por favor, selecione um chat para iniciar a conversa.
        </v-alert>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
  import { defineComponent, ref, onMounted } from 'vue'
  import axios from 'axios'
  import echo from '@/plugins/echo'
  import ChatList from '@/components/ChatList.vue'
  import Chat from '@/components/Chat.vue'
  import type { ChatWithLastMessage, MessageWithUser } from '@/types/types'

  export default defineComponent({
    name: 'ChatView',
    components: {
      ChatList,
      Chat,
    },
    setup() {
      const lastMessage = ref('')
      const messages = ref<MessageWithUser[]>([])
      const currentChat = ref<ChatWithLastMessage | null>(null)

      const handleChatSelected = (chat: ChatWithLastMessage | null) => {
        currentChat.value = chat
        loadMessages()
      }

      const loadMessages = async () => {
        if (!currentChat.value) return

        try {
          const response = await axios.get<MessageWithUser[]>(
            `api/chats/${currentChat.value.id}/messages`
          )
          messages.value = response.data
        } catch (error: any) {
          console.error('Erro ao carregar mensagens:', error)
        }
      }

      const handleUpdateChatName = (newName: string) => {
        if (currentChat.value) {
          currentChat.value.name = newName
        }
      }

      const handleLastMessage = (message: string) => {
        lastMessage.value = message
      }

      return {
        messages,
        currentChat,
        lastMessage,
        handleChatSelected,
        handleUpdateChatName,
        handleLastMessage,
      }
    },
  })
</script>

<style scoped>
  .chat-view-container {
    height: 100vh;
  }

  .chat-area {
    height: 100%;
    display: flex;
    flex-direction: column;
  }
</style>
