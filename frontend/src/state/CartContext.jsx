import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { cartService } from "../services/cartService.js";
import { useAuth } from "./AuthContext.jsx";
import { navigate } from "./PageLoaderContext.jsx";

const CartContext = createContext(null);
const legacyKey = "e4engineers-development-cart";
const empty = { items: [], summary: { item_count: 0, quantity_count: 0, subtotal: "0.00", coupon_discount: "0.00", discounted_subtotal: "0.00", payable_before_shipping: "0.00", currency: "INR" }, coupon: null, issues: [], warnings: [], checkout_allowed: false };
const normalize = (data = empty) => ({ ...data, items: (data.items || []).map(item => ({ ...item, id: item.book_id, cartItemId: item.cart_item_id, price: Number(item.current_price), mrp: Number(item.mrp), image: item.cover?.url || "/books/electrical-power-systems.webp", author: (item.authors || []).join(", ") })) });

export function CartProvider({ children }) {
  const { isAuthenticated, isLoading: authLoading } = useAuth();
  const [cart, setCart] = useState(empty); const [isLoading, setIsLoading] = useState(true); const [error, setError] = useState("");
  const apply = useCallback(response => { const next = normalize(response.data); setCart(next); setError(""); return next; }, []);
  const refresh = useCallback(async () => { setIsLoading(true); try { return apply(await cartService.get()); } catch (e) { setError(e.message); } finally { setIsLoading(false); } }, [apply]);

  useEffect(() => { if (authLoading) return; let active = true; (async () => { setIsLoading(true); try {
    let response = isAuthenticated && localStorage.getItem("e4engineers-guest-cart-token") ? await cartService.merge() : await cartService.get();
    const raw = localStorage.getItem(legacyKey); localStorage.removeItem(legacyKey);
    if (raw) { try { for (const item of JSON.parse(raw)) if (Number.isInteger(Number(item.id)) && Number(item.id) > 0) response = await cartService.add(Number(item.id), Math.max(1, Number(item.quantity) || 1)); } catch { /* discard malformed or unavailable legacy entries */ } }
    if (active) apply(response);
  } catch (e) { if (active) setError(e.message); } finally { if (active) setIsLoading(false); } })(); return () => { active = false; }; }, [isAuthenticated, authLoading, apply]);

  const value = useMemo(() => ({ ...cart, isLoading, error, cartCount: cart.summary.quantity_count, subtotal: Number(cart.summary.subtotal), couponDiscount: Number(cart.summary.coupon_discount), discountedSubtotal: Number(cart.summary.discounted_subtotal), refresh,
    async addItem(book, quantity = 1) { return apply(await cartService.add(Number(book.id), quantity)); },
    async addItemAndOpenCart(book, quantity = 1) {
      const next = apply(await cartService.add(Number(book.id), quantity));
      navigate("/cart");
      return next;
    },
    async removeItem(id) { const found = cart.items.find(item => item.id === id || item.cartItemId === id); return apply(await cartService.remove(found?.cartItemId ?? id)); },
    async updateQuantity(id, quantity) { const found = cart.items.find(item => item.id === id || item.cartItemId === id); return apply(await cartService.update(found?.cartItemId ?? id, quantity)); },
    async clearCart() { return apply(await cartService.clear()); },
    async applyCoupon(code) { return apply(await cartService.applyCoupon(code)); },
    async removeCoupon() { return apply(await cartService.removeCoupon()); },
  }), [cart, isLoading, error, refresh, apply]);
  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart() { const value = useContext(CartContext); if (!value) throw new Error("useCart must be used within CartProvider"); return value; }
