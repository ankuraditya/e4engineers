import { useEffect, useState } from 'react';
import { adminService } from '../services/adminService.js';
import { InnerPageHeader } from '../components/shared/InnerPageComponents.jsx';

export function AdminReferralsPage() {
  const [settings, setSettings] = useState({ reward: '100', minimum: '100' });
  const [requests, setRequests] = useState([]);
  const [filter, setFilter] = useState('pending');
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);
  async function load() {
    try { const response = await adminService.withdrawals(filter); setRequests(response.data || []); }
    catch (error) { setMessage(error.message); }
  }
  useEffect(() => { adminService.settings().then(response => {
    const rows = response.data || [];
    setSettings({ reward: rows.find(row => row.key === 'referral_reward_rupees')?.value || '100', minimum: rows.find(row => row.key === 'referral_min_withdrawal_rupees')?.value || '100' });
  }).catch(error => setMessage(error.message)); }, []);
  useEffect(() => { load(); }, [filter]);
  async function save(event) {
    event.preventDefault();
    if (![settings.reward, settings.minimum].every(value => /^\d+$/.test(value) && Number(value) <= 10000) || Number(settings.minimum) < 1) { setMessage('Enter a reward from ₹0 to ₹10,000 and a minimum from ₹1 to ₹10,000.'); return; }
    setBusy(true);
    try { await adminService.updateSettings([{ key: 'referral_reward_rupees', value: settings.reward }, { key: 'referral_min_withdrawal_rupees', value: settings.minimum }]); setMessage('Referral settings saved.'); }
    catch (error) { setMessage(error.message); }
    finally { setBusy(false); }
  }
  async function review(item, status) {
    const payment_reference = status === 'paid' ? window.prompt('Enter the completed UPI payment reference:') : null;
    const admin_note = status === 'rejected' ? window.prompt('Reason for rejection (shown in the admin record):') : null;
    if (status === 'paid' && !payment_reference?.trim() || status === 'rejected' && !admin_note?.trim()) return;
    if (!window.confirm(`${status === 'paid' ? 'Confirm payment' : 'Reject withdrawal'} of ₹${(item.amount_paise / 100).toFixed(2)} for ${item.customer_name}?`)) return;
    setBusy(true);
    try { await adminService.reviewWithdrawal(item.id, { status, payment_reference, admin_note }); setMessage(`Withdrawal ${status}.`); await load(); }
    catch (error) { setMessage(error.message); }
    finally { setBusy(false); }
  }
  return <div className="admin-module"><InnerPageHeader eyebrow="Customers / Referrals" title="Referrals & Wallet" description="Configure cashback and review student withdrawal requests." />
    <section className="admin-panel"><header><div><h2>Referral settings</h2><p>Only customers with a paid book purchase can share a referral link.</p></div></header>
      <form className="admin-toolbar" onSubmit={save}><label><span>Cashback per first successful referred purchase (₹)</span><input type="number" min="0" max="10000" step="1" value={settings.reward} onChange={event => setSettings({ ...settings, reward: event.target.value })} /></label><label><span>Minimum withdrawal (₹)</span><input type="number" min="1" max="10000" step="1" value={settings.minimum} onChange={event => setSettings({ ...settings, minimum: event.target.value })} /></label><button className="button button--primary" disabled={busy}>Save settings</button></form>
    </section><section className="admin-panel"><header><div><h2>Withdrawal requests</h2><p>Pay manually to the listed UPI ID, then record the payment reference.</p></div></header>
      <div className="account-filters admin-tabs">{['pending', 'paid', 'rejected'].map(value => <button key={value} aria-pressed={filter === value} onClick={() => setFilter(value)}>{value}</button>)}</div>
      {requests.length ? <div className="admin-order-list">{requests.map(item => <article className="referral-withdraw-row" key={item.id}><div><strong>{item.customer_name}</strong><small>{item.customer_email}</small><small>{new Date(item.created_at).toLocaleString('en-IN')}</small></div><div><strong>₹{(item.amount_paise / 100).toFixed(2)}</strong><small>UPI: {item.upi_id}</small><small>{item.payment_reference || item.admin_note || item.status}</small></div>{item.status === 'pending' && <div><button className="button button--primary" disabled={busy} onClick={() => review(item, 'paid')}>Mark paid</button> <button className="button button--secondary" disabled={busy} onClick={() => review(item, 'rejected')}>Reject</button></div>}</article>)}</div> : <p className="admin-empty">No {filter} withdrawals.</p>}
    </section>{message && <p role="status" className="form-status">{message}</p>}
  </div>;
}
