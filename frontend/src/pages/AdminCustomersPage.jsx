import { useEffect, useState } from 'react';
import { InnerPageHeader } from '../components/shared/InnerPageComponents.jsx';
import { adminService } from '../services/adminService.js';

function registrationDate(value) {
  return value ? new Intl.DateTimeFormat('en-IN', { dateStyle: 'medium' }).format(new Date(value)) : '—';
}

export function AdminCustomersPage() {
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [customers, setCustomers] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [retry, setRetry] = useState(0);

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError('');
    adminService.customers({ search, page }).then((response) => {
      if (!active) return;
      setCustomers(response.data || []);
      setMeta(response.meta || null);
    }).catch((currentError) => {
      if (active) setError(currentError.message);
    }).finally(() => {
      if (active) setLoading(false);
    });
    return () => { active = false; };
  }, [search, page, retry]);

  return <div className="admin-module admin-customers">
    <InnerPageHeader eyebrow="Customers / Accounts" title="Registered Users" description="Find customers who have registered on the website and review their contact details." />
    <form className="admin-toolbar" onSubmit={(event) => { event.preventDefault(); setPage(1); setSearch(searchInput.trim()); }}>
      <label><span>Search users</span><input value={searchInput} onChange={(event) => setSearchInput(event.target.value)} placeholder="Name, email or mobile number" maxLength={100} /></label>
      <button className="button button--primary" type="submit">Search</button>
      {search && <button className="button button--secondary" type="button" onClick={() => { setSearchInput(''); setSearch(''); setPage(1); }}>Clear</button>}
    </form>
    <section className="admin-data-panel">
      <header><div><h2>Users</h2><p>{meta ? `${meta.total} registered ${meta.total === 1 ? 'account' : 'accounts'}` : 'Registered customer accounts'}</p></div></header>
      {error ? <div className="admin-empty"><h3>Could not load users</h3><p>{error}</p><button type="button" onClick={() => setRetry((value) => value + 1)}>Try again</button></div> : loading ? <div className="admin-list-loading">Loading registered users…</div> : customers.length ? <>
        <div className="admin-customer-table"><div className="admin-customer-head"><span>Customer</span><span>Contact</span><span>Registered</span><span>Orders</span><span>Status</span></div>
          {customers.map((customer) => <div className="admin-customer-row" key={customer.id}>
            <strong>{customer.name}</strong>
            <div><a href={`mailto:${customer.email}`}>{customer.email}</a>{customer.mobile && <a href={`tel:${customer.mobile}`}>{customer.mobile}</a>}</div>
            <span>{registrationDate(customer.registered_at)}</span>
            <span>{customer.orders_count ?? 0}</span>
            <span className={`admin-status admin-status--${customer.status}`}>{customer.status || '—'}</span>
          </div>)}
        </div>
        {meta && meta.last_page > 1 && <div className="admin-customer-pagination"><button type="button" disabled={page <= 1} onClick={() => setPage(page - 1)}>← Previous</button><span>Page {meta.current_page} of {meta.last_page}</span><button type="button" disabled={page >= meta.last_page} onClick={() => setPage(page + 1)}>Next →</button></div>}
      </> : <div className="admin-empty"><h3>No users found</h3><p>{search ? 'Try another name, email or mobile number.' : 'Registered customer accounts will appear here.'}</p></div>}
    </section>
  </div>;
}
