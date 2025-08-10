<template>
  <v-container fluid class="chat-container pa-4">
    <v-card class="pa-2" style="display: flex; flex-direction: column; height: 100%">
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
      <v-card-text
        id="chat-container"
        class="message-container ma-2"
        style="flex: 1; overflow-y: auto"
      >
        <v-list>
          <v-list-item v-for="(message, index) in messages" :key="index" message>
            <v-list-item-title>
              <div class="d-flex flex-column">
                <div class="sent-message">
                  <div class="message-header d-flex">
                    <div>
                      <span>{{ formatDate(message.updated_at) }}</span>
                      -
                      <strong>{{ message.user.name }}</strong>
                    </div>
                  </div>

                  <div class="message-text">{{ message.text }}</div>
                </div>

                <div v-if="message.answer" class="received-message">
                  <div v-if="message.answer" class="message-header d-flex mt-2">
                    <div>
                      <span>{{ formatDate(message.updated_at) }}</span>
                      -
                      <strong>IA</strong>
                    </div>
                    <span class="ver-fontes" @click="openInformationSources(message.id)">
                      ver fonte
                    </span>
                  </div>

                  <div class="message-text">{{ message.answer }}</div>
                </div>
              </div>
            </v-list-item-title>
          </v-list-item>
          <v-list-item v-if="isLoading" class="loading-message">
            <v-list-item-title>
              <v-progress-circular indeterminate color="primary"></v-progress-circular>
            </v-list-item-title>
          </v-list-item>
        </v-list>
      </v-card-text>
      <v-dialog v-model="isModalOpen" max-width="600px">
        <v-card>
          <v-card-title>Fonte de informações</v-card-title>
          <v-card-text>
            <div v-if="informationSources.length">
              <v-row v-for="source in informationSources" :key="source.id">
                <v-textarea readonly outlined :value="source.content" rows="auto">
                  {{ source.content }}
                </v-textarea>
              </v-row>
            </div>

            <div v-else>
              <p>Nenhuma fonte disponível.</p>
            </div>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn @click="isModalOpen = false" variant="text">Fechar</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
      <v-card-actions class="message-input pa-2" style="position: sticky; bottom: 0">
        <v-textarea
          v-model="newMessage"
          label="Digite sua mensagem"
          @keyup.enter="handleSendMessage"
          append-icon=""
          hide-details
          class="w-100"
          :disabled="isLoading"
        ></v-textarea>
      </v-card-actions>
    </v-card>
  </v-container>
</template>

<script lang="ts">
  import { defineComponent, ref, watch, nextTick, onMounted } from 'vue'
  import axios from 'axios'
  import { format } from 'date-fns'
  import { type PropType } from 'vue'
  import { useToast } from '@/composables/useToast'
  import { useAuth } from '@/composables/auth'
  import type {
    MessageWithUser,
    ChatWithLastMessage,
    InformationSource,
    SendMessageResponse,
  } from '@/types/types'

  const scrollToBottom = async () => {
    await nextTick()

    const chatContainer = document.getElementById('chat-container')

    if (chatContainer) {
      chatContainer.scrollTop = chatContainer.scrollHeight
    }
  }

  export default defineComponent({
    name: 'Chat',
    props: {
      messages: {
        type: Array as PropType<MessageWithUser[]>,
        required: false,
      },
      currentChat: {
        type: Object as PropType<ChatWithLastMessage>,
        required: true,
      },
    },
    emits: ['sendMessage', 'updateChatName'],
    setup(props, { emit }) {
      const { showToast } = useToast()

      const isLoading = ref(false)
      const isModalOpen = ref(false)

      const newMessage = ref('')
      const chatName = ref(props.currentChat.name)
      const { user } = useAuth()
      const informationSources = ref<InformationSource[]>([])

      watch(
        () => props.currentChat,
        newChat => {
          chatName.value = newChat.name
        }
      )

      const handleSendMessage = async () => {
        if (newMessage.value.trim() === '') {
          showToast('A mensagem não pode estar vazia.')
          return
        }

        isLoading.value = true

        try {
          const response = await axios.post<SendMessageResponse>(
            `api/chat/${props.currentChat.id}`,
            {
              text: newMessage.value,
            },
            {
              headers: {
                Authorization: `Bearer ${localStorage.getItem('authToken')}`,
              },
            }
          )

          if (response.data.status === 'error') {
            showToast(response.data.message || 'Erro ao enviar mensagem.')
            return
          }

          if (user.value) {
            props.messages?.push({
              id: Date.now(), // temporary ID
              chat_id: props.currentChat.id,
              user_id: user.value.id,
              user: user.value,
              text: newMessage.value,
              updated_at: new Date().toISOString(),
              answer: response.data.answer ? response.data.answer.text : '',
            })
          }

          if (response.data.status === 'partial_success') {
            showToast(
              response.data.message || 'Mensagem enviada, mas houve erro na resposta da IA.',
              'warning'
            )
          }

          emit('sendMessage', newMessage.value)
          newMessage.value = ''
        } catch (error: any) {
          let errorMsg = 'Erro ao enviar mensagem.'

          if (error.response) {
            if (error.response.status === 422) {
              const validationErrors = error.response.data.errors
              if (validationErrors && validationErrors.text) {
                errorMsg = validationErrors.text[0]
              } else {
                errorMsg = error.response.data.message || 'Dados inválidos.'
              }
            } else if (error.response.status === 500) {
              errorMsg = 'Erro interno do servidor. Tente novamente mais tarde.'
            } else {
              errorMsg = error.response.data.message || errorMsg
            }
          } else if (error.request) {
            errorMsg = 'Erro de conexão. Verifique sua internet e tente novamente.'
          }

          showToast(errorMsg, 'error')
        } finally {
          isLoading.value = false
          scrollToBottom()
        }
      }

      const updateChatName = async () => {
        try {
          await axios.put(`api/chat/${props.currentChat.id}`, {
            name: chatName.value,
          })
          emit('updateChatName', chatName.value)
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao atualizar nome do chat.'
          showToast(errorMsg)
        }
      }

      const openInformationSources = async (messageId: number) => {
        try {
          const response = await axios.get<InformationSource[]>(
            `/api/message/${messageId}/information-sources`
          )
          informationSources.value = response.data
          isModalOpen.value = true
        } catch (error: any) {
          const errorMsg = error.response?.data?.message || 'Erro ao buscar fontes.'
          showToast(errorMsg)
        }
      }

      return {
        newMessage,
        chatName,
        isLoading,
        isModalOpen,
        informationSources,
        handleSendMessage,
        updateChatName,
        openInformationSources,
      }
    },
    updated() {
      scrollToBottom()
    },
    methods: {
      formatDate(dateString: string): string {
        return format(new Date(dateString), 'dd/MM HH:mm')
      },
    },
  })
</script>

<style scoped>
  .chat-container {
    height: 90vh;
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
    max-width: 80%;
    text-align: left;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .message-header {
    justify-content: space-between;
    font-size: 12px;
    color: #666;
  }

  .sent-message .message-text {
    background-color: #e0f7fa;
    color: var(--color-message-text);
    padding: 8px;
    border-radius: 8px;
  }

  .received-message {
    align-self: flex-start;
    max-width: 80%;
    text-align: left;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  .received-message .message-text {
    background-color: #f1f8e9;
    color: var(--color-message-text);
    padding: 8px;
    border-radius: 8px;
  }

  .ver-fontes {
    cursor: pointer;
    display: none;
  }

  [message]:hover .ver-fontes {
    display: inline;
  }

  .loading-message {
    justify-content: center;
    align-items: center;
  }

  @media (max-width: 600px) {
    .chat-container {
      height: 100vh;
      padding: 8px;
    }

    .message-container {
      padding: 8px;
    }

    .v-text-field,
    .v-textarea {
      font-size: 14px;
    }
  }
</style>
