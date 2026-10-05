import { useEffect, useState } from "react";
import { Breadcrumb, InnerPageHeader } from "./InnerPageComponents";
import { cmsService } from "../../services/cmsService";

export function CmsLegalPage({ slug, fallback }) {
  const [page, setPage] = useState(null);

  useEffect(() => {
    let active = true;
    cmsService.page(slug).then((result) => {
      if (active) setPage(result.data);
    }).catch(() => {});
    return () => { active = false; };
  }, [slug]);

  if (!page?.content) return <div className="inner-page"><div className="container inner-page__container">
    <Breadcrumb items={[{ label: "Home", href: "/" }, { label: fallback.title }]} />
    <InnerPageHeader eyebrow="E4ENGINEERS policies" title={fallback.title} description="This policy is being finalized." />
    <p className="legal-content">Please contact E4ENGINEERS for current policy details before placing an order.</p>
  </div></div>;

  return <div className="inner-page"><div className="container inner-page__container">
    <Breadcrumb items={[{ label: "Home", href: "/" }, { label: page.title }]} />
    <InnerPageHeader eyebrow="E4ENGINEERS policies" title={page.title} description={page.excerpt || ""} />
    <article className="legal-content" dangerouslySetInnerHTML={{ __html: page.content }} />
  </div></div>;
}
