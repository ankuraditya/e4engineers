import { useEffect, useState } from 'react';
import { adminService } from '../../services/adminService';

const groups = [
  ['Overview', [['Dashboard', '/admin', 'grid']]],
  ['Website', [['Pages, Banners & Media', '/admin/content', 'edit'], ['Notices, Gallery & Videos', '/admin/discovery', 'media']]],
  ['Publishing', [['Articles, Courses & Resources', '/admin/publishing', 'edit']]],
  ['Store', [['Book Catalogue & Stock', '/admin/books', 'bag'], ['Orders', '/admin/orders', 'box'], ['Payments & Gateways', '/admin/payments', 'card'], ['Invoices', '/admin/invoices', 'file'], ['Shipping Providers', '/admin/shipping', 'truck'], ['Shipments', '/admin/shipments', 'truck']]],
  ['Customers', [['Enquiries, Workshops & Careers', '/admin/operations', 'chat'], ['Internship Certificates', '/admin/certificates', 'file']]],
  ['System', [['Notifications', '/admin/notifications', 'bell']]],
];

const icons = {
  grid: 'M4 4h6v6H4zm10 0h6v6h-6zM4 14h6v6H4zm10 0h6v6h-6z', edit: 'M4 20h4l11-11-4-4L4 16zm10-14 4 4',
  media: 'M4 5h16v14H4zm3 10 3-3 3 3 2-2 3 3M9 9h.01', bag: 'M5 8h14l-1 12H6zm4 0V6a3 3 0 0 1 6 0v2',
  card: 'M3 6h18v12H3zm0 4h18M7 15h3', file: 'M6 3h9l4 4v14H6zm9 0v5h5M9 13h6M9 17h6',
  truck: 'M3 6h11v10H3zm11 4h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4m11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4',
  box: 'M4 7 12 3l8 4-8 4zm0 0v10l8 4 8-4V7M12 11v10', chat: 'M4 5h16v12H8l-4 4zm4 5h8m-8 3h5',
  bell: 'M6 17h12l-2-3V9a4 4 0 0 0-8 0v5zm4 3h4',
};
function NavIcon({ name }) { return <svg viewBox="0 0 24 24" aria-hidden="true"><path d={icons[name]} /></svg>; }
function readCachedUser() { try { return JSON.parse(sessionStorage.getItem('e4-admin-user') || 'null'); } catch { return null; } }

export function AdminLayout({ children }) {
  const [user, setUser] = useState(readCachedUser);
  const [menuOpen, setMenuOpen] = useState(false);
  useEffect(() => { adminService.me().then((response) => { const currentUser = response.data.user; sessionStorage.setItem('e4-admin-user', JSON.stringify(currentUser)); setUser(currentUser); }).catch(() => { sessionStorage.removeItem('e4-admin-user'); location.replace(`/admin/login?redirect=${encodeURIComponent(location.pathname)}`); }); }, []);
  if (!user) return <div className="admin-loading"><span className="admin-spinner" />Checking administrator access…</div>;
  return <div className="admin-shell"><aside className="admin-sidebar">
    <a className="admin-brand" href="/admin"><img src="/brand/e4engineers-logo.png" alt="E4ENGINEERS" /></a>
    <button className="admin-menu-toggle" type="button" aria-expanded={menuOpen} aria-controls="admin-navigation" onClick={() => setMenuOpen((open) => !open)}>{menuOpen ? 'Close menu' : '☰ Menu'}</button>
    <div id="admin-navigation" className={`admin-sidebar-scroll${menuOpen ? ' is-open' : ''}`}>{groups.map(([title, links]) => <nav key={title}><strong>{title}</strong>{links.map(([label, href, icon]) => { const active = href === '/admin' ? location.pathname === href : location.pathname.startsWith(href); return <a className={active ? 'active' : ''} href={href} key={href}><NavIcon name={icon} /><span>{label}</span></a>; })}</nav>)}</div>
    <div className="admin-profile"><span>{user.name?.slice(0, 1) || 'A'}</span><div><strong>{user.name}</strong><small>Super administrator</small></div></div>
    <button className="admin-signout" onClick={async () => { await adminService.logout(); sessionStorage.removeItem('e4-admin-user'); location.replace('/admin/login'); }}>Sign out</button>
  </aside><div className="admin-main"><header><div><small>Workspace</small><strong>E4ENGINEERS Administration</strong></div><div className="admin-header-actions"><span className="admin-live"><i /> System online</span><a href="/" target="_blank">View website ↗</a></div></header><main>{children}</main></div></div>;
}
