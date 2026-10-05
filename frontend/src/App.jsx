import { useEffect, useRef, useState, useSyncExternalStore } from "react";
import { ArrowRight, BookOpen, CaretDown, ChartBar, Database, Lightbulb, List, MagnifyingGlass, ShoppingCart, User, X } from "@phosphor-icons/react";
import { FeaturedCourses } from "./components/home/FeaturedCourses";
import { updateSeo } from "./services/seo.js";
import { EngineeringBookCatalogue } from "./components/home/EngineeringBookCatalogue";
import { FeaturedPublications } from "./components/home/FeaturedPublications";
import { EngineeringDisciplines } from "./components/home/EngineeringDisciplines";
import { StudyResources } from "./components/home/StudyResources";
import { FeaturedKnowledge } from "./components/home/FeaturedKnowledge";
import { HomeBottomInfo } from "./components/home/HomeBottomInfo";
import { Footer } from "./components/layout/Footer";
import { EmptyState } from "./components/shared/InnerPageComponents";
import { AboutPage, DisciplineDetailPage, EngineeringPage } from "./pages/PhaseOnePages";
import { ArticleDetailPage, ArticlesPage } from "./pages/ArticlePages";
import { PublicationDetailPage, PublicationsPage } from "./pages/PublicationPages";
import { InternshipsPage } from "./pages/InternshipsPage";
import { VerifyCertificatePage } from "./pages/VerifyCertificatePage";
import { CourseDetailPage, CoursesPage } from "./pages/CoursePages";
import { ContactPage, SearchPage } from "./pages/PhaseThreePages";
import { BookDetailPage, BooksPage } from "./pages/BookPages";
import { ResourceDetailPage, ResourcesPage } from "./pages/ResourcePages";
import { CartPage, ForgotPasswordPage, LoginPage, RegisterPage, ResetPasswordPage } from "./pages/PhaseFourPages";
import { AccountPage, AddressesPage, CheckoutPage, OrderSuccessPage, ProfilePage } from "./pages/PhaseFivePages";
import { OrderDetailPage, OrdersPage, TrackOrderPage } from "./pages/PhaseSixPages";
import { DigitalResourcesPage, DownloadsPage } from "./pages/AccountDigitalPages";
import { AccountSettingsPage, ChangePasswordPage, ContributorsPage, FaqPage, PaymentHistoryPage } from "./pages/PhaseSevenPages";
import { GalleryPage, NoticesPage, SupportPage, VideosPage, WorkshopsPage } from "./pages/PhaseEightPages";
import { CareersPage, PrivacyPolicyPage, ReturnsRefundsPage, ShippingPolicyPage, TermsPage } from "./pages/PhaseNinePages";
import { useCart } from "./state/CartContext";
import { useAuth } from "./state/AuthContext.jsx";
import { accountDestination } from "./services/authNavigation.js";
import { AdminShippingPage } from "./pages/AdminShippingPage.jsx";
import { AdminPaymentPage } from "./pages/AdminPaymentPage.jsx";
import { AdminInvoicePage } from "./pages/AdminInvoicePage.jsx";
import { AdminShipmentPage } from "./pages/AdminShipmentPage.jsx";
import { AdminNotificationsPage } from "./pages/AdminNotificationsPage.jsx";
import { AdminOperationsPage } from "./pages/AdminOperationsPage.jsx";
import { AdminCertificatesPage } from "./pages/AdminCertificatesPage.jsx";
import { AdminDiscoveryPage } from "./pages/AdminDiscoveryPage.jsx";
import { AdminLayout } from "./components/admin/AdminLayout.jsx";
import { AdminDashboardPage, AdminLoginPage } from "./pages/AdminCorePages.jsx";
import { AdminOrdersPage } from "./pages/AdminOrdersPage.jsx";
import { AdminContentManager } from "./pages/AdminContentManager.jsx";
import { AdminBooksPage } from "./pages/AdminBooksPage.jsx";
import { discoveryService } from "./services/discoveryService.js";
import { cmsService } from "./services/cmsService.js";
import { navigate, ROUTE_CHANGE_EVENT } from "./state/PageLoaderContext.jsx";

function subscribeToRoute(callback) {
  window.addEventListener(ROUTE_CHANGE_EVENT, callback);
  window.addEventListener("popstate", callback);
  return () => {
    window.removeEventListener(ROUTE_CHANGE_EVENT, callback);
    window.removeEventListener("popstate", callback);
  };
}

function currentRoute() { return `${window.location.pathname}${window.location.search}`; }

const navigation = [
  { label: "Engineering", items: [
    ["Electrical Engineering", "/engineering/electrical-engineering"],
    ["Mechanical Engineering", "/engineering/mechanical-engineering"],
    ["Civil Engineering", "/engineering/civil-engineering"],
    ["Mining Engineering", "/engineering/mining-engineering"],
    ["Electronics Engineering", "/engineering/electronics-engineering"],
    ["Computer Science Engineering", "/engineering/computer-science-engineering"],
    ["Power Systems Engineering", "/engineering/power-systems-engineering"],
    ["Engineering Mathematics", "/engineering/engineering-mathematics"],
  ] },
  { label: "Learn", items: [
    ["Courses", "/courses"],
    ["Study Resources", "/resources"],
    ["Lecture Notes", "/resources?type=lecture-notes"],
    ["Solved Question Papers", "/resources?type=solved-question-papers"],
    ["Technical Diagrams", "/resources?type=technical-diagrams"],
    ["Formula Sheets", "/resources?type=formula-sheets"],
    ["Study Guides", "/resources?type=study-guides"],
    ["Practice Materials", "/resources?type=practice-materials"],
  ] },
  { label: "Internship", items: [
    ["Explore Internships", "/internships"],
    ["Verify Certificate", "/verify-certificate"],
  ] },
  { label: "Books", href: "/books" }, { label: "About", href: "/about" },
];

const trustItems = [
  { icon: BookOpen, lines: ["Learn", "Anytime"] },
  { icon: Database, lines: ["Trusted", "Content"] },
  { icon: ChartBar, lines: ["For Students,", "Professionals & Faculty"] },
  { icon: Lightbulb, lines: ["Build a", "Brighter Tomorrow"] },
];

function SearchBar({ compact = false }) {
  const [query, setQuery] = useState("");
  const [submitted, setSubmitted] = useState(false);
  const [suggestions,setSuggestions]=useState([]);
  useEffect(()=>{if(query.trim().length<2){setSuggestions([]);return}const timer=setTimeout(()=>discoveryService.suggestions(query.trim()).then(r=>setSuggestions(r.data||[])).catch(()=>setSuggestions([])),180);return()=>clearTimeout(timer)},[query]);
  function submitSearch(event) { event.preventDefault(); if (query.trim()) navigate(`/search?q=${encodeURIComponent(query.trim())}`); else setSubmitted(false); }
  return (
    <form className={`search-bar ${compact ? "search-bar--compact" : ""}`} role="search" onSubmit={submitSearch}>
      <MagnifyingGlass aria-hidden="true" weight="bold" />
      <label className="sr-only" htmlFor={compact ? "header-search" : "hero-search"}>Search E4ENGINEERS</label>
      <input list={compact?"header-suggestions":"hero-suggestions"} id={compact ? "header-search" : "hero-search"} value={query} onChange={(event) => { setQuery(event.target.value); setSubmitted(false); }} placeholder={compact ? "Search articles, courses, books..." : "Search for topics, articles, courses, books and more..."} /><datalist id={compact?"header-suggestions":"hero-suggestions"}>{suggestions.map(item=><option value={item.title} key={`${item.type}-${item.url}`}>{item.type}</option>)}</datalist>
      <button type="submit" aria-label={compact ? "Submit header search" : undefined}>{compact ? <MagnifyingGlass aria-hidden="true" weight="bold" /> : "Search"}</button>
      {submitted && <span className="search-feedback" role="status">Search connection is ready for a future API.</span>}
    </form>
  );
}

function Header({ publicationsEnabled }) {
  const [openMenu, setOpenMenu] = useState(null);
  const [mobileOpen, setMobileOpen] = useState(false);
  const navRef = useRef(null);
  const currentPath = window.location.pathname;
  const { cartCount } = useCart();
  const { isAuthenticated } = useAuth();
  useEffect(() => {
    function closeOutside(event) { if (navRef.current && !navRef.current.contains(event.target)) setOpenMenu(null); }
    function closeOnEscape(event) { if (event.key === "Escape") { setOpenMenu(null); setMobileOpen(false); } }
    document.addEventListener("pointerdown", closeOutside); document.addEventListener("keydown", closeOnEscape);
    return () => { document.removeEventListener("pointerdown", closeOutside); document.removeEventListener("keydown", closeOnEscape); };
  }, []);
  return (
    <header className="site-header">
      <div className="container header-inner">
        <a className="wordmark" href="/" aria-label="E4ENGINEERS home"><img src="/brand/e4engineers-logo.png" alt="E4ENGINEERS — Engineering Knowledge Connected" /></a>
        <button className="mobile-menu-button" type="button" aria-label={mobileOpen ? "Close navigation" : "Open navigation"} aria-expanded={mobileOpen} onClick={() => { setMobileOpen((value) => !value); if (mobileOpen) setOpenMenu(null); }}>{mobileOpen ? <X weight="bold" /> : <List weight="bold" />}</button>
        <nav className={`main-nav ${mobileOpen ? "main-nav--open" : ""}`} aria-label="Primary navigation" ref={navRef}>
          {navigation.map((item) => (
            <div className="nav-item" key={item.label}>
              {item.items ? <>
                <button className={`nav-link ${openMenu === item.label ? "nav-link--active" : ""} ${(item.label === "Engineering" && currentPath.startsWith("/engineering")) || (item.label === "Learn" && (currentPath.startsWith("/courses") || currentPath.startsWith("/resources"))) || (item.label === "Internship" && (currentPath.startsWith("/internships") || currentPath === "/verify-certificate")) ? "nav-link--current" : ""}`} type="button" aria-expanded={openMenu === item.label} aria-haspopup="menu" onClick={() => setOpenMenu((current) => current === item.label ? null : item.label)}>{item.label}<CaretDown aria-hidden="true" weight="bold" /></button>
                {openMenu === item.label && <div className={`nav-dropdown nav-dropdown--${item.label.toLowerCase()}`} role="menu">{item.items.filter(([, href]) => publicationsEnabled || !href.startsWith('/publications')).map(([label, href]) => <a href={href} role="menuitem" onClick={() => { setOpenMenu(null); setMobileOpen(false); }} key={label}>{label}</a>)}</div>}
              </> : <a className={`nav-link ${(currentPath === item.href || (item.href === "/books" && currentPath.startsWith("/books/"))) ? "nav-link--current" : ""}`} href={item.href} onClick={() => { setOpenMenu(null); setMobileOpen(false); }}>{item.label}</a>}
            </div>
          ))}
          <div className="mobile-nav-search"><SearchBar compact /></div>
        </nav>
        <div className="header-actions"><SearchBar compact /><a className="icon-button" href={accountDestination(isAuthenticated)} aria-label="My account"><User aria-hidden="true" /></a><a className="icon-button header-cart-link" href="/cart" aria-label={`Cart${cartCount?` with ${cartCount} items`:""}`}><ShoppingCart aria-hidden="true" />{cartCount>0&&<span>{cartCount}</span>}</a></div>
      </div>
    </header>
  );
}

function TrustStrip() {
  return <div className="trust-strip" aria-label="Why learners choose E4ENGINEERS"><div className="container trust-strip__inner">{trustItems.map(({ icon: Icon, lines }) => <div className="trust-item" key={lines.join(" ")}><Icon aria-hidden="true" /><span>{lines[0]}<br />{lines[1]}</span></div>)}</div></div>;
}

function safeBannerHref(value, fallback) {
  if (!value || value.startsWith("//")) return fallback;
  try {
    const url = new URL(value, "https://e4engineers.local");
    return ["http:", "https:"].includes(url.protocol) ? value : fallback;
  } catch {
    return fallback;
  }
}

function Hero() {
  const [banner, setBanner] = useState(null);
  useEffect(() => {
    let active = true;
    cmsService.banners("homepage-hero").then((result) => {
      if (active) setBanner(result.data?.[0] || null);
    }).catch(() => {});
    return () => { active = false; };
  }, []);
  return <section className="hero" aria-labelledby="hero-title">
    <div className="container hero-layout">
      <div className="hero-copy">
        <p className="eyebrow">{banner?.eyebrow || "A modern engineering knowledge portal"}</p>
        <h1 id="hero-title">{banner?.heading || <>Engineering<br />knowledge, connected.</>}</h1>
        <p className="hero-description">{banner?.description || "Explore articles, courses, study resources, publications and books."}</p>
        <SearchBar />
        <div className="hero-actions"><a className="button button--primary" href={safeBannerHref(banner?.primary_cta?.url, "#engineering")}>{banner?.primary_cta?.label || "Explore engineering"} <ArrowRight aria-hidden="true" weight="bold" /></a><a className="button button--secondary" href={safeBannerHref(banner?.secondary_cta?.url, "#resources")}>{banner?.secondary_cta?.label || "Browse resources"}</a></div>
      </div>
      <div className="hero-visual">
        <img src={banner?.desktop_media?.url || "/hero-engineering-ecosystem.png"} alt={banner?.desktop_media?.alt_text || "Blueprint illustration combining electrical, mechanical, civil, mining, computing, energy and mathematics disciplines"} />
        <div className="hero-side-note" aria-hidden="true"><span>Engineering</span><span>ideas for a</span><span>better tomorrow</span><i /></div>
      </div>
    </div>
    <TrustStrip />
  </section>;
}

function HomePage({ publicationsEnabled }) { return <><Hero /><FeaturedCourses /><EngineeringBookCatalogue />{publicationsEnabled && <FeaturedPublications />}<EngineeringDisciplines /><StudyResources /><FeaturedKnowledge /><HomeBottomInfo /></>; }

function SiteLayout({ children, publicationsEnabled }) { return <div id="top" className="app-shell"><Header publicationsEnabled={publicationsEnabled} /><main>{children}</main><Footer publicationsEnabled={publicationsEnabled} /></div>; }

export function App() {
  const route = useSyncExternalStore(subscribeToRoute, currentRoute);
  const path = route.split("?")[0].replace(/\/+$/, "") || "/";
  const [publicationsEnabled, setPublicationsEnabled] = useState(false);
  useEffect(() => { let active = true; cmsService.settings().then(response => { if (active) setPublicationsEnabled(response.data?.publications_enabled !== '0'); }).catch(() => { if (active) setPublicationsEnabled(true); }); return () => { active = false; }; }, [route]);
  useEffect(() => { updateSeo(path, publicationsEnabled); }, [path, publicationsEnabled]);
  if (path === "/admin/login") return <AdminLoginPage />;
  let page = path === "/" ? <HomePage publicationsEnabled={publicationsEnabled} /> : <div className="inner-page"><div className="container inner-page__container"><EmptyState title="Page not found" message="The page you requested is unavailable." /></div></div>;
  if (path === "/about") page = <AboutPage />;
  else if (path === "/engineering") page = <EngineeringPage />;
  else if (path.startsWith("/engineering/")) page = <DisciplineDetailPage slug={decodeURIComponent(path.split("/")[2] || "electrical-engineering")} publicationsEnabled={publicationsEnabled} />;
  else if (path === "/articles") page = <ArticlesPage />;
  else if (path.startsWith("/articles/")) page = <ArticleDetailPage slug={decodeURIComponent(path.split("/")[2] || "power-system-economics")} />;
  else if (path === "/courses") page = <CoursesPage />;
  else if (path === "/internships") page = <InternshipsPage />;
  else if (path === "/verify-certificate") page = <VerifyCertificatePage />;
  else if (path.startsWith("/courses/")) page = <CourseDetailPage slug={decodeURIComponent(path.split("/")[2] || "fundamentals-of-electrical-and-electronics-engineering")} />;
  else if (path === "/publications") page = publicationsEnabled ? <PublicationsPage /> : <div className="inner-page"><div className="container inner-page__container"><EmptyState title="Publications unavailable" message="Journals and publications are currently hidden." /></div></div>;
  else if (path.startsWith("/publications/")) page = publicationsEnabled ? <PublicationDetailPage slug={decodeURIComponent(path.split("/")[2] || "international-journal-of-electrical-engineering")} /> : <div className="inner-page"><div className="container inner-page__container"><EmptyState title="Publications unavailable" message="Journals and publications are currently hidden." /></div></div>;
  else if (path === "/resources") page = <ResourcesPage />;
  else if (path.startsWith("/resources/")) page = <ResourceDetailPage slug={decodeURIComponent(path.split("/")[2] || "engineering-mathematics-formula-sheet")} />;
  else if (path === "/books") page = <BooksPage />;
  else if (path.startsWith("/books/")) page = <BookDetailPage slug={decodeURIComponent(path.split("/")[2] || "electrical-power-systems")} />;
  else if (path === "/search") page = <SearchPage />;
  else if (path === "/contact") page = <ContactPage />;
  else if (path === "/faq") page = <FaqPage />;
  else if (path === "/contributors") page = <ContributorsPage />;
  else if (path === "/notices") page = <NoticesPage />;
  else if (path === "/workshops") page = <WorkshopsPage />;
  else if (path === "/gallery") page = <GalleryPage />;
  else if (path === "/videos") page = <VideosPage />;
  else if (path === "/support") page = <SupportPage />;
  else if (path === "/privacy-policy") page = <PrivacyPolicyPage />;
  else if (path === "/terms") page = <TermsPage />;
  else if (path === "/shipping-policy") page = <ShippingPolicyPage />;
  else if (path === "/returns-refunds") page = <ReturnsRefundsPage />;
  else if (path === "/careers") page = <CareersPage />;
  else if (path === "/login") page = <LoginPage />;
  else if (path === "/register") page = <RegisterPage />;
  else if (path === "/forgot-password") page = <ForgotPasswordPage />;
  else if (path === "/reset-password") page = <ResetPasswordPage />;
  else if (path === "/cart") page = <CartPage />;
  else if (path === "/checkout") page = <CheckoutPage />;
  else if (path.startsWith("/order-success/")) page = <OrderSuccessPage orderNumber={decodeURIComponent(path.split("/")[2] || "")} />;
  else if (path === "/account") page = <AccountPage />;
  else if (path === "/account/profile") page = <ProfilePage />;
  else if (path === "/account/addresses") page = <AddressesPage />;
  else if (path === "/account/orders") page = <OrdersPage />;
  else if (path.startsWith("/account/orders/") && path.endsWith("/track")) page = <TrackOrderPage orderNumber={decodeURIComponent(path.split("/")[3] || "")} />;
  else if (path.startsWith("/account/orders/")) page = <OrderDetailPage orderNumber={decodeURIComponent(path.split("/")[3] || "")} />;
  else if (path === "/account/digital-resources") page = <DigitalResourcesPage />;
  else if (path === "/account/downloads") page = <DownloadsPage />;
  else if (path === "/account/payments") page = <PaymentHistoryPage />;
  else if (path === "/account/settings") page = <AccountSettingsPage />;
  else if (path === "/account/change-password") page = <ChangePasswordPage />;
  else if (path === "/admin") page = <AdminDashboardPage />;
  else if (path === "/admin/content") page = <AdminContentManager scope="website" />;
  else if (path === "/admin/publishing") page = <AdminContentManager scope="publishing" />;
  else if (path === "/admin/books") page = <AdminBooksPage />;
  else if (path === "/admin/orders") page = <AdminOrdersPage />;
  else if (path === "/admin/shipping") page = <AdminShippingPage />;
  else if (path === "/admin/shipments") page = <AdminShipmentPage />;
  else if (path === "/admin/notifications") page = <AdminNotificationsPage />;
  else if (path === "/admin/operations") page = <AdminOperationsPage />;
  else if (path === "/admin/certificates") page = <AdminCertificatesPage />;
  else if (path === "/admin/discovery") page = <AdminDiscoveryPage />;
  else if (path === "/admin/payments") page = <AdminPaymentPage />;
  else if (path === "/admin/invoices") page = <AdminInvoicePage />;
  if (path.startsWith('/admin')) return <AdminLayout key={route}>{page}</AdminLayout>;
  return <SiteLayout key={route} publicationsEnabled={publicationsEnabled}>{page}</SiteLayout>;
}
