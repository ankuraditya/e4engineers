import { useEffect, useState } from 'react';
import { InnerPageHeader } from '../components/shared/InnerPageComponents';
import { adminOperationsService } from '../services/adminOperationsService';

export function AdminCertificatesPage() {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [editing, setEditing] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);

  async function load() {
    setLoading(true);
    try { const response = await adminOperationsService.certificates(); setItems(response.data?.data || []); }
    catch (error) { setMessage(error.message); }
    finally { setLoading(false); }
  }
  useEffect(() => { load(); }, []);

  async function save(event) {
    event.preventDefault();
    setBusy(true);
    setMessage('');
    const form = new FormData(event.currentTarget);
    try {
      if (editing?.id) {
        if (!form.get('certificate')?.name) form.delete('certificate');
        await adminOperationsService.updateCertificate(editing.id, form);
      } else {
        await adminOperationsService.addCertificate(form);
      }
      setEditing(null);
      setMessage('Certificate saved.');
      await load();
    } catch (error) { setMessage(error.message); }
    finally { setBusy(false); }
  }

  async function toggle(item) {
    setBusy(true);
    setMessage('');
    try {
      const form = new FormData();
      form.set('is_active', item.is_active ? '0' : '1');
      await adminOperationsService.updateCertificate(item.id, form);
      await load();
    } catch (error) { setMessage(error.message); }
    finally { setBusy(false); }
  }

  async function remove() {
    if (!deleteTarget) return;
    setBusy(true);
    setMessage('');
    try {
      await adminOperationsService.deleteCertificate(deleteTarget.id);
      setDeleteTarget(null);
      setMessage('Certificate and its PDF deleted.');
      await load();
    } catch (error) { setMessage(error.message); }
    finally { setBusy(false); }
  }

  return <div className="admin-module"><InnerPageHeader eyebrow="Internships / Certificates" title="Internship Certificates" description="Upload issued PDF certificates and control whether candidates can download them." />
    <section className="admin-data-panel"><header><div><h2>Issued certificates</h2><p>{items.length} records</p></div><button type="button" className="button button--primary" onClick={() => setEditing({})}>＋ Add certificate</button></header>
      {loading ? <div className="admin-list-loading">Loading…</div> : items.length ? <div className="admin-content-list">{items.map((item) => <article key={item.id}><span className="admin-row-index">{String(item.id).padStart(2, '0')}</span><div><strong>{item.candidate_name}</strong><small>{item.program_title} · Phone ending {item.mobile_last_four}</small></div><span className={`admin-status admin-status--${item.is_active ? 'published' : 'draft'}`}>{item.is_active ? 'Active' : 'Disabled'}</span><div className="admin-crud-actions"><button type="button" disabled={busy} onClick={() => setEditing(item)}>Edit / replace PDF</button><button type="button" disabled={busy} onClick={() => toggle(item)}>{item.is_active ? 'Disable' : 'Enable'}</button><button type="button" className="danger" disabled={busy} onClick={() => setDeleteTarget(item)}>Delete</button></div></article>)}</div> : <div className="admin-empty"><h3>No certificates issued</h3><p>Add a candidate and upload their PDF to enable verification.</p></div>}</section>
    {message && <p className="form-status" role="status">{message}</p>}
    {deleteTarget && <div className="admin-modal-backdrop"><section className="admin-confirm" role="alertdialog" aria-modal="true" aria-labelledby="delete-certificate-title" aria-describedby="delete-certificate-description"><h2 id="delete-certificate-title">Delete {deleteTarget.candidate_name}'s certificate?</h2><p id="delete-certificate-description">This permanently removes the certificate record and PDF. The candidate will no longer be able to verify or download it.</p><div><button type="button" className="button button--secondary" disabled={busy} onClick={() => setDeleteTarget(null)}>Cancel</button><button type="button" className="button admin-delete-button" disabled={busy} onClick={remove}>{busy ? 'Deleting…' : 'Delete certificate'}</button></div></section></div>}
    {editing && <div className="admin-modal-backdrop"><section className="admin-modal" role="dialog" aria-modal="true" aria-label={editing.id ? 'Edit certificate' : 'Add certificate'}><header><div><small>INTERNSHIPS</small><h2>{editing.id ? 'Edit certificate' : 'Add certificate'}</h2></div><button type="button" onClick={() => setEditing(null)} aria-label="Close">×</button></header><form onSubmit={save}><div className="admin-editor-grid">
      <label><span>Candidate name *</span><input name="candidate_name" required maxLength="150" defaultValue={editing.candidate_name || ''} /></label>
      <label><span>Program title *</span><input name="program_title" required maxLength="200" defaultValue={editing.program_title || ''} /></label>
      <label><span>{editing.id ? 'New phone number (optional)' : 'Registered phone number *'}</span><input name="mobile" type="tel" inputMode="numeric" pattern="[6-9][0-9]{9}" required={!editing.id} /></label><label><span>{editing.id ? 'New date of birth (optional)' : 'Date of birth *'}</span><input name="date_of_birth" type="date" required={!editing.id} max={new Date().toISOString().slice(0, 10)} /></label>
      <label className="wide"><span>Certificate PDF {editing.id ? '(optional replacement)' : '*'}</span><input name="certificate" type="file" accept="application/pdf,.pdf" required={!editing.id} /></label>
    </div><footer><button type="button" className="button button--secondary" onClick={() => setEditing(null)}>Cancel</button><button className="button button--primary" disabled={busy}>{busy ? 'Saving…' : 'Save certificate'}</button></footer></form></section></div>}
  </div>;
}
