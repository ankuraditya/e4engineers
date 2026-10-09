import { useState } from 'react';
import { checkoutService } from '../../services/checkoutService.js';

export function ReferralWallet({ referral }) {
  const [copied, setCopied] = useState(false);
  const [amount, setAmount] = useState('');
  const [upi, setUpi] = useState('');
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);
  const balance = Number(referral.wallet_balance_rupees || 0);
  const minimum = Number(referral.minimum_withdrawal_rupees || 100);
  const link = referral.code ? `${location.origin}/register?ref=${referral.code}` : '';
  async function withdraw(event) {
    event.preventDefault();
    setBusy(true);
    try {
      await checkoutService.requestWithdrawal(Number(amount), upi.trim());
      location.reload();
    } catch (error) { setMessage(error.message); setBusy(false); }
  }
  return <section className="account-section referral-panel">
    <h2>Referral cashback wallet</h2>
    {referral.eligible ? <>
      <p>Share your link. When a friend registers and makes their first successful purchase, you earn ₹{referral.reward_amount_rupees} cashback.</p>
      <label>Your referral link<input readOnly value={link} onFocus={event => event.target.select()} /></label>
      <button className="button button--secondary" type="button" onClick={async () => { await navigator.clipboard.writeText(link); setCopied(true); }}>{copied ? 'Copied' : 'Copy referral link'}</button>
    </> : <p>Your referral link becomes available after your first paid book purchase.</p>}
    <div className="account-stats"><article><strong>{referral.registered}</strong><span>Registered friends</span></article><article><strong>{referral.rewarded}</strong><span>Rewarded purchases</span></article><article><strong>₹{balance.toFixed(2)}</strong><span>Cashback available</span></article></div>
    {Number(referral.store_credit_rupees) > 0 && <p>Existing store credit: ₹{referral.store_credit_rupees}. You can still use it at checkout; it is separate from withdrawable cashback.</p>}
    {referral.eligible && <form className="referral-withdraw-form" onSubmit={withdraw}>
      <h3>Request a withdrawal</h3><p>Minimum withdrawal: ₹{minimum}. Payment is sent to your UPI ID after admin review.</p>
      <label>Amount (₹)<input type="number" min={minimum} max={balance} step="1" required value={amount} onChange={event => setAmount(event.target.value)} /></label>
      <label>UPI ID<input required autoComplete="off" placeholder="name@bank" value={upi} onChange={event => setUpi(event.target.value)} /></label>
      <button className="button button--primary" disabled={busy || balance < minimum}>{busy ? 'Submitting…' : 'Request withdrawal'}</button>
      {message && <p role="alert">{message}</p>}
    </form>}
    {referral.withdrawals?.length > 0 && <div className="orders-table"><h3>Withdrawal requests</h3>{referral.withdrawals.map(item => <div key={item.id}><span>{new Date(item.created_at).toLocaleDateString('en-IN')}</span><strong>₹{(item.amount_paise / 100).toFixed(2)}</strong><span>{item.status}</span></div>)}</div>}
    {referral.rewards?.length > 0 && <div className="orders-table"><h3>Referral rewards</h3>{referral.rewards.map((reward, index) => <div key={index}><span>{reward.name}</span><span>{new Date(reward.credited_at).toLocaleDateString('en-IN')}</span><strong>+₹{(reward.amount_paise / 100).toFixed(2)} {reward.wallet_entry_id ? 'cashback' : 'store credit'}</strong></div>)}</div>}
  </section>;
}
