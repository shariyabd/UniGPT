# UniGPT — Document Upload Metadata Grouping
**Institution:** Northern University Bangladesh
**Dataset:** 10 documents (5 PDF + 5 DOCX)
**Purpose:** Upload-time metadata assignment (category, department, scope, visibility, priority, tags)

---

## Upload Grouping

| # | File | Category | Department(s) | Scope | Visibility | Priority |
|---|------|----------|---------------|-------|------------|----------|
| 1 | 01_exam_notice_midterm_final.pdf | Schedule | All | Common | student, faculty, admin | High |
| 2 | 02_dept_exam_schedule.pdf | Schedule | CSE, EEE, BBA | Multi-dept | student, faculty, admin | High |
| 3 | 03_admission_notice.pdf | General | All | Common | student, admin | Medium |
| 4 | 04_attendance_policy.pdf | Policy | All | Common | student, faculty, admin | High |
| 5 | 05_attendance_sheet_sample.pdf | General | CSE | Single-dept | faculty, admin | Low |
| 6 | 06_cse_course_structure.docx | Syllabus | CSE | Single-dept | student, faculty, admin | High |
| 7 | 07_departments_overview.docx | Syllabus | EEE, BBA | Multi-dept | student, faculty, admin | Medium |
| 8 | 08_fee_policy.docx | Policy | All | Common | student, admin | High |
| 9 | 09_registration_credit_probation_policy.docx | Policy | All | Common | student, faculty, admin | High |
| 10 | 10_faculty_section_overview.docx | Schedule | CSE, EEE, BBA | Multi-dept | faculty, admin | Medium |

---

## Tags (per document)

| # | File | Tags |
|---|------|------|
| 1 | 01_exam_notice_midterm_final.pdf | exam, midterm, final, registration deadline, spring 2026 |
| 2 | 02_dept_exam_schedule.pdf | final exam, schedule, course code, room, spring 2026 |
| 3 | 03_admission_notice.pdf | admission, intake, summer 2026, registration deadline, fees |
| 4 | 04_attendance_policy.pdf | attendance, 70% threshold, eligibility, penalty |
| 5 | 05_attendance_sheet_sample.pdf | attendance sheet, CSE-2103, eligibility, section B |
| 6 | 06_cse_course_structure.docx | CSE, course structure, credits, semester, section |
| 7 | 07_departments_overview.docx | EEE, BBA, English, credits, course distribution |
| 8 | 08_fee_policy.docx | fee, tuition, installment, payment rules, waiver |
| 9 | 09_registration_credit_probation_policy.docx | late registration, extra credit, probation, academic warning |
| 10 | 10_faculty_section_overview.docx | faculty assignment, section size, course registration |

---

## Scope Groups

- **Common (All departments):** 1, 3, 4, 8, 9
- **Multi-department:** 2 (CSE / EEE / BBA), 7 (EEE / BBA), 10 (CSE / EEE / BBA)
- **Single-department:** 5 (CSE), 6 (CSE)

## Category Groups

- **Policy:** 4, 8, 9
- **Schedule:** 1, 2, 10
- **Syllabus:** 6, 7
- **General:** 3, 5

## Visibility Groups

- **student + faculty + admin:** 1, 2, 4, 6, 7, 9
- **student + admin** (no faculty): 3, 8
- **faculty + admin** (staff-only, no student): 5, 10

---

## Notes

- **BBA** = Business Administration.
- **Common** scope means no department filter is applied; the document is returned for any department query.
- **Doc 7** also covers the **English** program, but English is not in the department dropdown, so it remains untagged for department filtering. English-specific queries should still surface Doc 7 via semantic/keyword search.
- Departments in the dropdown with no documents (Biology, Chemistry, Civil, Mathematics, Mechanical, Physics, Psychology) should correctly return **only Common documents** — a useful negative test case for department-filtered retrieval.
