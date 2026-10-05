import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from "react";
import { GlobalPageLoader } from "../components/shared/GlobalPageLoader";

const PageLoaderContext = createContext(null);
export const ROUTE_CHANGE_EVENT = "e4engineers:route-change";

export function navigate(href, { replace = false } = {}) {
  const next = new URL(href, window.location.href);
  if (next.origin !== window.location.origin) {
    window.location.assign(next.href);
    return;
  }
  const current = `${window.location.pathname}${window.location.search}`;
  const destination = `${next.pathname}${next.search}`;
  if (destination !== current) {
    window.history[replace ? "replaceState" : "pushState"]({}, "", next.href);
    window.dispatchEvent(new Event(ROUTE_CHANGE_EVENT));
    window.scrollTo(0, 0);
  } else if (next.hash) {
    window.history.pushState({}, "", next.href);
    document.getElementById(decodeURIComponent(next.hash.slice(1)))?.scrollIntoView();
  }
}

export function PageLoaderProvider({ children }) {
  const [loaderState, setLoaderState] = useState("visible");
  const hideTimer = useRef(null);
  const showLoader = useCallback(() => {
    clearTimeout(hideTimer.current);
    setLoaderState("visible");
    document.body.classList.add("page-loader-active");
  }, []);
  const hideLoader = useCallback(() => {
    clearTimeout(hideTimer.current);
    setLoaderState("exiting");
    hideTimer.current = setTimeout(() => {
      setLoaderState("hidden");
      document.body.classList.remove("page-loader-active");
    }, 180);
  }, []);
  useEffect(() => {
    document.body.classList.add("page-loader-active");
    const frame = requestAnimationFrame(hideLoader);
    function handleLink(event) {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      const link = event.target.closest?.("a[href]");
      if (!link || link.target || link.hasAttribute("download")) return;
      const next = new URL(link.href, window.location.href);
      if (next.origin !== window.location.origin) return;
      event.preventDefault();
      navigate(next.href);
    }
    document.addEventListener("click", handleLink);
    return () => {
      cancelAnimationFrame(frame);
      document.removeEventListener("click", handleLink);
      clearTimeout(hideTimer.current);
      document.body.classList.remove("page-loader-active");
    };
  }, [hideLoader]);
  const value = useMemo(() => ({ showLoader, hideLoader, isLoading: loaderState !== "hidden" }), [showLoader, hideLoader, loaderState]);
  return <PageLoaderContext.Provider value={value}>{children}{loaderState !== "hidden" && <GlobalPageLoader state={loaderState} />}</PageLoaderContext.Provider>;
}

export function usePageLoader() {
  const value = useContext(PageLoaderContext);
  if (!value) throw new Error("usePageLoader must be used within PageLoaderProvider");
  return value;
}
