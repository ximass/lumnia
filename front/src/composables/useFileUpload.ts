import { ref } from 'vue'
import axios from 'axios'
import { useToast } from './useToast'

export interface UploadProgress {
  fileName: string
  percentage: number
  status: string
}

export const useFileUpload = () => {
  const { showToast } = useToast()
  const uploadProgress = ref<UploadProgress[]>([])
  const isUploading = ref(false)

  const validateFiles = (files: File[]): boolean => {
    const allowedTypes = ['text/plain', 'application/pdf']
    const maxSize = 10 * 1024 * 1024 // 10MB

    for (const file of files) {
      if (!allowedTypes.includes(file.type) && 
          !file.name.toLowerCase().endsWith('.txt') && 
          !file.name.toLowerCase().endsWith('.pdf')) {
        showToast(`Arquivo ${file.name} não é um formato válido (apenas .txt e .pdf)`)
        return false
      }

      if (file.size > maxSize) {
        showToast(`Arquivo ${file.name} é muito grande (máximo 10MB)`)
        return false
      }
    }

    return true
  }

  const uploadFiles = async (files: File[], knowledgeBaseId: string): Promise<boolean> => {
    if (!validateFiles(files)) {
      return false
    }

    isUploading.value = true
    uploadProgress.value = files.map(file => ({
      fileName: file.name,
      percentage: 0,
      status: 'Iniciando...'
    }))

    let allSuccessful = true

    for (let i = 0; i < files.length; i++) {
      const file = files[i]
      const progressItem = uploadProgress.value[i]

      try {
        progressItem.status = 'Enviando...'
        progressItem.percentage = 25

        const formData = new FormData()
        formData.append('file', file)
        formData.append('kb_id', knowledgeBaseId)

        await axios.post('/api/sources/upload', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          },
          onUploadProgress: (progressEvent) => {
            if (progressEvent.total) {
              progressItem.percentage = Math.round((progressEvent.loaded * 100) / progressEvent.total)
            }
          }
        })

        progressItem.status = 'Concluído'
        progressItem.percentage = 100

      } catch (error: any) {
        allSuccessful = false
        progressItem.status = 'Erro'
        progressItem.percentage = 100
        
        const errorMsg = error.response?.data?.message || `Erro ao enviar ${file.name}`
        showToast(errorMsg)
      }
    }

    isUploading.value = false
    return allSuccessful
  }

  const resetProgress = () => {
    uploadProgress.value = []
  }

  return {
    uploadProgress,
    isUploading,
    uploadFiles,
    validateFiles,
    resetProgress
  }
}
