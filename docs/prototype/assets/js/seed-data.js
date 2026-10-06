/* ============================================================
   SmartCEMES — Seed Data (prototype)
   Realistic Leyte Normal University extension context.
   All figures fictional but plausible, for demo purposes.

   v5.0 (adviser-revision model · see revisions.md)
   ---------------------------------------------------------------
   Hierarchy is now:  College -> Program -> Project -> Activity
     colleges     : the 3 LNU colleges + the Graduate School that
                    deliver extension work
     programs     : BROAD thematic programs (social / economic /
                    environmental) — the level the adviser asked for
     projects     : what used to be called a "program" (EXT-2026-00x).
                    Kept on window.DATA.programs as an alias so every
                    existing page keeps working untouched.
     activities   : unchanged shape, + no_of_days + trainors snapshot
   ============================================================ */
window.DATA = (() => {

  /* ==========================================================
     1. COLLEGES  (new — D-R1)
     ========================================================== */
  const colleges = [
    { id:'CAS', code:'CAS', name:'College of Arts and Sciences', short:'Arts & Sciences',
      color:'#003599', accent:'bg-lnu-50 text-lnu-800',
      dean:'Dr. Editha R. Manlapaz',
      thrust:'Literacy, numeracy, environmental science, culture and the arts',
      description:'Delivers literacy, numeracy, cultural and environmental extension work anchored on the sciences and the humanities.'
    },
    { id:'COE', code:'COE', name:'College of Education', short:'Education',
      color:'#F6B800', accent:'bg-gold-50 text-gold-700',
      dean:'Dr. Fernando M. Batulan',
      thrust:'Teacher training, pedagogy, special education, sports and values formation',
      description:'Delivers instructional, pedagogical, special-education, sports and values-formation extension work.'
    },
    { id:'CME', code:'CME', name:'College of Management and Entrepreneurship', short:'Management',
      color:'#10b981', accent:'bg-emerald-50 text-emerald-600',
      dean:'Dr. Aileen M. Gorordo',
      thrust:'Livelihood, enterprise, business management and financial literacy',
      description:'Delivers livelihood, enterprise development and business-management extension work for community income generation.'
    },
    /* The Graduate School (owner request 2026-09-25). Not a college in the
       institutional sense — it is the unit that runs the master's and doctoral
       programmes — but it delivers extension work, so it sits at the same level.
       Violet keeps it distinct from blue / gold / emerald. */
    { id:'GRAD', code:'GRAD', name:'Graduate School', short:'Graduate School',
      color:'#7c3aed', accent:'bg-violet-50 text-violet-700',
      dean:'Dr. Marilou S. Villanueva',
      thrust:'Research, graduate capability building and advanced professional practice',
      description:'Runs the master\'s and doctoral programmes and delivers research-based extension work and graduate capability building.'
    }
  ];
  const collegeById = Object.fromEntries(colleges.map(c => [c.id, c]));

  /* ==========================================================
     2. USERS
     ========================================================== */
  const users = {
    admin:     { name: 'Dr. Lowell A. Quisumbing', role: 'Director, CESO',  roleKey: 'admin',     initials: 'LQ' },
    secretary: { name: 'Prof. Angela D. Salazar',  role: 'CESO Secretary',  roleKey: 'secretary', initials: 'AS' },
    faculty:   { name: 'Prof. John Ryl E. Bautista', role: 'Faculty, COE',  roleKey: 'faculty',   initials: 'JB' }
  };

  /* ==========================================================
     3. FACULTY  (+ college, expertise, involvement — D-R9 / 4.5)
        NOTE: there is NO per-professor training-hours target.
        A faculty member's contribution is measured by total training
        hours rendered and project involvement only. `targetHours` was
        removed as a metric — do not reintroduce an attainment %
        surface against it.
     ========================================================== */
  const faculty = [
    { id:1, employeeId:'LNU-2026-0001', name:'Prof. John Ryl E. Bautista',   college:'COE', dept:'College of Education',            position:'Assistant Professor II',  email:'jrbautista@lnu.edu.ph',   phone:'0917 442 1188', birthdate:'1988-04-12', sex:'Male',   civilStatus:'Married',  address:'12 Maharlika St., Brgy. Sagkahan, Tacloban City, Leyte', status:'Active',   programs:3, expertise:['Literacy & Reading','Remedial Instruction','Mother-Tongue Pedagogy'],          renderedHours:64.0, involvement:'Lead' },
    { id:2, employeeId:'LNU-2026-0002', name:'Prof. Lovelyn F. Maglasang',  college:'COE', dept:'College of Education',            position:'Associate Professor II',  email:'lfmaglasang@lnu.edu.ph',  phone:'0918 233 7745', birthdate:'1981-11-03', sex:'Female', civilStatus:'Married',  address:'88 Rizal Ave., Brgy. San Jose, Tacloban City, Leyte',    status:'Active',   programs:2, expertise:['Numeracy & Math Instruction','Assessment Design','Sports Coaching'],          renderedHours:25.5, involvement:'Co-Lead' },
    { id:3, employeeId:'LNU-2026-0003', name:'Prof. Ricmar P. Aquino',      college:'CAS', dept:'College of Arts and Sciences',    position:'Instructor I',            email:'rpaquino@lnu.edu.ph',     phone:'0995 118 3402', birthdate:'1995-07-28', sex:'Male',   civilStatus:'Single',   address:'Purok 4, Brgy. Apitong, Tacloban City, Leyte',           status:'Active',   programs:2, expertise:['Environmental Conservation','Solid Waste Management','Disaster Preparedness'], renderedHours:47.0, involvement:'Lead' },
    { id:4, employeeId:'LNU-2026-0004', name:'Prof. Shaira Mae T. Cabalquinto', college:'CME', dept:'College of Management and Entrepreneurship', position:'Instructor III', email:'smcabalquinto@lnu.edu.ph', phone:'0906 771 2250', birthdate:'1992-02-19', sex:'Female', civilStatus:'Single', address:'45 Real St., Brgy. Salvacion, Tacloban City, Leyte', status:'Active', programs:1, expertise:['Entrepreneurship','Business Planning','Financial Literacy'],                    renderedHours:32.0, involvement:'Lead' },
    { id:5, employeeId:'LNU-2026-0005', name:'Prof. Elmer D. Padilla',      college:'CAS', dept:'College of Arts and Sciences',    position:'Assistant Professor I',   email:'edpadilla@lnu.edu.ph',    phone:'0921 660 9931', birthdate:'1986-09-14', sex:'Male',   civilStatus:'Married',  address:'7 Bonifacio Ext., Brgy. El Reposo, Tacloban City, Leyte', status:'On Leave', programs:1, expertise:['Community Organizing','Peace & Conflict Resolution','Local Governance'],      renderedHours:0,    involvement:'Co-Lead' },
    { id:6, employeeId:'LNU-2026-0006', name:'Dr. Norilyn B. Gabo',         college:'CAS', dept:'College of Arts and Sciences',    position:'Professor III',           email:'nbgabo@lnu.edu.ph',       phone:'0932 448 5517', birthdate:'1972-06-30', sex:'Female', civilStatus:'Widowed',  address:'23 Justice Romualdez St., Tacloban City, Leyte',         status:'Active',   programs:2, expertise:['Health Literacy','Community Wellness','Geriatric Care'],                    renderedHours:33.0, involvement:'Lead' },
    { id:7, employeeId:'LNU-2026-0007', name:'Prof. Adrian T. Lumbre',      college:'CAS', dept:'College of Arts and Sciences',    position:'Instructor II',           email:'atlumbre@lnu.edu.ph',     phone:'0977 305 8824', birthdate:'1993-12-05', sex:'Male',   civilStatus:'Single',   address:'61 Magsaysay Blvd., Brgy. San Rafael, Dulag, Leyte',     status:'Active',   programs:2, expertise:['Digital Literacy','ICT Training','Media & Information Literacy'],          renderedHours:40.0, involvement:'Lead' },
    { id:8, employeeId:'LNU-2026-0008', name:'Prof. Divina Grace O. Alcoy', college:'COE', dept:'College of Education',            position:'Assistant Professor III', email:'dgalcoy@lnu.edu.ph',      phone:'0910 224 6693', birthdate:'1984-03-22', sex:'Female', civilStatus:'Married',  address:'9 Zamora St., Brgy. Sagkahan, Tacloban City, Leyte',     status:'Active',   programs:0, expertise:['Special & Inclusive Education','Early Childhood Education'],                  renderedHours:12.0, involvement:'—' }
  ];
  faculty.forEach(f => { f.collegeName = (collegeById[f.college] || {}).name || '—'; });

  /* canonical expertise vocabulary — powers the multi-select dropdown
     (a datalist-backed picker, so it is also type-to-filter) */
  const expertiseOptions = [
    'Assessment Design','Business Planning','Community Organizing','Community Wellness',
    'Cultural Heritage','Digital Literacy','Disaster Preparedness','Early Childhood Education',
    'Entrepreneurship','Environmental Conservation','Financial Literacy','Geriatric Care',
    'Health Literacy','ICT Training','Literacy & Reading','Local Governance',
    'Media & Information Literacy','Mother-Tongue Pedagogy','Numeracy & Math Instruction',
    'Peace & Conflict Resolution','Remedial Instruction','Solid Waste Management',
    'Special & Inclusive Education','Sports Coaching'
  ];

  /* faculty position ladder — INSTITUTIONAL ORDER, most junior first.
     Stored as 'rank' for sorting, 'label' for display. */
  const positionLadder = [
    { rank:1,  group:'Instructor',             label:'Instructor I' },
    { rank:2,  group:'Instructor',             label:'Instructor II' },
    { rank:3,  group:'Instructor',             label:'Instructor III' },
    { rank:4,  group:'Assistant Professor',    label:'Assistant Professor I' },
    { rank:5,  group:'Assistant Professor',    label:'Assistant Professor II' },
    { rank:6,  group:'Assistant Professor',    label:'Assistant Professor III' },
    { rank:7,  group:'Assistant Professor',    label:'Assistant Professor IV' },
    { rank:8,  group:'Associate Professor',    label:'Associate Professor I' },
    { rank:9,  group:'Associate Professor',    label:'Associate Professor II' },
    { rank:10, group:'Associate Professor',    label:'Associate Professor III' },
    { rank:11, group:'Associate Professor',    label:'Associate Professor IV' },
    { rank:12, group:'Associate Professor',    label:'Associate Professor V' },
    { rank:13, group:'Professor',              label:'Professor I' },
    { rank:14, group:'Professor',              label:'Professor II' },
    { rank:15, group:'Professor',              label:'Professor III' },
    { rank:16, group:'Professor',              label:'Professor IV' },
    { rank:17, group:'Professor',              label:'Professor V' },
    { rank:18, group:'Professor',              label:'Professor VI' }
  ];
  faculty.forEach(f => {
    const hit = positionLadder.find(p => p.label === f.position);
    f.positionRank = hit ? hit.rank : null;
    f.initials = f.name.replace(/^(Prof\.|Dr\.)\s/, '').split(' ')
                   .map(w => w[0]).slice(0, 2).join('');
  });

  /* derived AFTER the projects array — see bottom of file (hydrateFacultyProjects) */

  /* ==========================================================
     4. COMMUNITIES
     ========================================================== */
  const communities = [
    { id:1, name:'Brgy. San Jose',      municipality:'Tacloban City', province:'Leyte', address:'Purok 3, near San Jose Elementary School', contactPerson:'Kagawad Roberto Tan',      contactNumber:'0917 553 2210', email:'sanjose.barangay@tacloban.gov.ph', beneficiaries:342, programs:2, status:'Active' },
    { id:2, name:'Brgy. Sagkahan',      municipality:'Tacloban City', province:'Leyte', address:'Barangay Hall, Sagkahan Rd',               contactPerson:'Capt. Erlinda Ybañez',       contactNumber:'0928 447 1105', email:'sagkahan.barangay@tacloban.gov.ph', beneficiaries:287, programs:2, status:'Active' },
    { id:3, name:'Brgy. El Reposo',     municipality:'Tacloban City', province:'Leyte', address:'Coastal road, Barangay Hall',              contactPerson:'Kagawad Marissa Dolina',     contactNumber:'0935 220 8764', email:'elreposo.barangay@tacloban.gov.ph', beneficiaries:198, programs:1, status:'Active' },
    { id:4, name:'Brgy. Salvacion',     municipality:'Tacloban City', province:'Leyte', address:'Salvacion proper, near covered court',     contactPerson:'Capt. Rodrigo Amistoso',     contactNumber:'0946 118 3390', email:'salvacion.barangay@tacloban.gov.ph', beneficiaries:164, programs:1, status:'Active' },
    { id:5, name:'Brgy. San Rafael',    municipality:'Dulag',         province:'Leyte', address:'Health station compound',                  contactPerson:'Kagawad Teresita Bionat',    contactNumber:'0912 884 5571', email:'sanrafael.barangay@dulag.gov.ph', beneficiaries:151, programs:1, status:'Active' },
    { id:6, name:'Brgy. Apitong',       municipality:'Tacloban City', province:'Leyte', address:'Barangay Hall, Apitong',                   contactPerson:'Kagawad Noel Sabalza',       contactNumber:'0999 512 0087', email:'apitong.barangay@tacloban.gov.ph', beneficiaries:129, programs:0, status:'Prospecting' }
  ];

  /* ==========================================================
     5. PROGRAMS  (BROAD level — new, D-R6)
        Six thematic programs under the three pillars.
        Grounded in CESO's official "Extension Service Thrust and
        Priorities". KAHAYAG is deliberately NOT referenced in UI.
     ========================================================== */
  const programs = [
    { id:'PROG-01', code:'PROG-LIT', title:'Literacy, Numeracy & Language', pillar:'Social',
      college:'COE',
      blurb:'Reading, numeracy and language development for school-age learners and out-of-school youth.',
      full:'A broad community-education program covering remedial reading, numeracy and language proficiency for elementary learners, parents and out-of-school youth in partner barangays and schools.',
      thrust:'Reading & Numeracy Development',
      status:'Ongoing', targetHours:640, budget:180000 },
    { id:'PROG-02', code:'PROG-ICE', title:'Information, Communication & Education', pillar:'Social',
      college:'COE',
      blurb:'Digital, media and information literacy for parents, senior citizens and community members.',
      full:'Bridges the digital divide by equipping parents, senior citizens and community members with digital, media and information literacy skills for everyday life and livelihood.',
      thrust:'Digital & Media Literacy',
      status:'Ongoing', targetHours:420, budget:120000 },
    { id:'PROG-03', code:'PROG-CULT', title:'Cultural Development', pillar:'Social',
      college:'CAS',
      blurb:'Preservation of local culture, heritage and the arts through community-based workshops.',
      full:'Preserves and promotes Leyteño culture, heritage and the arts through community-based workshops, documentation and cultural exchange activities.',
      thrust:'Culture & the Arts',
      status:'Draft', targetHours:160, budget:60000 },
    { id:'PROG-04', code:'PROG-SPORTS', title:'Physical Fitness & Sports Development', pillar:'Social',
      college:'COE',
      blurb:'Sports clinics and values formation channelling youth energy into discipline and teamwork.',
      full:'Channels youth energy into organised sports while instilling discipline, teamwork and positive values through community sports clinics and tournaments.',
      thrust:'Sports & Values Formation',
      status:'Draft', targetHours:200, budget:80000 },
    { id:'PROG-05', code:'PROG-LIVE', title:'Livelihood, Technical & Business Management', pillar:'Economic',
      college:'CME',
      blurb:'Starter-livelihood, technical and enterprise skills for household income generation.',
      full:'Provides starter-livelihood, technical and business-management skills so households can generate sustainable income and form community enterprises.',
      thrust:'Livelihood & Enterprise Development',
      status:'Ongoing', targetHours:480, budget:150000 },
    { id:'PROG-06', code:'PROG-ENVI', title:'Environmental Conservation & Disaster Preparedness', pillar:'Environmental',
      college:'CAS',
      blurb:'Environmental protection, climate resilience and disaster readiness for coastal communities.',
      full:'Builds environmental protection, climate resilience and disaster readiness in coastal and disaster-prone communities through training and community drills.',
      thrust:'Environmental Protection & Disaster Resilience',
      status:'Ongoing', targetHours:440, budget:135000 }
  ];
  const programById = Object.fromEntries(programs.map(p => [p.id, p]));
  programs.forEach(p => { p.collegeName = (collegeById[p.college] || {}).name || '—'; });

  /* ==========================================================
     6. PROJECTS  (what used to be called "programs")
        Kept on window.DATA.programs AS AN ALIAS so every page
        that already reads DATA.programs keeps rendering.
        Each project now carries: college, program (broad),
        trainors, trainingHours target/actual, allocated budget.
     ========================================================== */
  const projects = [
    { code:'CAS-2026-001', legacyCode:'EXT-2026-001', projectCode:'EXT-2026-001',
      title:'LITRAWIYA: Barangay Reading Proficiency Project',
      acr:'LITRAWIYA',
      program:'PROG-01', college:'COE',
      lead:faculty[0], coLeads:[faculty[1]], community:communities[0], status:'Ongoing',
      budgetAllocated:48000, utilized:31500, target:250, reached:186,
      start:'Jan 20, 2026', end:'Oct 30, 2026', progress:64,
      noOfDays:24, trainors:2, trainees:186, trainingHoursTarget:640, trainingHours:576,
      activitiesCount:3,
      goal:'Raise reading proficiency of Grades 2–4 pupils in Brgy. San Jose through structured remedial reading sessions.',
      strategy:'Weekly remedial reading sessions with pre/post proficiency testing and parent engagement.' },
    { code:'CAS-2026-002', legacyCode:'EXT-2026-002', projectCode:'EXT-2026-002',
      title:'HANDA: Disaster Preparedness Training for Coastal Households',
      acr:'HANDA',
      program:'PROG-06', college:'CAS',
      lead:faculty[2], coLeads:[], community:communities[2], status:'Ongoing',
      budgetAllocated:62500, utilized:64500, target:180, reached:171,
      start:'Feb 10, 2026', end:'Sep 15, 2026', progress:88, overAllocated:true,
      noOfDays:18, trainors:3, trainees:171, trainingHoursTarget:440, trainingHours:648,
      activitiesCount:2,
      goal:'Equip coastal households in Tacloban City with evacuation planning and first-response skills.',
      strategy:'Simulation drills, first-aid certification and community evacuation mapping.' },
    { code:'CME-2026-001', legacyCode:'EXT-2026-003', projectCode:'EXT-2026-003',
      title:'KABUHIAN: Livelihood Skills Training on Soap & Detergent Making',
      acr:'KABUHIAN',
      program:'PROG-05', college:'CME',
      lead:faculty[3], coLeads:[], community:communities[3], status:'Completed',
      budgetAllocated:35000, utilized:33750, target:120, reached:126,
      start:'Mar 03, 2026', end:'Jun 27, 2026', progress:100,
      noOfDays:12, trainors:1, trainees:126, trainingHoursTarget:480, trainingHours:126,
      activitiesCount:1,
      goal:'Provide starter-livelihood skills to unemployed mothers and out-of-school youth.',
      strategy:'Hands-on production training, cost-and-pricing clinic and a product showcase.' },
    { code:'CAS-2026-003', legacyCode:'EXT-2026-004', projectCode:'EXT-2026-004',
      title:'e-LITERACY: Digital Literacy for Parents & Senior Citizens',
      acr:'e-LITERACY',
      program:'PROG-02', college:'CAS',
      lead:faculty[6], coLeads:[faculty[5]], community:communities[1], status:'Ongoing',
      budgetAllocated:40000, utilized:14200, target:150, reached:74,
      start:'Jun 08, 2026', end:'Nov 28, 2026', progress:38,
      noOfDays:16, trainors:2, trainees:74, trainingHoursTarget:420, trainingHours:256,
      activitiesCount:1,
      goal:'Bridge the digital divide for parents and senior citizens in Sagkahan.',
      strategy:'Hands-on device workshops in weekday and weekend cohorts at the barangay learning hub.' },
    { code:'CAS-2026-004', legacyCode:'EXT-2026-005', projectCode:'EXT-2026-005',
      title:'SENIOR CARE: Health & Wellness Intervention for Senior Citizens',
      acr:'SENIOR CARE',
      program:'PROG-02', college:'CAS',
      lead:faculty[5], coLeads:[], community:communities[4], status:'Ongoing',
      budgetAllocated:55000, utilized:21000, target:200, reached:96,
      start:'Jul 13, 2026', end:'Dec 12, 2026', progress:31,
      noOfDays:14, trainors:2, trainees:96, trainingHoursTarget:300, trainingHours:224,
      activitiesCount:2,
      goal:'Improve health literacy and self-care practices among senior citizens in Dulag.',
      strategy:'Wellness talks, screening drives and home-based care kits for mobile-limited seniors.' },
    { code:'COE-2026-001', legacyCode:'EXT-2026-006', projectCode:'EXT-2026-006',
      title:'BATANG MATINIK: Sports & Values Formation Clinic',
      acr:'BATANG MATINIK',
      program:'PROG-04', college:'COE',
      lead:faculty[1], coLeads:[faculty[0]], community:communities[0], status:'Draft',
      budgetAllocated:28000, utilized:0, target:140, reached:0,
      start:'Jan 11, 2027', end:'May 29, 2027', progress:0,
      noOfDays:20, trainors:2, trainees:0, trainingHoursTarget:200, trainingHours:0,
      activitiesCount:0,
      goal:'Channel youth energy into sports while instilling discipline and teamwork values.',
      strategy:'Sport-specific clinics culminating in a barangay-level tournament and values sessions.' },
    { code:'CAS-2026-005', legacyCode:null, projectCode:'EXT-2026-007',
      title:'KULTURA: Leyteño Heritage Documentation & Arts Workshop',
      acr:'KULTURA',
      program:'PROG-03', college:'CAS',
      lead:faculty[4], coLeads:[faculty[2]], community:communities[5], status:'Draft',
      budgetAllocated:22000, utilized:0, target:60, reached:0,
      start:'Nov 09, 2026', end:'Feb 27, 2027', progress:0,
      noOfDays:10, trainors:2, trainees:0, trainingHoursTarget:160, trainingHours:0,
      activitiesCount:0,
      goal:'Document and revitalise local Leyteño heritage, folk arts and oral traditions with community custodians.',
      strategy:'Oral-history documentation, folk-arts workshops and a community exhibit.' }
  ];
  projects.forEach(p => {
    p.collegeName = (collegeById[p.college] || {}).name || '—';
    p.programTitle = (programById[p.program] || {}).title || '—';
    p.pillar = (programById[p.program] || {}).pillar || '—';
  });

  /* ==========================================================
     7. UNIVERSITY TARGETS  (new — D-R5 / R-Q3)
        Director-editable, year-keyed. One annual training-hours
        POOL per year, drawn down by project actuals (§2.2B).
        There is NO program-level target: broad programs carry
        no target of any kind, which is why no targetPrograms
        field exists here (removed in P0n).
     ========================================================== */
  const universityTargets = [
    { year:2026, label:'AY 2026–2027',
      colleges:'CAS, COE, CME', activeColleges:3,
      targetTrainingHours:2500, targetBudget:668000,
      targetBeneficiaries:1100, targetFaculty:8,
      note:'Approved AY 2026–2027 extension service targets, CESO Director.',
      approvedBy:users.admin.name, approvedAt:'Jan 12, 2026' },
    { year:2025, label:'AY 2025–2026',
      colleges:'CAS, COE, CME', activeColleges:3,
      targetTrainingHours:2100, targetBudget:580000,
      targetBeneficiaries:980, targetFaculty:7,
      note:'Prior-year approved targets (baseline for comparison).',
      approvedBy:users.admin.name, approvedAt:'Jan 15, 2025' }
  ];

  /* ==========================================================
     8. INTERAGENCY AGENCY CATALOGUE  (new — D-R10)
        Curated, Director-editable. Seeded from CESO's own
        3 Community Outreach categories so referrals trace to a
        real institutional source rather than being invented.
     ========================================================== */
  const interagencyAgencies = [
    { id:1, agency:'DOH — Department of Health', abbr:'DOH', category:'Food / Nutrition / Health',
      scope:'Feeding programs, nutrition interventions, immunization, medical screening',
      contact:'Regional Office VIII, Palo, Leyte', moa:'None',
      pillar:'Social', status:'Active' },
    { id:2, agency:'DSWD — Dept. of Social Welfare and Development', abbr:'DSWD', category:'Food / Nutrition / Health',
      scope:'Supplementary feeding, food packs, social protection, senior-citizen welfare',
      contact:'Field Office VIII, Tacloban City', moa:'None',
      pillar:'Social', status:'Active' },
    { id:3, agency:'DOH / LGU Health Office — Medical Missions', abbr:'DOH-LGU', category:'Medical / Dental / Optical Missions',
      scope:'Medical, dental and optical mission coordination',
      contact:'Tacloban City Health Office', moa:'None',
      pillar:'Social', status:'Active' },
    { id:4, agency:'DPWH — Dept. of Public Works and Highways', abbr:'DPWH', category:'Infrastructure',
      scope:'Building construction, flood control, road and drainage works',
      contact:'Leyte 1st District Engineering Office', moa:'None',
      pillar:'Environmental', status:'Active' },
    { id:5, agency:'DENR — Dept. of Environment and Natural Resources', abbr:'DENR', category:'Clean & Green / Coastal Clean-up',
      scope:'Water testing, coastal clean-up, tree planting, environmental permits',
      contact:'CENRO Tacloban', moa:'None',
      pillar:'Environmental', status:'Active' },
    { id:6, agency:'BFAR — Bureau of Fisheries and Aquatic Resources', abbr:'BFAR', category:'Clean & Green / Coastal Clean-up',
      scope:'Fisheries livelihood, coastal resource assessment, water quality',
      contact:'BFAR VIII, Tacloban City', moa:'None',
      pillar:'Environmental', status:'Active' },
    { id:7, agency:'DA — Department of Agriculture', abbr:'DA', category:'Food / Nutrition / Health',
      scope:'Agricultural inputs, vegetable production, food security programs',
      contact:'Regional Field Office VIII', moa:'None',
      pillar:'Economic', status:'Active' },
    { id:8, agency:'TESDA — Technical Education and Skills Dev. Authority', abbr:'TESDA', category:'Livelihood & Skills',
      scope:'National certificates, technical-vocational training, assessment',
      contact:'Leyte Provincial Office', moa:'Active MOA',
      pillar:'Economic', status:'Active' },
    { id:9, agency:'DTI — Dept. of Trade and Industry', abbr:'DTI', category:'Livelihood & Skills',
      scope:'MSME development, business registration, market linkage',
      contact:'Leyte Provincial Office', moa:'None',
      pillar:'Economic', status:'Active' },
    { id:10, agency:'DSWD — KALAHI-CIDSS', abbr:'KC', category:'Infrastructure',
      scope:'Community-driven development, small infrastructure, capacity building',
      contact:'Regional Program Office VIII', moa:'None',
      pillar:'Economic', status:'Active' }
  ];

  /* ==========================================================
     9. ACTIVITIES  (+ no_of_days, trainors, training hours)
     ========================================================== */
  const activities = [
    { id:1, program:projects[0], title:'Pre-Assessment & Reading Camp Kick-off', date:'Feb 03, 2026', start:'8:00 AM', end:'12:00 PM', venue:'San Jose Elementary School', status:'Completed', attendees:112, noOfDays:0.5, trainors:2, trainingHours:112 },
    { id:2, program:projects[0], title:'Remedial Reading Session Batch 3',           date:'Jul 18, 2026', start:'1:00 PM', end:'4:00 PM',  venue:'San Jose Day Care Center',   status:'Completed', attendees:87,  noOfDays:0.5, trainors:2, trainingHours:87 },
    { id:3, program:projects[0], title:'Mid-Year Reading Proficiency Evaluation',    date:'Aug 22, 2026', start:'8:00 AM', end:'11:00 AM', venue:'San Jose Elementary School', status:'Upcoming',  attendees:null, noOfDays:0.5, trainors:1, trainingHours:0 },
    { id:4, program:projects[1], title:'Typhoon Drill & Evacuation Simulation',      date:'Jun 21, 2026', start:'7:00 AM', end:'12:00 PM', venue:'El Reposo Barangay Hall',    status:'Completed', attendees:154, noOfDays:0.5, trainors:3, trainingHours:231 },
    { id:5, program:projects[1], title:'First-Aid & Water Rescue Training',          date:'Aug 09, 2026', start:'9:00 AM', end:'4:00 PM',  venue:'Tacloban City Convention Center', status:'Completed', attendees:141, noOfDays:1, trainors:3, trainingHours:423 },
    { id:6, program:projects[3], title:'Basic Computer Hands-on Workshop 2',         date:'Aug 16, 2026', start:'1:00 PM', end:'5:00 PM',  venue:'Sagkahan Learning Hub',      status:'Completed', attendees:52,  noOfDays:0.5, trainors:2, trainingHours:52 },
    { id:7, program:projects[4], title:'Blood Pressure Screening & Wellness Talk',   date:'Aug 30, 2026', start:'8:00 AM', end:'12:00 PM', venue:'San Rafael Health Station',  status:'Upcoming',  attendees:null, noOfDays:0.5, trainors:2, trainingHours:0 },
    { id:8, program:projects[2], title:'Post-Training Product Showcase',             date:'Jun 26, 2026', start:'10:00 AM', end:'2:00 PM', venue:'Salvacion Covered Court',    status:'Completed', attendees:126, noOfDays:0.5, trainors:1, trainingHours:63 },
    { id:9, program:projects[4], title:'PEACE corners: Youth Conflict Resolution Workshop 1', date:'Sep 12, 2026', start:'1:00 PM', end:'4:00 PM', venue:'Salvacion Barangay Hall', status:'Draft', attendees:null, noOfDays:0.5, trainors:1, trainingHours:0 }
  ];
  // Training-hours formula (D-R3 REVISED): trainors x trainees x days
  // NOTE: the x8 hourly factor was REMOVED by the owner — days already carry
  // the duration, so hours are NOT multiplied by 8. A 0.5-day activity with
  // 2 trainors and 112 trainees yields 112 hrs, not 896.
  activities.forEach(a => {
    a.formula = a.attendees && a.noOfDays
      ? `${a.trainors} trainor${a.trainors > 1 ? 's' : ''} × ${a.attendees} trainees × ${a.noOfDays} day${a.noOfDays === 1 ? '' : 's'} = ${a.trainingHours.toLocaleString()} hrs`
      : 'Not yet rendered — no attendance recorded';
  });

  /* ==========================================================
     10. PROPOSALS
     ========================================================== */
  const proposals = [
    { id:1, title:'SOLID Start: Solid Waste Segregation IEC Campaign', faculty:faculty[2], program:projects[1], community:communities[5],
      proposedStart:'Sep 01, 2026', proposedEnd:'Sep 12, 2026',
      submitted:'Aug 14, 2026', status:'Pending',  amount:42000, attachments:['solid-start-proposal.pdf','budget-matrix.xlsx'] },
    { id:2, title:'GULAYAN SA PAARALAN: School Vegetable Gardening Project', faculty:faculty[1], program:projects[0], community:communities[0],
      proposedStart:'Sep 10, 2026', proposedEnd:'Oct 15, 2026',
      submitted:'Aug 19, 2026', status:'Pending',  amount:36500, attachments:['gulayan-proposal.pdf'] },
    { id:3, title:'PEACE corners: Youth Conflict Resolution Workshops', faculty:faculty[4], program:projects[4], community:communities[3],
      proposedStart:'Sep 01, 2026', proposedEnd:'Oct 30, 2026',
      submitted:'Aug 05, 2026', status:'Approved', amount:51000, attachments:['peace-corners.pdf','moa-signed.pdf'], specialOrder:true, createdActivityId:9,
      approvedBy:users.admin.name, approvedAt:'Aug 08, 2026' },
    { id:4, title:'TESDA-Ready: Bread & Pastry NC II Pre-Training', faculty:faculty[6], program:projects[3], community:communities[1],
      proposedStart:'Oct 05, 2026', proposedEnd:'Nov 20, 2026',
      submitted:'Jul 28, 2026', status:'Rejected', amount:78000, attachments:['bread-pastry.pdf'],
      rejectionReason:'Budget exceeds FY allocation ceiling; revise costing or split into two phases.',
      rejectedBy:users.admin.name, rejectedAt:'Aug 02, 2026' },
    { id:5, title:'SIKAD BUHAY: Bike Safety & Repair Livelihood Clinic', faculty:faculty[7], program:projects[2], community:communities[4],
      proposedStart:'Sep 15, 2026', proposedEnd:'Oct 10, 2026',
      submitted:'Aug 22, 2026', status:'Pending',  amount:24500, attachments:['sikad-buhay.pdf'],
      rangeViolation:true }
  ];

  /* ==========================================================
     11. AVAILABILITY REQUESTS (admin-initiated)
     ========================================================== */
  const availabilityRequests = [
    { id:1, activity:activities[2], faculty:faculty[0], date:'Aug 22, 2026', start:'8:00 AM',  end:'11:00 AM', status:'accepted',
      requestedBy:users.admin.name, requestedAt:'Aug 10, 2026', remarks:'Proctoring for the mid-year reading evaluation.', decline_reason:null, respondedAt:'Aug 11, 2026' },
    { id:2, activity:activities[6], faculty:faculty[5], date:'Aug 30, 2026', start:'8:00 AM',  end:'12:00 PM', status:'pending',
      requestedBy:users.admin.name, requestedAt:'Aug 24, 2026', remarks:'BP screening at San Rafael Health Station — needs a health faculty lead.', decline_reason:null, respondedAt:null },
    { id:3, activity:activities[4], faculty:faculty[2], date:'Jun 21, 2026', start:'7:00 AM',  end:'12:00 PM', status:'accepted',
      requestedBy:users.admin.name, requestedAt:'Jun 05, 2026', remarks:'Lead the evacuation simulation drill.', decline_reason:null, respondedAt:'Jun 06, 2026' },
    { id:4, activity:activities[6], faculty:faculty[7], date:'Aug 30, 2026', start:'8:00 AM',  end:'12:00 PM', status:'declined',
      requestedBy:users.admin.name, requestedAt:'Aug 24, 2026', remarks:'Support BP screening logistics.', decline_reason:'Class conflict — university accreditation week.', respondedAt:'Aug 25, 2026' },
    { id:5, activity:activities[6], faculty:faculty[6], date:'Aug 30, 2026', start:'1:00 PM',  end:'5:00 PM',  status:'pending',
      requestedBy:users.admin.name, requestedAt:'Aug 24, 2026', remarks:'Set up digital literacy demo booth at Sagkahan hub.', decline_reason:null, respondedAt:null },
    { id:6, activity:activities[6], faculty:faculty[0], date:'Aug 30, 2026', start:'1:00 PM',  end:'5:00 PM',  status:'pending',
      requestedBy:users.admin.name, requestedAt:'Aug 25, 2026', remarks:'Afternoon registration desk & participant tracking for the BP screening.', decline_reason:null, respondedAt:null }
  ];

  /* ==========================================================
     12. LEGACY RESULTS FRAMEWORK  (kept but NOT shown on the
         project view any more — D-R7 / R-Q2). Pages that still
         read it render, but the new project page ignores it.
     ========================================================== */
  const programObjectives = {
    'EXT-2026-001': [
      { objective:'Enroll Grades 2–4 pupils in structured remedial reading sessions', kpi:null,              baseline:0, target:250, actual:186, unit:'pupils', status:'on_track',  target_date:'Oct 30, 2026', evidence:'Attendance rosters; mid-year evaluation.' },
      { objective:'Raise reading proficiency from baseline to target',                kpi:null,                    baseline:2.1,target:3.2, actual:2.8, unit:'/5 score', status:'on_track',  target_date:'Oct 30, 2026', evidence:'Pre/post reading tests.' },
      { objective:'Sustain ≥80% session attendance consistency',                      kpi:null,                     baseline:0,  target:80,  actual:76,  unit:'%',          status:'on_track',  target_date:'Oct 30, 2026', evidence:'' }
    ],
    'EXT-2026-002': [
      { objective:'Train coastal households in evacuation & first response',          kpi:null,              baseline:0, target:180, actual:171, unit:'households', status:'on_track', target_date:'Sep 15, 2026', evidence:'Drill participation logs.' },
      { objective:'Achieve ≥90% budget utilization without overrun',                  kpi:null,                    baseline:0, target:90,  actual:94,  unit:'%',          status:'achieved', target_date:'Sep 15, 2026', evidence:'Finance ledger Q3.' }
    ],
    'EXT-2026-003': [
      { objective:'Deliver livelihood skills to unemployed mothers & OSY',            kpi:null,                    baseline:0, target:120, actual:126, unit:'persons',    status:'achieved', target_date:'Jun 27, 2026', evidence:'Registration + showcase attendance.' },
      { objective:'Improve livelihood confidence from baseline',                      kpi:null,                    baseline:1.8,target:2.6, actual:2.7, unit:'/5 score',   status:'achieved', target_date:'Jun 27, 2026', evidence:'Pre/post self-assessment.' },
      { objective:'Form a graduate enterprise association within the program period', kpi:null,                    baseline:null, target:1, actual:0, unit:'association', status:'not_met', target_date:'Jun 27, 2026', evidence:'Association organizing deferred — graduates requested a Q4 schedule.' }
    ],
    'EXT-2026-004': [
      { objective:'Bridge the digital divide for parents & seniors',                  kpi:null,              baseline:0, target:150, actual:74,  unit:'persons',    status:'on_track',  target_date:'Nov 28, 2026', evidence:'Hub sign-in sheets.' },
      { objective:'Achieve ≥20% skill uplift in digital literacy',                    kpi:null,                    baseline:1.4,target:1.9, actual:1.5, unit:'/5 score',   status:'on_track',  target_date:'Nov 28, 2026', evidence:'Pre/post module quizzes.' }
    ],
    'EXT-2026-005': [
      { objective:'Serve senior citizens through health & wellness sessions',        kpi:null,                    baseline:0, target:200, actual:96,  unit:'persons',    status:'on_track',  target_date:'Dec 12, 2026', evidence:'Health station logs.' },
      { objective:'Improve health literacy self-assessment',                          kpi:null,                    baseline:2.0,target:2.6, actual:2.1, unit:'/5 score',   status:'on_track',  target_date:'Dec 12, 2026', evidence:'Pre/post wellness talk forms.' },
      { objective:'Achieve ≥70% participation rate among enrolled seniors',           kpi:null,                    baseline:0, target:70,  actual:64,  unit:'%',          status:'on_track',  target_date:'Dec 12, 2026', evidence:'Attendance vs enrollment roll.' }
    ],
    'EXT-2026-006': [
      { objective:'Channel youth energy into sports while instilling discipline and teamwork values', kpi:null,              baseline:0, target:140, actual:0, unit:'youth', status:'not_started', target_date:'May 29, 2027', evidence:'' }
    ]
  };

  /* ==========================================================
     13. PROGRAM NARRATIVES  (Director-only, with TIERED guardrail
         output — D-R8 / §7). Each recommendation is tagged:
           tier 1 = CESO intervention        (in-scope, deliverable)
           tier 2 = interagency referral     (out of scope -> agency)
           tier 3 = prohibited               (never recommended)
         SENIOR CARE is the demo case for a tier-2 referral (D-R8).
     ========================================================== */
  const programNarratives = [
    { program:projects[0], programRef:programById['PROG-01'], generatedAt:'Aug 24, 2026 · 8:15 AM', generatedBy:users.admin.name,
      model:'Gemini 2.0 Flash (aggregated inputs only)', health_label:'on-track', scopeStatus:'in-scope',
      summary:'LITRAWIYA is on track: 186 of 250 target pupils enrolled (74%) with 576 of 640 target training hours rendered (90%). Attendance consistency is slightly below target at 76% but improving. Two more remedial batches are scheduled before October.',
      risks:['Attendance dips among Grade 4 pupils during harvest season (Jun–Aug)'],
      recommendations:[
        { tier:1, action:'Schedule catch-up reading sessions for the 64 enrolled-but-under-attending pupils', priority:'High',
          rationale:'Attendance consistency is 76% against the 80% target — the gap is concentrated in 64 pupils.',
          agency:null, pillar:'Social' },
        { tier:1, action:'Deploy a second trainor from COE to lift the trainor-to-pupil ratio', priority:'Medium',
          rationale:'Current ratio is 1:93; adding one COE reading specialist moves it toward the 1:50 benchmark.',
          agency:null, pillar:'Social' }
      ] },
    { program:projects[1], programRef:programById['PROG-06'], generatedAt:'Aug 24, 2026 · 8:15 AM', generatedBy:users.admin.name,
      model:'Gemini 2.0 Flash (aggregated inputs only)', health_label:'on-track', scopeStatus:'in-scope',
      summary:'HANDA is nearing completion with 171 of 180 households trained (95%) and 648 of 440 target training hours rendered. Budget utilization is 94% against a 90% target — within tolerance. The final water-rescue refresher is the remaining deliverable.',
      risks:['Budget is 4 points above the 90% utilization target — watch for overrun on remaining logistics'],
      recommendations:[
        { tier:1, action:'Close remaining 9 household gaps via barangay hall make-up drills', priority:'High',
          rationale:'Make-up drills reuse existing facilitators and venue at near-zero marginal cost.',
          agency:null, pillar:'Social' },
        { tier:2, action:'Request DENR-supported coastal clean-up and water-safety signage at the El Reposo shoreline', priority:'Low',
          rationale:'Shoreline signage and coastal clean-up fall outside CESO training delivery and require DENR permits.',
          agency:'DENR — Dept. of Environment and Natural Resources', pillar:'Environmental' }
      ] },
    { program:projects[2], programRef:programById['PROG-05'], generatedAt:'Aug 24, 2026 · 8:15 AM', generatedBy:users.admin.name,
      model:'Gemini 2.0 Flash (aggregated inputs only)', health_label:'on-track', scopeStatus:'in-scope',
      summary:'KABUHIAN is fully achieved: 126 beneficiaries trained against a 120 target. The post-training product showcase confirmed 38 micro-enterprise starts. Recommend formal NC II assessment so graduates can be certified.',
      risks:[], recommendations:[
        { tier:2, action:'Arrange TESDA NC II assessment for 38 showcase graduates', priority:'Medium',
          rationale:'National certification is issued by TESDA, not by the university — CESO can only convene and endorse the cohort.',
          agency:'TESDA — Technical Education and Skills Dev. Authority', pillar:'Economic' }
      ] },
    { program:projects[3], programRef:programById['PROG-02'], generatedAt:'Aug 24, 2026 · 8:15 AM', generatedBy:users.admin.name,
      model:'Gemini 2.0 Flash (aggregated inputs only)', health_label:'needs-attention', scopeStatus:'in-scope',
      summary:'e-LITERACY is behind pace: 74 of 150 target participants reached (49%) and 256 of 420 target training hours rendered (61%). Enrollment skews to weekday evening slots, which may be limiting senior turnout.',
      risks:['Mid-year training-hour target likely to be missed if pace holds','Low senior turnout in Sagkahan'],
      recommendations:[
        { tier:1, action:'Add weekend morning cohorts and a barangay-hall satellite site', priority:'High',
          rationale:'Weekend mornings avoid the weekday-evening bias that limits senior turnout.',
          agency:null, pillar:'Social' },
        { tier:1, action:'Run a targeted senior outreach drive with the Brgy. council', priority:'Medium',
          rationale:'Council endorsement historically lifts senior participation in Sagkahan programmes.',
          agency:null, pillar:'Social' }
      ] },
    { program:projects[4], programRef:programById['PROG-02'], generatedAt:'Aug 24, 2026 · 8:15 AM', generatedBy:users.admin.name,
      model:'Gemini 2.0 Flash (aggregated inputs only)', health_label:'needs-attention', scopeStatus:'out-of-scope-detected',
      summary:'SENIOR CARE has reached 96 of 200 seniors (48%) with 224 of 300 target training hours rendered. Needs-assessment data shows recurring hypertension and unmanaged maintenance-medicine access — a health-service delivery gap rather than a training gap.',
      risks:['Care needs exceed training scope — sustained medication access required','Mobile-limited seniors cannot reach station-based sessions'],
      recommendations:[
        { tier:1, action:'Add home-based wellness kits and caregiver coaching for homebound seniors', priority:'Medium',
          rationale:'Mobile-limited seniors cannot attend station sessions; kits extend CESO training reach without transport.',
          agency:null, pillar:'Social' },
        { tier:2, action:'Refer sustained hypertension medication access to DOH / LGU health office', priority:'High',
          rationale:'Continuous medication supply and clinical management are health-service delivery functions, not community training. CESO cannot deliver or fund this.',
          agency:'DOH — Department of Health', pillar:'Social' },
        { tier:2, action:'Refer supplementary feeding for identified undernourished seniors to DSWD', priority:'Medium',
          rationale:'Feeding programmes are a social-welfare service, not a CESO training activity.',
          agency:'DSWD — Dept. of Social Welfare and Development', pillar:'Social' },
        { tier:3, action:'Construct a barangay senior-citizen center with potable water supply', priority:'—',
          rationale:'Construction and water-system works are infrastructure delivery outside CESO expertise. Flagged as PROHIBITED — never surfaced as a CESO recommendation.',
          agency:'DPWH — Dept. of Public Works and Highways', pillar:'Environmental' }
      ] }
  ];

  /* ==========================================================
     14. ASSESSMENT SUMMARIES
     ========================================================== */
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

  /* ==========================================================
     15. AI ANALYSIS  (with tiered guardrail output)
     ========================================================== */
  const aiAnalysis = {
    summaryCommunity:assessmentSummaries[0],
    generatedAt:'Aug 24, 2026 · 9:42 AM',
    model:'Gemini 2.0 Flash (aggregated inputs only)',
    confidence:0.87,
    approvalStatus:'draft',
    scopeStatus:'out-of-scope-detected',
    scopeNote:'2 of 5 drafted items fall outside CESO / LNU college expertise and have been reclassified as interagency referrals. 1 additional item was suppressed as prohibited.',
    narrative:[
      'Household survey responses from Brgy. San Jose (n=86, Q2 2026) point to income insufficiency as the dominant household concern, cited by 61% of respondents, followed closely by lack of local employment (47%) and rising education expenses (39%).',
      'Livelihood readiness is high: 72% of respondents indicate availability for skills training, with strongest interest in food processing (38%), dressmaking and sewing (26%), and computer literacy (19%). Interest clusters among married female respondents aged 25–45.',
      'Water access remains a structural concern: 42% of households still rely primarily on deep wells, and sanitation-related problems appear among the top five issues. Electricity access is relatively high at 81%, suggesting that digital or appliance-based livelihood options are feasible.',
      'Community organization membership is low at 34%, indicating limited existing structures for sustaining interventions. Partnering with existing barangay associations is recommended to improve program continuity.'
    ],
    interventions:[
      { rank:1, tier:1, title:'Launch food processing starter-livelihood cohort',
        detail:'Prioritize a 5-session food processing training aligned with the 38% expressed interest; target 40 participants initially.',
        priority:'High', agency:null, pillar:'Economic', scope:'CESO / CME — Livelihood, Technical & Business Management' },
      { rank:2, tier:1, title:'Pair reading project with parental livelihood support',
        detail:'Schedule LITRAWIYA sessions concurrently with adult skills training to lift attendance across both.',
        priority:'High', agency:null, pillar:'Social', scope:'CESO / CAS — Literacy, Numeracy & Language' },
      { rank:3, tier:2, title:'Refer household water-potability testing and treatment to DENR / LGU',
        detail:'The 42% deep-well reliance and 28% sanitation concern require water-quality testing and treatment, which is a regulatory and infrastructure function.',
        priority:'High', agency:'DENR — Dept. of Environment and Natural Resources', pillar:'Environmental', scope:'Interagency — outside CESO delivery' },
      { rank:4, tier:1, title:'Organize a community savings & enterprise circle',
        detail:'Build on training graduates to form a registered association, raising the 34% organisational base.',
        priority:'Medium', agency:null, pillar:'Economic', scope:'CESO / CME — Livelihood, Technical & Business Management' },
      { rank:5, tier:2, title:'Refer supplementary feeding for identified undernourished households to DSWD',
        detail:'Household food insufficiency was cited by 36% of respondents; feeding is a social-welfare service, not a CESO training activity.',
        priority:'Medium', agency:'DSWD — Dept. of Social Welfare and Development', pillar:'Social', scope:'Interagency — outside CESO delivery' }
    ],
    suppressed:[
      { title:'Construct a barangay multi-purpose building and drainage system',
        reason:'Infrastructure construction is outside CESO and LNU college expertise. Suppressed as a prohibited CESO recommendation.',
        agency:'DPWH — Dept. of Public Works and Highways' }
    ]
  };

  /* ==========================================================
     16. ASSESSMENT REVIEW QUEUE
     ========================================================== */
  const assessmentQueue = [
    { id:1, community:communities[0], quarter:'Q2', year:2026, responses:86, submittedBy:faculty[0], submitted:'Aug 20, 2026', reviewStatus:'pending' },
    { id:2, community:communities[2], quarter:'Q1', year:2026, responses:64, submittedBy:faculty[2], submitted:'Feb 18, 2026', reviewStatus:'validated' },
    { id:3, community:communities[3], quarter:'Q2', year:2026, responses:57, submittedBy:faculty[3], submitted:'Aug 12, 2026', reviewStatus:'validated' },
    { id:4, community:communities[1], quarter:'Q2', year:2026, responses:41, submittedBy:faculty[6], submitted:'Aug 23, 2026', reviewStatus:'pending' },
    { id:5, community:communities[4], quarter:'Q2', year:2026, responses:38, submittedBy:faculty[5], submitted:'Aug 11, 2026', reviewStatus:'returned', remarks:'Section V incomplete for 9 respondents — please encode toilet type before resubmission.' }
  ];

  /* ==========================================================
     17. BENEFICIARIES  (+ college / project)
     ========================================================== */
  const beneficiaries = [
    { name:'Lucia R. Amistoso',   barangay:'San Jose',   sex:'Female', age:41, category:'Housewife',   program:'KABUHIAN', college:'CME' },
    { name:'Roberto G. Tan Jr.',  barangay:'San Jose',   sex:'Male',   age:37, category:'Farmer',      program:'HANDA',    college:'CAS' },
    { name:'Marilou D. Sabalza',  barangay:'Apitong',    sex:'Female', age:29, category:'Fisherfolk',  program:'LITRAWIYA',college:'CAS' },
    { name:'Efren L. Bionat',     barangay:'San Rafael', sex:'Male',   age:63, category:'Senior Citizen', program:'SENIOR CARE', college:'CAS' },
    { name:'Jocelyn M. Gorrido',  barangay:'Sagkahan',   sex:'Female', age:34, category:'Parent',      program:'e-LITERACY', college:'CAS' },
    { name:'Antonio P. Ybañez',   barangay:'Sagkahan',   sex:'Male',   age:68, category:'Senior Citizen', program:'e-LITERACY', college:'CAS' },
    { name:'Rebecca S. Dolina',   barangay:'El Reposo',  sex:'Female', age:45, category:'Vendor',      program:'HANDA',    college:'CAS' },
    { name:'Nestor C. De Paz',    barangay:'Salvacion',  sex:'Male',   age:52, category:'Fisherman',   program:'KABUHIAN', college:'CME' },
    { name:'Analyn T. Catugas',   barangay:'San Jose',   sex:'Female', age:26, category:'OSY',         program:'LITRAWIYA',college:'CAS' },
    { name:'Rodrigo E. Balila',   barangay:'Dulag',      sex:'Male',   age:59, category:'Tricycle Driver', program:'SENIOR CARE', college:'CAS' },
    { name:'Rosario T. Ebdane',   barangay:'San Jose',   sex:'Female', age:47, category:'Barangay Worker', program:'—',    college:'—' },
    { name:'Danilo P. Ondoy',     barangay:'Apitong',    sex:'Male',   age:35, category:'Construction Worker', program:'—', college:'—' }
  ];
  beneficiaries.forEach(b => { b.contact = b.contact || '09123456789'; });

  const programEnrollments = {
    'EXT-2026-001': [2, 8],
    'EXT-2026-002': [1, 6],
    'EXT-2026-003': [0, 7],
    'EXT-2026-004': [4, 5],
    'EXT-2026-005': [3, 9],
    'EXT-2026-006': []
  };

  /* ==========================================================
     18. BUDGET ENTRIES
     ========================================================== */
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

  /* ==========================================================
     19. RENDERED HOURS
     ========================================================== */
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

  /* ==========================================================
     20. NOTIFICATIONS
     ========================================================== */
  const notifications = {
    admin: [
      { icon:'doc',    color:'blue',  title:'New proposal submitted', body:'“SOLID Start: Solid Waste Segregation IEC Campaign” by Prof. Aquino', time:'12m ago', unread:true },
      { icon:'check',  color:'gold',  title:'Proposal approved',      body:'“PEACE corners” special order attached and finalized',                time:'1h ago',  unread:true },
      { icon:'clock',  color:'yellow',title:'Availability responded', body:'Prof. Alcoy declined BP Screening — reason: accreditation week',       time:'3h ago',  unread:true },
      { icon:'clock',  color:'yellow',title:'Rendered hours submitted',body:'Prof. Lumbre — 4.0 hrs (Basic Computer Workshop 2) awaiting approval',  time:'2h ago',  unread:true },
      { icon:'ai',     color:'blue',  title:'Narrative flagged out-of-scope', body:'SENIOR CARE — 2 interagency referrals detected · Director-only', time:'40m ago', unread:true },
      { icon:'chart',  color:'green', title:'Training-hour milestone', body:'HANDA rendered 648 of 440 target training hours (147%)',              time:'Yesterday', unread:false }
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

  /* ==========================================================
     21. DERIVED ROLL-UPS  (computed once, used by new pages)
     ========================================================== */
  const num = (v) => Number(v) || 0;

  // Per-project activity + training-hour roll-up
  projects.forEach(p => {
    const acts = activities.filter(a => a.program === p);
    p.activityCount = acts.length;
    p.completedActivities = acts.filter(a => a.status === 'Completed').length;
    p.renderedTrainingHours = acts.reduce((s, a) => s + num(a.trainingHours), 0);
    p.actualTrainors = acts.reduce((s, a) => s + num(a.trainors), 0) || p.trainors;
    p.actualTrainees = acts.reduce((s, a) => s + num(a.attendees), 0);
    p.hoursPct = p.trainingHoursTarget ? Math.round(p.renderedTrainingHours / p.trainingHoursTarget * 100) : 0;
    p.budgetPct = p.budgetAllocated ? Math.round(p.utilized / p.budgetAllocated * 100) : 0;
  });

  // Per-program (broad) roll-up
  programs.forEach(pg => {
    const kids = projects.filter(p => p.program === pg.id);
    pg.projectCount = kids.length;
    pg.trainingHoursTarget = kids.reduce((s, p) => s + num(p.trainingHoursTarget), 0);
    pg.renderedTrainingHours = kids.reduce((s, p) => s + num(p.renderedTrainingHours), 0);
    pg.budgetAllocated = kids.reduce((s, p) => s + num(p.budgetAllocated), 0);
    pg.utilized = kids.reduce((s, p) => s + num(p.utilized), 0);
    pg.target = kids.reduce((s, p) => s + num(p.target), 0);
    pg.reached = kids.reduce((s, p) => s + num(p.reached), 0);
    pg.trainors = kids.reduce((s, p) => s + num(p.trainors), 0);
    pg.hoursPct = pg.trainingHoursTarget ? Math.round(pg.renderedTrainingHours / pg.trainingHoursTarget * 100) : 0;
    pg.status = kids.some(k => k.status === 'Ongoing') ? 'Ongoing'
              : kids.every(k => k.status === 'Draft') ? 'Draft'
              : kids.some(k => k.status === 'Completed') ? 'Ongoing' : 'Draft';
    pg.collegeName = (collegeById[pg.college] || {}).name || '—';
  });

  // Per-college roll-up
  colleges.forEach(c => {
    const kids = projects.filter(p => p.college === c.id);
    const pgms = programs.filter(p => p.college === c.id);
    c.projectCount = kids.length;
    c.programCount = pgms.length;
    c.facultyCount = faculty.filter(f => f.college === c.id).length;
    c.trainingHoursTarget = kids.reduce((s, p) => s + num(p.trainingHoursTarget), 0);
    c.renderedTrainingHours = kids.reduce((s, p) => s + num(p.renderedTrainingHours), 0);
    c.budgetAllocated = kids.reduce((s, p) => s + num(p.budgetAllocated), 0);
    c.utilized = kids.reduce((s, p) => s + num(p.utilized), 0);
    c.target = kids.reduce((s, p) => s + num(p.target), 0);
    c.reached = kids.reduce((s, p) => s + num(p.reached), 0);
    c.hoursPct = c.trainingHoursTarget ? Math.round(c.renderedTrainingHours / c.trainingHoursTarget * 100) : 0;
    c.projects = kids;
    c.programs = pgms;
  });

  /* Faculty × project involvement index.
     A professor's contribution is measured by the projects they are
     attached to (lead or co-lead) and the training hours rendered —
     never against a personal target. Feeds the directory, the drawer
     and the colleges faculty cards. */
  faculty.forEach(f => {
    const mine = projects.filter(p =>
      (p.lead && p.lead.id === f.id) ||
      (p.coLeads || []).some(c => c.id === f.id)
    );
    f.projectList = mine.map(p => ({
      code:p.code, acr:p.acr, title:p.title, status:p.status,
      college:p.college, hours:p.renderedTrainingHours,
      role:(p.lead && p.lead.id === f.id) ? 'Lead' : 'Co-Lead'
    }));
    f.projectCount   = mine.length;
    f.leadCount      = f.projectList.filter(p => p.role === 'Lead').length;
    f.coLeadCount    = f.projectList.filter(p => p.role === 'Co-Lead').length;
    f.communityCount = new Set(mine.map(p => p.community && p.community.name).filter(Boolean)).size;
  });

  // University-level actuals vs AY 2026–2027 targets
  const universityActuals = {
    year:2026,
    colleges:colleges.length,
    programs:programs.length,
    projects:projects.length,
    activities:activities.length,
    trainingHours:projects.reduce((s, p) => s + num(p.renderedTrainingHours), 0),
    budgetUtilized:projects.reduce((s, p) => s + num(p.utilized), 0),
    budgetAllocated:projects.reduce((s, p) => s + num(p.budgetAllocated), 0),
    beneficiaries:projects.reduce((s, p) => s + num(p.reached), 0),
    faculty:faculty.length,
    activeFaculty:faculty.filter(f => f.status === 'Active').length
  };

  // Ranking helpers used by the "most active" filters (D-R5)
  const mostActiveProjects = [...projects]
    .sort((a, b) => (b.renderedTrainingHours + b.reached) - (a.renderedTrainingHours + a.reached))
    .map(p => ({
      code:p.code, title:p.title, acr:p.acr, college:p.college,
      trainingHours:p.renderedTrainingHours, target:p.trainingHoursTarget,
      trainees:p.trainees, trainors:p.trainors, activities:p.activityCount,
      reached:p.reached, engagement:p.renderedTrainingHours + p.reached
    }));

  /* Faculty ranking uses hours rendered + involvement weight ONLY.
     No target, no attainment % — there is no per-professor target. */
  const mostActiveFaculty = [...faculty]
    .sort((a, b) => (b.renderedHours + b.projectCount * 10) - (a.renderedHours + a.projectCount * 10))
    .map(f => ({
      id:f.id, name:f.name, college:f.college, collegeName:f.collegeName,
      position:f.position, expertise:f.expertise, renderedHours:f.renderedHours,
      projects:f.projectCount, leads:f.leadCount, coLeads:f.coLeadCount,
      engagement:f.renderedHours + f.projectCount * 10
    }));

  /* ==========================================================
     EXPORT
     NAMING CONTRACT (read this before touching a page):
       DATA.programs      = the 6 BROAD thematic programs  (PROG-xx)
       DATA.projects      = the 6 PROJECTS  (CAS/COE/CME-2026-00x)
       DATA.colleges      = the 3 LNU colleges (CAS / COE / CME)

     `programs` used to hold the flat project list. That meaning
     moved to `projects`; the ten call sites that read the old
     shape now read DATA.projects. There is intentionally NO alias,
     so a stale read fails loudly instead of rendering garbage.
     ========================================================== */
  return {
    users, faculty, communities,
    expertiseOptions, positionLadder,
    colleges, programs, projects,
    universityTargets, universityActuals,
    interagencyAgencies,
    activities, proposals, availabilityRequests,
    programObjectives, programNarratives,
    assessmentSummaries, aiAnalysis, assessmentQueue,
    beneficiaries, programEnrollments, budgetEntries, renderedHours,
    notifications,
    mostActiveProjects, mostActiveFaculty
  };
})();

// Guard: catches any page still written against the pre-revision meaning
// of DATA.programs (which was the flat project list). Fails loudly rather
// than silently rendering the wrong rows — see NAMING CONTRACT above.
Object.defineProperty(window.DATA, 'programAlias', {
  get() { throw new Error('DATA.programAlias is gone — DATA.programs is now the BROAD program list. Use DATA.projects for the project list.'); }
});
