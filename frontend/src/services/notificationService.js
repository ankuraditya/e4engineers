import { apiRequest } from './apiClient.js';
export const notificationService={
 preferences:()=>apiRequest('/account/notification-preferences'),savePreferences:data=>apiRequest('/account/notification-preferences',{method:'PUT',csrf:true,body:data}),
 settings:()=>apiRequest('/admin/notifications/email/settings'),saveSettings:data=>apiRequest('/admin/notifications/email/settings',{method:'PUT',csrf:true,body:data}),toggle:enabled=>apiRequest('/admin/notifications/email/toggle',{method:'PATCH',csrf:true,body:{enabled}}),
 testConnection:()=>apiRequest('/admin/notifications/email/test-connection',{method:'POST',csrf:true}),sendTest:email=>apiRequest('/admin/notifications/email/send-test',{method:'POST',csrf:true,body:{email}}),
 templates:()=>apiRequest('/admin/notifications/templates'),saveTemplate:(id,data)=>apiRequest(`/admin/notifications/templates/${id}`,{method:'PUT',csrf:true,body:data}),preview:(id,context={})=>apiRequest(`/admin/notifications/templates/${id}/preview`,{method:'POST',csrf:true,body:{context}}),
 logs:()=>apiRequest('/admin/notifications/logs'),retry:id=>apiRequest(`/admin/notifications/logs/${id}/retry`,{method:'POST',csrf:true}),
};
