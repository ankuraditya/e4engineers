import { useEffect, useState } from "react";
import { ArrowRight, CheckCircle, FileText } from "@phosphor-icons/react";
import { Breadcrumb, EmptyState, InnerPageHeader, InnerSection, Pagination } from "../components/shared/InnerPageComponents";
import { AccessBadge, FilterBar, FilterField, ResourceCard } from "../components/shared/PhaseTwoComponents";
import { resourcesService } from "../services/resourcesService";
import { digitalAccessService } from "../services/digitalAccessService";
import { useAuth } from "../state/AuthContext";

const Page = ({ children }) => <div className="inner-page"><div className="container inner-page__container">{children}</div></div>;
const accessLabel = (value) => value === "free" ? "Free" : value === "login_required" ? "Login Required" : "Premium";
const card = (resource) => ({ ...resource, type: resource.type?.name, discipline: resource.discipline?.name, description: resource.short_description, access: accessLabel(resource.access_type), format: resource.file_format });

export function ResourcesPage() {
  const [items, setItems] = useState([]);
  const [types, setTypes] = useState([]);
  const [disciplines, setDisciplines] = useState([]);
  const [meta, setMeta] = useState({});
  const initialType = typeof window === "undefined" ? "" : new URLSearchParams(window.location.search).get("type") || "";
  const [filters, setFilters] = useState({ search: "", type: initialType, discipline: "", access: "", sort: "latest", page: 1 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  useEffect(() => { Promise.all([resourcesService.getResourceTypes(), resourcesService.getDisciplines()]).then(([typeResult, disciplineResult]) => { setTypes(typeResult.data || []); setDisciplines(disciplineResult.data || []); }); }, []);
  useEffect(() => { let active = true; setLoading(true); setError(""); const timer = setTimeout(() => resourcesService.getResources(filters).then((result) => { if (active) { setItems(result.data || []); setMeta(result.meta || {}); } }).catch((reason) => active && setError(reason.message)).finally(() => active && setLoading(false)), 200); return () => { active = false; clearTimeout(timer); }; }, [filters]);
  const set = (key, value) => setFilters((current) => ({ ...current, [key]: value, page: key === "page" ? value : 1 }));
  const selectedType = filters.type ? types.find((type) => type.slug === filters.type)?.name : "All Resources";
  return <Page><Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Study Resources" }]}/><InnerPageHeader eyebrow="Engineering study library" title="Study Resources" description="Access engineering notes, question papers, formula sheets, diagrams and learning resources across multiple disciplines."/><div className="catalog-tabs" role="group" aria-label="Resource types">{["All Resources", ...types.slice(0, 8).map((type) => type.name)].map((name) => <button type="button" aria-pressed={selectedType === name} onClick={() => set("type", name === "All Resources" ? "" : types.find((type) => type.name === name)?.slug || "")} key={name}>{name}</button>)}</div><FilterBar><FilterField label="Search study resources" className="catalog-search"><input placeholder="Search study resources..." value={filters.search} onChange={(event) => set("search", event.target.value)}/></FilterField><FilterField label="Engineering Discipline"><select value={filters.discipline} onChange={(event) => set("discipline", event.target.value)}><option value="">All Disciplines</option>{disciplines.map((discipline) => <option value={discipline.slug} key={discipline.slug}>{discipline.name}</option>)}</select></FilterField><FilterField label="Access Type"><select value={filters.access} onChange={(event) => set("access", event.target.value)}><option value="">All</option><option value="free">Free</option><option value="login_required">Login Required</option><option value="paid">Premium</option></select></FilterField><FilterField label="Sort By"><select value={filters.sort} onChange={(event) => set("sort", event.target.value)}><option value="latest">Latest</option><option value="oldest">Oldest</option><option value="a-z">A–Z</option><option value="featured">Featured</option></select></FilterField></FilterBar>{loading ? <p role="status">Loading resources…</p> : error ? <EmptyState title="Resources unavailable" message={error}/> : items.length ? <><div className="resource-list-grid">{items.map((item) => <ResourceCard resource={card(item)} key={item.id}/>)}</div>{meta.last_page > 1 && <Pagination/>}</> : <EmptyState/>}<div className="inner-cta"><div><h2>Looking for a specific topic?</h2><p>Browse each engineering discipline and its connected learning materials.</p></div><a className="button button--primary" href="/engineering">Explore Engineering Disciplines <ArrowRight/></a></div></Page>;
}

export function ResourceDetailPage({ slug }) {
  const [data, setData] = useState(null); const [error, setError] = useState(""); const [downloadStatus, setDownloadStatus] = useState("");
  useEffect(() => { setData(null); setError(""); resourcesService.getResource(slug).then((result) => setData(result.data)).catch((reason) => setError(reason.message)); }, [slug]);
  useEffect(() => {
    if (!data?.resource) return;
    const resource = data.resource;
    document.title = resource.seo?.meta_title || `${resource.title} — E4ENGINEERS`;
    const description = resource.seo?.meta_description || resource.short_description;
    let meta = document.querySelector('meta[name="description"]');
    if (!meta) { meta = document.createElement("meta"); meta.name = "description"; document.head.append(meta); }
    if (description) meta.content = description;
  }, [data]);
  if (error) return <Page><EmptyState title="Resource unavailable" message={error}/></Page>;
  if (!data) return <Page><p role="status">Loading resource…</p></Page>;
  const resource = data.resource; const view = card(resource); const canDownload = resource.access?.can_download; const needsLogin = resource.access?.reason === "authentication_required"; const callToAction = canDownload ? "Download Resource" : needsLogin ? "Login to Access" : resource.access_type === "paid" ? `Purchase Resource${resource.price ? ` · ₹${resource.price}` : ""}` : "File unavailable"; async function accessResource() { if (needsLogin) { window.location.href = `/login?redirect=${encodeURIComponent(`/resources/${resource.slug}`)}`; return; } if (!canDownload) return; setDownloadStatus("Preparing secure download…"); try { await digitalAccessService.download("resource", resource.slug, resource.title); setDownloadStatus("Download authorized."); } catch (reason) { setDownloadStatus(reason.message); } }
  return <Page><Breadcrumb items={[{ label: "Home", href: "/" }, { label: "Study Resources", href: "/resources" }, { label: resource.title }]}/><div className="resource-detail-header"><div className="resource-document">{resource.thumbnail?.url ? <img src={resource.thumbnail.url} alt={resource.thumbnail.alt_text || ""}/> : <FileText aria-hidden="true"/>}<span>{resource.file_format || "Resource"}</span></div><div><p>{view.type} · {view.discipline}</p><h1>{resource.title}</h1><span>{resource.short_description}</span><AccessBadge type={view.access}/><button className="button button--primary" type="button" disabled={!canDownload && !needsLogin} onClick={accessResource}>{callToAction} <ArrowRight/></button>{downloadStatus && <p role="status">{downloadStatus}</p>}</div></div><dl className="resource-meta">{[["Resource Type", view.type], ["Discipline", view.discipline], ["Access", view.access], ["Format", resource.file_format || "—"], ["Pages", resource.pages || "—"], ["File size", resource.file_size_display || "—"], ["Version", resource.version || "—"]].map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}</dl><InnerSection title="About this Resource"><div className="section-copy" dangerouslySetInnerHTML={{ __html: resource.description }}/></InnerSection>{resource.tags?.length > 0 && <InnerSection title="Topics Covered"><ul className="topic-grid">{resource.tags.map((tag) => <li key={tag.slug}><CheckCircle weight="fill"/>{tag.name}</li>)}</ul></InnerSection>}<InnerSection title="Resource Preview"><div className="document-preview">{resource.preview?.media?.url ? <img src={resource.preview.media.url} alt={resource.preview.media.alt_text || "Resource preview"}/> : <FileText/>}<div dangerouslySetInnerHTML={{ __html: resource.preview?.content || "<p>No public preview is available.</p>" }}/></div></InnerSection><InnerSection title="Related Resources"><div className="resource-list-grid">{data.related.map((item) => <ResourceCard resource={card(item)} key={item.id}/>)}</div></InnerSection><a className="discipline-back-link" href={`/engineering/${resource.discipline.slug}`}>Explore {resource.discipline.name} <ArrowRight/></a></Page>;
}



