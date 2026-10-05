import{CheckCircle}from"@phosphor-icons/react";
export function StatusBadge({status,kind="order"}){return <span className={`status-badge status-badge--${kind} status-badge--${status.toLowerCase().replaceAll(" ","-")}`}>{status}</span>}
export function TrackingTimeline({timeline}){return <ol className="tracking-timeline">{timeline.map(([label,date,state])=><li className={state} key={label}><span>{state==='complete'?<CheckCircle weight="fill"/>:""}</span><div><strong>{label}</strong>{date&&<small>{date}</small>}</div></li>)}</ol>}
