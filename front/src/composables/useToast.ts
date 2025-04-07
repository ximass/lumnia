import { ref } from 'vue';

const isToastVisible = ref(false);
const toastMessage = ref('');
const toastColor = ref('');

export function useToast() {
  function showToast(message: string, color = 'error', timeout = 3000) {
    toastMessage.value = message;
    toastColor.value = color;
    isToastVisible.value = true;
    setTimeout(() => {
      isToastVisible.value = false;
    }, timeout);
  }

  return {
    isToastVisible,
    toastMessage,
    toastColor,
    showToast,
  };
}