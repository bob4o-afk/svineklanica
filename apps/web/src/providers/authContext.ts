import { createContext } from 'react';
import type { AdminUser } from '@/types/api';

export interface AuthContextValue {
  user: AdminUser | null;
  isAdmin: boolean;
  isLoading: boolean;
}

export const AuthContext = createContext<AuthContextValue | null>(null);
