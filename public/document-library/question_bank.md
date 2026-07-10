# UniNexus — RAG Test Question Bank
**Institution:** Northern University Bangladesh
**Dataset:** 10 documents (5 PDF + 5 DOCX)
**Purpose:** Retrieval testing, semantic search validation, end-to-end answer-quality evaluation

---

## Document Index (source map)

| Doc | File | Key topics |
|-----|------|-----------|
| D1 | 01_exam_notice_midterm_final.pdf | Midterm/final dates, exam registration deadline, eligibility |
| D2 | 02_dept_exam_schedule.pdf | Department-wise final exam schedule, course codes, rooms |
| D3 | 03_admission_notice.pdf | Summer 2026 intake, programs, admission fee, late reg deadline |
| D4 | 04_attendance_policy.pdf | 70% threshold, 60–69% band, attendance marks |
| D5 | 05_attendance_sheet_sample.pdf | CSE-2103 sample attendance, per-student eligibility |
| D6 | 06_cse_course_structure.docx | CSE semester-wise courses, credits, sections |
| D7 | 07_departments_overview.docx | EEE/BBA/English structure, credit totals |
| D8 | 08_fee_policy.docx | Fee structure, payment rules, installments |
| D9 | 09_registration_credit_probation_policy.docx | Late reg, extra credit, probation |
| D10 | 10_faculty_section_overview.docx | Faculty-section assignment, section sizes, course reg announcement |

---

## 1. Single-Document Factual Lookup (easy retrieval)
*Tests: basic semantic match, one chunk, one answer.*

1. When does the midterm examination for Spring 2026 start? → **D1** (15 March 2026)
2. When is the final examination for Spring 2026? → **D1** (10–24 May 2026)
3. What is the exam registration deadline for Spring 2026? → **D1** (01 March 2026)
4. How much weight does the midterm exam carry? → **D1** (30%)
5. How much does the final exam count toward the total marks? → **D1** (50%)
6. What time is the Database Management Systems final exam? → **D2** (14 May 2026, 02:00 PM, Room 305)
7. Which room is the Artificial Intelligence exam held in? → **D2** (Room 410)
8. When is the Digital Logic Design exam for EEE? → **D2** (13 May 2026)
9. When does the Summer 2026 application open? → **D3** (20 April 2026)
10. What is the one-time admission fee? → **D3** (BDT 15,000)
11. When does the admission test take place? → **D3** (30 May 2026)
12. What merit waiver is available for high admission-test scorers? → **D3** (20% for above 90%)
13. What is the minimum attendance required to sit for the final exam? → **D4** (70%)
14. How much weight do attendance marks carry? → **D4** (10%)
15. How many days do I have to submit a medical certificate? → **D4** (within 7 days)
16. What is the total credit requirement for the CSE program? → **D6** (150 credits)
17. How many credits is the Data Structures course? → **D6** (3.0 theory + 1.5 lab)
18. What is the maximum number of students per section in CSE? → **D6** (40)
19. How many credits does the BBA program require? → **D7** (126)
20. How many credits does the English (BA Hons) program require? → **D7** (120)
21. What is the per-semester tuition fee for CSE? → **D8** (BDT 48,000 tuition / 55,000 total)
22. How many installments can I pay my semester fee in? → **D8** (up to 3)
23. What is the late payment surcharge for overdue installments? → **D8** (BDT 500/week)
24. What CGPA do I need to take extra-credit courses? → **D9** (3.50)
25. What semester GPA puts me on academic probation? → **D9** (below 2.00 for two consecutive semesters)
26. How many credits am I limited to while on probation? → **D9** (12 credits)
27. When does course registration for the next semester open? → **D10** (01 June 2026)
28. Who teaches CSE-2103 Section B? → **D10** (Mr. Tanvir Hasan)
29. What is the section capacity for CSE sections? → **D10** (40)

---

## 2. Table-Extraction Questions (tests structured retrieval)
*Tests: whether your chunker preserves tables and the model reads rows/columns correctly.*

30. List all CSE 1st-semester courses with their credits. → **D6**
31. What is the total per-semester fee for the EEE department? → **D8** (BDT 53,500)
32. Compare the total semester fees of CSE, BBA, and English. → **D8**
33. Which CSE 3rd-semester courses are theory vs lab? → **D6**
34. Give me the full final-exam schedule for the CSE department. → **D2**
35. What is the attendance percentage of student CSE-22-004? → **D5** (63.3%)
36. Which students in the sample sheet are barred from the final exam? → **D5** (Imran Kabir, Mehjabin Rahman)
37. How many classes did Nusrat Jahan attend? → **D5** (30 of 30)
38. Which sections are at full capacity? → **D10** (CSE Section A)
39. List all faculty assigned to CSE courses. → **D10**
40. What is the enrolled count for BBA Section A? → **D10** (39 of 45)

---

## 3. Multi-Hop / Cross-Document Questions (hard retrieval)
*Tests: combining evidence from 2+ documents. The single biggest RAG stressor.*

41. I have 64% attendance in CSE-2103 — can I sit for the final exam, and if so what must I do? → **D4 + D5** (60–69% band → pay BDT 2,000 non-collegiate fee + departmental approval)
42. I'm a CSE student — what's my total semester fee and what's the exam registration deadline I must pay it by? → **D8 + D1**
43. Student CSE-22-005 is barred — which policy explains why and what's the threshold? → **D5 + D4** (below 60%)
44. What's the final exam date for Database Management Systems and which faculty teaches it? → **D2 + D10**
45. If I miss the registration deadline as a continuing student, what fee applies and by when must I register? → **D9** (and contrast with **D3**)
46. I scored above 90% on the admission test for CSE — what's my admission fee after the waiver consideration, and what's the CSE semester fee I'll pay later? → **D3 + D8**
47. Which CSE courses appear in BOTH the course structure and the final exam schedule? → **D6 + D2** (e.g., Data Structures, DBMS, AI, OS)
48. What attendance percentage do I need, and what happens to my eligibility if I fall just below it? → **D4 + D5**
49. What's the section capacity for CSE, and which sections still have open seats for registration? → **D10** (B: 3 seats, C: 9 seats)
50. As a probation student limited to 12 credits, how many typical CSE courses can I take? → **D9 + D6**

---

## 4. Contradiction / Precision Questions (tests retrieval accuracy on conflicting data)
*Tests: whether the model surfaces the RIGHT chunk and distinguishes contexts. These are intentional traps.*

51. What is the late registration deadline? → **D3 (fixed 12 June 2026, new students) vs D9 (rolling 7/14-day rule, continuing students)** — correct answer depends on student type.
52. I'm a continuing student who missed registration — what's the late fee? → **D9** (BDT 3,000 first week, NOT the D3 admission deadline)
53. I'm a new Summer 2026 applicant — what's my late registration deadline? → **D3** (12 June 2026)
54. Is 70% attendance enough to take the final, or is there a lower conditional limit? → **D4** (70% clean pass, but 60–69% conditional with fee — test if model captures the nuance)
55. What's the registration deadline mentioned across the documents — are they all the same? → **D1 (01 Mar exam reg), D3 (25 May admission), D10 (15 Jun course reg)** — should distinguish three different deadlines.
56. Does every department have the same attendance requirement? → **D4 + D7** (yes, 70% across all — confirm consistency)

---

## 5. Phrasing-Variation / Paraphrase Questions (tests semantic search robustness)
*Tests: retrieval when the user's words differ from the document's words. Same answer, reworded queries.*

57. "How many days off can I take before I can't sit my exams?" → **D4** (paraphrase of 70% threshold)
58. "What's the cutoff to be allowed into the final test?" → **D4**
59. "When's the big end-of-term test?" → **D1** (final exam)
60. "How much is school per term for computer science?" → **D8** (CSE semester fee)
61. "Can I pay my fees bit by bit?" → **D8** (installments)
62. "What grades get me kicked into probation?" → **D9**
63. "Who's my Data Structures teacher in section B?" → **D10**
64. "How big are the class groups in CSE?" → **D6 / D10** (section size)
65. "When do I sign up for next term's classes?" → **D10** (course registration)
66. "Is there a penalty for signing up late?" → **D9 / D3**

---

## 6. Negative / Out-of-Scope Questions (tests hallucination resistance)
*Tests: whether the model correctly says "not in the documents" instead of inventing answers.*

67. What is the Wi-Fi password for the campus library? → **NOT in dataset** (should decline)
68. Who is the Vice-Chancellor of the university? → **NOT in dataset**
69. What is the hostel/dormitory fee? → **NOT in dataset** (fee policy has no hostel line)
70. What is the exam schedule for the Law department? → **NOT in D2** (only CSE/EEE/BBA listed)
71. What is the 7th-semester CSE course list? → **NOT fully in D6** (only 1st–4th detailed) — tests partial-coverage honesty
72. What is the grading scale / how is CGPA calculated? → **NOT in dataset**
73. Can I get a scholarship for sports? → **NOT in dataset** (only merit waivers exist)
74. What is the refund amount if I withdraw in week 5? → **NOT answerable** (D8 only covers first two weeks)

---

## 7. Reasoning / Computation Questions (tests answer synthesis beyond lookup)
*Tests: whether the model can compute or infer from retrieved facts.*

75. If I attended 21 of 30 classes, am I eligible for the final? → **D5/D4** (70.0% → yes, just at threshold)
76. I'm paying CSE fees in 3 installments — how much is each installment? → **D8** (40/30/30 of 55,000 = 22,000 / 16,500 / 16,500)
77. How many total credits across CSE semesters 1–4 shown in the structure? → **D6** (sum the listed courses)
78. If midterm is 30% and final is 50%, what's left for attendance and other components? → **D1 + D4** (20%, of which attendance is 10%)
79. I have CGPA 3.40 — can I register for extra-credit courses? → **D9** (no, need 3.50)
80. Two sections of CSE are full or nearly full — how many total open seats remain across CSE sections? → **D10** (B:3 + C:9 = 12)

---

## 8. Listing / Aggregation Questions (tests multi-chunk recall)
*Tests: whether retrieval gathers ALL relevant items, not just the top one.*

81. List every program offered at the university with its credit total. → **D3 + D6 + D7**
82. What are all the important dates for the Summer 2026 intake? → **D3**
83. List all the policies that mention the 70% attendance threshold. → **D1, D4, D5, D7, D9**
84. What are all the fees a new CSE student pays in their first semester? → **D3 (admission) + D8 (tuition+lab)**
85. Name all faculty members listed across all documents. → **D10**
86. What documents reference the "exam registration deadline"? → **D1, D4, D8**

---

## Coverage & Scoring Notes

- **Total questions:** 86, spanning all 10 documents.
- **Difficulty mix:** ~34% easy single-doc, ~24% table/aggregation, ~17% multi-hop, ~9% contradiction traps, ~12% paraphrase, ~9% negative/out-of-scope.
- **Critical eval cases:** Q41, Q43, Q48 (band logic); Q51–Q53 (late-registration contradiction); Q55 (three-deadline disambiguation). A naive retriever will fail these — they're your highest-signal regression tests.
- **Hallucination guardrail:** Q67–Q74 should return a graceful "not found in the provided documents." Track these separately as a hallucination-rate metric.
- **Ground-truth caution:** For Q51–Q53, the "correct" answer depends on whether the student is a *new applicant* (D3) or *continuing student* (D9). Make sure your answer key encodes that distinction so you don't penalize a correct retrieval.
