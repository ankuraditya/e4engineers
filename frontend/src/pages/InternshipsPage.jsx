import { useEffect, useState } from 'react';
import { ArrowRight, Briefcase, GraduationCap, MapPin } from '@phosphor-icons/react';
import { Breadcrumb, InnerPageHeader } from '../components/shared/InnerPageComponents';
import { operationsService } from '../services/operationsService';

export function InternshipsPage() {
  const [programs, setPrograms] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    operationsService.careers()
      .then((response) => {
        if (active) setPrograms((response.data || []).filter((job) => job.employment_type === 'internship'));
      })
      .catch(() => { if (active) setError('Internship programs could not be loaded. Please try again later.'); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);

  return <div className="inner-page internships-page"><div className="container inner-page__container">
    <Breadcrumb items={[{ label: 'Home', href: '/' }, { label: 'Internships' }]} />
    <InnerPageHeader eyebrow="Learn through real work" title="Explore Internships" description="Discover available internship programs at E4ENGINEERS and take the next step in your engineering journey." />
    <section className="internships-section" aria-labelledby="internships-heading">
      <div className="internships-section__heading"><div><p className="inner-page-header__eyebrow">Current opportunities</p><h2 id="internships-heading">Available programs</h2><p>Programs published by the E4ENGINEERS team appear here.</p></div><GraduationCap aria-hidden="true" /></div>
      {loading ? <p className="internships-status" role="status">Loading internship programs…</p>
        : error ? <p className="internships-status" role="alert">{error}</p>
          : programs.length ? <div className="internships-grid">{programs.map((program) => <article className="internship-card" key={program.id}>
            <div className="internship-card__icon"><Briefcase aria-hidden="true" /></div>
            <p className="internship-card__department">{program.department}</p>
            <h3>{program.title}</h3>
            <p className="internship-card__summary">{program.summary}</p>
            <p className="internship-card__meta"><MapPin aria-hidden="true" /> {program.location}{program.work_mode ? ` · ${program.work_mode}` : ''}</p>
            <a className="button button--secondary" href="/careers?type=internship">View details &amp; apply <ArrowRight aria-hidden="true" /></a>
          </article>)}</div>
            : <div className="internships-empty"><GraduationCap aria-hidden="true" /><h3>No internships are open right now</h3><p>New programs will be listed here as soon as they are announced.</p></div>}
    </section>
  </div></div>;
}
