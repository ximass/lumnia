import { ref } from 'vue';

const isToastVisible = ref(false);
const toastMessage = ref('');

export function useToast() {
  function showToast(message: string, timeout = 3000) {
    toastMessage.value = message;
    isToastVisible.value = true;
    setTimeout(() => {
      isToastVisible.value = false;
    }, timeout);
  }

  return {
    isToastVisible,
    toastMessage,
    showToast,
  };
}