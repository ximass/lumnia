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
              <span class="ml-2">Aguardando resposta...</span>
            </v-list-item-title>
          </v-list-item>
          <v-list-item v-if="currentStreamingMessage" class="streaming-message">
            <v-list-item-title>
              <div class="received-message">
                <div class="message-text">
                  {{ currentStreamingMessage }}
                  <span class="blinking-cursor">|</span>
                </div>
              </div>
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
          @keydown.enter="handleEnterKey"
          append-icon=""
          class="w-100"
          :disabled="isLoading"
          rows="1"
          auto-grow
          max-rows="4"
          persistent-hint
          hint="Enter para enviar, Shift+Enter para nova linha"
        ></v-textarea>
        <v-btn
          @click="handleSendMessage"
          :disabled="isLoading || !newMessage.trim()"
          color="primary"
          icon="mdi-send"
          class="ml-2"
        ></v-btn>
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
      const currentStreamingMessage = ref('')

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
        currentStreamingMessage.value = ''

        try {
          // Add user message immediately
          if (user.value) {
            const userMessage: MessageWithUser = {
              id: Date.now(),
              chat_id: props.currentChat.id,
              user_id: user.value.id,
              user: user.value,
              text: newMessage.value,
              updated_at: new Date().toISOString(),
              answer: '',
            }
            props.messages?.push(userMessage)
          }

          const messageText = newMessage.value
          newMessage.value = ''
          emit('sendMessage', messageText)

          // Try streaming first (if enabled by backend config)
          try {
            await fetch(`/api/chat/${props.currentChat.id}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
                'Accept': 'text/event-stream',
              },
              body: JSON.stringify({
                text: messageText,
              }),
            }).then(response => {
              // Check if response is streaming (event-stream)
              const contentType = response.headers.get('content-type')
              
              if (contentType && contentType.includes('text/event-stream')) {
                // Handle as stream
                if (!response.ok) {
                  throw new Error(`HTTP error! status: ${response.status}`)
                }

                const reader = response.body?.getReader()
                if (!reader) {
                  throw new Error('Response body is not readable')
                }

                let streamingAnswer = ''
                const decoder = new TextDecoder()

                const readStream = async () => {
                  try {
                    while (true) {
                      const { done, value } = await reader.read()
                      
                      if (done) break

                      const chunk = decoder.decode(value, { stream: true })
                      const lines = chunk.split('\n')

                      for (const line of lines) {
                        if (line.startsWith('data: ')) {
                          try {
                            const data = JSON.parse(line.slice(6))
                            
                            switch (data.type) {
                              case 'start':
                                currentStreamingMessage.value = ''
                                break

                              case 'chunk':
                                streamingAnswer += data.content
                                currentStreamingMessage.value = streamingAnswer
                                
                                // Update the last message in real-time
                                if (props.messages && props.messages.length > 0) {
                                  const lastMessage = props.messages[props.messages.length - 1]
                                  lastMessage.answer = streamingAnswer
                                }
                                scrollToBottom()
                                break

                              case 'complete':
                                currentStreamingMessage.value = ''
                                if (props.messages && props.messages.length > 0) {
                                  const lastMessage = props.messages[props.messages.length - 1]
                                  lastMessage.answer = streamingAnswer
                                  lastMessage.updated_at = data.updated_at
                                }
                                isLoading.value = false
                                return
                                
                              case 'error':
                                showToast(data.message || 'Erro ao processar mensagem', 'error')
                                isLoading.value = false
                                return
                            }
                          } catch (parseError) {
                            console.error('Error parsing stream data:', parseError)
                          }
                        }
                      }
                    }
                  } finally {
                    reader.releaseLock()
                    isLoading.value = false
                  }
                }

                readStream()
              } else {
                // Handle as regular JSON response
                return response.json().then(data => {
                  if (data.status === 'error') {
                    showToast(data.message || 'Erro ao enviar mensagem.')
                    return
                  }

                  if (props.messages && props.messages.length > 0) {
                    const lastMessage = props.messages[props.messages.length - 1]
                    lastMessage.answer = data.answer ? data.answer.text : ''
                    lastMessage.updated_at = data.answer ? data.answer.updated_at : new Date().toISOString()
                  }

                  if (data.status === 'partial_success') {
                    showToast(
                      data.message || 'Mensagem enviada, mas houve erro na resposta da IA.',
                      'warning'
                    )
                  }

                  isLoading.value = false
                })
              }
            })
          } catch (streamError) {
            console.error('Stream error:', streamError)
            throw streamError
          }

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
          isLoading.value = false
        } finally {
          scrollToBottom()
        }
      }

      const handleEnterKey = (event: KeyboardEvent) => {
        // Se Shift+Enter, permite quebra de linha
        if (event.shiftKey) {
          return
        }
        
        // Se apenas Enter, previne quebra de linha e envia mensagem
        event.preventDefault()
        handleSendMessage()
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
        currentStreamingMessage,
        handleSendMessage,
        handleEnterKey,
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

  .streaming-message {
    justify-content: flex-start;
  }

  .blinking-cursor {
    animation: blink 1s infinite;
    font-weight: bold;
  }

  @keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0; }
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
