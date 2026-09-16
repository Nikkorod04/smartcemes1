# Guide 06 — Encoding a Needs Assessment (full worked example, Sections I–IX)

**Role: Faculty or Secretary** · Path: **Encode Assessment**
· Prerequisite: none

You will encode one complete respondent — **Marites D. Cabalquinto** of
Brgy. Sagkahan — through the whole nine-section wizard, exercising the
conditional logic, exclusive chips, and caps along the way.

**Log in as any faculty account** (e.g. `faculty1@lnu.com`) or as the
secretary.

## 0. Record context (top of the wizard)

| Field | Value |
|---|---|
| Community | **Brgy. Sagkahan · Tacloban City** (searchable dropdown) |
| Quarter | **Q3 · Jul–Sep** |
| Year | **2026** (dropdown, current year preselected) |

Minimum required to save overall: Community + respondent First name + Last
name (age is optional, 15–120 when filled). Everything else can be left
blank — but fill the sample below for a realistic record.

## Section I — Respondent Information

| Field | Value |
|---|---|
| First name | `Marites` |
| Middle name | `Dalogdog` |
| Last name | `Cabalquinto` |
| Age | `47` |
| Sex | Female |
| Civil status | Married |
| Religion | Roman Catholic |
| Highest educational attainment | College Undergraduate |

**Try it:** type digits into the First name field — they are rejected as
you type (letters only: spaces, periods, hyphens, apostrophes, Ñ allowed).

## Section II — Family Composition

| Field | Value |
|---|---|
| Family composition | 5 members |
| Household member currently studying | Yes |
| Household members in organization | 2 members |

## Section III — Economic / Livelihood

| Field | Value |
|---|---|
| Main source of household livelihood | Retail / sari-sari store |
| Desired livelihood training | Food processing |

## Section IV — Education

| Field | Value |
|---|---|
| Barangay educational facilities (multi) | Elementary school · Day care center |
| Interested in continuing studies | **Yes** → reveals the next question |
| Area of educational interest | Computer literacy |
| Preferred training time | Morning 8:00-12:00 |
| Preferred training days (multi) | Monday · Wednesday · Friday |

**Try it (exclusive chip):** click **Flexible** in training days — it
replaces the three days; click **Tuesday** — Flexible drops out.

## Section V — Health and Sanitation

| Field | Value |
|---|---|
| Most common illness | Hypertension |
| Action when sick | Consult barangay health worker |
| Barangay medical supplies available (multi) | First aid kit · Paracetamol |
| Has barangay health programs | **Yes** → reveals next |
| Benefits from barangay programs | **Yes** → reveals next |
| Programs benefited from (multi) | Vaccination · Nutrition program |
| Water source | Level II / piped |
| Water source distance | Just outside |
| Garbage disposal method | Burning |
| Has own toilet | **Yes** → reveals next |
| Toilet type | Pour flush |
| Keeps animals | **Yes** → reveals next |
| Animals kept (multi) | Chicken · Dog |

**Try it (conditional clearing):** flip **Has own toilet → No** — the
*Toilet type* question hides AND its saved value is cleared (hidden answers
can never be saved by accident). Flip it back to Yes and re-pick Pour flush.

## Section VI — Housing and Basic Amenities

| Field | Value |
|---|---|
| House type | Semi-concrete |
| Tenure status | Owner |
| Has electricity | **Yes** → shows appliances, hides light source |
| Appliances owned (multi) | Television · Cellphone · Electric fan |

**Try it:** flip **Has electricity → No** — *Appliances owned* hides and
clears, *Light source without power* appears (pick Candles if you do), then
flip back to Yes and re-pick the appliances.

## Section VII — Recreation, Organization & Social Participation

| Field | Value |
|---|---|
| Barangay recreational facilities (multi) | Basketball court |
| Use of free time (multi) | Household chores · Socializing |
| Member of organization | **Yes** → reveals the four org questions |
| Organization type | Women organization |
| Organization meeting frequency | Monthly |
| Organization usual | Livelihood activities |
| Position in organization | Member |

**Try it (rule 9):** set **Household members in organization → None**
(Section II) — *Member of Organization* auto-answers **No** and the org
questions hide and clear. Set it back to 2 members and re-answer Yes.

## Section VIII — Problems and Priorities (all multi, max 3 each)

| Field | Value (max 3) |
|---|---|
| Family problems | Low income · Education expenses |
| Health problems | High blood pressure |
| Educational problems | Lack of school supplies · No internet access |
| Employment problems | Lack of jobs |
| Infrastructure problems | No drainage |
| Economic problems | Low income · Price inflation |
| Security problems | No street lights |

**Try it (cap):** click a 4th option in any problem list — the click is
ignored ("up to 3 per field").

## Section IX — Service Ratings and Summary

| Field | Value |
|---|---|
| Barangay service rating | Fair |
| Available for training | **Yes** (choosing No would reveal the required Reason Not Available list) |

## Save

Press **Save**.

**Expected:**
- Toast: **"Assessment submitted — pending secretary validation"**.
- The form resets. The record now sits in the **Secretary's review queue**
  as **pending** (see `secretaryguide.md` §2 — a secretary friend can
  validate it, which recomputes the Sagkahan quarterly summary that the AI
  analysis later consumes).

## Report back

| Check | Pass? |
|---|---|
| Name fields reject digits | ☐ |
| Toilet/electricity/org conditionals show + clear on hide | ☐ |
| None-in-household auto-answers Member of Organization = No | ☐ |
| Flexible exclusive chip replaces/drops correctly | ☐ |
| 4th problem click ignored | ☐ |
| Save → pending → appears in secretary queue | ☐ |
