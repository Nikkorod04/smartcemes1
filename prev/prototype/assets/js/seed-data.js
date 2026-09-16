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
    { id:1, name:'Brgy. San Jose',      municipality:'Tacloban City', province:'Leyte', contactPerson:'Kagawad Roberto Tan',        contactNumber:'0917 553 2210', beneficiaries:342, programs:2, status:'Active' },
    { id:2, name:'Brgy. Sagkahan',      municipality:'Tacloban City', province:'Leyte', contactPerson:'Capt. Erlinda Ybañez',       contactNumber:'0928 447 1105', beneficiaries:287, programs:2, status:'Active' },
    { id:3, name:'Brgy. El Reposo',     municipality:'Palo',          province:'Leyte', contactPerson:'Kagawad Marissa Dolina',     contactNumber:'0935 220 8764', beneficiaries:198, programs:1, status:'Active' },
    { id:4, name:'Brgy. Salvacion',     municipality:'Tanauan',       province:'Leyte', contactPerson:'Capt. Rodrigo Amistoso',     contactNumber:'0946 118 3390', beneficiaries:164, programs:1, status:'Active' },
    { id:5, name:'Brgy. San Rafael',    municipality:'Dulag',         province:'Leyte', contactPerson:'Kagawad Teresita Bionat',    contactNumber:'0912 884 5571', beneficiaries:151, programs:1, status:'Active' },
    { id:6, name:'Brgy. Apitong',       municipality:'Tacloban City', province:'Leyte', contactPerson:'Kagawad Noel Sabalza',       contactNumber:'0999 512 0087', beneficiaries:129, programs:0, status:'Prospecting' }
  ];

  const programs = [
    { code:'EXT-2026-001', title:'LITRAWIYA: Barangay Reading Proficiency Program', lead:faculty[0], community:communities[0], status:'Ongoing',
      budget:48000, utilized:31500, target:250, reached:186, start:'Jan 20, 2026', end:'Oct 30, 2026', progress:64,
      goal:'Raise reading proficiency of Grades 2–4 pupils in Brgy. San Jose through structured remedial reading sessions.' },
    { code:'EXT-2026-002', title:'HANDA: Disaster Preparedness Training for Coastal Households', lead:faculty[2], community:communities[2], status:'Ongoing',
      budget:62500, utilized:58900, target:180, reached:171, start:'Feb 10, 2026', end:'Sep 15, 2026', progress:88,
      goal:'Equip coastal households in Palo with evacuation planning and first-response skills.' },
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
    { id:1, program:programs[0], title:'Pre-Assessment & Reading Camp Kick-off', date:'Feb 03, 2026', venue:'San Jose Elementary School', status:'Completed', attendees:112 },
    { id:2, program:programs[0], title:'Remedial Reading Session Batch 3',           date:'Jul 18, 2026', venue:'San Jose Day Care Center',   status:'Completed', attendees:87 },
    { id:3, program:programs[0], title:'Mid-Year Reading Proficiency Evaluation',    date:'Aug 22, 2026', venue:'San Jose Elementary School', status:'Upcoming',  attendees:null },
    { id:4, program:programs[1], title:'Typhoon Drill & Evacuation Simulation',      date:'Jun 21, 2026', venue:'El Reposo Barangay Hall',    status:'Completed', attendees:154 },
    { id:5, program:programs[1], title:'First-Aid & Water Rescue Training',          date:'Aug 09, 2026', venue:'Palo Town Civic Center',     status:'Completed', attendees:141 },
    { id:6, program:programs[3], title:'Basic Computer Hands-on Workshop 2',         date:'Aug 16, 2026', venue:'Sagkahan Learning Hub',      status:'Completed', attendees:52 },
    { id:7, program:programs[4], title:'Blood Pressure Screening & Wellness Talk',   date:'Aug 30, 2026', venue:'San Rafael Health Station',  status:'Upcoming',  attendees:null },
    { id:8, program:programs[2], title:'Post-Training Product Showcase',             date:'Jun 26, 2026', venue:'Salvacion Covered Court',    status:'Completed', attendees:126 }
  ];

  const proposals = [
    { id:1, title:'SOLID Start: Solid Waste Segregation IEC Campaign', faculty:faculty[2], community:communities[5],
      submitted:'Aug 14, 2026', status:'Pending',  amount:42000, attachments:['solid-start-proposal.pdf','budget-matrix.xlsx'] },
    { id:2, title:'GULAYAN SA PAARALAN: School Vegetable Gardening Project', faculty:faculty[1], community:communities[0],
      submitted:'Aug 19, 2026', status:'Pending',  amount:36500, attachments:['gulayan-proposal.pdf'] },
    { id:3, title:'PEACE corners: Youth Conflict Resolution Workshops', faculty:faculty[4], community:communities[3],
      submitted:'Aug 05, 2026', status:'Approved', amount:51000, attachments:['peace-corners.pdf','moa-signed.pdf'], specialOrder:true },
    { id:4, title:'TESDA-Ready: Bread & Pastry NC II Pre-Training', faculty:faculty[6], community:communities[1],
      submitted:'Jul 28, 2026', status:'Rejected', amount:78000, attachments:['bread-pastry.pdf'],
      rejectionReason:'Budget exceeds FY allocation ceiling; revise costing or split into two phases.' },
    { id:5, title:'SIKAD BUHAY: Bike Safety & Repair Livelihood Clinic', faculty:faculty[7], community:communities[4],
      submitted:'Aug 22, 2026', status:'Pending',  amount:24500, attachments:['sikad-buhay.pdf'] }
  ];

  const availabilities = [
    { id:1, faculty:faculty[0], date:'Sep 02, 2026', slot:'8:00 AM – 12:00 PM', status:'Pending' },
    { id:2, faculty:faculty[1], date:'Sep 04, 2026', slot:'1:00 PM – 5:00 PM',  status:'Pending' },
    { id:3, faculty:faculty[2], date:'Sep 09, 2026', slot:'Whole Day',          status:'Approved', remarks:'Coordinate with HANDA team.' },
    { id:4, faculty:faculty[5], date:'Sep 11, 2026', slot:'8:00 AM – 12:00 PM', status:'Rejected', remarks:'Conflict with university accreditation week.' },
    { id:5, faculty:faculty[6], date:'Sep 15, 2026', slot:'1:00 PM – 5:00 PM',  status:'Approved', remarks:'' }
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
    { name:'Rodrigo E. Balila',   barangay:'Dulag',      sex:'Male',   age:59, category:'Tricycle Driver', program:'SENIOR CARE' }
  ];

  const notifications = {
    admin: [
      { icon:'doc',    color:'blue',  title:'New proposal submitted', body:'“SOLID Start: Solid Waste Segregation IEC Campaign” by Prof. Aquino', time:'12m ago', unread:true },
      { icon:'check',  color:'gold',  title:'Proposal approved',      body:'“PEACE corners” special order attached and finalized',                time:'1h ago',  unread:true },
      { icon:'clock',  color:'yellow',title:'Availability pending',   body:'2 availability requests awaiting your approval',                      time:'3h ago',  unread:true },
      { icon:'chart',  color:'green', title:'Program milestone',      body:'HANDA reached 95% of beneficiary target',                             time:'Yesterday', unread:false }
    ],
    secretary: [
      { icon:'ai',     color:'blue',  title:'AI analysis ready for review', body:'Brgy. San Jose Q2 2026 insights drafted — your approval needed', time:'18m ago', unread:true },
      { icon:'doc',    color:'yellow',title:'Assessment returned',    body:'Brgy. San Rafael submission missing Section V entries',               time:'2h ago',  unread:true },
      { icon:'check',  color:'green', title:'Validation complete',    body:'Brgy. Salvacion Q2 assessment validated',                             time:'Yesterday', unread:false }
    ],
    faculty: [
      { icon:'check',  color:'green', title:'Availability approved',  body:'Sep 09, 2026 · Whole Day approved by the Director’s Office',          time:'1h ago',  unread:true },
      { icon:'doc',    color:'red',   title:'Proposal rejected',      body:'“TESDA-Ready” — see remarks from the Director',                       time:'5h ago',  unread:true },
      { icon:'cal',    color:'blue',  title:'Activity scheduled',     body:'Mid-Year Reading Proficiency Evaluation · Aug 22',                    time:'2d ago',  unread:false }
    ]
  };

  return { users, faculty, communities, programs, activities, proposals, availabilities, assessmentSummaries, aiAnalysis, assessmentQueue, beneficiaries, notifications };
})();
