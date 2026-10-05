import React from "react";
import { createRoot } from "react-dom/client";
import { App } from "./App.jsx";
import "./styles.css";
import { CartProvider } from "./state/CartContext.jsx";
import { CustomerProvider } from "./state/CustomerContext.jsx";
import { PageLoaderProvider } from "./state/PageLoaderContext.jsx";
import { AuthProvider } from "./state/AuthContext.jsx";

createRoot(document.getElementById("root")).render(
  <React.StrictMode>
    <PageLoaderProvider><AuthProvider><CustomerProvider><CartProvider><App /></CartProvider></CustomerProvider></AuthProvider></PageLoaderProvider>
  </React.StrictMode>,
);
