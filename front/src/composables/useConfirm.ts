import { ref } from 'vue';

export function useConfirm() {
  const isConfirmDialogOpen = ref(false);
  const confirmTitle = ref('Confirmar');
  const confirmMessage = ref('Tem certeza que deseja continuar?');
  const confirmCallback = ref<(() => void) | null>(null);

  const openConfirm = (
    message: string,
    callback: () => void,
    title: string = 'Confirmar'
  ) => {
    confirmTitle.value = title;
    confirmMessage.value = message;
    confirmCallback.value = callback;
    isConfirmDialogOpen.value = true;
  };

  const closeConfirm = () => {
    isConfirmDialogOpen.value = false;
    confirmCallback.value = null;
  };

  const handleConfirm = () => {
    if (confirmCallback.value) {
      confirmCallback.value();
    }
    closeConfirm();
  };

  return {
    isConfirmDialogOpen,
    confirmTitle,
    confirmMessage,
    openConfirm,
    closeConfirm,
    handleConfirm,
  };
}
