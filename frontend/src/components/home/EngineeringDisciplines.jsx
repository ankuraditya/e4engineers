import {
  ArrowRight,
  Bridge,
  CellTower,
  Cpu,
  Gear,
  Hammer,
  Lightning,
  Monitor,
  Sigma,
} from "@phosphor-icons/react";

const disciplines = [
  { name: "Electrical Engineering", lines: ["Electrical", "Engineering"], slug: "electrical-engineering", icon: Lightning },
  { name: "Mechanical Engineering", lines: ["Mechanical", "Engineering"], slug: "mechanical-engineering", icon: Gear },
  { name: "Civil Engineering", lines: ["Civil", "Engineering"], slug: "civil-engineering", icon: Bridge },
  { name: "Mining Engineering", lines: ["Mining", "Engineering"], slug: "mining-engineering", icon: Hammer },
  { name: "Electronics Engineering", lines: ["Electronics", "Engineering"], slug: "electronics-engineering", icon: Cpu },
  { name: "Computer Science Engineering", lines: ["Computer Science", "Engineering"], slug: "computer-science-engineering", icon: Monitor },
  { name: "Power Systems Engineering", lines: ["Power Systems", "Engineering"], slug: "power-systems-engineering", icon: CellTower },
  { name: "Engineering Mathematics", lines: ["Engineering", "Mathematics"], slug: "engineering-mathematics", icon: Sigma },
  { name: "Metallurgy Engineering", lines: ["Metallurgy", "Engineering"], slug: "metallurgy-engineering", icon: Gear },
];

function DisciplineCard({ discipline }) {
  const Icon = discipline.icon;

  return (
    <a className="discipline-card" href={`/engineering/${discipline.slug}`} aria-label={discipline.name}>
      <Icon aria-hidden="true" weight="regular" />
      <span>{discipline.lines[0]}<br />{discipline.lines[1]}</span>
    </a>
  );
}

export function EngineeringDisciplines() {
  return (
    <section className="engineering-disciplines" id="engineering" aria-labelledby="engineering-disciplines-title">
      <div className="container engineering-disciplines__inner">
        <div className="section-heading">
          <div>
            <h2 id="engineering-disciplines-title">Explore by engineering discipline</h2>
            <p>Discover articles, courses, books and resources tailored to your engineering field.</p>
          </div>
          <a className="section-heading__link" href="/engineering">
            View all disciplines <ArrowRight aria-hidden="true" weight="bold" />
          </a>
        </div>
        <div className="discipline-grid">
          {disciplines.map((discipline) => <DisciplineCard discipline={discipline} key={discipline.slug} />)}
        </div>
      </div>
    </section>
  );
}
