import { LinkedinLogo, Rss, TwitterLogo, YoutubeLogo } from "@phosphor-icons/react";

const footerNavigation = [
  {
    title: "Engineering",
    links: [
      ["Electrical Engineering", "/engineering/electrical-engineering"],
      ["Mechanical Engineering", "/engineering/mechanical-engineering"],
      ["Civil Engineering", "/engineering/civil-engineering"],
      ["Mining Engineering", "/engineering/mining-engineering"],
      ["Electronics Engineering", "/engineering/electronics-engineering"],
      ["Computer Science Engineering", "/engineering/computer-science-engineering"],
      ["Power Systems Engineering", "/engineering/power-systems-engineering"],
      ["Engineering Mathematics", "/engineering/engineering-mathematics"],
      ["Metallurgy Engineering", "/engineering/metallurgy-engineering"],
    ],
  },
  {
    title: "Learning",
    links: [
      ["All Courses", "/courses"],
      ["Study Resources", "/resources"],
      ["Lecture Notes", "/resources?type=lecture-notes"],
      ["Question Papers", "/resources?type=solved-question-papers"],
      ["Technical Diagrams", "/resources?type=technical-diagrams"],
      ["Formula Sheets", "/resources?type=formula-sheets"],
    ],
  },
  {
    title: "Publications",
    links: [
      ["Journals", "/publications?type=journal"],
      ["Research Articles", "/articles"],
      ["Resources", "/resources"],
    ],
  },
  {
    title: "Shop",
    links: [
      ["Books", "/books"],
      ["New Arrivals", "/books?new_arrival=1"],
      ["Featured Books", "/books?featured=1"],
      ["Track Order", "/account/orders"],
    ],
  },
  {
    title: "Company",
    links: [
      ["About Us", "/about"],
      ["Our Mission", "/about#mission"],
      ["Contributors", "/contributors"],
      ["Careers", "/careers"],
      ["Contact Us", "/contact"],
    ],
  },
  {
    title: "Privacy / Legal",
    links: [
      ["Privacy Policy", "/privacy-policy"],
      ["Terms of Use", "/terms"],
      ["Shipping Policy", "/shipping-policy"],
      ["Returns & Refunds", "/returns-refunds"],
      ["FAQ", "/faq"],
      ["Support", "/support"],
    ],
  },
];

const socialLinks = [
  { label: "LinkedIn", href: "#linkedin", icon: LinkedinLogo },
  { label: "YouTube", href: "#youtube", icon: YoutubeLogo },
  { label: "Twitter", href: "#twitter", icon: TwitterLogo },
  { label: "RSS", href: "/rss.xml", icon: Rss },
];

function FooterColumn({ group, publicationsEnabled }) {
  return (
    <nav className="footer-column" aria-label={group.title}>
      <h2>{group.title}</h2>
      <ul>{group.links.filter(([, href]) => publicationsEnabled || !href.startsWith('/publications')).map(([label, href]) => <li key={label}><a href={href}>{label}</a></li>)}</ul>
    </nav>
  );
}

export function Footer({ publicationsEnabled = true }) {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="site-footer">
      <div className="container footer-inner">
        <div className="footer-main">
          <div className="footer-brand">
            <a className="footer-wordmark" href="#top" aria-label="E4ENGINEERS home"><img src="/brand/e4engineers-logo.png" alt="E4ENGINEERS — Engineering Knowledge Connected" /></a>
            <p>Engineering knowledge for<br />a brighter tomorrow.</p>
            <div className="footer-socials">
              {socialLinks.map(({ label, href, icon: Icon }) => <a href={href} aria-label={label} key={label}><Icon aria-hidden="true" weight="fill" /></a>)}
            </div>
          </div>
          {footerNavigation.map((group) => <FooterColumn group={group} publicationsEnabled={publicationsEnabled} key={group.title} />)}
        </div>
        <div className="footer-bottom">
          <p>© {currentYear} E4ENGINEERS. All rights reserved.</p>
          <p>Engineering knowledge. Connected.</p>
        </div>
      </div>
    </footer>
  );
}
