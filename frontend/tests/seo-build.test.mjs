import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const base = new URL('../', import.meta.url);
const source = JSON.parse(await readFile(new URL('../backend/database/seeders/legacy-wordpress-posts.json', base), 'utf8'));

test('sitemap contains canonical published articles and no private or empty shop pages', async () => {
  const xml = await readFile(new URL('public/sitemap.xml', base), 'utf8');
  for (const post of source) assert.match(xml, new RegExp(`https://e4engineers\\.in/articles/${post.slug}/`));
  for (const path of ['/admin/', '/account/', '/cart/', '/checkout/', '/books/', '/publications/']) {
    assert.ok(!xml.includes(`https://e4engineers.in${path}`), `${path} should not be submitted for indexing`);
  }
});

test('published article HTML has unique metadata and crawlable text', async () => {
  const slug = source[0].slug;
  const html = await readFile(new URL(`dist/client/articles/${slug}/index.html`, base), 'utf8');
  assert.match(html, new RegExp(`<link rel="canonical" href="https://e4engineers\\.in/articles/${slug}/">`));
  assert.match(html, /<h1>[^<]+<\/h1>/);
  assert.match(html, /application\/ld\+json/);
  assert.equal((html.match(/rel="canonical"/g) || []).length, 1);
});

test('legacy WordPress post URLs have permanent redirects', async () => {
  const rules = await readFile(new URL('public/.htaccess', base), 'utf8');
  for (const post of source) assert.ok(rules.includes(post.slug), `${post.slug} needs a redirect`);
  assert.match(rules, /\[R=301,L,NC\]/);
});
