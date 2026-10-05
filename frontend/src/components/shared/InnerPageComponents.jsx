import { ArrowRight, CaretRight, MagnifyingGlass } from "@phosphor-icons/react";

export function Breadcrumb({ items }) {
  return <nav className="breadcrumb" aria-label="Breadcrumb"><ol>{items.map((item,index)=><li key={item.label}>{item.href?<a href={item.href}>{item.label}</a>:<span aria-current="page">{item.label}</span>}{index<items.length-1&&<CaretRight aria-hidden="true" />}</li>)}</ol></nav>;
}

export function InnerPageHeader({ eyebrow, title, description, actions }) {
  return <header className="inner-page-header"><div>{eyebrow&&<p className="inner-page-header__eyebrow">{eyebrow}</p>}<h1>{title}</h1><p>{description}</p>{actions&&<div className="inner-page-actions">{actions}</div>}</div></header>;
}

export function InnerSection({ title, intro, children, id }) {
  return <section className="inner-section" id={id}><div className="inner-section__heading"><h2>{title}</h2>{intro&&<p>{intro}</p>}</div>{children}</section>;
}

export function ArticleCard({ article }) {
  return <article className="inner-article-card"><img src={article.image} alt="" /><div className="inner-article-card__body"><p className="inner-card-kicker">{article.discipline}</p><h3>{article.title}</h3><p>{article.excerpt}</p><div className="article-card-meta"><span>{article.author}</span><span>{article.date}</span></div><a href={`/articles/${article.slug}`}>Read article <ArrowRight aria-hidden="true" /></a></div></article>;
}

export function Pagination() { return <nav className="pagination" aria-label="Article pages"><a href="#page-previous" aria-label="Previous page">‹</a><a className="pagination__active" href="#page-1" aria-current="page">1</a><a href="#page-2">2</a><a href="#page-3">3</a><a href="#page-next" aria-label="Next page">›</a></nav>; }
export function EmptyState({ title="No matching content", message="Try a broader search or clear one of the filters.", action }) { return <div className="empty-state"><MagnifyingGlass aria-hidden="true" /><h2>{title}</h2><p>{message}</p>{action&&<a className="button button--primary" href={action.href}>{action.label}<ArrowRight aria-hidden="true"/></a>}</div>; }
