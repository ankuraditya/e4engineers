import { useEffect, useState } from "react";
import { ArrowRight, DownloadSimple, FileText } from "@phosphor-icons/react";
import { AccountLayout, ProtectedRoute } from "../components/shared/AccountComponents";
import { Breadcrumb, EmptyState } from "../components/shared/InnerPageComponents";
import { StatusBadge } from "../components/shared/OrderComponents";
import { digitalAccessService } from "../services/digitalAccessService";

const Page = ({ children }) => <ProtectedRoute><div className="inner-page"><div className="container inner-page__container">{children}</div></div></ProtectedRoute>;
const Filters = ({ value, onChange }) => <div className="account-filters">{["all", "resource", "publication"].map((item) => <button aria-pressed={value === item} onClick={() => onChange(item)} key={item}>{item === "all" ? "All" : `${item[0].toUpperCase()}${item.slice(1)}s`}</button>)}</div>;

export function DigitalResourcesPage() {
  const [type, setType] = useState("all"); const [items, setItems] = useState([]); const [error, setError] = useState(""); const [loading, setLoading] = useState(true);
  useEffect(() => { setLoading(true); digitalAccessService.getLibrary({ type: type === "all" ? "" : type }).then((result) => setItems(result.data || [])).catch((reason) => setError(reason.message)).finally(() => setLoading(false)); }, [type]);
  return <Page><Breadcrumb items={[{ label: "Home", href: "/" }, { label: "My Account", href: "/account" }, { label: "Digital Resources" }]}/><AccountLayout title="My Digital Resources" description="Access your entitled E4ENGINEERS digital learning resources."><Filters value={type} onChange={setType}/>{loading ? <p role="status">Loading your digital library…</p> : error ? <EmptyState title="Digital library unavailable" message={error}/> : items.length ? <div className="entitlement-grid">{items.map((item) => <article key={item.entitlement_id}>{item.image && <img src={item.image} alt=""/>}<div><small>{item.type} · {item.discipline}</small><h3>{item.title}</h3><p>Access granted {new Date(item.access_granted_at).toLocaleDateString()}</p><StatusBadge status={item.access_status}/><a href={`/${item.content_type}s/${item.slug}`}>View {item.content_type} <ArrowRight/></a></div></article>)}</div> : <EmptyState title="No entitled resources" message="Paid and manually granted resources will appear here."/>}</AccountLayout></Page>;
}

export function DownloadsPage() {
  const [items, setItems] = useState([]); const [status, setStatus] = useState(""); const [error, setError] = useState("");
  useEffect(() => { digitalAccessService.getDownloads().then((result) => setItems(result.data || [])).catch((reason) => setError(reason.message)); }, []);
  async function download(item) { setStatus("Preparing secure download…"); try { await digitalAccessService.download(item.content_type, item.slug, item.title); setStatus("Download authorized."); } catch (reason) { setStatus(reason.message); } }
  return <Page><Breadcrumb items={[{ label: "Home", href: "/" }, { label: "My Account", href: "/account" }, { label: "Downloads" }]}/><AccountLayout title="My Downloads" description="Download the engineering resources currently available to your account.">{status && <p className="form-status" role="status">{status}</p>}{error ? <EmptyState title="Downloads unavailable" message={error}/> : items.length ? <div className="downloads-list">{items.map((item) => <article key={item.entitlement_id}><FileText/><div><small>{item.type} · {item.discipline}</small><h3>{item.title}</h3><p>{item.download_count} previous downloads</p><span>Last downloaded: {item.last_downloaded_at ? new Date(item.last_downloaded_at).toLocaleString() : "Not downloaded"}</span></div><button onClick={() => download(item)}><DownloadSimple/>Download</button></article>)}</div> : <EmptyState title="No downloads available" message="Your downloadable entitled content will appear here."/>}</AccountLayout></Page>;
}
