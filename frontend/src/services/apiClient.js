const configuredBaseUrl = import.meta.env?.VITE_API_BASE_URL;
const localApiBaseUrl = typeof window !== "undefined" && ["localhost", "127.0.0.1"].includes(window.location.hostname)
  ? `${window.location.protocol}//${window.location.hostname}:8000/api/v1`
  : typeof window !== "undefined" ? `${window.location.origin}/api/v1` : "http://localhost:8000/api/v1";
export const API_BASE_URL = (configuredBaseUrl || localApiBaseUrl).replace(/\/$/, "");
const API_ORIGIN = new URL(API_BASE_URL).origin;
const publicResponseCache = new Map();
const pendingPublicRequests = new Map();
const PUBLIC_CACHE_TTL_MS = 15000;
const cacheablePublicPath = /^\/(?:articles|books|courses|publications|resources|contributors|engineering-disciplines|categories|topics|course-levels|publication-types|resource-types|pages|banners|settings\/public|notices|gallery|videos|workshops|careers)(?:[/?]|$)/;

export class ApiError extends Error {
  constructor(message, status = 0, errors = {}, code = "") {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.errors = errors;
    this.code = code;
  }
}

function readCookie(name) {
  if (typeof document === "undefined") return "";
  const entry = document.cookie.split("; ").find((cookie) => cookie.startsWith(`${name}=`));
  return entry ? decodeURIComponent(entry.slice(name.length + 1)) : "";
}

export async function initializeCsrf() {
  const response = await fetch(`${API_ORIGIN}/sanctum/csrf-cookie`, {
    credentials: "include",
    headers: { Accept: "application/json" },
  });
  if (!response.ok) throw new ApiError("Unable to initialize a secure session.", response.status);
}

export function apiRequest(path, options = {}) {
  const method = (options.method || "GET").toUpperCase();
  if (method !== "GET") publicResponseCache.clear();
  const cacheable = method === "GET" && !options.body && !options.csrf && !Object.keys(options.headers || {}).length && cacheablePublicPath.test(path);
  if (!cacheable) return performRequest(path, options);

  const cached = publicResponseCache.get(path);
  if (cached && cached.expiresAt > Date.now()) return Promise.resolve(cached.payload);
  if (pendingPublicRequests.has(path)) return pendingPublicRequests.get(path);

  const request = performRequest(path, options).then((payload) => {
    publicResponseCache.set(path, { payload, expiresAt: Date.now() + PUBLIC_CACHE_TTL_MS });
    return payload;
  }).finally(() => pendingPublicRequests.delete(path));
  pendingPublicRequests.set(path, request);
  return request;
}

async function performRequest(path, { method = "GET", body, csrf = false, headers: extraHeaders = {} } = {}) {
  if (csrf) await initializeCsrf();
  const headers = { Accept: "application/json" };
  const token = readCookie("XSRF-TOKEN");
  const isFormData = typeof FormData !== "undefined" && body instanceof FormData;
  if (body !== undefined && !isFormData) headers["Content-Type"] = "application/json";
  if (token) headers["X-XSRF-TOKEN"] = token;
  Object.assign(headers, extraHeaders);

  let response;
  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      method,
      credentials: "include",
      headers,
      body: body === undefined ? undefined : (isFormData ? body : JSON.stringify(body)),
    });
  } catch {
    throw new ApiError("Unable to connect to E4ENGINEERS. Please try again.");
  }

  const payload = response.status === 204 ? {} : await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new ApiError(payload.message || "Unable to process your request.", response.status, payload.errors || {}, payload.code || "");
  }
  return payload;
}
