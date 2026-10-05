import { createContext, useContext, useEffect, useMemo, useState } from "react";
import { authService } from "../services/authService.js";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [authError, setAuthError] = useState("");

  async function refreshUser() {
    setIsLoading(true);
    try {
      const response = await authService.getCurrentUser();
      setUser(response.data.user);
      setAuthError("");
    } catch (error) {
      setUser(null);
      if (error.status && error.status !== 401) setAuthError(error.message);
    } finally {
      setIsLoading(false);
    }
  }

  useEffect(() => { refreshUser(); }, []);

  const value = useMemo(() => ({
    user,
    isAuthenticated: Boolean(user),
    isLoading,
    authError,
    async login(values) { const response = await authService.login(values); setUser(response.data.user); setAuthError(""); return response; },
    async register(values) { const response = await authService.register(values); setUser(response.data.user); setAuthError(""); return response; },
    async logout() { await authService.logout(); setUser(null); setAuthError(""); },
    refreshUser,
  }), [user, isLoading, authError]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const value = useContext(AuthContext);
  if (!value) throw new Error("useAuth must be used within AuthProvider");
  return value;
}
