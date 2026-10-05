# Phase 23 QA report

Run date: 2 October 2026. Automated backend regression: 29 test files passed. Phase 23: 8 tests / 16 assertions passed. Frontend: 35 tests passed and the Vite production build completed. Live SMTP delivery was not exercised because deployment credentials are environment-owned.

1. [x] Contact endpoint registered
2. [x] Contact validation enforced
3. [x] Contact honeypot enforced
4. [x] Contact rate limit registered
5. [x] Contact idempotency enforced
6. [x] Recent duplicate contact suppressed
7. [x] Contact reference generated
8. [x] Contact client form connected
9. [x] Contact acknowledgement event emitted
10. [x] Contact admin alert event emitted
11. [x] Enquiry admin list available
12. [x] Enquiry admin detail available
13. [x] Enquiry assignment supported
14. [x] Enquiry priority supported
15. [x] Enquiry status supported
16. [x] Private enquiry notes supported
17. [x] Support endpoint registered
18. [x] Support validation enforced
19. [x] Support honeypot enforced
20. [x] Support rate limit registered
21. [x] Support idempotency enforced
22. [x] Support ticket reference generated
23. [x] Support client form connected
24. [x] Guest order linking denied
25. [x] Customer order ownership enforced
26. [x] Support messages persisted
27. [x] Support attachments validated
28. [x] Support attachments stored privately
29. [x] Customer ticket list scoped
30. [x] Customer ticket detail scoped
31. [x] Cross-customer ticket access returns 404
32. [x] Customer replies supported
33. [x] Closed ticket replies rejected
34. [x] Internal admin notes hidden from customer
35. [x] Admin support search and filter available
36. [x] Admin support assignment supported
37. [x] Admin support status and priority supported
38. [x] Admin replies notify customers
39. [x] Resolution timestamps recorded
40. [x] Admin attachment download protected
41. [x] Workshop public list filters unpublished records
42. [x] Workshop public detail filters unpublished records
43. [x] Workshop client listing connected
44. [x] Workshop registration form connected
45. [x] Workshop registration window enforced
46. [x] Workshop capacity enforced
47. [x] Workshop capacity uses row locking
48. [x] Workshop duplicate email prevented
49. [x] Workshop retry idempotency supported
50. [x] Workshop rate limit registered
51. [x] Workshop meeting URL encrypted
52. [x] Workshop meeting URL hidden publicly
53. [x] Workshop admin list available
54. [x] Workshop admin create and update available
55. [x] Workshop delete protects registrations
56. [x] Workshop registration admin list available
57. [x] Workshop notification events emitted
58. [x] Career public list filters closed roles
59. [x] Career deadline filters expired roles
60. [x] Career client listing connected
61. [x] Career application form connected
62. [x] Career resume is required
63. [x] Resume file types restricted
64. [x] Resume size limited to 5 MB
65. [x] Resumes stored privately
66. [x] Resume path hidden from responses
67. [x] Career duplicate email prevented per role
68. [x] Career retry idempotency supported
69. [x] Career rate limit registered
70. [x] Career admin list available
71. [x] Career opening create and update available
72. [x] Application status workflow available
73. [x] Resume download requires careers permission
74. [x] Notification templates and architecture docs included
