import { useEffect, useState } from "react";
import { BookOpen, ChartBar, FileText, GraduationCap, Leaf, Lightbulb, UsersThree } from "@phosphor-icons/react";
import { discoveryService } from "../../services/discoveryService";

const whyE4EngineersItems = [
  { id: 1, title: "Comprehensive learning", description: "Engineering articles and courses in one place.", icon: GraduationCap },
  { id: 2, title: "For every career stage", description: "Designed for students, engineers, faculty and technical professionals.", icon: UsersThree },
  { id: 3, title: "Engineering-focused content", description: "Curated for core engineering disciplines and real-world applications.", icon: Lightbulb },
  { id: 4, title: "Technical perspectives", description: "Explore practical engineering concepts and applications.", icon: ChartBar },
  { id: 5, title: "Learning at your pace", description: "Read engineering insights and explore available courses.", icon: BookOpen },
  { id: 6, title: "Knowledge for a better tomorrow", description: "Build skills. Solve real-world challenges. Shape a sustainable future.", icon: Leaf },
];

function WhyItem({ item }) {
  const Icon = item.icon;
  return (
    <div className="why-item">
      <Icon aria-hidden="true" weight="regular" />
      <div><h3>{item.title}</h3><p>{item.description}</p></div>
    </div>
  );
}

function UpdateItem({ update }) {
  return (
    <li className="update-item">
      <span className="update-item__marker" aria-hidden="true" />
      <FileText aria-hidden="true" weight="regular" />
      <div><a href={update.href}>{update.title}</a><p>{update.description}</p></div>
    </li>
  );
}

export function HomeBottomInfo() {
  const [latestUpdates,setLatestUpdates]=useState([]);
  useEffect(()=>{discoveryService.notices({per_page:5}).then(r=>setLatestUpdates((r.data||[]).map(n=>({id:n.id,title:n.title,description:n.short_description,href:`/notices#${n.slug}`})))).catch(()=>setLatestUpdates([]))},[]);
  return (
    <section className="home-bottom-info" aria-label="Why E4ENGINEERS and latest updates">
      <div className="container home-bottom-info__inner">
        <div className="why-e4engineers" aria-labelledby="why-e4engineers-title">
          <div className="bottom-info-heading">
            <h2 id="why-e4engineers-title">Why E4ENGINEERS</h2>
            <p>More than content. A community for engineering minds.</p>
          </div>
          <div className="why-grid">
            {whyE4EngineersItems.map((item) => <WhyItem item={item} key={item.id} />)}
          </div>
        </div>
        <div className="latest-updates" aria-labelledby="latest-updates-title">
          <div className="bottom-info-heading">
            <h2 id="latest-updates-title">Latest updates</h2>
            <p>New content, resources and more from E4ENGINEERS.</p>
            <a className="latest-updates-link" href="/notices">View all notices</a>
          </div>
          <ul className="updates-list">
            {latestUpdates.map((update) => <UpdateItem update={update} key={update.id} />)}
          </ul>
          {!latestUpdates.length && <p>There are no published updates yet.</p>}
        </div>
      </div>
    </section>
  );
}
