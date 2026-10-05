const section=(id,title,paragraphs,items=[])=>({id,title,paragraphs:Array.isArray(paragraphs)?paragraphs:[paragraphs],items});
export const legalPolicies={
 privacy:{title:"Privacy Policy",description:"Learn how E4ENGINEERS may collect, use and protect information when users interact with the platform.",eyebrow:"Privacy and information",lastUpdated:"Draft for client and legal review",sections:[
  section("introduction","Introduction","This draft describes the information practices planned for E4ENGINEERS. Final practices and wording will be confirmed before production launch."),
  section("information","Information We May Collect","Information may be collected when users create an account, place an order, access digital material, contact support or interact with platform features.",["Account information such as name, email and mobile number","Order, transaction and delivery information","Digital-resource access and download records","Technical and usage information needed to operate and improve the platform"]),
  section("use","How Information May Be Used","Information may be used to provide requested services, process eligible orders, support customers, improve platform reliability and send communications selected by the user.",["Order processing and delivery","Customer support and account administration","Platform security, performance and improvement","Service communications and permitted preferences"]),
  section("cookies","Cookies and Similar Technologies","The production platform may use essential storage or similar technologies for session, preference, security and performance purposes. Any optional use will be documented in the final policy."),
  section("payments","Payment Information","Online payments may be processed by authorized third-party payment providers. E4ENGINEERS does not plan to store complete credit or debit card details in the frontend."),
  section("third-parties","Third-Party Services","Selected payment, hosting, analytics, communication, delivery or media services may process limited information needed to provide their functions under their own terms and safeguards."),
  section("security-retention","Data Security and Retention","Reasonable technical and organizational safeguards are planned. Retention periods will be based on operational, contractual and applicable legal requirements and will be finalized before launch."),
  section("rights","User Rights","Users may be able to request access, correction or other available actions concerning their information, subject to identity verification and applicable requirements."),
  section("children","Children and Minors","The final platform policy will reflect the intended audience, consent requirements and applicable protections for minors where relevant."),
  section("changes","Changes to This Policy","Material revisions will be published on this page with an updated review date. Users should review the current version periodically."),
  section("contact","Contact Information","For privacy questions, use the verified E4ENGINEERS contact or support channels. Business and legal contact details will remain configurable rather than embedded in this draft."),
 ]},
 terms:{title:"Terms of Use",description:"These terms govern access to and use of the E4ENGINEERS platform, content, services and purchases.",eyebrow:"Platform terms",lastUpdated:"Draft for client and legal review",sections:[
  section("acceptance","Acceptance of Terms","By using the production platform, users will agree to the final published terms and applicable policies. This version is an implementation-ready draft pending review."),
  section("purpose","Platform Purpose","E4ENGINEERS is planned as an engineering education, publications, digital-resource and physical-book commerce platform."),
  section("accounts","User Accounts and Responsibility","Users should provide accurate information, protect account access and notify support about suspected unauthorized activity. Backend authentication and authorization will govern protected functions."),
  section("educational-content","Educational and Technical Content","Courses, articles and technical material are intended for learning and reference. Users should apply appropriate professional judgment, standards and independent verification in real-world work."),
  section("digital-content","Digital Resources and Publications","Access may be free, login-required or paid. Protected or paid materials may not be redistributed unless an explicit license permits it."),
  section("books-orders","Physical Books and Orders","Product availability, authoritative pricing, stock and order acceptance will be confirmed by the backend during checkout."),
  section("payments","Payments, COD, Coupons and Offers","Online payments will be verified through approved providers. Cash on delivery and promotional eligibility may depend on order and delivery conditions shown during checkout."),
  section("shipping-returns","Shipping, Cancellation, Returns and Refunds","Shipping and post-purchase requests are governed by the current Shipping Policy and Returns & Refunds policy. Eligibility can depend on order, payment and fulfilment status."),
  section("intellectual-property","Intellectual Property","Platform articles, course materials, study resources, publications, images, branding and protected downloads remain subject to their respective ownership and licensing terms."),
  section("conduct","User Conduct","Users should not misuse accounts, interfere with platform security, attempt unauthorized access or use content in a manner that violates applicable rights or law."),
  section("availability","Accuracy, External Services and Availability","Content and services may evolve. External links and third-party services remain governed by their providers, and continuous availability cannot be assumed."),
  section("changes-contact","Changes, Account Actions and Contact","The final terms may describe proportionate account restrictions for misuse and how changes are communicated. Questions can be directed through the verified contact channels."),
 ]},
 shipping:{title:"Shipping Policy",description:"Information about the processing, shipping and delivery of physical book orders placed through E4ENGINEERS.",eyebrow:"Physical book delivery",lastUpdated:"Draft for client review",sections:[
  section("coverage","Shipping Coverage","Available destinations will be confirmed during checkout based on serviceability and the selected delivery address. International shipping is not represented as available until approved."),
  section("processing","Order Processing and Dispatch","Physical-book orders may require payment confirmation, stock validation and address review before dispatch. Dispatch information will appear when available."),
  section("charges","Shipping Charges and Free Shipping","Shipping charges, where applicable, will be calculated and shown during checkout. Any free-shipping conditions will be displayed only after business rules are configured."),
  section("timelines","Delivery Timelines","Estimated delivery depends on destination, inventory, dispatch timing and courier operations. The checkout and tracking views will show available estimates without promising an unconfirmed fixed period."),
  section("address","Delivery Address","Customers should provide a complete and accurate address. Incorrect or incomplete information may delay delivery or result in an undeliverable shipment."),
  section("tracking","Courier Partner and Shipment Tracking","When provided, tracking information may include courier partner, AWB or tracking number, shipment status, estimated delivery and an authorized tracking link."),
  section("delays","Delayed, Failed or Undeliverable Shipments","Weather, service interruptions, address issues or recipient availability may affect delivery. Support can review the available courier and order information."),
  section("damage","Damaged Package on Delivery","If a package appears materially damaged, customers should preserve available evidence and contact support promptly so eligibility can be reviewed under the final returns policy."),
  section("support","Contact Support","For help with an existing shipment, use Support and provide the relevant order number where available."),
 ]},
 returns:{title:"Returns & Refunds",description:"Understand the return, cancellation and refund process for eligible E4ENGINEERS purchases.",eyebrow:"Purchase assistance",lastUpdated:"Draft for client review",sections:[
  section("book-cancellation","Physical Books: Order Cancellation","Cancellation eligibility may depend on whether an order is pending, processing or already dispatched. Final rules will be confirmed before production."),
  section("book-eligibility","Physical Books: Return Eligibility","Requests involving damaged books, an incorrect title or missing items may be reviewed using order information and reasonable supporting evidence."),
  section("book-process","Physical Books: Return Request and Inspection","Customers may be asked to contact support, identify the order and explain the issue. Returned items may require inspection before a refund or replacement decision."),
  section("book-outcome","Physical Books: Refunds and Replacements","Approved outcomes may include replacement, full refund or partial refund where appropriate. The method and timing will depend on the original payment method and confirmed policy."),
  section("book-exclusions","Physical Books: Potential Non-Returnable Conditions","Final policy may address damage caused after delivery, incomplete returns or requests outside an approved period. No strict exclusion is asserted in this draft."),
  section("digital-access","Digital Resources: Access and Technical Issues","Customers experiencing entitlement, duplicate-payment or download problems should contact support so access and payment records can be reviewed."),
  section("digital-eligibility","Digital Resources: Refund Eligibility","Eligibility for digital content will depend on access history, technical circumstances, applicable requirements and the final approved business policy. This draft does not impose a blanket non-refundable rule."),
  section("refund-status","Refund Status","Payment records may show Pending, Refunded or Partially Refunded independently from order and shipping status."),
  section("support","Contact Support","Use the Support page for cancellation, return or refund assistance and include the relevant order or purchase reference where available."),
 ]},
};

const job=(id,title,department,location,type,summary,overview,responsibilities,requirements,preferred)=>({id,title,department,location,type,summary,overview,responsibilities,requirements,preferred});
export const careerOpenings=[
 job(1,"Frontend Developer","Technology","Flexible / To be confirmed","Prototype opportunity","Build accessible, responsive React interfaces for engineering content and commerce.","Contribute reusable components; integrate API contracts; test responsive and accessible behavior.","Practical React, JavaScript, CSS and web accessibility knowledge.","Experience with content platforms or e-commerce interfaces."),
 job(2,"Laravel Developer","Technology","Flexible / To be confirmed","Prototype opportunity","Develop secure API foundations for content, commerce and customer workflows.","Design services and REST endpoints; implement validation and authorization; write maintainable tests.","Laravel, PHP, relational databases and API security fundamentals.","Experience with payments, queues or inventory systems."),
 job(3,"Engineering Content Writer","Content / Engineering","Flexible / To be confirmed","Prototype opportunity","Create clear, accurate engineering learning and reference material.","Research technical topics; structure articles and resources; collaborate with reviewers.","Engineering education or industry background with strong technical writing.","Experience preparing diagrams, examples or academic material."),
 job(4,"Subject Matter Expert – Electrical Engineering","Academic / Faculty","Flexible / To be confirmed","Prototype opportunity","Review and guide electrical engineering content for academic and professional learners.","Review technical accuracy; advise content scope; contribute practical examples.","Advanced qualification or substantial professional experience in electrical engineering.","Power systems, machines, protection or energy-market expertise."),
 job(5,"UI/UX Designer","Design","Flexible / To be confirmed","Prototype opportunity","Improve clear, trustworthy product experiences across learning and commerce.","Create flows and interface specifications; maintain consistency; support accessibility reviews.","Strong interaction, visual design and responsive product-design foundations.","Experience with education, publishing or technical products."),
];
export const careerValues=[
 ["Engineering-Focused Work","Work on products centered on practical engineering knowledge."],
 ["Learning & Growth","Develop skills through varied content, product and technical challenges."],
 ["Meaningful Educational Impact","Help make engineering learning easier to find and use."],
 ["Collaborative Environment","Work across engineering, education, content and technology disciplines."],
 ["Technology & Innovation","Build maintainable systems for modern learning and commerce."],
 ["Flexible Opportunities","Opportunity formats will be communicated clearly when roles are approved."],
];
