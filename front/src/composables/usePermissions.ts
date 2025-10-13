import { computed } from 'vue';
import { useAuth } from './auth';
import type { Permission } from '@/types/types';

export function usePermissions() {
  const { user } = useAuth();

  const permissions = computed<Permission[]>(() => {
    if (!user.value || !user.value.groups) return [];

    const perms: Record<number, Permission> = {};
    for (const g of user.value.groups) {
      if (!g.permissions) continue;
      for (const p of g.permissions) {
        perms[p.id] = p;
      }
    }
    return Object.values(perms);
  });

  function hasPermission(name: string) {
    return permissions.value.some(p => p.name === name);
  }

  return {
    permissions,
    hasPermission,
  };
}
