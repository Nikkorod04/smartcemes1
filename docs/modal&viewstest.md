# SmartCEMES — Manual View and Modal Test Plan

This document divides the manual testing work between Carlo, Bianca, and Kent.
The goal is to check that the recent frontend changes look correct and that the
main actions still work properly.

Use a laptop or desktop browser. Test at normal browser zoom first. Do not
change the browser window size while testing unless a step specifically asks
you to check scrolling.

## 1. How to start

1. Make sure MySQL is running in XAMPP.
2. Start the Laravel app if it is not already running:

   ```bash
   php artisan serve
   ```

3. Open the local URL shown by Laravel, normally `http://localhost:8000`.
4. Use the seeded accounts below. The password for the seeded accounts is
   usually `password`.

| Account | Use it for |
|---|---|
| `admin@lnu.com` | Director/Admin pages, approvals, AI, reports, catalogue, audit logs |
| `secretary@lnu.com` | Assessment review, imports, beneficiaries, secretary workflows |
| `faculty1@lnu.com` to `faculty4@lnu.com` | Faculty submissions, availability responses, own hours, own profile |

The seeded faculty names include Carlo Sumile, Bianca Oledan, Nikko Villas, and
Kent Naputo. The tester names in this document identify who owns each checklist;
they do not require the tester to use the matching faculty account for every
test.

## 2. Rules for every tester

For every page or modal, check these basic things:

- The page opens without a blank screen, red error page, or broken layout.
- Text is readable and buttons do not overlap.
- Hovering over buttons, cards, links, and table rows gives a clear visual response.
- Buttons show a loading state and cannot be clicked repeatedly while saving.
- Modals fit on the screen, have a clear title, and scroll inside the modal when
  the content is long.
- The close button, X button, Cancel button, and backdrop do not fight each
  other or open another modal by mistake.
- Required fields show a simple error when left blank.
- Error messages appear near the field that needs fixing.
- Search icons and clear/X icons stay inside the search field and remain centered.
- Pagination changes the visible records and does not lose the current filter or search.
- After saving, the new or updated item appears without needing a hard refresh.

Do not archive, delete, or permanently alter existing seeded records unless the
test specifically requires it. When creating test records, begin the name or
title with `TEST - <your name>` so they are easy to identify. Take a screenshot
when a test fails.

## 3. How to report a problem

Use this format for every failed or blocked test:

```text
Test ID:
Tester:
Date and time:
Account/role used:
Page and URL:
Steps I followed:
Expected result:
Actual result:
Screenshot or screen recording:
How often it happened: Always / Sometimes / Once
Severity: Blocking / High / Medium / Low
```

Use these severity meanings:

- **Blocking** — cannot continue, page crashes, or data cannot be saved.
- **High** — an important action saves the wrong data or cannot be completed.
- **Medium** — a feature works but has a confusing or broken UI.
- **Low** — small spacing, wording, alignment, or visual issue.

At the end of testing, send the completed checklist and all screenshots to the
project owner. Do not only say “it does not work”; include the exact steps.

---

# Carlo — Management pages, hierarchy, and reusable modals

Primary role: Admin/Director (`admin@lnu.com`).

Carlo checks the pages where projects, programs, people, communities, and
modals are created or edited. Pay special attention to the standardized modal
layout and multi-select fields.

## Carlo A — Sidebar and main hierarchy

| ID | Page | What to do | Expected result |
|---|---|---|---|
| C-01 | `/dashboard` | Open the Admin dashboard and hover over cards, links, and sidebar items. | The dashboard loads cleanly; cards and navigation links respond to hover. |
| C-02 | `/colleges` | Open the Manage Extension Programs page. Click each college card, then return to All colleges. | College cards open the correct college view and the back control works. |
| C-03 | `/colleges?college=CAS` | Check the CAS college view. | The page shows the college information, extension programs, and faculty cards without the old small explanatory labels. |
| C-04 | `/colleges?college=CAS` | Check the program cards and available actions. | The New Project button is not shown on the college view; the page is clean and not crowded. |
| C-05 | `/programs` | Open the broad programs page. Search, filter, sort, and change pages if available. | Search, filters, sorting, and pagination work without losing the page state. |
| C-06 | `/projects` | Open the project list and use the search/filter controls. | Projects can be found by title/code; the search clear button stays inside the field. |
| C-07 | `/projects/{project-id}` | Open an existing project and move through Overview, Activities, Beneficiaries, and Budget. | Each tab opens correctly and the numbers do not show a server error. |

## Carlo B — New program modal

Open the New Program action from the programs/management page.

- Check that the modal is centered and fits the laptop screen.
- Check that the header, field labels, helper text, and buttons are easy to understand.
- Leave required fields blank and try to save. The modal should stay open and
  show clear validation messages.
- Enter an invalid or incomplete value where possible. The field should explain
  what needs fixing.
- Open every dropdown. The selected value should be visible and the menu should
  not be clipped by the modal.
- Confirm that single-select dropdowns do not show unnecessary checkmarks.
- Close with Cancel, the X button, and the backdrop. The modal should close once.
- Reopen it and confirm old values did not incorrectly remain.
- Create one test program named `TEST - Carlo Program` if the environment allows it.
- Confirm that the success state is clear and the new program appears in the list.

## Carlo C — New project and edit project modals

Use the New Project action and then edit the test project.

1. Confirm the modal uses the standardized layout: colored header, clear title,
   scrollable body, footer buttons, and no content cut off at the bottom.
2. Check College and Program dropdowns. They should show the selected value and
   should not use a checkmark unless the field is a multiple-selection field.
3. Link more than one community/partner school. Select three items one at a
   time, closing and reopening the dropdown between selections. Previously
   selected items must remain selected.
4. Link more than one beneficiary category. The same items must remain selected.
5. Try to save without a title, college, program, date range, or other required
   value. Confirm the field-level validation is readable.
6. Enter an end date before the start date. Saving should be blocked with a
   clear message.
7. Create `TEST - Carlo Project` and confirm an automatic project code appears.
8. Open Edit Project for that project. Confirm the existing values are filled in.
9. Change one field, save, and verify the list/card shows the new value.
10. Cancel editing and confirm no accidental change is saved.

## Carlo D — Add Activity modal

Open a project hub and choose Add Activity.

- Confirm the default start date is the current date.
- Confirm labels explain the date, time, days, participants, budget, status, and
  assigned faculty fields.
- Leave required fields blank and verify validation.
- Try a date outside the project date range. Saving should be blocked.
- Try `0`, a negative number, and a value that is not a half-day increment in
  Days. The form should reject invalid values.
- Select multiple faculty members. They must remain selected while editing the
  other fields.
- Save a test activity and confirm it appears under Activities.
- Open the activity again and verify the saved values are correct.
- Check that the modal can scroll and that the Save/Cancel buttons remain easy
  to reach.

## Carlo E — Faculty Management and Add Faculty modal

Open `/faculty` and `/faculty/directory`.

- Use the search box and college, expertise, and status filters.
- Open the Add Faculty modal and check the standardized layout.
- Confirm Employee ID is automatically generated and cannot be edited.
- Try blank name/email fields and a duplicate email. Validation should stop the save.
- Add expertise, save a test faculty profile, and verify it appears in the directory.
- Open Edit Faculty and confirm the form is pre-filled.
- Change a field and save; cancel once and confirm no change is made.
- Open a faculty profile and check that the profile, contribution figures, and
  navigation are readable.

## Carlo F — Communities and Partner Schools

Open `/communities`.

- Check that the page opens without unnecessary summary cards or duplicate top content.
- Confirm the search bar is shorter enough for the Add Community/School button
  to remain visible at the right.
- Check the All statuses dropdown and the Add Community/School button.
- Open the Add Community/School modal. Check labels, required fields, and scrolling.
- Create a test community and a test partner school, if safe to do so.
- Open an item’s details modal. Confirm there is no redundant close button and
  X button; the close controls should be clear and not duplicated.
- Open Edit Community/Edit School and confirm existing values load correctly.
- Try invalid or missing school-level/community fields and check validation.
- Check archived records and restore actions if they are available.

---

# Kent — Data entry, validations, and operational workflows

Primary roles: Secretary (`secretary@lnu.com`) and Faculty when a faculty action
is required.

Kent checks whether normal users can complete everyday work without confusion.
Focus on form labels, validation messages, conditional fields, imports, and
whether saving a form preserves the values that were entered.

## Kent A — Needs Assessment Wizard

Open `/assessments/create` as Faculty or Secretary.

- Move through the wizard one step at a time.
- Leave required fields blank and try Continue/Save. The form should explain the
  missing information instead of silently doing nothing.
- Select Yes on conditional questions such as training availability, electricity,
  organization membership, animals, studies, and health programs.
- Confirm that the extra fields appear when Yes is selected.
- Change the answer to No and confirm the extra fields disappear and old values
  are cleared.
- Test multiple-choice fields. Select and unselect several values; selected
  values should not disappear unexpectedly.
- Enter names with numbers or unusual characters. The name fields should keep
  the intended letters-only validation.
- Save a test assessment and confirm the success message and saved record.

## Kent B — Assessment Import and Review

Open `/assessments/import` and `/assessments/review`.

1. Download the official assessment template.
2. Upload a correct file and confirm the preview appears before anything is saved.
3. Upload a file with missing or wrong headers. The page should give a useful
   error and a hint to download the correct template.
4. Check row-level errors. One bad row should not hide the good rows.
5. Confirm the final import action is clearly different from the preview action.
6. As Secretary, open `/assessments/review` and open a record drawer.
7. Check that the drawer scrolls, the field labels are readable, and the close
   action works once.
8. Validate a pending submission. Confirm the confirmation modal explains what
   will happen and the record becomes validated.
9. Return a submission with blank remarks. Saving should be blocked.
10. Enter remarks and return it. Confirm the status changes and the remarks are saved.

## Kent C — Beneficiaries and records

Open `/beneficiaries` as Secretary.

- Open Register New and check the modal layout, labels, and validation.
- Try a duplicate person. The warning should be understandable and should not
  save a duplicate accidentally.
- Register a test beneficiary and enroll the person into a project.
- Use search and filters to find the record.
- Open Edit/Unenroll actions and confirm the confirmation messages are clear.
- If a project has an Activities → Records area, open the attendance import modal.
- Download the template, upload it, review the preview, and confirm the import.
- Repeat with one invalid row and confirm the page identifies the bad row.
- Open the evaluation import flow and verify numeric/range validation.

## Kent D — Availability Requests

Open `/availability` using the correct role for each action.

- As Admin/Secretary, open the create request modal if available.
- As Faculty, open the request and respond to it.
- Check date, time, required field, and conflict validation.
- Confirm the search/filter controls work and the clear X stays inside the field.
- Check the pending, accepted, and declined states.
- Open every confirmation modal and verify that Cancel does not save anything.
- Confirm a successful action updates the list without a manual refresh.

## Kent E — Faculty proposals

Open `/proposals/create` as Faculty, then `/proposals`.

- Open the New Proposal form and check the standardized modal/form design.
- Try to submit with missing title, dates, description, or attachment fields.
- Enter a date outside the project/program range and confirm the validation message.
- Submit one safe test proposal.
- Open the proposal details and confirm the attachment/download links work.
- Check search/filter/sort and pagination.
- Confirm an ordinary Faculty account cannot see Admin-only approval actions.

## Kent F — Faculty self-service pages

Using a Faculty account, check:

- `/my-profile` — edit allowed personal/academic fields; confirm institutional
  fields cannot be changed.
- `/my-projects` — project cards, navigation, empty states, and buttons.
- `/rendered-hours/my` — rendered-hour entries, editing rules, validations, and
  search/pagination if present.
- `/calendar` — only the correct faculty activities are visible.

---

# Bianca — Approvals, AI, reports, and audit trail

Primary roles: Admin (`admin@lnu.com`) for Admin pages and Faculty for faculty
access checks.

Bianca checks the pages with approval decisions, AI generation, report previews,
and audit history. These are important because a button can look correct while
still performing the wrong action.

## Bianca A — Proposals approval and rejection

Open `/proposals` as Admin.

- Open a proposal detail modal.
- Click Approve and watch the transition carefully. There must not be a brief
  second modal that flashes and disappears.
- Confirm the approval modal is the only action modal visible.
- Approve a safe test proposal and confirm the success state and activity created.
- Open another proposal and click Reject.
- Try to submit without a rejection reason. Saving must be blocked.
- Enter a reason and reject it. Confirm the status, reason, and notification/audit
  record are correct.
- Test a date conflict. The page should explain why approval is blocked.

## Bianca B — Rendered Hours approval

Open `/rendered-hours` as Admin.

- Check the table, search, status filters, sorting, and pagination.
- Confirm the search icon and X icon are centered inside the search field.
- Open a rendered-hours detail or edit modal.
- Check that the modal fits the screen and scrolls if the content is long.
- Approve a safe pending entry and confirm the status updates.
- Reject/return an entry if the workflow offers it; test the required remarks.
- Confirm faculty cannot access the Admin approval page.

## Bianca C — AI Analysis queue

Open `/ai-analysis` as Admin.

1. Check the queue layout, search, filter chips, and pagination.
2. Click Generate for a row awaiting analysis.
3. Confirm the loading modal appears with the exact title **Generating AI Analysis**.
4. Confirm the loading modal does not show unrelated page content and the spinner moves.
5. Click Cancel generation. Confirm a second confirmation state appears.
6. Choose Keep generating and confirm the loading state continues.
7. Start another generation and choose Yes, cancel. Confirm the loading modal closes
   and the attempt is recorded as canceled/failed rather than published.
8. If the AI provider is configured, generate successfully. Confirm the success
   modal appears with a Review analysis action.
9. If the provider is unavailable or quota-limited, confirm the error modal says
   the analysis is unavailable and does not show a false success message.
10. Open a community history link and repeat the loading/cancel/result checks there.

## Bianca D — Project Narratives

Open `/program-narratives` as Admin, then open a project hub.

- Confirm the page does not show the removed “How to read this page” container.
- Check the portfolio summary strip at the top: Generated, On track, At risk,
  and Needs attention should be easy to read and visually separated.
- Click the On track, At risk, and Needs attention summary cards. Confirm each
  one applies the matching filter and clearly shows its active state.
- Check each project row’s metric cells: Trainors, Trainees, Training hours,
  Target attainment, Activities, and Status.
- Open a completed project by clicking its title area. Confirm the row expands
  smoothly and shows separate Summary, Top Risks, and Recommended Next Actions
  sections.
- Confirm clicking the Generate button does not also expand or collapse the
  project row.
- Collapse the project again and confirm the short summary preview remains
  visible.
- Check the loading feedback when searching or changing a state filter. The
  result area should indicate that projects are being updated and filter buttons
  should not be repeatedly clickable during the update.
- Search for text that has no result. Confirm the empty state explains what was
  searched and that Clear search and Show all states work correctly.
- Open Version history. Confirm it uses a readable timeline with separate
  entries, dates, status markers, and a Current badge for the latest version.
- If failed or interrupted attempts exist, confirm they are clearly labeled as
  failed/incomplete and are not presented as readable completed narratives.
- Click Generate on a project.
- Confirm the modal says **Generating extension project narrative** and does not
  say “executive narrative.”
- Confirm the modal shows the project title, code, college/unit, lead, and linked
  communities/partner schools.
- Check the aggregate-data explanation and the no-PII message.
- Test Keep generating and Yes, cancel.
- Confirm success, error, and canceled result states.
- From the Project Hub success modal, click View full narrative.
- Confirm the full narrative modal opens with summary, risks, actions, provenance,
  and reviewed aggregate information.
- Check version history after generating or canceling a test attempt.

## Bianca E — Reports and PDF preview

Open `/reports` as Admin.

- Confirm the four report cards are visible and readable.
- Open Preview for Annual Extension Performance, Faculty Rendered Hours,
  Community Partner Impact, and a Project Performance report.
- Confirm the preview modal is large enough for the report and the iframe loads.
- Confirm the modal header title changes to the selected report.
- Close using the X, backdrop, and Escape key.
- Open the full report in a new tab.
- Check that the report has print controls or can be printed using the browser.
- Test Print → Save as PDF if a printer dialog is available.
- Confirm the report does not expose raw personal information that should not be
  in an institutional report.
- Confirm non-Admin users cannot open `/reports` or the individual report URLs.

## Bianca F — Interagency Catalogue

Open `/interagency` as Admin.

- Confirm the old top-level introductory blocks that were removed do not return.
- Confirm the Add Agency button is at the upper-right of the catalogue container.
- Open Add Agency and check the standardized modal layout and scrolling.
- Confirm there is no Catalogue order field.
- Test required fields, duplicate agency codes, and invalid values.
- Add a safe test agency, edit it, and retire it if the workflow allows.
- Use the search and active/retired filters.
- Confirm retired agencies are not treated as active catalogue entries.

## Bianca G — Audit Logs

Open `/audit-logs` as Admin.

- Confirm the page loads with the newest activity first.
- Search for an action performed during this test session.
- Check filters, pagination, timestamps, user names, and event descriptions.
- Open any detail view if available and confirm it is readable.
- Confirm there are no create/edit/delete controls on the audit log page.
- Confirm non-Admin users cannot access the page.

---

# 4. Final group review

After Carlo, Bianca, and Kent finish their assigned sections, meet briefly and
check these shared questions:

- Did any page show a server error or blank screen?
- Did any modal flash briefly, disappear, or show two overlays at once?
- Did any selected multi-select item become unselected unexpectedly?
- Did any search clear button leave the input or overlap another control?
- Did any save action create duplicate records after a double click?
- Did any role see a page or button that should be restricted?
- Did any success message appear when the operation actually failed?
- Did any failed or canceled AI generation become readable/published content?
- Did any report preview fail to load or show the wrong report title?

## Final result summary

Each tester should submit this short summary in addition to the detailed issues:

```text
Tester:
Role/account used:
Pages checked:
Modals checked:
Passed:
Failed:
Blocked:
Most serious issue:
Screenshots attached: Yes / No
```

Do not mark a page as fully passed if an important action was not tested. Mark it
as **Blocked** and explain what was missing, such as unavailable AI credentials,
no pending proposal, or no test record available.
