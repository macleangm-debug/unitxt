import { createContext, useContext, useEffect, useState, useCallback } from "react";
import http from "@/lib/api";

const AuthCtx = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null); // null=loading, false=guest, object=user
  const [wallet, setWallet] = useState(null);

  const refresh = useCallback(async () => {
    try {
      const { data } = await http.get("/auth/me");
      setUser(data.user);
      setWallet(data.wallet);
    } catch {
      setUser(false);
      setWallet(null);
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);

  const login = async (email, password) => {
    const { data } = await http.post("/auth/login", { email, password });
    setUser(data.user);
    await refresh();
    return data.user;
  };

  const register = async (payload) => {
    const { data } = await http.post("/auth/register", payload);
    setUser(data.user);
    await refresh();
    return data.user;
  };

  const logout = async () => {
    try {
      await http.post("/auth/logout");
    } catch {
      /* ignore */
    }
    setUser(false);
    setWallet(null);
  };

  return (
    <AuthCtx.Provider value={{ user, wallet, login, register, logout, refresh }}>
      {children}
    </AuthCtx.Provider>
  );
}

export const useAuth = () => useContext(AuthCtx);
