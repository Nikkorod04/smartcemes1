/* ============================================================
   SmartCEMES — Seed Data (prototype)
   Realistic Leyte Normal University extension context.
   All figures fictional but plausible, for demo purposes.
   ============================================================ */
window.DATA = (() => {

  const users = {
    admin:     { name: 'Dr. Ma. Cristina A. Peñaranda', role: 'Director, CESO',  roleKey: 'admin',     initials: 'MP' },
    secretary: { name: 'Prof. Angela D. Salazar',        role: 'CESO Secretary',   roleKey: 'secretary', initials: 'AS' },
    faculty:   { name: 'Prof. John Ryl E. Bautista',     role: 'Faculty, COEd',    roleKey: 'faculty',   initials: 'JB' }
  };

  const faculty = [
    { id:1, employeeId:'LNU-2026-0001', name:'Prof. John Ryl E. Bautista',   dept:'College of Education',            position:'Assistant Professor II', spec:'Reading Education',      email:'jrbautista@lnu.edu.ph',  phone:'0917 442 1188', status:'Active',    programs:3 },
    { id:2, employeeId:'LNU-2026-0002', name:'Prof. Lovelyn F. Maglasang',  dept:'College of Education',            position:'Associate Professor',    spec:'Mathematics Education',  email:'lfmaglasang@lnu.edu.ph', phone:'0918 233 7745', status:'Active',    programs:2 },
    { id:3, employeeId:'LNU-2026-0003', name:'Prof. Ricmar P. Aquino',      dept:'College of Arts and Sciences',    position:'Instructor I',           spec:'Environmental Science',  email:'rpaquino@lnu.edu.ph',    phone:'0995 118 3402', status:'Active',    programs:2 },
    { id:4, employeeId:'LNU-2026-0004', name:'Prof. Shaira Mae T. Cabalquinto', dept:'College of Business Administration', position:'Instructor III', spec:'Entrepreneurship',   email:'smcabalquinto@lnu.edu.ph', phone:'0906 771 2250', status:'Active', programs:1 },
    { id:5, employeeId:'LNU-2026-0005', name:'Prof. Elmer D. Padilla',      dept:'College of Criminal Justice',     position:'Assistant Professor I',  spec:'Community Policing',     email:'edpadilla@lnu.edu.ph',   phone:'0921 660 9931', status:'On Leave',  programs:1 },
    { id:6, employeeId:'LNU-2026-0006', name:'Dr. Norilyn B. Gabo',         dept:'College of Nursing',              position:'Professor I',            spec:'Public Health Nursing',  email:'nbgabo@lnu.edu.ph',      phone:'0932 448 5517', status:'Active',    programs:2 },
    { id:7, employeeId:'LNU-2026-0007', name:'Prof. Adrian T. Lumbre',      dept:'College of Arts and Sciences',    position:'Instructor II',          spec:'Information Technology', email:'atlumbre@lnu.edu.ph',    phone:'0977 305 8824', status:'Active',    programs:1 },
    { id:8, employeeId:'LNU-2026-0008', name:'Prof. Divina Grace O. Alcoy', dept:'College of Education',            position:'Assistant Professor III',spec:'Special Education',      email:'dgalcoy@lnu.edu.ph',     phone:'0910 224 6693', status:'Active',    programs:0 }
  ];

  const communities = [
    { id:1, name:'Brgy. San Jose',      municipality:'Tacloban City', province:'Leyte', address:'Purok 3, near San Jose Elementary School', contactPerson:'Kagawad Roberto Tan',      contactNumber:'0917 553 2210', email:'sanjose.barangay@tacloban.gov.ph', beneficiaries:342, programs:2, status:'Active' },
    { id:2, name:'Brgy. Sagkahan',      municipality:'Tacloban City', province:'Leyte', address:'Barangay Hall, Sagkahan Rd',               contactPerson:'Capt. Erlinda Ybañez',       contactNumber:'0928 447 1105', email:'sagkahan.barangay@tacloban.gov.ph', beneficiaries:287, programs:2, status:'Active' },
    { id:3, name:'Brgy. El Reposo',     municipality:'Tacloban City', province:'Leyte', address:'Coastal road, Barangay Hall',              contactPerson:'Kagawad Marissa Dolina',     contactNumber:'0935 220 8764', email:'elreposo.barangay@tacloban.gov.ph', beneficiaries:198, programs:1, status:'Active' },
    { id:4, name:'Brgy. Salvacion',     municipality:'Tacloban City', province:'Leyte', address:'Salvacion proper, near covered court',     contactPerson:'Capt. Rodrigo Amistoso',     contactNumber:'0946 118 3390', email:'salvacion.barangay@tacloban.gov.ph', beneficiaries:164, programs:1, status:'Active' },
    { id:5, name:'Brgy. San Rafael',    municipality:'Dulag',         province:'Leyte', address:'Health station compound',                  contactPerson:'Kagawad Teresita Bionat',    contactNumber:'0912 884 5571', email:'sanrafael.barangay@dulag.gov.ph', beneficiaries:151, programs:1, status:'Active' },
    { id:6, name:'Brgy. Apitong',       municipality:'Tacloban City', province:'Leyte', address:'Barangay Hall, Apitong',                   contactPerson:'Kagawad Noel Sabalza',       contactNumber:'0999 512 0087', email:'apitong.barangay@tacloban.gov.ph', beneficiaries:129, programs:0, status:'Prospecting' }
  ];

  const programs = [
    { code:'EXT-2026-001', title:'LITRAWIYA: Barangay Reading Proficiency Program', lead:faculty[0], community:communities[0], status:'Ongoing',
      budget:48000, utilized:31500, target:250, reached:186, start:'Jan 20, 2026', end:'Oct 30, 2026', progress:64,
      goal:'Raise reading proficiency of Grades 2–4 pupils in Brgy. San Jose through structured remedial reading sessions.' },
    { code:'EXT-2026-002', title:'HANDA: Disaster Preparedness Training for Coastal Households', lead:faculty[2], community:communities[2], status:'Ongoing',
      budget:62500, utilized:64500, target:180, reached:171, start:'Feb 10, 2026', end:'Sep 15, 2026', progress:88, overAllocated:true,
      goal:'Equip coastal households in Tacloban City with evacuation planning and first-response skills.' },
    { code:'EXT-2026-003', title:'KABUHIAN: Livelihood Skills Training on Soap & Detergent Making', lead:faculty[3], community:communities[3], status:'Completed',
      budget:35000, utilized:33750, target:120, reached:126, start:'Mar 03, 2026', end:'Jun 27, 2026', progress:100,
      goal:'Provide starter-livelihood skills to unemployed mothers and out-of-school youth.' },
    { code:'EXT-2026-004', title:'e-LITERACY: Digital Literacy for Parents & Senior Citizens', lead:faculty[6], community:communities[1], status:'Ongoing',
      budget:40000, utilized:14200, target:150, reached:74, start:'Jun 08, 2026', end:'Nov 28, 2026', progress:38,
      goal:'Bridge the digital divide for parents and senior citizens in Sagkahan.' },
    { code:'EXT-2026-005', title:'SENIOR CARE: Health & Wellness Program for Senior Citizens', lead:faculty[5], community:communities[4], status:'Ongoing',
      budget:55000, utilized:21000, target:200, reached:96, start:'Jul 13, 2026', end:'Dec 12, 2026', progress:31,
      goal:'Improve health literacy and self-care practices among senior citizens in Dulag.' },
    { code:'EXT-2026-006', title:'BATANG MATINIK: Sports & Values Formation Clinic', lead:faculty[1], community:communities[0], status:'Draft',
      budget:28000, utilized:0, target:140, reached:0, start:'Jan 11, 2027', end:'May 29, 2027', progress:0,
      goal:'Channel youth energy into sports while instilling discipline and teamwork values.' }
  ];

  const activities = [
    { id:1, program:programs[0], title:'Pre-Assessment & Reading Camp Kick-off', date:'Feb 03, 2026', start:'8:00 AM', end:'12:00 PM', venue:'San Jose Elementary School', status:'Completed', attendees:112 },
    { id:2, program:programs[0], title:'Remedial Reading Session Batch 3',           date:'Jul 18, 2026', start:'1:00 PM', end:'4:00 PM',  venue:'San Jose Day Care Center',   status:'Completed', attendees:87 },
    { id:3, program:programs[0], title:'Mid-Year Reading Proficiency Evaluation',    date:'Aug 22, 2026', start:'8:00 AM', end:'11:00 AM', venue:'San Jose Elementary School', status:'Upcoming',  attendees:null },
    { id:4, program:programs[1], title:'Typhoon Drill & Evacuation Simulation',      date:'Jun 21, 2026', start:'7:00 AM', end:'12:00 PM', venue:'El Reposo Barangay Hall',    status:'Completed', attendees:154 },
    { id:5, program:programs[1], title:'First-Aid & Water Rescue Training',          date:'Aug 09, 2026', start:'9:00 AM', end:'4:00 PM',  venue:'Tacloban City Convention Center', status:'Completed', attendees:141 },
    { id:6, program:programs[3], title:'Basic Computer Hands-on Workshop 2',         date:'Aug 16, 2026', start:'1:00 PM', end:'5:00 PM',  venue:'Sagkahan Learning Hub',      status:'Completed', attendees:52 },
    { id:7, program:programs[4], title:'Blood Pressure Screening & Wellness Talk',   date:'Aug 30, 2026', start:'8:00 AM', end:'12:00 PM', venue:'San Rafael Health Station',  status:'Upcoming',  attendees:null },
    { id:8, program:programs[2], title:'Post-Training Product Showcase',             date:'Jun 26, 2026', start:'10:00 AM', end:'2:00 PM', venue:'Salvacion Covered Court',    status:'Completed', attendees:126 },
    { id:9, program:programs[4], title:'PEACE corners: Youth Conflict Resolution Workshop 1', date:'Sep 12, 2026', start:'1:00 PM', end:'4:00 PM', venue:'Salvacion Barangay Hall', status:'Draft', attendees:null }
  ];

  const proposals = [
    { id:1, title:'SOLID Start: Solid Waste Segregation IEC Campaign', faculty:faculty[2], program:programs[1], community:communities[5],
      proposedStart:'Sep 01, 2026', proposedEnd:'Sep 12, 2026',
      submitted:'Aug 14, 2026', status:'Pending',  amount:42000, attachments:['solid-start-proposal.pdf','budget-matrix.xlsx'] },
    { id:2, title:'GULAYAN SA PAARALAN: School Vegetable Gardening Project', faculty:faculty[1], program:programs[0], community:communities[0],
      proposedStart:'Sep 10, 2026', proposedEnd:'Oct 15, 2026',
      submitted:'Aug 19, 2026', status:'Pending',  amount:36500, attachments:['gulayan-proposal.pdf'] },
    { id:3, title:'PEACE corners: Youth Conflict Resolution Workshops', faculty:faculty[4], program:programs[4], community:communities[3],
      proposedStart:'Sep 01, 2026', proposedEnd:'Oct 30, 2026',
      submitted:'Aug 05, 2026', status:'Approved', amount:51000, attachments:['peace-corners.pdf','moa-signed.pdf'], specialOrder:true, createdActivityId:9,
      approvedBy:users.admin.name, approvedAt:'Aug 08, 2026' },
    { id:4, title:'TESDA-Ready: Bread & Pastry NC II Pre-Training', faculty:faculty[6], program:programs[3], community:communities[1],
      proposedStart:'Oct 05, 2026', proposedEnd:'Nov 20, 2026',
      submitted:'Jul 28, 2026', status:'Rejected', amount:78000, attachments:['bread-pastry.pdf'],
      rejectionReason:'Budget exceeds FY allocation ceiling; revise costing or split into two phases.',
      rejectedBy:users.admin.name, rejectedAt:'Aug 02, 2026' },
    { id:5, title:'SIKAD BUHAY: Bike Safety & Repair Livelihood Clinic', faculty:faculty[7], program:programs[2], community:communities[4],
      proposedStart:'Sep 15, 2026', proposedEnd:'Oct 10, 2026',
      submitted:'Aug 22, 2026', status:'Pending',  amount:24500, attachments:['sikad-buhay.pdf'],
      rangeViolation:true }
  ];

  // ADMIN-INITIATED availability requests (v3.4): tied to an activity; faculty accept/decline
  const availabilityRequests = [
    { id:1, activity:activities[2], faculty:faculty[0], date:'Aug 22, 2026', start:'8:00 AM',  end:'11:00 AM', status:'accepted',
      requestedBy:users.admin.name, requestedAt:'Aug 10, 2026', remarks:'Proctoring for the mid-year reading evaluation.', decline_reason:null, respondedAt:'Aug 11, 2026' },
    { id:2, activity:activities[6], faculty:faculty[5], date:'Aug 30, 2026', start:'8:00 AM',  end:'12:00 PM', status:'pending',
      requestedBy:users.admin.name, requestedAt:'Aug 24, 2026', remarks:'BP screening at San Rafael Health Station — needs a nursing faculty lead.', decline_reason:null, respondedAt:null },
    { id:3, activity:activities[4], faculty:faculty[2], date:'Jun 21, 2026', start:'7:00 AM',  end:'12:00 PM', status:'accepted',
      requestedBy:users.admin.name, requestedAt:'Jun 05, 2026', remarks:'Lead the evacuation simulation drill.', decline_reason:null, respondedAt:'Jun 06, 2026' },
    { id:4, activity:activities[6], faculty:faculty[7], date:'Aug 30, 2026', start:'8:00 AM',  end:'12:00 PM', status:'declined',
      requestedBy:users.admin.name, requestedAt:'Aug 24, 2026', remarks:'Support BP screening logistics.', decline_reason:'Class conflict — university accreditation week.', respondedAt:'Aug 25, 2026' },
    { id:5, activity:activities[6], faculty:faculty[6], date:'Aug 30, 2026', start:'1:00 PM',  end:'5:00 PM',  status:'pending',
      requestedBy:users.admin.name, requestedAt:'Aug 24, 2026', remarks:'Set up digital literacy demo booth at Sagkahan hub.', decline_reason:null, respondedAt:null },
    { id:6, activity:activities[6], faculty:faculty[0], date:'Aug 30, 2026', start:'1:00 PM',  end:'5:00 PM',  status:'pending',
      requestedBy:users.admin.name, requestedAt:'Aug 25, 2026', remarks:'Afternoon registration desk & participant tracking for the BP screening.', decline_reason:null, respondedAt:null }
  ];

  // Program objectives (results framework, 7.15): baseline/target/actual/status
  const programObjectives = {
    'EXT-2026-001': [
      { objective:'Enroll Grades 2–4 pupils in structured remedial reading sessions', kpi:'community_reach', baseline:0, target:250, actual:186, unit:'pupils', status:'on_track',  target_date:'Oct 30, 2026', evidence:'Attendance rosters; mid-year evaluation.' },
      { objective:'Raise reading proficiency from baseline to target',                kpi:'knowledge_gain',        baseline:2.1,target:3.2, actual:2.8, unit:'/5 score', status:'on_track',  target_date:'Oct 30, 2026', evidence:'Pre/post reading tests.' },
      { objective:'Sustain ≥80% session attendance consistency',                      kpi:'attendance_consistency', baseline:0,  target:80,  actual:76,  unit:'%',          status:'on_track',  target_date:'Oct 30, 2026', evidence:'' }
    ],
    'EXT-2026-002': [
      { objective:'Train coastal households in evacuation & first response',          kpi:'community_reach', baseline:0, target:180, actual:171, unit:'households', status:'on_track', target_date:'Sep 15, 2026', evidence:'Drill participation logs.' },
      { objective:'Achieve ≥90% budget utilization without overrun',                  kpi:'budget_utilization',    baseline:0, target:90,  actual:94,  unit:'%',          status:'achieved', target_date:'Sep 15, 2026', evidence:'Finance ledger Q3.' }
    ],
    'EXT-2026-003': [
      { objective:'Deliver livelihood skills to unemployed mothers & OSY',            kpi:'community_reach',       baseline:0, target:120, actual:126, unit:'persons',    status:'achieved', target_date:'Jun 27, 2026', evidence:'Registration + showcase attendance.' },
      { objective:'Improve livelihood confidence from baseline',                      kpi:'knowledge_gain',        baseline:1.8,target:2.6, actual:2.7, unit:'/5 score',   status:'achieved', target_date:'Jun 27, 2026', evidence:'Pre/post self-assessment.' },
      { objective:'Form a graduate enterprise association within the program period', kpi:null,                    baseline:null, target:1, actual:0, unit:'association', status:'not_met', target_date:'Jun 27, 2026', evidence:'Association organizing deferred — graduates requested a Q4 schedule.' }
    ],
    'EXT-2026-004': [
      { objective:'Bridge the digital divide for parents & seniors',                  kpi:'community_reach', baseline:0, target:150, actual:74,  unit:'persons',    status:'on_track',  target_date:'Nov 28, 2026', evidence:'Hub sign-in sheets.' },
      { objective:'Achieve ≥20% skill uplift in digital literacy',                    kpi:'knowledge_gain',        baseline:1.4,target:1.9, actual:1.5, unit:'/5 score',   status:'on_track',  target_date:'Nov 28, 2026', evidence:'Pre/post module quizzes.' }
    ],
    'EXT-2026-005': [
      { objective:'Serve senior citizens through health & wellness sessions',        kpi:'community_reach',       baseline:0, target:200, actual:96,  unit:'persons',    status:'on_track',  target_date:'Dec 12, 2026', evidence:'Health station logs.' },
      { objective:'Improve health literacy self-assessment',                          kpi:'knowledge_gain',        baseline:2.0,target:2.6, actual:2.1, unit:'/5 score',   status:'on_track',  target_date:'Dec 12, 2026', evidence:'Pre/post wellness talk forms.' },
      { objective:'Achieve ≥70% participation rate among enrolled seniors',           kpi:'participation_rate',    baseline:0, target:70,  actual:64,  unit:'%',          status:'on_track',  target_date:'Dec 12, 2026', evidence:'Attendance vs enrollment roll.' }
    ],
    'EXT-2026-006': [
      { objective:'Channel youth energy into sports while instilling discipline and teamwork values', kpi:'community_reach', baseline:0, target:140, actual:0, unit:'youth', status:'not_started', target_date:'May 29, 2027', evidence:'' }
    ]
  };

  // Director-only executive program narratives (4.15) — one per program (latest)
  const programNarratives = [
    { program:programs[0], generatedAt:'Aug 24, 2026 · 8:15 AM', generatedBy:users.admin.name, model:'gpt-4o-mini (aggregated inputs only)',
      health_label:'on-track',
      summary:'LITRAWIYA is on track: 186 of 250 target pupils enrolled (74%) with reading proficiency rising from 2.1 to 2.8 of 5. Attendance consistency is slightly below target at 76% but improving. Two more remedial batches are scheduled before October.',
      risks:['Attendance dips among Grade 4 pupils during harvest season (Jun–Aug)'],
      recommendations:[
        { action:'Schedule catch-up sessions for the 64 enrolled-but-under-attending pupils', priority:'High', rationale:'Attendance consistency is 76% against the 80% target — the gap is concentrated in 64 pupils.' },
        { action:'Coordinate with Brgy. San Jose day care center to absorb afternoon cohorts', priority:'Medium', rationale:'Afternoon slots are free at the day care and conflict less with pupil home duties.' }
      ] },
    { program:programs[1], generatedAt:'Aug 24, 2026 · 8:15 AM', generatedBy:users.admin.name, model:'gpt-4o-mini (aggregated inputs only)',
      health_label:'on-track',
      summary:'HANDA is nearing completion with 171 of 180 households trained (95%). Budget utilization is 94% against a 90% target — within tolerance. The final water-rescue refresher is the remaining deliverable.',
      risks:['Budget is 4 points above the 90% utilization target — watch for overrun on remaining logistics'],
      recommendations:[
        { action:'Close remaining 9 household gaps via barangay hall make-up drills', priority:'High', rationale:'Make-up drills reuse existing facilitators and venue at near-zero marginal cost.' }
      ] },
    { program:programs[2], generatedAt:'Aug 24, 2026 · 8:15 AM', generatedBy:users.admin.name, model:'gpt-4o-mini (aggregated inputs only)',
      health_label:'on-track',
      summary:'KABUHIAN is fully achieved: 126 beneficiaries trained against a 120 target, with livelihood confidence up from 1.8 to 2.7 of 5. The post-training product showcase confirmed 38 micro-enterprise starts.',
      risks:[], recommendations:[] },
    { program:programs[3], generatedAt:'Aug 24, 2026 · 8:15 AM', generatedBy:users.admin.name, model:'gpt-4o-mini (aggregated inputs only)',
      health_label:'needs-attention',
      summary:'e-LITERACY is behind pace: 74 of 150 target participants reached (49%) and skill uplift is minimal (1.5 of 5 vs 1.9 target). Enrollment skews to weekday evening slots, which may be limiting senior turnout.',
      risks:['Mid-year skill-uplift objective likely to be missed if pace holds','Low senior turnout in Sagkahan'],
      recommendations:[
        { action:'Add weekend morning cohorts and a barangay-hall satellite site', priority:'High', rationale:'Weekend mornings avoid the weekday-evening bias that limits senior turnout.' },
        { action:'Run a targeted senior outreach drive with the Brgy. council', priority:'Medium', rationale:'Council endorsement historically lifts senior participation in Sagkahan programs.' }
      ] },
    { program:programs[4], generatedAt:'Aug 24, 2026 · 8:15 AM', generatedBy:users.admin.name, model:'gpt-4o-mini (aggregated inputs only)',
      health_label:'on-track',
      summary:'SENIOR CARE is on pace: 96 of 200 seniors served (48%) with health literacy improving from 2.0 to 2.1 of 5. Wellness talks are tracking to plan; the November health fair should close the remaining reach gap.',
      risks:['Low-to-moderate attendance among mobile-limited seniors'],
      recommendations:[
        { action:'Add home-based wellness kits for homebound seniors', priority:'Medium', rationale:'Mobile-limited seniors cannot attend station sessions; kits extend reach without transport.' }
      ] }
  ];

  // Aggregated needs-assessment summaries per community per quarter
  const assessmentSummaries = [
    { id:1, community:communities[0], quarter:'Q2', year:2026, responses:86,
      gender:{ Female:54, Male:31, 'Prefer not to say':1 },
      electricityAccess:81, orgMembership:34, trainingAvailability:72, avgSatisfaction:3.4,
      topProblems:[['Low income',61],['Lack of employment',47],['Education expenses',39],['Poor sanitation',28]],
      waterSources:[['Deep well',42],['Community water system',24],['Spring',11],['Bottled water',9]],
      trainingInterests:[['Food processing',38],['Dressmaking / sewing',26],['Computer literacy',19],['Beauty care',14]] },
    { id:2, community:communities[2], quarter:'Q1', year:2026, responses:64,
      gender:{ Female:37, Male:26, 'Prefer not to say':1 },
      electricityAccess:88, orgMembership:41, trainingAvailability:69, avgSatisfaction:3.8,
      topProblems:[['No potable water',44],['Low income',40],['Medical expenses',33],['No flood control',27]],
      waterSources:[['Level II / piped',31],['Deep well',17],['Rainwater',9],['Spring',7]],
      trainingInterests:[['First aid / disaster response',35],['Livelihood entrepreneurship',22],['Vegetable production',15],['Other',6]] },
    { id:3, community:communities[3], quarter:'Q2', year:2026, responses:57,
      gender:{ Female:33, Male:24 },
      electricityAccess:76, orgMembership:29, trainingAvailability:78, avgSatisfaction:3.6,
      topProblems:[['Lack of employment',49],['Insufficient food',36],['Debt',30],['Poor housing',21]],
      waterSources:[['Deep well',28],['Community water system',15],['River / stream',9],['Bottled water',5]],
      trainingInterests:[['Soap making',31],['Handicraft making',24],['Rice/corn farming',12],['Fish processing',8]] }
  ];

  // AI analysis draft for flagship demo
  const aiAnalysis = {
    summaryCommunity:assessmentSummaries[0],
    generatedAt:'Aug 24, 2026 · 9:42 AM',
    model:'gpt-4o-mini (aggregated inputs only)',
    confidence:0.87,
    approvalStatus:'draft',
    narrative:[
      'Household survey responses from Brgy. San Jose (n=86, Q2 2026) point to income insufficiency as the dominant household concern, cited by 61% of respondents, followed closely by lack of local employment (47%) and rising education expenses (39%).',
      'Livelihood readiness is high: 72% of respondents indicate availability for skills training, with strongest interest in food processing (38%), dressmaking and sewing (26%), and computer literacy (19%). Interest clusters among married female respondents aged 25–45.',
      'Water access remains a structural concern: 42% of households still rely primarily on deep wells, and sanitation-related problems appear among the top five issues. Electricity access is relatively high at 81%, suggesting that digital or appliance-based livelihood options are feasible.',
      'Community organization membership is low at 34%, indicating limited existing structures for sustaining interventions. Partnering with existing barangay associations is recommended to improve program continuity.'
    ],
    interventions:[
      { rank:1, title:'Launch food processing starter-livelihood cohort', detail:'Prioritize a 5-session food processing training aligned with the 38% expressed interest; target 40 participants initially.', priority:'High' },
      { rank:2, title:'Pair reading program with parental livelihood support', detail:'Schedule LITRAWIYA sessions concurrently with adult skills training to lift attendance across both programs.', priority:'High' },
      { rank:3, title:'Sanitation IEC + household water safety kit distribution', detail:'Address the 28% sanitation concern through coordinated IEC with the barangay health station.', priority:'Medium' },
      { rank:4, title:'Organize a community savings & enterprise circle', detail:'Build on training graduates to form a registered association, raising the 34% organizational base.', priority:'Medium' },
      { rank:5, title:'Quarterly digital literacy refresher for parents', detail:'Extend e-LITERACY modules using Sagkahan hub facilities; low-cost given 81% electricity access.', priority:'Low' }
    ]
  };

  // Needs-assessment review queue (secretary)
  const assessmentQueue = [
    { id:1, community:communities[0], quarter:'Q2', year:2026, responses:86, submittedBy:faculty[0], submitted:'Aug 20, 2026', reviewStatus:'pending' },
    { id:2, community:communities[2], quarter:'Q1', year:2026, responses:64, submittedBy:faculty[2], submitted:'Feb 18, 2026', reviewStatus:'validated' },
    { id:3, community:communities[3], quarter:'Q2', year:2026, responses:57, submittedBy:faculty[3], submitted:'Aug 12, 2026', reviewStatus:'validated' },
    { id:4, community:communities[1], quarter:'Q2', year:2026, responses:41, submittedBy:faculty[6], submitted:'Aug 23, 2026', reviewStatus:'pending' },
    { id:5, community:communities[4], quarter:'Q2', year:2026, responses:38, submittedBy:faculty[5], submitted:'Aug 11, 2026', reviewStatus:'returned', remarks:'Section V incomplete for 9 respondents — please encode toilet type before resubmission.' }
  ];

  const beneficiaries = [
    { name:'Lucia R. Amistoso',   barangay:'San Jose',   sex:'Female', age:41, category:'Housewife',   program:'KABUHIAN' },
    { name:'Roberto G. Tan Jr.',  barangay:'San Jose',   sex:'Male',   age:37, category:'Farmer',      program:'HANDA' },
    { name:'Marilou D. Sabalza',  barangay:'Apitong',    sex:'Female', age:29, category:'Fisherfolk',  program:'LITRAWIYA' },
    { name:'Efren L. Bionat',     barangay:'San Rafael', sex:'Male',   age:63, category:'Senior Citizen', program:'SENIOR CARE' },
    { name:'Jocelyn M. Gorrido',  barangay:'Sagkahan',   sex:'Female', age:34, category:'Parent',      program:'e-LITERACY' },
    { name:'Antonio P. Ybañez',   barangay:'Sagkahan',   sex:'Male',   age:68, category:'Senior Citizen', program:'e-LITERACY' },
    { name:'Rebecca S. Dolina',   barangay:'El Reposo',  sex:'Female', age:45, category:'Vendor',      program:'HANDA' },
    { name:'Nestor C. De Paz',    barangay:'Salvacion',  sex:'Male',   age:52, category:'Fisherman',   program:'KABUHIAN' },
    { name:'Analyn T. Catugas',   barangay:'San Jose',   sex:'Female', age:26, category:'OSY',         program:'LITRAWIYA' },
    { name:'Rodrigo E. Balila',   barangay:'Dulag',      sex:'Male',   age:59, category:'Tricycle Driver', program:'SENIOR CARE' },
    { name:'Rosario T. Ebdane',   barangay:'San Jose',   sex:'Female', age:47, category:'Barangay Worker', program:'—' },
    { name:'Danilo P. Ondoy',     barangay:'Apitong',    sex:'Male',   age:35, category:'Construction Worker', program:'—' }
  ];

  // Contact number feature (v4.7): every beneficiary defaults to the demo number
  beneficiaries.forEach(b => { b.contact = b.contact || '09123456789'; });

  // Program-scoped enrollment (extension_program_beneficiary pivot): program code -> beneficiary indexes
  const programEnrollments = {
    'EXT-2026-001': [2, 8],
    'EXT-2026-002': [1, 6],
    'EXT-2026-003': [0, 7],
    'EXT-2026-004': [4, 5],
    'EXT-2026-005': [3, 9],
    'EXT-2026-006': []
  };

  // Budget utilization entries (budget_utilizations): program required, activity optional
  const budgetEntries = [
    { id:1,  program:'EXT-2026-001', activityIndex:0,   item:'Reading materials & big books',        amount:12500, date:'Feb 10, 2026', ref:'REC-2026-0101' },
    { id:2,  program:'EXT-2026-001', activityIndex:0,   item:'Kick-off snacks & logistics',          amount:6800,  date:'Feb 03, 2026', ref:'REC-2026-0102' },
    { id:3,  program:'EXT-2026-001', activityIndex:1,   item:'Session materials — batch 3',          amount:5200,  date:'Jul 18, 2026', ref:'REC-2026-0103' },
    { id:4,  program:'EXT-2026-001', activityIndex:null,item:'Mid-year evaluation printing',         amount:7000,  date:'Aug 21, 2026', ref:'REC-2026-0802' },
    { id:5,  program:'EXT-2026-002', activityIndex:3,   item:'Drill equipment & PPE',                amount:21400, date:'Jun 21, 2026', ref:'REC-2026-0201' },
    { id:6,  program:'EXT-2026-002', activityIndex:4,   item:'First-aid consumables & rescue gear',  amount:18600, date:'Aug 09, 2026', ref:'REC-2026-0202' },
    { id:7,  program:'EXT-2026-002', activityIndex:null,item:'Venue & transport (overrun entry)',    amount:24500, date:'Aug 28, 2026', ref:'REC-2026-0203' },
    { id:8,  program:'EXT-2026-003', activityIndex:7,   item:'Soap & detergent raw materials',       amount:15200, date:'Mar 15, 2026', ref:'REC-2026-0301' },
    { id:9,  program:'EXT-2026-003', activityIndex:7,   item:'Training kits & packaging supplies',   amount:11400, date:'Apr 20, 2026', ref:'REC-2026-0302' },
    { id:10, program:'EXT-2026-003', activityIndex:null,item:'Showcase logistics',                   amount:7150,  date:'Jun 26, 2026', ref:'REC-2026-0303' },
    { id:11, program:'EXT-2026-004', activityIndex:5,   item:'Workshop laptops rental & internet',   amount:8600,  date:'Aug 16, 2026', ref:'REC-2026-0401' },
    { id:12, program:'EXT-2026-004', activityIndex:null,item:'Printed handouts & certificates',      amount:5600,  date:'Jun 08, 2026', ref:'REC-2026-0402' },
    { id:13, program:'EXT-2026-005', activityIndex:6,   item:'BP apparatus & screening supplies',    amount:12700, date:'Aug 30, 2026', ref:'REC-2026-0501' },
    { id:14, program:'EXT-2026-005', activityIndex:null,item:'Wellness talk materials',              amount:8300,  date:'Jul 13, 2026', ref:'REC-2026-0502' }
  ];

  // Rendered hours (8.9): auto-drafts from completed activities, faculty adjust down, admin approves
  const renderedHours = [
    { id:1, faculty:faculty[0], activity:activities[0], date:'Feb 03, 2026', hours:4.0, source:'auto', status:'approved',
      submittedBy:faculty[0].name, submittedAt:'Feb 05, 2026', approvedBy:users.admin.name, approvedAt:'Feb 06, 2026', remarks:'' },
    { id:2, faculty:faculty[0], activity:activities[1], date:'Jul 18, 2026', hours:3.0, source:'auto', status:'pending',
      submittedBy:faculty[0].name, submittedAt:'Jul 20, 2026', approvedBy:null, approvedAt:null, remarks:'Co-led with Prof. Maglasang — adjusted from 3.0 to 2.5 hrs (partial session).' },
    { id:3, faculty:faculty[2], activity:activities[3], date:'Jun 21, 2026', hours:5.0, source:'auto', status:'approved',
      submittedBy:faculty[2].name, submittedAt:'Jun 23, 2026', approvedBy:users.admin.name, approvedAt:'Jun 24, 2026', remarks:'' },
    { id:4, faculty:faculty[2], activity:activities[4], date:'Aug 09, 2026', hours:7.0, source:'auto', status:'pending',
      submittedBy:faculty[2].name, submittedAt:'Aug 11, 2026', approvedBy:null, approvedAt:null, remarks:'' },
    { id:5, faculty:faculty[6], activity:activities[5], date:'Aug 16, 2026', hours:4.0, source:'auto', status:'pending',
      submittedBy:faculty[6].name, submittedAt:'Aug 18, 2026', approvedBy:null, approvedAt:null, remarks:'' },
    { id:6, faculty:faculty[1], activity:activities[7], date:'Jun 26, 2026', hours:4.0, source:'auto', status:'approved',
      submittedBy:faculty[1].name, submittedAt:'Jun 27, 2026', approvedBy:users.admin.name, approvedAt:'Jun 28, 2026', remarks:'' }
  ];

  const notifications = {
    admin: [
      { icon:'doc',    color:'blue',  title:'New proposal submitted', body:'“SOLID Start: Solid Waste Segregation IEC Campaign” by Prof. Aquino', time:'12m ago', unread:true },
      { icon:'check',  color:'gold',  title:'Proposal approved',      body:'“PEACE corners” special order attached and finalized',                time:'1h ago',  unread:true },
      { icon:'clock',  color:'yellow',title:'Availability responded', body:'Prof. Alcoy declined BP Screening — reason: accreditation week',       time:'3h ago',  unread:true },
      { icon:'clock',  color:'yellow',title:'Rendered hours submitted',body:'Prof. Lumbre — 4.0 hrs (Basic Computer Workshop 2) awaiting approval',  time:'2h ago',  unread:true },
      { icon:'ai',     color:'blue',  title:'Program narrative ready', body:'LITRAWIYA executive summary regenerated · Director-only',             time:'40m ago', unread:true },
      { icon:'chart',  color:'green', title:'Program milestone',      body:'HANDA reached 95% of beneficiary target',                             time:'Yesterday', unread:false }
    ],
    secretary: [
      { icon:'doc',    color:'blue',  title:'XLSX import received',   body:'Needs-assessment import — Brgy. Sagkahan Q2 · 41 respondents (pending validation)', time:'18m ago', unread:true },
      { icon:'doc',    color:'yellow',title:'Assessment returned',    body:'Brgy. San Rafael submission missing Section V entries',               time:'2h ago',  unread:true },
      { icon:'check',  color:'green', title:'Validation complete',    body:'Brgy. Salvacion Q2 assessment validated',                             time:'Yesterday', unread:false }
    ],
    faculty: [
      { icon:'clock',  color:'yellow',title:'Availability requested', body:'BP Screening registration desk · Aug 30, 1:00 – 5:00 PM — please accept or decline', time:'1h ago',  unread:true },
      { icon:'clock',  color:'blue',  title:'Rendered hours approved', body:'Pre-Assessment & Reading Camp Kick-off · 4.0 hrs approved by Director', time:'3h ago',  unread:true },
      { icon:'doc',    color:'red',   title:'Proposal rejected',      body:'“TESDA-Ready” — see remarks from the Director',                       time:'5h ago',  unread:true },
      { icon:'cal',    color:'blue',  title:'Activity scheduled',     body:'Mid-Year Reading Proficiency Evaluation · Aug 22',                    time:'2d ago',  unread:false }
    ]
  };

  return { users, faculty, communities, programs, activities, proposals, availabilityRequests, programObjectives, programNarratives, assessmentSummaries, aiAnalysis, assessmentQueue, beneficiaries, programEnrollments, budgetEntries, renderedHours, notifications };
})();
