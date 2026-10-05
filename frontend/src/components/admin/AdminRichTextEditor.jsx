import { useEffect, useRef, useState } from 'react';

export function AdminRichTextEditor({ value, onChange }) {
  const editor = useRef(null);
  const [preview, setPreview] = useState(false);
  useEffect(() => { if (editor.current) editor.current.innerHTML = value || ''; }, []);
  function format(command, argument) { editor.current?.focus(); document.execCommand(command, false, argument); onChange(editor.current?.innerHTML || ''); }
  function addLink() { const address = window.prompt('Paste a link starting with https://'); if (address && /^https?:\/\//i.test(address)) format('createLink', address); }
  return <div className="admin-rich-editor"><div className="admin-rich-toolbar"><button type="button" onClick={() => format('formatBlock','p')}>Paragraph</button><button type="button" onClick={() => format('formatBlock','h2')}>Heading</button><button type="button" onClick={() => format('bold')}>Bold</button><button type="button" onClick={() => format('italic')}>Italic</button><button type="button" onClick={() => format('insertUnorderedList')}>List</button><button type="button" onClick={addLink}>Link</button><button type="button" onClick={() => setPreview(current => !current)}>{preview ? 'Edit' : 'Preview'}</button></div><div ref={editor} className="admin-rich-canvas" contentEditable={!preview} role="textbox" aria-multiline="true" aria-label="Article body" onInput={event => onChange(event.currentTarget.innerHTML)} suppressContentEditableWarning /><small>Use headings to create an article contents list. Links must start with https://.</small></div>;
}
