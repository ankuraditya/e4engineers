import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const output = path.join(root, 'dist/client');
const source = path.resolve(root, '../backend/database/seeders');
const site = (process.env.VITE_SITE_URL || 'https://e4engineers.in').replace(/\/$/, '');
const api = process.env.SEO_API_BASE_URL?.replace(/\/$/, '');
const shell = await readFile(path.join(output, 'index.html'), 'utf8');
const escape = (text) => String(text || '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');
const plain = (html) => String(html || '').replace(/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/gi, ' ')
  .replace(/<[^>]+>/g, ' ').replace(/&nbsp;|&#160;/gi, ' ').replace(/&amp;/gi, '&')
  .replace(/&lt;/gi, '<').replace(/&gt;/gi, '>').replace(/&quot;/gi, '"')
  .replace(/&#39;|&apos;/gi, "'").replace(/\s+/g, ' ').trim();
const summary = (value) => { const text = plain(value); return text.length > 160 ? `${text.slice(0, 157).trimEnd()}…` : text; };

async function published(kind) {
  const items = [];
  for (let page = 1; page <= 100; page++) {
    const response = await fetch(`${api}/${kind}?per_page=100&page=${page}`, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error(`SEO API ${kind}: ${response.status}`);
    const payload = await response.json();
    items.push(...(payload.data || []));
    if (page >= (payload.meta?.last_page || 1)) break;
  }
  return items;
}

async function page(kind, record) {
  if (record.seo?.robots?.startsWith('noindex')) return;
  const canonical = record.seo?.canonical_url || `${site}/${kind}/${encodeURIComponent(record.slug)}/`;
  const title = plain(record.seo?.meta_title || record.title);
  const description = summary(record.seo?.meta_description || record.excerpt || record.short_description || record.description);
  const content = plain(record.content || record.description);
  const imageUrl = record.seo?.og_image || record.image;
  const image = imageUrl ? `<meta property="og:image" content="${escape(imageUrl)}">` : '';
  const schema = kind === 'articles' ? {
    '@context': 'https://schema.org', '@type': 'Article', headline: title, description,
    datePublished: record.date, author: { '@type': 'Organization', name: 'E4ENGINEERS' },
    publisher: { '@type': 'Organization', name: 'E4ENGINEERS' }, mainEntityOfPage: canonical,
  } : kind === 'books' && imageUrl && record.selling_price ? {
    '@context': 'https://schema.org', '@type': 'Product', name: title, description, sku: record.sku, image: [imageUrl],
    offers: { '@type': 'Offer', url: canonical, priceCurrency: record.currency || 'INR',
      price: record.selling_price, availability: record.inventory?.is_available
        ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' },
  } : null;
  const json = schema ? `<script type="application/ld+json">${JSON.stringify(schema).replace(/</g, '\\u003c')}</script>` : '';
  const html = shell.replace(/<meta property="og:(?:type|title|description|image)"[^>]*>/g, '')
    .replace(/<meta name="twitter:image"[^>]*>/g, '')
    .replace(/<meta name="twitter:card"[^>]*>/, `<meta name="twitter:card" content="${imageUrl ? 'summary_large_image' : 'summary'}">`)
    .replace(/<title>[\s\S]*?<\/title>/, `<title>${escape(title)} | E4ENGINEERS</title>`)
    .replace(/<meta name="description"[^>]*>/, `<meta name="description" content="${escape(description)}">`)
    .replace('</head>', `<link rel="canonical" href="${escape(canonical)}"><meta property="og:type" content="${kind === 'articles' ? 'article' : 'website'}"><meta property="og:title" content="${escape(record.seo?.og_title || title)}"><meta property="og:description" content="${escape(record.seo?.og_description || description)}"><meta property="og:url" content="${escape(canonical)}">${image}${json}</head>`)
    .replace(/<div id="root">[\s\S]*?<\/div>/, `<div id="root"><main><article><h1>${escape(title)}</h1><p>${escape(content)}</p>${kind === 'books' && record.selling_price ? `<p>Price: ₹${escape(record.selling_price)}</p>` : ''}</article></main></div>`);
  const directory = path.join(output, kind, record.slug);
  await mkdir(directory, { recursive: true });
  await writeFile(path.join(directory, 'index.html'), html);
}

if (api) {
  const singular = { articles: 'article', courses: 'course', books: 'book', resources: 'resource', publications: 'publication' };
  for (const kind of Object.keys(singular)) {
    let items;
    try { items = await published(kind); }
    catch (error) { if (kind === 'publications') continue; throw error; }
    for (const item of items) {
      const response = await fetch(`${api}/${kind}/${encodeURIComponent(item.slug)}`, { headers: { Accept: 'application/json' } });
      if (!response.ok) continue;
      const detail = (await response.json()).data?.[singular[kind]];
      if (!detail) continue;
      await page(kind, { ...detail, date: detail.published_at,
        image: detail.featured_image?.url || detail.cover?.url || detail.thumbnail?.url });
    }
  }
} else {
  const posts = JSON.parse(await readFile(path.join(source, 'legacy-wordpress-posts.json'), 'utf8'));
  for (const post of posts.filter((item) => item.status === 'publish')) {
    await page('articles', { slug: post.slug, title: post.title.rendered, excerpt: post.excerpt.rendered,
      content: post.content.rendered, date: post.date_gmt });
  }
  const course = JSON.parse(await readFile(path.join(source, 'legacy-wordpress-course.json'), 'utf8'));
  if (course.status === 'publish') await page('courses', { slug: course.slug, title: course.title.rendered,
    short_description: course.excerpt.rendered, description: course.content.rendered, date: course.date_gmt });
}
console.log('Generated crawlable article and course HTML.');
