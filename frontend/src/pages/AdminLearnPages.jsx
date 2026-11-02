import { useEffect, useState } from 'react';
import { InnerPageHeader } from '../components/shared/InnerPageComponents.jsx';
import { mcqPracticeService } from '../services/mcqPracticeService.js';

const resourceSections = [
  ['Lecture Notes', 'lecture-notes'],
  ['Solved Question Papers', 'solved-question-papers'],
  ['Technical Diagrams', 'technical-diagrams'],
  ['Formula Sheets', 'formula-sheets'],
  ['Study Guides', 'study-guides'],
  ['Practice Materials', 'practice-materials'],
];

export function AdminLearnPage() {
  return <div className="admin-module"><InnerPageHeader eyebrow="Publishing / Learn" title="Learn Content" description="Choose the section you want to fill. Published items appear in the corresponding Learn menu section." />
    <div className="admin-learn-grid"><article><span>01</span><h2>Courses</h2><p>Create a course, choose its engineering discipline, add its description and image, then publish it.</p><a href="/admin/publishing?module=courses">Manage courses →</a></article>
      <article><span>02</span><h2>Study Resources</h2><p>Upload a resource file and choose its resource type. That type determines whether it appears under Lecture Notes, Formula Sheets, and the other Learn sections.</p><a href="/admin/publishing?module=resources">Manage all resources →</a></article>
      <article><span>03</span><h2>MCQ Practice</h2><p>Create a subject, add question sets and four answer choices per question. Publish a set when it is ready for students.</p><a href="/admin/mcq-practice">Manage MCQ sets →</a></article></div>
    <section className="admin-panel"><header><div><h2>Resource sections</h2><p>Each shortcut opens the Resources editor with its type preselected when adding content.</p></div></header><div className="admin-learn-links">{resourceSections.map(([label, slug]) => <a key={slug} href={`/admin/publishing?module=resources&resource_type=${slug}`}>{label}<span>Manage →</span></a>)}</div></section>
  </div>;
}

const blankQuestion = () => ({ prompt: '', options: ['', '', '', ''], correct_option: 0, explanation: '' });
const blankSet = () => ({ title: '', subject: '', description: '', status: 'draft', questions: [] });

export function AdminMcqPracticePage() {
  const [sets, setSets] = useState([]);
  const [meta, setMeta] = useState({});
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [editor, setEditor] = useState(null);
  const [form, setForm] = useState(blankSet);

  async function load() { setLoading(true); setError(''); try { const response = await mcqPracticeService.adminList(search, page); setSets(response.data || []); setMeta(response.meta || {}); } catch (reason) { setError(reason.message); } finally { setLoading(false); } }
  useEffect(() => { load(); }, [search, page]);
  function open(set) { setEditor(set ? set.id : 'new'); setForm(set ? { title: set.title, subject: set.subject, description: set.description || '', status: set.status, questions: set.questions || [] } : blankSet()); setError(''); }
  function updateQuestion(index, patch) { setForm((current) => ({ ...current, questions: current.questions.map((question, position) => position === index ? { ...question, ...patch } : question) })); }
  function updateOption(questionIndex, optionIndex, value) { const options = [...form.questions[questionIndex].options]; options[optionIndex] = value; updateQuestion(questionIndex, { options }); }
  function errorText(reason) { const first = Object.values(reason.errors || {}).flat()[0]; return first || reason.message; }
  async function save(event) { event.preventDefault(); setBusy(true); setError(''); setMessage(''); try { const payload = { ...form, title: form.title.trim(), subject: form.subject.trim(), questions: form.questions.map((question) => ({ ...question, prompt: question.prompt.trim(), options: question.options.map((option) => option.trim()) })) }; if (editor === 'new') await mcqPracticeService.create(payload); else await mcqPracticeService.update(editor, payload); setMessage(`Practice set ${editor === 'new' ? 'created' : 'updated'}.`); setEditor(null); await load(); } catch (reason) { setError(errorText(reason)); } finally { setBusy(false); } }
  async function archive(set) { if (!window.confirm(`Archive “${set.title}”? Students will no longer see it.`)) return; setBusy(true); setError(''); try { await mcqPracticeService.archive(set.id); setMessage('Practice set archived.'); await load(); } catch (reason) { setError(errorText(reason)); } finally { setBusy(false); } }

  return <div className="admin-module"><InnerPageHeader eyebrow="Publishing / Learn" title="MCQ Practice" description="Manage subjects, question sets and answer keys. Only published sets are visible to students." />
    <div className="admin-toolbar"><label><span>Search sets or subjects</span><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search MCQ practice…" /></label><button className="button button--secondary" onClick={() => { setPage(1); setSearch(query.trim()); }}>Search</button><button className="button button--primary" onClick={() => open(null)}>＋ New set</button></div>
    <section className="admin-data-panel"><header><div><h2>Question sets</h2><p>{meta.total ?? 0} sets in the library</p></div></header>{loading ? <div className="admin-list-loading">Loading question sets…</div> : sets.length ? <div className="admin-content-list">{sets.map((set) => <article key={set.id}><span className="admin-row-index">{set.question_count}</span><div><strong>{set.title}</strong><small>{set.subject} · {set.question_count} questions</small></div><span className={`admin-status admin-status--${set.status}`}>{set.status}</span><div className="admin-crud-actions"><button onClick={() => open(set)}>Edit</button><button className="danger" disabled={busy} onClick={() => archive(set)}>Archive</button></div></article>)}</div> : <div className="admin-empty"><h3>No question sets yet</h3><p>Create a set and add questions before publishing it.</p></div>}{meta.last_page > 1 && <div className="admin-customer-pagination"><button disabled={page <= 1} onClick={() => setPage(page - 1)}>Previous</button><span>Page {page} of {meta.last_page}</span><button disabled={page >= meta.last_page} onClick={() => setPage(page + 1)}>Next</button></div>}</section>
    {message && <p role="status" className="form-status">{message}</p>}{error && !editor && <p role="alert" className="form-status">{error}</p>}
    {editor && <div className="admin-modal-backdrop"><section className="admin-modal admin-mcq-editor" role="dialog" aria-modal="true" aria-labelledby="mcq-editor-title"><header><div><small>{editor === 'new' ? 'CREATE' : 'EDIT'} MCQ SET</small><h2 id="mcq-editor-title">{editor === 'new' ? 'New' : 'Edit'} practice set</h2></div><button aria-label="Close editor" type="button" onClick={() => setEditor(null)}>×</button></header><form onSubmit={save}><div className="admin-editor-grid"><label><span>Set title *</span><input required maxLength={180} value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })}/></label><label><span>Subject *</span><input required maxLength={120} list="mcq-subject-options" placeholder="e.g. Engineering Mathematics" value={form.subject} onChange={(event) => setForm({ ...form, subject: event.target.value })}/><datalist id="mcq-subject-options">{[...new Set(sets.map((set) => set.subject))].map((name) => <option key={name} value={name}/>)}</datalist></label><label className="wide"><span>Short introduction</span><textarea rows={3} maxLength={2000} value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })}/></label><label><span>Status</span><select value={form.status} onChange={(event) => setForm({ ...form, status: event.target.value })}><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select></label></div>
        <div className="admin-mcq-questions"><div><h3>Questions</h3><button type="button" className="button button--secondary" onClick={() => setForm((current) => ({ ...current, questions: [...current.questions, blankQuestion()] }))}>＋ Add question</button></div>{form.questions.map((question, index) => <fieldset key={index}><legend>Question {index + 1}</legend><button type="button" className="admin-mcq-remove" onClick={() => setForm((current) => ({ ...current, questions: current.questions.filter((_, position) => position !== index) }))}>Remove</button><label><span>Question *</span><textarea rows={2} required maxLength={2000} value={question.prompt} onChange={(event) => updateQuestion(index, { prompt: event.target.value })}/></label><div className="admin-mcq-options">{question.options.map((option, optionIndex) => <label key={optionIndex}><span>Option {String.fromCharCode(65 + optionIndex)} *</span><input required maxLength={500} value={option} onChange={(event) => updateOption(index, optionIndex, event.target.value)}/></label>)}</div><label><span>Correct answer</span><select value={question.correct_option} onChange={(event) => updateQuestion(index, { correct_option: Number(event.target.value) })}>{question.options.map((_, optionIndex) => <option value={optionIndex} key={optionIndex}>Option {String.fromCharCode(65 + optionIndex)}</option>)}</select></label><label><span>Explanation (shown after submission)</span><textarea rows={2} maxLength={3000} value={question.explanation || ''} onChange={(event) => updateQuestion(index, { explanation: event.target.value })}/></label></fieldset>)}</div>
        {error && <p role="alert" className="form-status">{error}</p>}<footer><button type="button" className="button button--secondary" onClick={() => setEditor(null)}>Cancel</button><button className="button button--primary" disabled={busy}>{busy ? 'Saving…' : 'Save set'}</button></footer></form></section></div>}
  </div>;
}
