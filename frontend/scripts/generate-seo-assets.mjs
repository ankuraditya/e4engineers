import { readFile, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const site = (process.env.VITE_SITE_URL || 'https://e4engineers.in').replace(/\/$/, '');
const api = process.env.SEO_API_BASE_URL?.replace(/\/$/, '');
const source = path.resolve(root, '../backend/database/seeders');
const urls = new Map();
const add = (pathname, lastmod) => {
  const url = `${site}${pathname}`;
  urls.set(url, lastmod ? String(lastmod).slice(0, 10) : null);
};

for (const route of ['/', '/about/', '/articles/', '/courses/', '/contact/', '/engineering/', '/internships/']) add(route);

async function published(kind) {
  const results = [];
  for (let page = 1; page <= 100; page++) {
    const response = await fetch(`${api}/${kind}?per_page=100&page=${page}`, { headers: { Accept: 'application/json' } });
    if (response.status === 404 && kind === 'publications') return [];
    if (!response.ok) throw new Error(`SEO API ${kind}: ${response.status}`);
    const payload = await response.json();
    results.push(...(payload.data || []));
    if (page >= (payload.meta?.last_page || 1)) break;
  }
  return results;
}

if (api) {
  const singular = { articles: 'article', courses: 'course', books: 'book', resources: 'resource', publications: 'publication' };
  for (const kind of ['articles', 'courses', 'books', 'resources', 'publications']) {
    const items = await published(kind);
    if (items.length && !['articles', 'courses'].includes(kind)) add(`/${kind}/`);
    for (const item of items) {
      const response = await fetch(`${api}/${kind}/${encodeURIComponent(item.slug)}`, { headers: { Accept: 'application/json' } });
      if (!response.ok) continue;
      const detail = (await response.json()).data?.[singular[kind]];
      if (!detail || detail.seo?.robots?.startsWith('noindex')) continue;
      const canonical = detail.seo?.canonical_url;
      if (canonical && canonical !== `${site}/${kind}/${encodeURIComponent(item.slug)}/`) continue;
      add(`/${kind}/${encodeURIComponent(item.slug)}/`, item.updated_at || item.published_at);
    }
  }
} else {
  const posts = JSON.parse(await readFile(path.join(source, 'legacy-wordpress-posts.json'), 'utf8'));
  for (const post of posts.filter((item) => item.status === 'publish')) {
    add(`/articles/${encodeURIComponent(post.slug)}/`, post.modified_gmt || post.date_gmt);
  }
  const course = JSON.parse(await readFile(path.join(source, 'legacy-wordpress-course.json'), 'utf8'));
  if (course.status === 'publish') add(`/courses/${encodeURIComponent(course.slug)}/`, course.modified_gmt || course.date_gmt);
}

const xml = (value) => String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
const sitemap = '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n'
  + [...urls].map(([url, lastmod]) => `  <url><loc>${xml(url)}</loc>${lastmod ? `<lastmod>${xml(lastmod)}</lastmod>` : ''}</url>`).join('\n')
  + '\n</urlset>\n';
await writeFile(path.join(root, 'public/sitemap.xml'), sitemap);
const apiSitemap = process.env.VITE_API_BASE_URL
  ? `${process.env.VITE_API_BASE_URL.replace(/\/$/, '')}/sitemap.xml`
  : `${site}/api/v1/sitemap.xml`;
await writeFile(path.join(root, 'public/robots.txt'), `User-agent: *\nAllow: /\nSitemap: ${site}/sitemap.xml\nSitemap: ${apiSitemap}\n`);
console.log(`Generated sitemap with ${urls.size} published URLs.`);
