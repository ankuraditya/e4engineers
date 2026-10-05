import {
  ArrowRight,
  BookOpen,
  Calculator,
  File,
  FileText,
  Image as ImageIcon,
  ListChecks,
} from "@phosphor-icons/react";
import { useEffect, useState } from "react";
import { resourcesService } from "../../services/resourcesService";

function StudyResourceCard({ resource }) {
  const Icon = resource.icon;

  return (
    <a className="study-resource-card" href={`/resources?type=${encodeURIComponent(resource.slug)}`}>
      <Icon aria-hidden="true" weight="regular" />
      <span className="study-resource-card__content">
        <strong>{resource.title}</strong>
        <small>Browse resources</small>
      </span>
    </a>
  );
}

export function StudyResources() {
  const [studyResources, setStudyResources] = useState([]);
  useEffect(() => {
    Promise.all([resourcesService.getResourceTypes(), resourcesService.getResources({ per_page: 100 })]).then(([result, resourcesResult]) => {
      const icons = [FileText, File, ImageIcon, Calculator, BookOpen, ListChecks];
      const availableTypeIds = new Set((resourcesResult.data || []).map((resource) => resource.type?.id));
      const types = (result.data || []).filter((type) => availableTypeIds.has(type.id)).slice(0, 6).map((type, index) => ({ id: type.id, title: type.name, slug: type.slug, icon: icons[index] }));
      setStudyResources(types);
    }).catch(() => {});
  }, []);
  if (!studyResources.length) return null;
  return (
    <section className="study-resources" id="resources" aria-labelledby="study-resources-title">
      <div className="container study-resources__inner">
        <div className="section-heading">
          <div>
            <h2 id="study-resources-title">Study resources</h2>
            <p>Notes, question papers, diagrams and more.</p>
          </div>
          <a className="section-heading__link" href="/resources">
            View all resources <ArrowRight aria-hidden="true" weight="bold" />
          </a>
        </div>
        <div className="study-resources-grid">
          {studyResources.map((resource) => <StudyResourceCard resource={resource} key={resource.id} />)}
        </div>
      </div>
    </section>
  );
}
