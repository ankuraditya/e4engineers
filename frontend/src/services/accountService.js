import { apiRequest } from "./apiClient.js";
import { notificationService } from "./notificationService.js";
const settingsKey="e4engineers-development-preferences";
export const defaultPreferences={emailUpdates:true,orderUpdates:true,paymentUpdates:true,shippingUpdates:true,resourceUpdates:true,promotionalCommunications:false};
export const accountService={
 getProfile:()=>apiRequest('/account/profile'),
 updateProfile:profile=>apiRequest('/account/profile',{method:'PATCH',body:profile,csrf:true}),
 getAddresses:()=>apiRequest('/account/addresses'),
 createAddress:address=>apiRequest('/account/addresses',{method:'POST',body:toApiAddress(address),csrf:true}),
 updateAddress:(id,address)=>apiRequest(`/account/addresses/${id}`,{method:'PATCH',body:toApiAddress(address),csrf:true}),
 deleteAddress:id=>apiRequest(`/account/addresses/${id}`,{method:'DELETE',csrf:true}),
 setDefaultAddress:id=>apiRequest(`/account/addresses/${id}/default`,{method:'PATCH',body:{},csrf:true}),
 async readPreferences(){const response=await notificationService.preferences();const p=response.data;return{...defaultPreferences,emailUpdates:p.email_enabled,orderUpdates:p.order_updates,paymentUpdates:p.payment_updates,shippingUpdates:p.shipping_updates,resourceUpdates:p.learning_updates,promotionalCommunications:p.promotional_communications}},
 async savePreferences(preferences){await notificationService.savePreferences({email_enabled:preferences.emailUpdates,order_updates:preferences.orderUpdates,payment_updates:preferences.paymentUpdates??true,shipping_updates:preferences.shippingUpdates??true,learning_updates:preferences.resourceUpdates,promotional_communications:preferences.promotionalCommunications});return{ok:true,message:"Notification preferences saved."}},
 changePassword:({currentPassword,newPassword})=>apiRequest('/account/password',{method:'PUT',csrf:true,body:{current_password:currentPassword,password:newPassword,password_confirmation:newPassword}}),
};
export const fromApiAddress=a=>({...a,name:a.full_name,line1:a.address_line_1,line2:a.address_line_2||'',pin:a.postal_code,country:a.country_code,isDefault:a.is_default,type:a.type?`${a.type[0].toUpperCase()}${a.type.slice(1)}`:'Home'});
const toApiAddress=a=>({type:(a.type||'Home').toLowerCase(),full_name:a.name||a.full_name,mobile:a.mobile,address_line_1:a.line1||a.address_line_1,address_line_2:a.line2||a.address_line_2||null,landmark:a.landmark||null,city:a.city,state:a.state,postal_code:a.pin||a.postal_code,country_code:'IN',is_default:Boolean(a.isDefault??a.is_default)});
