import { useEffect, useState } from "react";
import { InnerPageHeader } from "../components/shared/InnerPageComponents";
import { adminPaymentService as api } from "../services/adminPaymentService";
import { API_BASE_URL } from "../services/apiClient.js";

const unpack = response => response?.data?.data || response?.data || [];

export function AdminPaymentPage() {
  const [providers, setProviders] = useState([]);
  const [settings, setSettings] = useState({});
  const [attempts, setAttempts] = useState([]);
  const [transactions, setTransactions] = useState([]);
  const [reviewNotes, setReviewNotes] = useState({});
  const [tab, setTab] = useState("gateways");
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);

  async function load() {
    try {
      const [providersResponse, settingsResponse] = await Promise.all([api.providers(), api.settings()]);
      setProviders(unpack(providersResponse));
      setSettings(unpack(settingsResponse));
    } catch (error) { setMessage(error.message); }
  }
  async function loadHistory(type) {
    try {
      const response = await (type === "attempts" ? api.attempts() : api.transactions());
      if (type === "attempts") setAttempts(unpack(response));
      else setTransactions(unpack(response));
    } catch (error) { setMessage(error.message); }
  }
  useEffect(() => { load(); }, []);
  useEffect(() => { if (tab !== "gateways") loadHistory(tab); }, [tab]);

  async function act(work, success) {
    setBusy(true); setMessage("");
    try { await work(); setMessage(success); await load(); }
    catch (error) { setMessage(error.message); }
    finally { setBusy(false); }
  }
  async function saveCredentials(event, provider) {
    event.preventDefault();
    const credentials = Object.fromEntries(new FormData(event.currentTarget));
    await act(() => api.credentials(provider.id, credentials), `${provider.name} credentials saved. Test the connection before enabling.`);
  }
  async function saveScanCode(event, provider) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    if (!form.get("qr")?.size) form.delete("qr");
    await act(() => api.scanCode(provider.id, form), "Scan & Pay details saved. You can now enable the method.");
  }
  async function review(attempt, decision) {
    await act(async () => {
      await api.review(attempt.id, decision, reviewNotes[attempt.id] || "");
      await loadHistory("attempts");
    }, decision === "approve" ? "Payment approved and order confirmed." : "Payment proof rejected.");
  }

  return <div className="admin-module">
    <InnerPageHeader eyebrow="Store / Payments" title="Payments & Gateways" description="Configure payment methods and review payment activity in one workspace." />
    <div className="account-filters admin-tabs">{[["gateways", "Gateways"], ["attempts", "Payment attempts"], ["transactions", "Transactions"]].map(([value, label]) => <button key={value} aria-pressed={tab === value} onClick={() => setTab(value)}>{label}</button>)}</div>

    {tab === "gateways" && <>
      <section className="admin-data-panel admin-settings-panel"><header><div><h2>Checkout payment methods</h2><p>Enable only methods your business is ready to accept.</p></div></header><div className="admin-setting-toggles">
        <label><input type="checkbox" checked={Boolean(settings.online_payments_enabled)} disabled={busy} onChange={event => act(() => api.saveSettings({ online_payments_enabled: event.target.checked }), "Online payment setting updated.")} /> Online payments enabled</label>
        <label><input type="checkbox" checked={Boolean(settings.cod_enabled)} disabled={busy} onChange={event => act(() => api.saveSettings({ cod_enabled: event.target.checked }), "Cash on delivery setting updated.")} /> Cash on delivery enabled</label>
      </div></section>
      <div className="admin-gateway-grid">{providers.map(provider => <section className="admin-data-panel" key={provider.code}>
        <header><div><h2>{provider.name}</h2><p>{provider.environment?.toUpperCase()} · {provider.connection_status?.replaceAll("_", " ")}</p></div><span className={`admin-status admin-status--${provider.is_enabled ? "published" : "draft"}`}>{provider.is_enabled ? "Enabled" : "Disabled"}</span></header>
        <div className="admin-gateway-body">
          {provider.type === "online" && <>
            <label>Environment<select value={provider.environment} disabled={busy} onChange={event => act(() => api.environment(provider.id, event.target.value), "Environment changed. Credentials must be configured again.")}><option value={provider.code === "CASHFREE" ? "sandbox" : "test"}>{provider.code === "CASHFREE" ? "Sandbox" : "Test"}</option><option value="live">Live</option></select></label>
            <form onSubmit={event => saveCredentials(event, provider)}>{provider.credential_fields.map(field => <label key={field.name}>{field.name.replaceAll("_", " ")}<input name={field.name} type={field.secret ? "password" : "text"} autoComplete="off" placeholder={field.secret && provider.configuration?.[`${field.name}_configured`] ? "Configured — leave blank to keep" : ""} defaultValue={field.secret ? "" : provider.configuration?.[field.name] || ""} /></label>)}<button className="button button--primary" disabled={busy}>Save credentials</button></form>
            <button className="button button--secondary" disabled={busy} onClick={() => act(async () => { const result = await api.test(provider.id); if (!result.data?.connected) throw new Error("Connection test failed. Check the gateway credentials."); }, "Connection successful.")}>Test connection</button>
          </>}
          {provider.code === "SCANPAY" && <form onSubmit={event => saveScanCode(event, provider)}>
            <p>Upload the QR image customers will scan. Their screenshot will require manual approval.</p>
            <label>UPI ID<input name="upi_id" required defaultValue={provider.configuration?.upi_id || ""} placeholder="business@upi" /></label>
            <label>Payee name<input name="payee_name" required defaultValue={provider.configuration?.payee_name || ""} /></label>
            <label>Payment QR image<input name="qr" type="file" accept="image/png,image/jpeg,image/webp" required={!provider.configuration?.qr_configured} /></label>
            {provider.configuration?.qr_configured && <small>A QR image is saved. Select a file only to replace it.</small>}
            <button className="button button--primary" disabled={busy}>Save Scan & Pay</button>
          </form>}
          <label className="admin-checkbox"><input type="checkbox" checked={Boolean(provider.is_enabled)} disabled={busy} onChange={event => act(() => api.toggle(provider.id, event.target.checked), `${provider.name} ${event.target.checked ? "enabled" : "disabled"}.`)} /> Enable {provider.name}</label>
          {provider.type === "online" && provider.is_enabled && !provider.is_default && <button className="button button--secondary" disabled={busy} onClick={() => act(() => api.makeDefault(provider.id), `${provider.name} is now the default online gateway.`)}>Make default</button>}
        </div>
      </section>)}</div>
    </>}

    {tab === "attempts" && <section className="admin-data-panel"><header><div><h2>Payment attempts</h2><p>Review gateway payments and submitted Scan & Pay screenshots.</p></div></header>{attempts.length ? <div className="admin-content-list">{attempts.map(item => <article key={item.id}>
      <span className="admin-row-index">#{item.id}</span><div><strong>{item.order?.order_number || "Order"}</strong><small>{item.provider?.name || item.provider?.code} · {new Date(item.created_at).toLocaleString("en-IN")}</small>{item.proof_reference && <small>UPI reference: {item.proof_reference}</small>}</div>
      <span className={`admin-status admin-status--${item.status}`}>{item.status.replaceAll("_", " ")}</span>
      <div className="admin-crud-actions">{item.provider?.code === "SCANPAY" && item.proof_uploaded_at && <a href={`${API_BASE_URL}/admin/payments/attempts/${item.id}/proof`} target="_blank" rel="noopener noreferrer">View screenshot</a>}
        {item.provider?.code === "SCANPAY" && item.status === "pending_review" ? <><input aria-label={`Review note for ${item.order?.order_number}`} value={reviewNotes[item.id] || ""} onChange={event => setReviewNotes({ ...reviewNotes, [item.id]: event.target.value })} placeholder="Review note (optional)" maxLength={500} /><button disabled={busy} onClick={() => review(item, "approve")}>Approve payment</button><button disabled={busy} onClick={() => review(item, "reject")}>Reject proof</button></> : <button disabled={busy} onClick={() => act(async () => { await api.reconcile(item.id); await loadHistory("attempts"); }, "Attempt reconciled.")}>Reconcile</button>}
      </div>
    </article>)}</div> : <div className="admin-empty"><h3>No payment attempts</h3></div>}</section>}

    {tab === "transactions" && <section className="admin-data-panel"><header><div><h2>Transactions</h2><p>Recorded payments and refunds.</p></div></header>{transactions.length ? <div className="admin-content-list">{transactions.map(item => <article key={item.id}><span className="admin-row-index">#{item.id}</span><div><strong>{item.order?.order_number || "Order"}</strong><small>{item.provider_transaction_id || item.gateway_reference || item.type}</small></div><span>{item.currency || "INR"} {item.amount}</span><span className={`admin-status admin-status--${item.status}`}>{item.status}</span></article>)}</div> : <div className="admin-empty"><h3>No transactions yet</h3></div>}</section>}
    {message && <p className="form-status" role="status">{message}</p>}
  </div>;
}
