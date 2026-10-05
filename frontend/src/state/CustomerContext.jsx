import { createContext, useContext, useEffect, useMemo, useState } from "react";
import { accountService, fromApiAddress } from "../services/accountService.js";
import { useAuth } from "./AuthContext.jsx";

const CustomerContext=createContext(null);
const emptyProfile={name:"",email:"",mobile:""};
export function CustomerProvider({children}){
 const{isAuthenticated,isLoading,refreshUser}=useAuth();const[profile,setProfile]=useState(emptyProfile);const[addresses,setAddresses]=useState([]);const[loading,setLoading]=useState(false);const[error,setError]=useState("");
 async function reload(){if(!isAuthenticated)return;setLoading(true);try{const[p,a]=await Promise.all([accountService.getProfile(),accountService.getAddresses()]);setProfile(p.data);setAddresses((a.data||[]).map(fromApiAddress));setError("")}catch(e){setError(e.message)}finally{setLoading(false)}}
 useEffect(()=>{if(!isLoading){if(isAuthenticated)reload();else{setProfile(emptyProfile);setAddresses([])}}},[isAuthenticated,isLoading]);
 const value=useMemo(()=>({profile,addresses,loading,error,reload,async updateProfile(data){const r=await accountService.updateProfile(data);setProfile(r.data);await refreshUser();return r},async addAddress(data){await accountService.createAddress(data);await reload()},async updateAddress(id,data){await accountService.updateAddress(id,data);await reload()},async removeAddress(id){await accountService.deleteAddress(id);await reload()},async setDefault(id){await accountService.setDefaultAddress(id);await reload()}}),[profile,addresses,loading,error,isAuthenticated]);
 return <CustomerContext.Provider value={value}>{children}</CustomerContext.Provider>;
}
export function useCustomer(){const value=useContext(CustomerContext);if(!value)throw new Error("useCustomer must be used within CustomerProvider");return value;}
