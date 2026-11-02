import { useEffect, useState } from 'react';
import { ArrowRight, CheckCircle, XCircle } from '@phosphor-icons/react';
import { Breadcrumb, EmptyState, InnerPageHeader } from '../components/shared/InnerPageComponents.jsx';
import { mcqPracticeService } from '../services/mcqPracticeService.js';

function Page({ children }) { return <div className="inner-page"><div className="container inner-page__container">{children}</div></div>; }

export function McqPracticePage() {
  const [subject, setSubject] = useState('');
  const [sets, setSets] = useState([]);
  const [subjects, setSubjects] = useState([]);
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    setLoading(true); setError('');
    mcqPracticeService.list(subject, page).then((response) => {
      if (!active) return;
      setSets(response.data || []);
      setSubjects(response.meta?.subjects || []);
      setMeta(response.meta || {});
    }).catch((reason) => { if (active) setError(reason.message); }).finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [subject, page]);

  return <Page><Breadcrumb items={[{ label: 'Home', href: '/' }, { label: 'MCQ Practice' }]}/>
    <InnerPageHeader eyebrow="Learn / Self assessment" title="MCQ Practice" description="Choose a subject, practise a question set and check your score with the full answer key." />
    <div className="mcq-filter"><label htmlFor="mcq-subject">Select subject</label><select id="mcq-subject" value={subject} onChange={(event) => { setSubject(event.target.value); setPage(1); }}><option value="">All subjects</option>{subjects.map((name) => <option value={name} key={name}>{name}</option>)}</select></div>
    {loading ? <p role="status" className="mcq-message">Loading practice sets…</p> : error ? <EmptyState title="Practice sets unavailable" message={error}/> : sets.length ? <><div className="mcq-set-grid">{sets.map((set) => <article className="mcq-set-card" key={set.id}><span>{set.subject}</span><h2>{set.title}</h2><p>{set.description || 'Practise questions and review your answers after submitting.'}</p><div><small>{set.question_count} questions</small><a href={`/mcq-practice/${set.slug}`}>Start practice <ArrowRight size={17}/></a></div></article>)}</div>{meta.last_page > 1 && <div className="mcq-page-controls"><button disabled={page === 1} onClick={() => setPage(page - 1)}>Previous</button><span>Page {page} of {meta.last_page}</span><button disabled={page >= meta.last_page} onClick={() => setPage(page + 1)}>Next</button></div>}</> : <EmptyState title="No practice sets yet" message={subject ? 'Choose another subject to see available sets.' : 'New practice sets will appear here when published.'}/>}
  </Page>;
}

export function McqPracticeSetPage({ slug }) {
  const [set, setSet] = useState(null);
  const [answers, setAnswers] = useState([]);
  const [result, setResult] = useState(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  useEffect(() => { let active = true; setSet(null); setResult(null); setError(''); mcqPracticeService.show(slug).then((response) => { if (active) { setSet(response.data); setAnswers(Array(response.data.questions.length).fill(null)); } }).catch((reason) => { if (active) setError(reason.message); }); return () => { active = false; }; }, [slug]);
  async function submit(event) { event.preventDefault(); setBusy(true); setError(''); try { const response = await mcqPracticeService.submit(slug, answers); setResult(response.data); window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (reason) { setError(reason.message); } finally { setBusy(false); } }
  return <Page><Breadcrumb items={[{ label: 'Home', href: '/' }, { label: 'MCQ Practice', href: '/mcq-practice' }, { label: set?.title || 'Practice set' }]}/>
    {error && !set ? <EmptyState title="Practice set unavailable" message={error}/> : !set ? <p role="status" className="mcq-message">Loading questions…</p> : <><InnerPageHeader eyebrow={set.subject} title={set.title} description={set.description || 'Select one answer for each question. You may leave a question unanswered.'}/>
      {result ? <section className="mcq-result" aria-live="polite"><span>Practice complete</span><h2>{result.score} / {result.total}</h2><p>{result.percentage}% correct · One point per correct answer · No negative marking</p><div><button className="button button--secondary" onClick={() => { setAnswers(Array(set.questions.length).fill(null)); setResult(null); }}>Try again</button><a className="button button--primary" href="/mcq-practice">More practice sets</a></div></section> : <p className="mcq-message">{set.questions.length} questions · Answer at your own pace</p>}
      {result ? <div className="mcq-question-list">{result.answer_key.map((item, index) => <article className="mcq-question" key={index}><div className="mcq-question-title">{item.is_correct ? <CheckCircle color="#18845b" size={23}/> : <XCircle color="#bd3643" size={23}/>}<h3>{index + 1}. {item.prompt}</h3></div><p>Your answer: {item.selected_option === null ? 'Not answered' : item.options[item.selected_option]}</p><p className="mcq-correct">Correct answer: {item.options[item.correct_option]}</p>{item.explanation && <p className="mcq-explanation">{item.explanation}</p>}</article>)}</div> : <form onSubmit={submit} className="mcq-question-list">{set.questions.map((question, index) => <fieldset className="mcq-question" key={index}><legend>{index + 1}. {question.prompt}</legend>{question.options.map((option, optionIndex) => <label className={answers[index] === optionIndex ? 'is-selected' : ''} key={optionIndex}><input type="radio" name={`question-${index}`} checked={answers[index] === optionIndex} onChange={() => setAnswers((current) => current.map((value, position) => position === index ? optionIndex : value))}/><span>{option}</span></label>)}</fieldset>)}{error && <p role="alert" className="form-status">{error}</p>}<button className="button button--primary mcq-submit" disabled={busy}>{busy ? 'Checking answers…' : 'Submit & view answer key'}</button></form>}
    </>}
  </Page>;
}
