import { useEffect, useRef, useState } from 'react';

export function AdminRichTextEditor({ value, onChange, ariaLabel = 'Article body', hint = 'Use headings to create an article contents list. Links must start with https://.' }) {
  const editor = useRef(null);
  const [preview, setPreview] = useState(false);
  useEffect(() => { if (editor.current) editor.current.innerHTML = value || ''; }, []);
  function updateValue() { onChange((editor.current?.innerHTML || '').replace(/<b(\s[^>]*)?>/gi, '<strong>').replace(/<\/b>/gi, '</strong>').replace(/<i(\s[^>]*)?>/gi, '<em>').replace(/<\/i>/gi, '</em>')); }
  function format(command, argument) { editor.current?.focus(); document.execCommand('styleWithCSS', false, false); document.execCommand(command, false, argument); updateValue(); }
  function pastePlainText(event) { event.preventDefault(); document.execCommand('insertText', false, event.clipboardData.getData('text/plain')); updateValue(); }
  function addLink() { const address = window.prompt('Paste a link starting with https://'); if (address && /^https?:\/\//i.test(address)) format('createLink', address); }
  return <div className="admin-rich-editor"><div className="admin-rich-toolbar"><button type="button" onClick={() => format('formatBlock','p')}>Paragraph</button><button type="button" onClick={() => format('formatBlock','h2')}>Heading</button><button type="button" onClick={() => format('bold')}>Bold</button><button type="button" onClick={() => format('italic')}>Italic</button><button type="button" onClick={() => format('insertUnorderedList')}>Bullets</button><button type="button" onClick={() => format('insertOrderedList')}>Numbered list</button><button type="button" onClick={addLink}>Link</button><button type="button" onClick={() => setPreview(current => !current)}>{preview ? 'Edit' : 'Preview'}</button></div><div ref={editor} className="admin-rich-canvas" contentEditable={!preview} role="textbox" aria-multiline="true" aria-label={ariaLabel} onInput={updateValue} onPaste={pastePlainText} suppressContentEditableWarning /><small>{hint}</small></div>;
}
