export const courses = [
  {slug:"fundamentals-electrical-electronics-engineering",title:"Fundamentals of Electrical and Electronics Engineering",discipline:"Electrical Engineering",description:"A structured introduction to circuits, machines, measurements and electronic devices.",mode:"Self-paced",level:"Beginner",duration:"8 weeks",image:"/courses/electrical-engineering.webp",instructor:"Dr. Ananya Rao"},
  {slug:"thermodynamics-for-engineers",title:"Thermodynamics for Engineers",discipline:"Mechanical Engineering",description:"Energy systems, properties, cycles and practical thermal analysis.",mode:"Online",level:"Intermediate",duration:"6 weeks",image:"/courses/mechanical-engineering.webp",instructor:"Prof. R. Mehta"},
  {slug:"structural-analysis-basics",title:"Structural Analysis Basics",discipline:"Civil Engineering",description:"Loads, reactions and the behaviour of determinate structural systems.",mode:"Self-paced",level:"Beginner",duration:"7 weeks",image:"/courses/civil-engineering.webp",instructor:"Dr. Kavita Sen"},
  {slug:"introduction-computer-systems",title:"Introduction to Computer Systems",discipline:"Computer Science Engineering",description:"Processor architecture, memory, operating systems and digital foundations.",mode:"Online",level:"Beginner",duration:"6 weeks",image:"/courses/computer-systems.webp",instructor:"Arjun Nair"},
  {slug:"mining-engineering-fundamentals",title:"Mining Engineering Fundamentals",discipline:"Mining Engineering",description:"Mine planning, excavation, safety and responsible mineral development.",mode:"Instructor-led",level:"Beginner",duration:"10 weeks",image:"/publications/mining-engineering.webp",instructor:"Dr. N. Sharma"},
  {slug:"power-system-analysis",title:"Power System Analysis",discipline:"Power Systems Engineering",description:"Network modelling, load flow, faults and reliable grid operation.",mode:"Self-paced",level:"Advanced",duration:"9 weeks",image:"/publications/power-energy-systems.webp",instructor:"Dr. Ananya Rao"},
  {slug:"engineering-mathematics-essentials",title:"Engineering Mathematics Essentials",discipline:"Engineering Mathematics",description:"Calculus, differential equations, matrices and numerical methods.",mode:"Online",level:"Intermediate",duration:"8 weeks",image:"/books/engineering-mathematics.webp",instructor:"Prof. S. Iyer"},
  {slug:"electronic-circuits-systems",title:"Electronic Circuits and Systems",discipline:"Electronics Engineering",description:"Semiconductor devices, amplifiers and practical circuit design.",mode:"Instructor-led",level:"Intermediate",duration:"8 weeks",image:"/publications/electronics-computer-systems.webp",instructor:"Dr. P. Bose"},
];

export const publications = [
  {slug:"international-journal-electrical-engineering",title:"International Journal of Electrical Engineering",discipline:"Electrical Engineering",type:"Journal",issue:"Volume 12 · Issue 3",access:"Free",date:"September 2026",pages:"148",editor:"Dr. Mira Kapoor",image:"/courses/electrical-engineering.webp"},
  {slug:"journal-mechanical-engineering",title:"Journal of Mechanical Engineering",discipline:"Mechanical Engineering",type:"Journal",issue:"Volume 9 · Issue 2",access:"Paid",date:"August 2026",pages:"126",editor:"Prof. R. Mehta",image:"/courses/mechanical-engineering.webp"},
  {slug:"journal-civil-engineering",title:"Journal of Civil Engineering",discipline:"Civil Engineering",type:"Journal",issue:"Volume 14 · Issue 1",access:"Free",date:"July 2026",pages:"164",editor:"Dr. Kavita Sen",image:"/courses/civil-engineering.webp"},
  {slug:"journal-mining-engineering",title:"Journal of Mining Engineering",discipline:"Mining Engineering",type:"Journal",issue:"Volume 7 · Issue 4",access:"Paid",date:"June 2026",pages:"112",editor:"Dr. N. Sharma",image:"/publications/mining-engineering.webp"},
  {slug:"journal-electronics-computer-systems",title:"Journal of Electronics and Computer Systems",discipline:"Electronics Engineering",type:"Journal",issue:"Volume 11 · Issue 2",access:"Free",date:"May 2026",pages:"136",editor:"Dr. P. Bose",image:"/publications/electronics-computer-systems.webp"},
  {slug:"journal-power-energy-systems",title:"Journal of Power and Energy Systems",discipline:"Power Systems Engineering",type:"Journal",issue:"Volume 15 · Issue 1",access:"Paid",date:"April 2026",pages:"172",editor:"Dr. Ananya Rao",image:"/publications/power-energy-systems.webp"},
  {slug:"journal-applied-mathematics",title:"Journal of Applied Mathematics",discipline:"Engineering Mathematics",type:"Journal",issue:"Volume 8 · Issue 3",access:"Free",date:"March 2026",pages:"120",editor:"Prof. S. Iyer",image:"/books/engineering-mathematics.webp"},
  {slug:"smart-grid-technical-guide",title:"Smart Grid Technical Guide",discipline:"Electrical Engineering",type:"Study Guide",issue:"Second edition",access:"Paid",date:"February 2026",pages:"94",editor:"E4ENGINEERS Editorial Team",image:"/publications/power-energy-systems.webp"},
];

export const studyResources = [
  ["Electrical Engineering Lecture Notes","Electrical Engineering","Lecture Notes","Free","Core circuit, machine and measurement concepts."],
  ["Engineering Mathematics Formula Sheet","Engineering Mathematics","Formula Sheets","Free","Essential calculus, algebra and differential-equation formulas."],
  ["Structural Analysis Solved Question Paper","Civil Engineering","Solved Question Papers","Premium","Worked structural-analysis examination problems."],
  ["Power Systems Technical Diagrams","Power Systems Engineering","Technical Diagrams","Premium","Generation, transmission and protection diagrams."],
  ["Mechanical Engineering Study Guide","Mechanical Engineering","Study Guides","Free","A concise guide to mechanics, materials and thermal systems."],
  ["Computer Systems Practice Materials","Computer Science Engineering","Practice Materials","Premium","Exercises covering architecture, memory and operating systems."],
  ["Mining Engineering Notes","Mining Engineering","Lecture Notes","Free","Mine planning, safety and equipment reference notes."],
  ["Electronics Formula Reference","Electronics Engineering","Formula Sheets","Free","Circuit, device and signal formulas for quick revision."],
  ["Electrical Machines Practice Set","Electrical Engineering","Practice Materials","Premium","Applied problems for transformers and rotating machines."],
  ["Civil Engineering Technical Diagrams","Civil Engineering","Technical Diagrams","Free","Structural and infrastructure drawing references."],
].map(([title,discipline,type,access,description],index)=>({id:index+1,slug:title.toLowerCase().replaceAll(" ","-").replaceAll("/","-"),title,discipline,type,access,description,format:"PDF",pages:12+index*3,updated:"September 2026",language:"English"}));
