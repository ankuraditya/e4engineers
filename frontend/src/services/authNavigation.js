export function safeRedirectTarget(value, fallback = "/account") {
  if (!value || typeof value !== "string" || !value.startsWith("/") || value.startsWith("//")) return fallback;
  try {
    const url = new URL(value, "https://e4engineers.local");
    return url.origin === "https://e4engineers.local" ? `${url.pathname}${url.search}${url.hash}` : fallback;
  } catch {
    return fallback;
  }
}

export function protectedLoginUrl(pathname, search = "") {
  const target = safeRedirectTarget(`${pathname || "/"}${search || ""}`, "/account");
  return `/login?redirect=${encodeURIComponent(target)}`;
}

export function accountDestination(isAuthenticated) {
  return isAuthenticated ? "/account" : "/login";
}
