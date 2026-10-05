import { useEffect, useState } from 'react';
import { Breadcrumb, InnerPageHeader } from '../components/shared/InnerPageComponents.jsx';
import { adminShipmentService as api } from '../services/adminShipmentService.js';

export function AdminShipmentPage() {
  const [shipments, setShipments] = useState([]);
  const [selected, setSelected] = useState(null);
  const [status, setStatus] = useState('');
  const [message, setMessage] = useState('');
  async function load() { try { const response = await api.list(status); setShipments(response.data || []); } catch (error) { setMessage(error.message); } }
  useEffect(() => { load(); }, [status]);
  async function run(id, action) { try { await api.action(id, action); setMessage(`Shipment action completed: ${action}.`); setSelected((await api.show(id)).data); load(); } catch (error) { setMessage(error.message); } }
  return <div className="inner-page"><div className="container inner-page__container"><Breadcrumb items={[{ label: 'Admin' }, { label: 'Shipments' }]}/><InnerPageHeader eyebrow="Fulfilment" title="Shipment Management" description="Book, reconcile, document and track physical order shipments."/><label>Status filter <select value={status} onChange={event => setStatus(event.target.value)}><option value="">All</option>{['booking','booked','awb_assigned','pickup_scheduled','in_transit','out_for_delivery','delivered','delivery_failed','rto_initiated','cancelled','booking_failed','unknown'].map(value => <option key={value}>{value}</option>)}</select></label><section className="account-section">{shipments.map(shipment => <article className="contact-review" key={shipment.id}><strong>{shipment.order?.order_number} · {shipment.status}</strong><span>{shipment.courier_name || shipment.provider?.name} {shipment.awb_number ? `· ${shipment.awb_number}` : ''}</span><button className="text-action" onClick={async () => setSelected((await api.show(shipment.id)).data)}>Manage</button></article>)}</section>{selected && <section className="account-section"><h2>Shipment #{selected.id}</h2><p>{selected.status} · {selected.provider?.name} · {selected.awb_number || 'AWB pending'}</p><div className="success-actions">{['awb','pickup','label','manifest','refresh-tracking','reconcile','retry','cancel'].map(action => <button key={action} className="button button--secondary" onClick={() => run(selected.id, action)}>{action.replaceAll('-', ' ')}</button>)}</div><h3>Tracking timeline</h3>{selected.tracking_events?.map(event => <p key={event.id}><strong>{event.label}</strong> · {event.location || 'Location unavailable'} · {new Date(event.occurred_at).toLocaleString()}</p>)}</section>}{message && <p className="form-status" role="status">{message}</p>}</div></div>;
}
