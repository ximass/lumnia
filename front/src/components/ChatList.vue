<template>
  <v-container fluid class="chat-list-container pa-4">
    <v-row align="center" class="mb-4" style="margin-bottom: 0">
      <v-col cols="8">
        <h2>Chats</h2>
      </v-col>
      <v-col cols="4" class="d-flex justify-end">
        <v-btn @click="openNewChatDialog" variant="text"><v-icon>mdi-plus</v-icon></v-btn>
      </v-col>
    </v-row>
    <v-list class="chat-list">
      <v-list-item v-for="chat in chats" :key="chat.id" @click="selectChat(chat)" class="chat-item">
        <v-row>
          <v-col cols="10">
            <v-list-item-title>{{ chat.name }}</v-list-item-title>
            <v-list-item-subtitle>{{ chat.lastMessage }}</v-list-item-subtitle>
          </v-col>
          <v-col cols="2" class="d-flex justify-end">
            <v-list-item-action>
              <v-icon class="delete-icon" @click.stop="confirmDelete(chat)">mdi-close</v-icon>
            </v-list-item-action>
          </v-col>
        </v-row>
      </v-list-item>
    </v-list>
    <NewChatDialog v-model="isNewChatDialogOpen" @chatCreated="handleChatCreated" />

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" :max-width="$vuetify.display.xs ? '90%' : '500'">
      <v-card>
        <v-card-title class="text-subtitle-1 text-sm-h5 d-flex align-center pa-4 pa-sm-6">
          <v-icon color="info" class="me-2 me-sm-3" :size="$vuetify.display.xs ? 20 : 24">mdi-alert-circle</v-icon>
          Confirmar exclusão
        </v-card-title>
        <v-card-text class="pa-4 pa-sm-6">
          <p class="text-body-2 text-sm-body-1">Tem certeza que deseja excluir o chat <strong>"{{ selectedChat?.name }}"</strong>?</p>
          <v-alert class="mb-0" :density="$vuetify.display.smAndDown ? 'compact' : 'default'">
            <strong>Atenção:</strong> Esta ação não pode ser desfeita. Todas as mensagens serão removidas permanentemente.
          </v-alert>
        </v-card-text>
        <v-card-actions class="pa-4 pa-sm-6">
          <v-spacer />
          <v-btn 
            variant="flat" 
            color="primary"
            @click="deleteDialog = false"
            :size="$vuetify.display.xs ? 'small' : 'default'"
          >
            Cancelar
          </v-btn>
          <v-btn 
            variant="outlined"
            @click="deleteChat"
            :loading="deleting"
            :size="$vuetify.display.xs ? 'small' : 'default'"
          >
            Excluir
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script lang="ts">
  import { defineComponent, ref, onMounted } from 'vue'
  import axios from 'axios'
  import NewChatDialog from '@/components/NewChatDialog.vue'
  import { useToast } from '@/composables/useToast'
  import type { ChatWithLastMessage } from '@/types/types'

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
      const chats = ref<ChatWithLastMessage[]>([])
      const currentChat = ref<ChatWithLastMessage | null>(null)
      const selectedChat = ref<ChatWithLastMessage | null>(null)
      const isNewChatDialogOpen = ref(false)
      const deleteDialog = ref(false)
      const deleting = ref(false)

      const { showToast } = useToast()

      const fetchChats = async () => {
        try {
          const response = await axios.get<ChatWithLastMessage[]>('/api/chats', {
            headers: {
              Authorization: `Bearer ${localStorage.getItem('authToken')}`,
            },
          })
          chats.value = response.data
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao buscar chats'
          showToast(errorMsg)
        }
      }
      const selectChat = (chat: ChatWithLastMessage | null) => {
        currentChat.value = chat
        emit('chatSelected', chat)
      }

      const confirmDelete = (chat: ChatWithLastMessage) => {
        selectedChat.value = chat
        deleteDialog.value = true
      }

      const deleteChat = async () => {
        if (!selectedChat.value) return

        deleting.value = true
        try {
          await axios.delete(`/api/chat/${selectedChat.value.id}`)
          chats.value = chats.value.filter(chat => chat.id !== selectedChat.value?.id)

          if (currentChat.value?.id === selectedChat.value.id) {
            selectChat(null)
          }

          showToast('Chat excluído com sucesso!', 'success')
          deleteDialog.value = false
          selectedChat.value = null
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Ocorreu um erro ao excluir o chat.'
          showToast(errorMsg)
        } finally {
          deleting.value = false
        }
      }

      const openNewChatDialog = () => {
        isNewChatDialogOpen.value = true
      }

      const handleChatCreated = (newChat: ChatWithLastMessage) => {
        chats.value = [newChat, ...chats.value]
        isNewChatDialogOpen.value = false
      }

      onMounted(() => {
        fetchChats()
      })

      return {
        chats,
        currentChat,
        selectedChat,
        selectChat,
        confirmDelete,
        deleteChat,
        isNewChatDialogOpen,
        deleteDialog,
        deleting,
        openNewChatDialog,
        handleChatCreated,
      }
    },
    updated() {
      if (this.currentChat && this.lastMessage !== undefined) {
        const chat = this.chats.find(chat => chat.id === this.currentChat?.id)
        if (chat && chat.lastMessage !== this.lastMessage) {
          chat.lastMessage = this.lastMessage
        }
      }
    },
  })
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
