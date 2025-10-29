import { computed } from 'vue'

export function useAvatar() {
  const getInitials = (name: string | undefined): string => {
    if (!name) return '?'
    
    const words = name.trim().split(' ').filter(word => word.length > 0)
    
    if (words.length === 0) return '?'
    if (words.length === 1) return words[0].charAt(0).toUpperCase()
    
    return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase()
  }

  const getAvatarUrl = (avatar: string | undefined): string | null => {
    if (!avatar || avatar.length === 0) return null
    
    return `/api/avatars/${avatar.split('/').pop()}`
  }

  const hasAvatar = (avatar: string | undefined): boolean => {
    return !!(avatar && avatar.length > 0)
  }

  return {
    getInitials,
    getAvatarUrl,
    hasAvatar,
  }
}
