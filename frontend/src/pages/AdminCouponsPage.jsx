import { useEffect, useMemo, useState } from 'react';
import { InnerPageHeader } from '../components/shared/InnerPageComponents';
import { adminCommerceService } from '../services/adminCommerceService';
import { adminCouponService } from '../services/adminCouponService';

const unwrap = response => response?.data?.data || response?.data || [];
const empty = { code: '', name: '', description: '', discount_type: 'percentage', discount_value: '', applies_to: 'all_books', minimum_subtotal: '', maximum_discount: '', starts_at: '', expires_at: '', usage_limit: '', per_customer_limit: '', is_active: false, book_ids: [], category_ids: [], discipline_ids: [] };
const scopes = { all_books: 'All books', specific_books: 'Selected books', book_categories: 'Book categories', engineering_disciplines: 'Engineering disciplines' };
const scopeFields = { specific_books: 'book_ids', book_categories: 'category_ids', engineering_disciplines: 'discipline_ids' };
const localDate = value => { if (!value) return ''; const date = new Date(value); if (Number.isNaN(date.getTime())) return ''; return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16); };
const numberOrNull = value => value === '' || value == null ? null : Number(value);

export function AdminCouponsPage() {
  const [coupons, setCoupons] = useState([]), [masters, setMasters] = useState({ books: [], categories: [], disciplines: [] });
  const [loading, setLoading] = useState(true), [busy, setBusy] = useState(false), [message, setMessage] = useState(''), [search, setSearch] = useState('');
  const [editor, setEditor] = useState(null), [form, setForm] = useState(empty), [deleteTarget, setDeleteTarget] = useState(null);
  const visible = useMemo(() => coupons.filter(item => `${item.code} ${item.name}`.toLowerCase().includes(search.toLowerCase())), [coupons, search]);

  async function load() { setLoading(true); try { setCoupons(unwrap(await adminCouponService.list())); } catch (error) { setMessage(error.message); } finally { setLoading(false); } }
  useEffect(() => { load(); }, []);
  function set(key, value) { setForm(current => ({ ...current, [key]: value })); }
  async function open(item = null) {
    setBusy(true); setMessage('');
    try {
      const [books, categories, disciplines] = await Promise.all([adminCommerceService.books(), adminCommerceService.categories(), adminCommerceService.disciplines()]);
      setMasters({ books: unwrap(books), categories: unwrap(categories).filter(category => !category.context || category.context === 'book'), disciplines: unwrap(disciplines) });
      setForm(item ? { ...empty, ...item, starts_at: localDate(item.starts_at), expires_at: localDate(item.expires_at), book_ids: item.book_ids || [], category_ids: item.category_ids || [], discipline_ids: item.discipline_ids || [] } : { ...empty });
      setEditor(item || { id: null });
    } catch (error) { setMessage(error.message); }
    finally { setBusy(false); }
  }
  function toggleId(field, id) { setForm(current => ({ ...current, [field]: current[field].some(value => Number(value) === Number(id)) ? current[field].filter(value => Number(value) !== Number(id)) : [...current[field], id] })); }
  async function save(event) {
    event.preventDefault(); setBusy(true); setMessage('');
    try {
      const field = scopeFields[form.applies_to];
      if (field && !form[field].length) throw new Error('Select at least one eligible item for this offer.');
      if (form.starts_at && form.expires_at && new Date(form.expires_at) <= new Date(form.starts_at)) throw new Error('End date must be after start date.');
      const data = { code: form.code.trim().toUpperCase(), name: form.name.trim(), description: form.description || null, discount_type: form.discount_type, discount_value: Number(form.discount_value), applies_to: form.applies_to, minimum_subtotal: numberOrNull(form.minimum_subtotal), maximum_discount: form.discount_type === 'percentage' ? numberOrNull(form.maximum_discount) : null, starts_at: form.starts_at ? new Date(form.starts_at).toISOString() : null, expires_at: form.expires_at ? new Date(form.expires_at).toISOString() : null, usage_limit: numberOrNull(form.usage_limit), per_customer_limit: numberOrNull(form.per_customer_limit), is_active: Boolean(form.is_active), ...(field ? { [field]: form[field].map(Number) } : {}) };
      if (editor.id) await adminCouponService.update(editor.id, data); else await adminCouponService.create(data);
      setEditor(null); setMessage(`Offer ${editor.id ? 'updated' : 'created'} successfully.`); await load();
    } catch (error) { setMessage(error.message); }
    finally { setBusy(false); }
  }
  async function changeStatus(item) { setBusy(true); setMessage(''); try { await adminCouponService.setActive(item.id, !item.is_active); setMessage(`${item.code} ${item.is_active ? 'disabled' : 'enabled'}.`); await load(); } catch (error) { setMessage(error.message); } finally { setBusy(false); } }
  async function remove() { setBusy(true); setMessage(''); try { await adminCouponService.remove(deleteTarget.id); setDeleteTarget(null); setMessage('Offer deleted.'); await load(); } catch (error) { setMessage(error.message); } finally { setBusy(false); } }
  const field = scopeFields[form.applies_to];
  const choices = field === 'book_ids' ? masters.books : field === 'category_ids' ? masters.categories : masters.disciplines;
  const formatDate = value => value ? new Date(value).toLocaleDateString('en-IN') : 'No expiry';

  return <div className="admin-module"><InnerPageHeader eyebrow="Store / Promotions" title="Coupons & Offers" description="Create checkout discount codes, choose eligible books and control when each offer is available." />
    {message && <p className="admin-feedback" role="status">{message}</p>}
    <div className="admin-module-summary"><div><strong>{coupons.length}</strong><span>offers</span></div><label><span>Find an offer</span><input value={search} onChange={event => setSearch(event.target.value)} placeholder="Search code or name" /></label></div>
    <section className="admin-data-panel"><header><div><h2>Offers</h2><p>Only enabled offers within their dates can be applied at checkout.</p></div><button type="button" className="button button--primary" disabled={busy} onClick={() => open()}>＋ Add offer</button></header>
      {loading ? <div className="admin-list-loading">Loading offers…</div> : visible.length ? <div className="admin-content-list">{visible.map((item, index) => <article key={item.id}><span className="admin-row-index">{String(index + 1).padStart(2, '0')}</span><div><strong>{item.code} · {item.name}</strong><small>{item.discount_type === 'percentage' ? `${Number(item.discount_value)}% off` : `₹${Number(item.discount_value)} off`} · {scopes[item.applies_to]} · Ends {formatDate(item.expires_at)} · Used {item.usage_count || 0}{item.usage_limit ? `/${item.usage_limit}` : ''}</small></div><span className={`admin-status admin-status--${item.is_active ? 'published' : 'draft'}`}>{item.is_active ? 'Enabled' : 'Disabled'}</span><div className="admin-crud-actions"><button type="button" disabled={busy} onClick={() => open(item)}>Edit</button><button type="button" disabled={busy} onClick={() => changeStatus(item)}>{item.is_active ? 'Turn off' : 'Turn on'}</button><button type="button" className="danger" disabled={busy} onClick={() => setDeleteTarget(item)}>Delete</button></div></article>)}</div> : <div className="admin-empty"><h3>No offers found</h3><p>Add a coupon code to run a promotion at checkout.</p></div>}
    </section>
    {editor && <div className="admin-modal-backdrop"><section className="admin-modal" role="dialog" aria-modal="true" aria-labelledby="coupon-editor-title"><header><div><small>STORE PROMOTION</small><h2 id="coupon-editor-title">{editor.id ? 'Edit offer' : 'Add an offer'}</h2></div><button type="button" aria-label="Close editor" onClick={() => setEditor(null)}>×</button></header><form onSubmit={save}>{message && <p className="admin-feedback" role="alert">{message}</p>}<div className="admin-editor-grid">
      <label><span>Coupon code *</span><input required maxLength="64" pattern="[A-Za-z0-9_-]+" value={form.code} onChange={event => set('code', event.target.value.toUpperCase())} placeholder="SAVE10" /></label>
      <label><span>Offer name *</span><input required maxLength="255" value={form.name} onChange={event => set('name', event.target.value)} placeholder="Launch discount" /></label>
      <label className="wide"><span>Description</span><textarea rows="2" maxLength="2000" value={form.description || ''} onChange={event => set('description', event.target.value)} /></label>
      <label><span>Discount type *</span><select value={form.discount_type} onChange={event => set('discount_type', event.target.value)}><option value="percentage">Percentage (%)</option><option value="fixed">Fixed amount (₹)</option></select></label>
      <label><span>Discount value *</span><input required type="number" min="0.01" max={form.discount_type === 'percentage' ? '100' : undefined} step="0.01" value={form.discount_value} onChange={event => set('discount_value', event.target.value)} /></label>
      <label><span>Minimum cart subtotal (₹)</span><input type="number" min="0" step="0.01" value={form.minimum_subtotal ?? ''} onChange={event => set('minimum_subtotal', event.target.value)} /></label>
      {form.discount_type === 'percentage' && <label><span>Maximum discount (₹)</span><input type="number" min="0.01" step="0.01" value={form.maximum_discount ?? ''} onChange={event => set('maximum_discount', event.target.value)} /></label>}
      <label><span>Valid from</span><input type="datetime-local" value={form.starts_at} onChange={event => set('starts_at', event.target.value)} /></label>
      <label><span>Valid until</span><input type="datetime-local" value={form.expires_at} onChange={event => set('expires_at', event.target.value)} /></label>
      <label><span>Total usage limit</span><input type="number" min="1" step="1" value={form.usage_limit ?? ''} onChange={event => set('usage_limit', event.target.value)} /></label>
      <label><span>Uses per customer</span><input type="number" min="1" step="1" value={form.per_customer_limit ?? ''} onChange={event => set('per_customer_limit', event.target.value)} /></label>
      <label><span>Eligible products *</span><select value={form.applies_to} onChange={event => set('applies_to', event.target.value)}>{Object.entries(scopes).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></label>
      <label><span>Status</span><select value={form.is_active ? 'active' : 'inactive'} onChange={event => set('is_active', event.target.value === 'active')}><option value="inactive">Disabled</option><option value="active">Enabled</option></select></label>
      {field && <fieldset className="admin-coupon-choices"><legend>Select eligible {scopes[form.applies_to].toLowerCase()} *</legend>{choices.length ? choices.map(choice => <label key={choice.id}><input type="checkbox" checked={form[field].some(value => Number(value) === Number(choice.id))} onChange={() => toggleId(field, choice.id)} />{choice.title || choice.name}</label>) : <p>No eligible items found. Add them in the catalogue first.</p>}</fieldset>}
    </div><footer><button type="button" className="button button--secondary" onClick={() => setEditor(null)}>Cancel</button><button className="button button--primary" disabled={busy}>{busy ? 'Saving…' : 'Save offer'}</button></footer></form></section></div>}
    {deleteTarget && <div className="admin-modal-backdrop"><section className="admin-confirm" role="alertdialog" aria-modal="true"><h2>Delete {deleteTarget.code}?</h2><p>Customers will no longer be able to apply this code. Existing orders keep their recorded discount.</p><div><button type="button" className="button button--secondary" onClick={() => setDeleteTarget(null)}>Cancel</button><button type="button" className="button admin-delete-button" disabled={busy} onClick={remove}>Delete offer</button></div></section></div>}
  </div>;
}
