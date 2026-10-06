# White Box Testing

White Box Testing is the method of software testing where the code and internal structure of the system is known to the tester. This will strengthen the security, usability and design of the system while testing the logic of the software.

**System under test:** SmartCEMES — AI-Powered Community Extension Monitoring and Evaluation System, Leyte Normal University (CESO).

**Success:** ✓ &nbsp;&nbsp; **Fail:** ✗

**Table 4. White Box Testing**

| Action | Test 1 | Test 2 |
|---|:---:|:---:|
| Test if the login checks the credentials of the Admin, Secretary, and Faculty accounts. |  |  |
| Test if the role middleware blocks a role from opening another role's page. |  |  |
| Test if a role can only perform its own approvals and not another role's. |  |  |
| Test if the training hours are computed as trainors × trainees × days. |  |  |
| Test if the training hours target is only accepted at the university and project level. |  |  |
| Test if a project's college is derived from its subject domain and not from its lead. |  |  |
| Test if the activity dates are validated against the parent project's date range. |  |  |
| Test if the faculty assignment is refused when it overlaps an existing schedule. |  |  |
| Test if the availability request is refused when it overlaps an accepted request. |  |  |
| Test if a budget entry exceeding the allocation warns without blocking the save. |  |  |
| Test if the employee ID and project code generators never produce a duplicate. |  |  |
| Test if the attendance import updates an existing record instead of duplicating it. |  |  |
| Test if the evaluation import merges per metric and keeps a metric with no values. |  |  |
| Test if the imported attendance replaces the trainee fallback in the hours computation. |  |  |
| Test if an activity marked Completed auto-drafts rendered hours equal to its duration. |  |  |
| Test if an invalid or overnight schedule produces no auto-draft. |  |  |
| Test if approved rendered hours become locked and can no longer be edited. |  |  |
| Test if the XLSX import maps an unknown value to "Other" and keeps the raw text. |  |  |
| Test if the import skips a duplicate row instead of rejecting the whole file. |  |  |
| Test if the AI request sends aggregate data only and never respondent details. |  |  |
| Test if an unresolvable agency code is dropped and counted instead of being cited. |  |  |
| Test if a missing denominator returns a null value instead of a zero. |  |  |
| Test if the beneficiary count is a distinct person count and not a sum of attendances. |  |  |
| Test if the audit log records every approval, rejection, and deletion. |  |  |

*Legend: mark ✓ when the action performs its function as expected; mark ✗ when it fails. Both test runs are executed on the deployed system using the three provisioned role accounts (Admin, Secretary, Faculty).*

**Tested by:** ______________________ &nbsp;&nbsp;&nbsp; **Date:** ______________

**Verified by:** ______________________ &nbsp;&nbsp;&nbsp; **Date:** ______________
