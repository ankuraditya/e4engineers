import { apiRequest } from "./apiClient";
const request=(path,options)=>apiRequest(`/admin${path}`,options);
export const adminOperationsService={
 enquiries:()=>request('/enquiries?per_page=100'), enquiry:id=>request(`/enquiries/${id}`), updateEnquiry:(id,body)=>request(`/enquiries/${id}`,{method:'PATCH',body,csrf:true}), noteEnquiry:(id,note)=>request(`/enquiries/${id}/notes`,{method:'POST',body:{note},csrf:true}),
 tickets:()=>request('/support-tickets?per_page=100'), ticket:id=>request(`/support-tickets/${id}`), updateTicket:(id,body)=>request(`/support-tickets/${id}`,{method:'PATCH',body,csrf:true}), replyTicket:(id,message,is_internal=false)=>request(`/support-tickets/${id}/messages`,{method:'POST',body:{message,is_internal},csrf:true}),
 workshops:()=>request('/workshops?per_page=100'), saveWorkshop:(body,id)=>request(id?`/workshops/${id}`:'/workshops',{method:id?'PUT':'POST',body,csrf:true}), deleteWorkshop:id=>request(`/workshops/${id}`,{method:'DELETE',csrf:true}),
 jobs:()=>request('/career-openings?per_page=100'), saveJob:(body,id)=>request(id?`/career-openings/${id}`:'/career-openings',{method:id?'PUT':'POST',body,csrf:true}), deleteJob:id=>request(`/career-openings/${id}`,{method:'DELETE',csrf:true}),
 applications:()=>request('/career-applications'), updateApplication:(id,body)=>request(`/career-applications/${id}`,{method:'PATCH',body,csrf:true}),
};
