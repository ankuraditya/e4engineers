import { useState } from 'react';
import { Certificate, DownloadSimple, LockKey } from '@phosphor-icons/react';
import { Breadcrumb, InnerPageHeader } from '../components/shared/InnerPageComponents';
import { API_BASE_URL, initializeCsrf } from '../services/apiClient';

function csrfToken() {
  const cookie = document.cookie.split('; ').find((entry) => entry.startsWith('XSRF-TOKEN='));
  return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : '';
}

export function VerifyCertificatePage() {
  const [mobile, setMobile] = useState('');
  const [dateOfBirth, setDateOfBirth] = useState('');
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    setMessage('');
    try {
      await initializeCsrf();
      const response = await fetch(`${API_BASE_URL}/internships/certificates/download`, {
        method: 'POST',
        credentials: 'include',
        headers: { Accept: 'application/pdf, application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': csrfToken() },
        body: JSON.stringify({ mobile: mobile.trim(), date_of_birth: dateOfBirth }),
      });
      if (!response.ok) {
        setMessage(response.status === 429 ? 'Too many attempts. Please try again later.' : 'No certificate was found for those details. Please check your phone number and date of birth.');
        return;
      }
      const url = URL.createObjectURL(await response.blob());
      const link = document.createElement('a');
      link.href = url;
      link.download = 'E4ENGINEERS-Internship-Certificate.pdf';
      document.body.append(link);
      link.click();
      link.remove();
      setTimeout(() => URL.revokeObjectURL(url), 60000);
      setMessage('Your certificate download has started.');
    } catch {
      setMessage('Certificate verification is temporarily unavailable. Please try again later.');
    } finally {
      setBusy(false);
    }
  }

  return <div className="inner-page verify-certificate-page"><div className="container inner-page__container">
    <Breadcrumb items={[{ label: 'Home', href: '/' }, { label: 'Internships', href: '/internships' }, { label: 'Verify Certificate' }]} />
    <InnerPageHeader eyebrow="Internship certificates" title="Verify Certificate" description="Enter the phone number and date of birth registered for your internship to download your certificate." />
    <div className="certificate-lookup"><div className="certificate-lookup__intro"><Certificate aria-hidden="true" /><h2>Find your certificate</h2><p>Your details are checked securely. A certificate is available for download only when both details match an issued record.</p></div>
      <form onSubmit={submit}><label htmlFor="certificate-mobile">Registered phone number</label><input id="certificate-mobile" type="tel" inputMode="numeric" autoComplete="tel-national" pattern="[6-9][0-9]{9}" maxLength="10" required value={mobile} onChange={(event) => setMobile(event.target.value.replace(/\D/g, '').slice(0, 10))} placeholder="10-digit mobile number" />
        <label htmlFor="certificate-dob">Date of birth</label><input id="certificate-dob" type="date" required max={new Date().toISOString().slice(0, 10)} value={dateOfBirth} onChange={(event) => setDateOfBirth(event.target.value)} />
        <button className="button button--primary" type="submit" disabled={busy}><DownloadSimple aria-hidden="true" /> {busy ? 'Checking…' : 'Verify & download'}</button>
        {message && <p className="certificate-lookup__message" role="status">{message}</p>}
      </form><p className="certificate-lookup__privacy"><LockKey aria-hidden="true" /> Your date of birth is used only to verify this request.</p>
    </div>
  </div></div>;
}
