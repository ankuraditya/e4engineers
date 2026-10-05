import { apiRequest } from "./apiClient";
const token=()=>crypto.randomUUID();
export const operationsService={
 contact:data=>apiRequest("/contact",{method:"POST",body:{...data,submission_token:token()},csrf:true}),
 support:form=>{form.set("submission_token",token());return apiRequest("/support",{method:"POST",body:form,csrf:true})},
 workshops:()=>apiRequest("/workshops"),
 registerWorkshop:(id,data)=>apiRequest(`/workshops/${id}/registrations`,{method:"POST",body:{...data,submission_token:token()},csrf:true}),
 careers:()=>apiRequest("/careers"),
 apply:(id,form)=>{form.set("submission_token",token());return apiRequest(`/careers/${id}/applications`,{method:"POST",body:form,csrf:true})},
};
