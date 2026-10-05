# Career architecture

Only published openings whose deadline has not passed appear publicly or accept applications. Admins manage role content, work mode, employment type, publication state and deadlines.

Applications enforce one email per opening and an idempotency token. Resumes accept PDF, DOC and DOCX files up to 5 MB, are stored on the private local disk, and can be downloaded only from an authenticated admin route with the careers permission. Public application responses never reveal the storage path.

Application acknowledgements and staff alerts use the shared event-driven notification pipeline. Admins can move an application through received, reviewing, shortlisted, rejected and hired states.
