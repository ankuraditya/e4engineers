import { articlesService } from './articlesService.js';
import { booksService } from './booksService.js';
import { coursesService } from './coursesService.js';
import { resourcesService } from './resourcesService.js';
import { publicationsService } from './publicationsService.js';

const BRAND = 'E4ENGINEERS';
const SITE = (import.meta.env?.VITE_SITE_URL || 'https://e4engineers.in').replace(/\/$/, '');
const DEFAULT_DESCRIPTION = 'Explore engineering articles and courses from E4ENGINEERS. Build stronger fundamentals with practical technical knowledge.';
const PRIVATE_PATH = /^\/(?:admin|account|cart|checkout|login|register|forgot-password|reset-password|order-success|support|search)(?:\/|$)/;
const UNFINISHED_POLICY = /^\/(?:privacy-policy|terms|shipping-policy|returns-refunds)(?:\/|$)/;
const STATIC_PAGES = {
  '/': ['Engineering Knowledge, Connected', DEFAULT_DESCRIPTION],
  '/about': ['About E4ENGINEERS', 'Learn about E4ENGINEERS and its mission to make engineering education easier to understand.'],
  '/articles': ['Engineering Articles', 'Read practical articles on electrical engineering, power systems and smart grids.'],
  '/courses': ['Engineering Courses', 'Explore published engineering courses and build a stronger technical foundation.'],
  '/contact': ['Contact E4ENGINEERS', 'Contact E4ENGINEERS about engineering education, courses and technical content.'],
  '/engineering': ['Engineering Disciplines', 'Explore engineering disciplines and discover relevant technical content.'],
};
const COLLECTION_PAGES = {
  '/books': ['Engineering Books', 'Browse available engineering books and academic references.', () => booksService.getBooks({ per_page: 1 })],
  '/resources': ['Study Resources', 'Explore published engineering study resources.', () => resourcesService.getResources({ per_page: 1 })],
  '/publications': ['Journals & Publications', 'Read published engineering journals and technical publications.', () => publicationsService.getPublications({ per_page: 1 })],
};

function plain(value) {
  return String(value || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

function description(value) {
  const text = plain(value);
  return text.length > 160 ? `${text.slice(0, 157).trimEnd()}…` : text || DEFAULT_DESCRIPTION;
}

function absolute(url) {
  if (!url) return null;
  try { return new URL(url, SITE).href; } catch { return null; }
}

function meta(attribute, key, content) {
  let element = document.head.querySelector(`meta[${attribute}="${key}"]`);
  if (!element) {
    element = document.createElement('meta');
    element.setAttribute(attribute, key);
    document.head.append(element);
  }
  element.content = content;
}

function applySeo(path, info = {}) {
  const canonicalPath = path === '/' ? '/' : `${path.replace(/\/$/, '')}/`;
  const canonical = `${SITE}${canonicalPath}`;
  const title = info.title ? `${info.title} | ${BRAND}` : BRAND;
  const summary = description(info.description);
  const liveHost = new URL(SITE).hostname;
  const noindex = info.noindex || info.robots?.startsWith('noindex')
    || ![liveHost, `www.${liveHost}`].includes(location.hostname);
  document.title = title;
  meta('name', 'description', summary);
  meta('name', 'robots', noindex ? (info.robots?.startsWith('noindex') ? info.robots : 'noindex,follow') : 'index,follow');
  meta('property', 'og:type', info.type || 'website');
  meta('property', 'og:site_name', BRAND);
  meta('property', 'og:title', info.ogTitle || title);
  meta('property', 'og:description', info.ogDescription || summary);
  meta('property', 'og:url', canonical);
  meta('name', 'twitter:card', info.image ? 'summary_large_image' : 'summary');
  meta('name', 'twitter:title', info.ogTitle || title);
  meta('name', 'twitter:description', info.ogDescription || summary);
  const image = document.head.querySelector('meta[property="og:image"]');
  const twitterImage = document.head.querySelector('meta[name="twitter:image"]');
  if (info.image) {
    meta('property', 'og:image', absolute(info.image));
    meta('name', 'twitter:image', absolute(info.image));
  } else {
    image?.remove();
    twitterImage?.remove();
  }

  let link = document.head.querySelector('link[rel="canonical"]');
  if (!link) {
    link = document.createElement('link');
    link.rel = 'canonical';
    document.head.append(link);
  }
  link.href = info.canonical || canonical;
  document.getElementById('page-structured-data')?.remove();
  if (info.structuredData && !noindex) {
    const script = document.createElement('script');
    script.id = 'page-structured-data';
    script.type = 'application/ld+json';
    script.textContent = JSON.stringify(info.structuredData).replace(/</g, '\\u003c');
    document.head.append(script);
  }
}

export async function updateSeo(pathname, publicationsEnabled = true) {
  const path = pathname.replace(/\/+$/, '') || '/';
  if (PRIVATE_PATH.test(path) || UNFINISHED_POLICY.test(path)) {
    applySeo(path, { title: 'E4ENGINEERS', noindex: true });
    return;
  }
  if (!publicationsEnabled && path.startsWith('/publications')) {
    applySeo(path, { title: 'Publications unavailable', noindex: true });
    return;
  }

  const fixed = STATIC_PAGES[path];
  if (fixed) {
    applySeo(path, { title: fixed[0], description: fixed[1], image: path === '/' ? `${SITE}/hero-engineering-ecosystem.png` : null, structuredData: path === '/' ? {
      '@context': 'https://schema.org', '@type': 'Organization', name: BRAND, url: `${SITE}/`,
      logo: `${SITE}/favicon-512x512.png`,
    } : null });
    return;
  }

  const collection = COLLECTION_PAGES[path];
  if (collection) {
    applySeo(path, { title: collection[0], description: collection[1] });
    try {
      const response = await collection[2]();
      if (location.pathname.replace(/\/+$/, '') === path) {
        applySeo(path, { title: collection[0], description: collection[1], noindex: !response.data?.length });
      }
    } catch {
      if (location.pathname.replace(/\/+$/, '') === path) applySeo(path, { title: collection[0], noindex: true });
    }
    return;
  }

  const [, kind, slug] = path.split('/');
  const detail = {
    articles: async () => {
      const article = (await articlesService.getArticle(slug)).data.article;
      return { title: article.seo?.meta_title || article.title, description: article.seo?.meta_description || article.excerpt,
        image: article.seo?.og_image || article.featured_image?.url, type: 'article',
        ogTitle: article.seo?.og_title, ogDescription: article.seo?.og_description, robots: article.seo?.robots, canonical: article.seo?.canonical_url,
        structuredData: { '@context': 'https://schema.org', '@type': 'Article', headline: article.title,
          description: description(article.excerpt), datePublished: article.published_at,
          author: { '@type': 'Organization', name: BRAND }, publisher: { '@type': 'Organization', name: BRAND },
          mainEntityOfPage: `${SITE}${path}/`, ...(article.featured_image?.url ? { image: [absolute(article.featured_image.url)] } : {}) } };
    },
    courses: async () => {
      const course = (await coursesService.getCourse(slug)).data.course;
      return { title: course.seo?.meta_title || course.title, description: course.seo?.meta_description || course.short_description,
        image: course.seo?.og_image || course.featured_image?.url, ogTitle: course.seo?.og_title, ogDescription: course.seo?.og_description, robots: course.seo?.robots, canonical: course.seo?.canonical_url };
    },
    books: async () => {
      const book = (await booksService.getBook(slug)).data.book;
      return { title: book.seo?.meta_title || book.title, description: book.seo?.meta_description || book.short_description,
        image: book.seo?.og_image || book.cover?.url, robots: book.seo?.robots, canonical: book.seo?.canonical_url,
        structuredData: book.cover?.url && book.selling_price ? { '@context': 'https://schema.org', '@type': 'Product', name: book.title,
          description: description(book.short_description), sku: book.sku,
          image: [absolute(book.cover.url)],
          offers: { '@type': 'Offer', url: `${SITE}${path}/`, priceCurrency: book.currency || 'INR',
            price: book.selling_price, availability: book.inventory?.is_available
              ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' } } : null };
    },
    resources: async () => {
      const resource = (await resourcesService.getResource(slug)).data.resource;
      return { title: resource.seo?.meta_title || resource.title, description: resource.seo?.meta_description || resource.short_description,
        image: resource.seo?.og_image || resource.thumbnail?.url, robots: resource.seo?.robots, canonical: resource.seo?.canonical_url };
    },
    publications: async () => {
      const publication = (await publicationsService.getPublication(slug)).data.publication;
      return { title: publication.seo?.meta_title || publication.title, description: publication.seo?.meta_description || publication.short_description,
        image: publication.seo?.og_image || publication.cover?.url, robots: publication.seo?.robots, canonical: publication.seo?.canonical_url };
    },
  };

  if (slug && detail[kind]) {
    applySeo(path, { title: BRAND });
    try {
      const info = await detail[kind]();
      if (location.pathname.replace(/\/+$/, '') === path) applySeo(path, info);
    } catch {
      if (location.pathname.replace(/\/+$/, '') === path) applySeo(path, { title: 'Page unavailable', noindex: true });
    }
    return;
  }

  applySeo(path, { title: 'E4ENGINEERS', noindex: true });
}
