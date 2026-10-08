import { useState } from "react";
import { ArrowLeft, ArrowRight, ShoppingCart, Trash } from "@phosphor-icons/react";
import { AuthLayout, PasswordField, TextField } from "../components/shared/AuthComponents";
import { Breadcrumb, InnerPageHeader } from "../components/shared/InnerPageComponents";
import { QuantitySelector } from "../components/shared/PhaseThreeComponents";
import { safeRedirectTarget } from "../services/authNavigation.js";
import { authService } from "../services/authService.js";
import { shippingService } from "../services/shippingService.js";
import { useAuth } from "../state/AuthContext.jsx";
import { useCart } from "../state/CartContext";

const AuthPage = ({ children }) => <div className="inner-page"><div className="container auth-page-container">{children}</div></div>;

function useValidation() {
  const [errors, setErrors] = useState({});
  const [status, setStatus] = useState("");
  return {
    errors, status, setStatus, setErrors,
    validate(values, rules) {
      const next = {};
      Object.entries(rules).forEach(([key, rule]) => { const message = rule(values[key], values); if (message) next[key] = message; });
      setErrors(next);
      return !Object.keys(next).length;
    },
    applyApiError(error) {
      const next = {};
      Object.entries(error.errors || {}).forEach(([key, messages]) => { next[key === "password_confirmation" ? "confirm" : key] = Array.isArray(messages) ? messages[0] : messages; });
      setErrors(next);
      setStatus(error.status === 429 ? "Too many attempts. Please wait and try again." : error.message);
    },
  };
}

function intendedRedirect() {
  return safeRedirectTarget(new URLSearchParams(location.search).get("redirect"), "/account");
}

export function LoginPage() {
  const v = useValidation();
  const { login } = useAuth();
  const [pending, setPending] = useState(false);
  const redirect = intendedRedirect();
  const resetSuccess = new URLSearchParams(location.search).get("reset") === "success";
  async function submit(event) {
    event.preventDefault();
    const values = Object.fromEntries(new FormData(event.currentTarget));
    if (!v.validate(values, { identity: x => x?.trim() ? "" : "Email or mobile is required.", password: x => x ? "" : "Password is required." })) return;
    setPending(true); v.setStatus("");
    try { await login({ identity: values.identity, password: values.password, remember: Boolean(values.remember) }); location.assign(redirect); }
    catch (error) { v.applyApiError(error); }
    finally { setPending(false); }
  }
  return <AuthPage><Breadcrumb items={[{label:"Home",href:"/"},{label:"Login"}]}/><AuthLayout title="Welcome back" description="Sign in to your E4ENGINEERS account to manage orders, access purchased resources and continue learning."><form className="auth-form" onSubmit={submit} noValidate><TextField label="Email / Mobile" name="identity" autoComplete="username" error={v.errors.identity||v.errors.login}/><PasswordField label="Password" name="password" error={v.errors.password}/><div className="auth-options"><label><input type="checkbox" name="remember"/> Remember me</label><a href="/forgot-password">Forgot Password?</a></div><button className="button button--primary" type="submit" disabled={pending}>{pending?"Signing in...":"Login"} <ArrowRight/></button>{resetSuccess&&<p className="form-status" role="status">Password reset successfully. You can now sign in.</p>}{v.status&&<p className="form-status" role="status">{v.status}</p>}<p className="auth-switch">New to E4ENGINEERS? <a href={`/register?redirect=${encodeURIComponent(redirect)}`}>Create an account</a></p></form></AuthLayout></AuthPage>;
}

export function RegisterPage() {
  const v = useValidation();
  const { register } = useAuth();
  const [pending, setPending] = useState(false);
  const redirect = intendedRedirect();
  const referralCode = new URLSearchParams(location.search).get("ref") || "";
  async function submit(event) {
    event.preventDefault();
    const values = Object.fromEntries(new FormData(event.currentTarget));
    if (!v.validate(values, { name:x=>x?.trim()?"":"Full name is required.",email:x=>/^\S+@\S+\.\S+$/.test(x||"")?"":"Enter a valid email.",mobile:x=>/^\d{10}$/.test(x||"")?"":"Enter a valid 10-digit mobile number.",password:x=>(x||"").length>=8?"":"Use at least 8 characters.",confirm:(x,a)=>x===a.password?"":"Passwords must match.",terms:x=>x?"":"You must accept the terms." })) return;
    setPending(true); v.setStatus("");
    try { await register({ name:values.name,email:values.email,mobile:values.mobile,password:values.password,passwordConfirmation:values.confirm,referralCode:values.referral_code }); location.assign(redirect); }
    catch (error) { v.applyApiError(error); }
    finally { setPending(false); }
  }
  return <AuthPage><Breadcrumb items={[{label:"Home",href:"/"},{label:"Register"}]}/><AuthLayout title="Create your E4ENGINEERS account" description="Create an account to purchase books, access digital resources and manage your orders."><form className="auth-form" onSubmit={submit} noValidate><TextField label="Full Name" name="name" autoComplete="name" error={v.errors.name}/><TextField label="Email" name="email" type="email" autoComplete="email" error={v.errors.email}/><TextField label="Mobile Number" name="mobile" autoComplete="tel" error={v.errors.mobile}/><TextField label="Referral code (optional)" name="referral_code" defaultValue={referralCode} error={v.errors.referral_code}/><PasswordField label="Password" name="password" autoComplete="new-password" error={v.errors.password}/><small className="password-guidance">Minimum 8 characters</small><PasswordField label="Confirm Password" name="confirm" autoComplete="new-password" error={v.errors.confirm}/><label className="terms-field"><input type="checkbox" name="terms"/><span>I agree to the <a href="/terms">Terms of Use</a> and <a href="/privacy-policy">Privacy Policy</a>.</span></label>{v.errors.terms&&<small className="field-error">{v.errors.terms}</small>}<button className="button button--primary" type="submit" disabled={pending}>{pending?"Creating account...":"Create Account"} <ArrowRight/></button>{v.status&&<p className="form-status" role="status">{v.status}</p>}<p className="auth-switch">Already have an account? <a href={`/login?redirect=${encodeURIComponent(redirect)}`}>Sign in</a></p></form></AuthLayout></AuthPage>;
}

export function ForgotPasswordPage() {
  const v = useValidation(); const [pending,setPending]=useState(false);
  async function submit(event){event.preventDefault();const values=Object.fromEntries(new FormData(event.currentTarget));if(!v.validate(values,{email:x=>/^\S+@\S+\.\S+$/.test(x||"")?"":"Enter a valid registered email."}))return;setPending(true);v.setStatus("");try{const response=await authService.forgotPassword(values.email);v.setStatus(response.message)}catch(error){v.applyApiError(error)}finally{setPending(false)}}
  return <AuthPage><Breadcrumb items={[{label:"Home",href:"/"},{label:"Forgot Password"}]}/><AuthLayout title="Forgot your password?" description="Enter your registered email address and we’ll help you reset your password."><form className="auth-form" onSubmit={submit} noValidate><TextField label="Email Address" name="email" type="email" autoComplete="email" error={v.errors.email}/><button className="button button--primary" type="submit" disabled={pending}>{pending?"Sending...":"Send Reset Link"} <ArrowRight/></button>{v.status&&<p className="form-status" role="status">{v.status}</p>}<a className="auth-back" href="/login"><ArrowLeft/> Back to Login</a></form></AuthLayout></AuthPage>;
}

export function ResetPasswordPage() {
  const v=useValidation();const[pending,setPending]=useState(false);const params=new URLSearchParams(location.search);const token=params.get("token")||"";const email=params.get("email")||"";
  async function submit(event){event.preventDefault();const values=Object.fromEntries(new FormData(event.currentTarget));if(!token||!email){v.setStatus("This password reset link is incomplete.");return}if(!v.validate(values,{password:x=>(x||"").length>=8?"":"Use at least 8 characters.",confirm:(x,a)=>x===a.password?"":"Passwords must match."}))return;setPending(true);v.setStatus("");try{await authService.resetPassword({email,token,password:values.password,passwordConfirmation:values.confirm});location.assign("/login?reset=success")}catch(error){v.applyApiError(error)}finally{setPending(false)}}
  return <AuthPage><Breadcrumb items={[{label:"Home",href:"/"},{label:"Reset Password"}]}/><AuthLayout title="Reset password" description="Create a new secure password for your E4ENGINEERS account."><form className="auth-form" onSubmit={submit} noValidate><PasswordField label="New Password" name="password" autoComplete="new-password" error={v.errors.password}/><PasswordField label="Confirm New Password" name="confirm" autoComplete="new-password" error={v.errors.confirm}/><button className="button button--primary" type="submit" disabled={pending}>{pending?"Resetting...":"Reset Password"} <ArrowRight/></button>{v.status&&<p className="form-status" role="status">{v.status}</p>}<a className="auth-back" href="/login"><ArrowLeft/> Back to Login</a></form></AuthLayout></AuthPage>;
}
export function CartPage(){
  const{items,removeItem,updateQuantity,clearCart,subtotal,coupon,couponDiscount,discountedSubtotal,applyCoupon,removeCoupon,warnings}=useCart();
  const[code,setCode]=useState("");const[status,setStatus]=useState("");const[pending,setPending]=useState(false);const[postalCode,setPostalCode]=useState("");const[shipping,setShipping]=useState(null);
  async function apply(event){event.preventDefault();if(!code.trim()){setStatus("Enter a coupon code.");return}setPending(true);try{await applyCoupon(code);setCode("");setStatus("Coupon applied successfully.")}catch(error){setStatus(error.message)}finally{setPending(false)}}
  async function remove(){setPending(true);try{await removeCoupon();setStatus("Coupon removed.")}catch(error){setStatus(error.message)}finally{setPending(false)}}
  async function estimate(event){event.preventDefault();setPending(true);try{const response=await shippingService.cartQuote({postalCode});setShipping(response.data.selected_quote);setStatus("Shipping estimate updated.")}catch(error){setShipping(null);setStatus(error.message)}finally{setPending(false)}}
  return <div className="inner-page"><div className="container inner-page__container"><Breadcrumb items={[{label:"Home",href:"/"},{label:"Shopping Cart"}]}/><InnerPageHeader eyebrow="Engineering bookshop" title="Shopping Cart" description="Review your selected engineering books before checkout."/>{items.length?<div className="cart-layout"><section className="cart-items" aria-label="Cart items">{items.map(book=><article className="cart-item" key={book.id}><img src={book.image} alt={`${book.title} cover`}/><div className="cart-item-info"><small>{book.discipline||book.category}</small><h2>{book.title}</h2><p>{book.author}</p><strong>₹{book.price}</strong></div><QuantitySelector value={book.quantity} onChange={q=>updateQuantity(book.id,q)}/><div className="cart-item-total"><strong>₹{book.price*book.quantity}</strong><button type="button" onClick={()=>removeItem(book.id)}><Trash/> Remove</button></div></article>)}<div className="cart-list-actions"><a href="/books"><ArrowLeft/> Continue Shopping</a><button type="button" onClick={clearCart}>Clear Cart</button></div></section><aside className="order-summary"><h2>Order Summary</h2><dl><div><dt>Subtotal</dt><dd>₹{subtotal}</dd></div><div><dt>Coupon Discount</dt><dd>−₹{couponDiscount}</dd></div><div><dt>Shipping</dt><dd>{shipping?`₹${shipping.shipping_charge}`:"Enter PIN below"}</dd></div><div className="order-total"><dt>Estimated payable</dt><dd>₹{discountedSubtotal+Number(shipping?.shipping_charge||0)}</dd></div></dl>{coupon?<div className="applied-coupon"><strong>{coupon.code}</strong><span>{coupon.description||coupon.name}</span><button type="button" onClick={remove} disabled={pending}>Remove</button></div>:<form onSubmit={apply}><label>Coupon Code<input value={code} onChange={e=>setCode(e.target.value.toUpperCase())} placeholder="Enter coupon code"/></label><button type="submit" disabled={pending}>{pending?"Applying…":"Apply"}</button></form>}<form onSubmit={estimate}><label>Delivery PIN<input inputMode="numeric" value={postalCode} onChange={e=>setPostalCode(e.target.value.replace(/\D/g,"").slice(0,6))} placeholder="Enter PIN code"/></label><button type="submit" disabled={pending||postalCode.length!==6}>Estimate</button></form>{shipping?.estimated_delivery&&<small>Estimated delivery: {shipping.estimated_delivery}</small>}{warnings?.map((warning,i)=><p className="form-status" role="status" key={`${warning.code}-${i}`}>{warning.message}</p>)}{status&&<p className="form-status" role="status">{status}</p>}<a className="button button--primary" href="/checkout">Proceed to Checkout <ArrowRight/></a></aside></div>:<div className="empty-cart"><ShoppingCart/><h2>Your cart is empty.</h2><p>Discover engineering books for your studies and professional growth.</p><a className="button button--primary" href="/books">Browse Books <ArrowRight/></a></div>}</div></div>
}
