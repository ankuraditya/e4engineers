import { apiRequest } from "./apiClient.js";

export const authService = {
  async login({ identity, password, remember = false }) {
    return apiRequest("/auth/login", { method: "POST", csrf: true, body: { login: identity, password, remember } });
  },
  async register({ name, email, mobile, password, passwordConfirmation }) {
    return apiRequest("/auth/register", { method: "POST", csrf: true, body: { name, email, mobile, password, password_confirmation: passwordConfirmation } });
  },
  async logout() {
    return apiRequest("/auth/logout", { method: "POST", csrf: true });
  },
  async getCurrentUser() {
    return apiRequest("/auth/me");
  },
  async forgotPassword(email) {
    return apiRequest("/auth/forgot-password", { method: "POST", csrf: true, body: { email } });
  },
  async resetPassword({ email, token, password, passwordConfirmation }) {
    return apiRequest("/auth/reset-password", { method: "POST", csrf: true, body: { email, token, password, password_confirmation: passwordConfirmation } });
  },
};
