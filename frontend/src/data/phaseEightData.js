export const notices=[
 {id:1,type:"Courses",title:"New Course: Structural Analysis Basics",description:"A new introductory civil engineering course is available in the catalogue.",fullContent:"Explore fundamental load paths, structural behaviour and analysis methods through a concise prototype course outline.",publishedAt:"28 Sep 2026",important:false,relatedUrl:"/courses/structural-analysis-basics"},
 {id:2,type:"Publications",title:"New Publication: Journal of Applied Mathematics",description:"The latest prototype issue is now listed in Publications.",fullContent:"The issue presents planned coverage of mathematical modelling, numerical methods and applied engineering analysis.",publishedAt:"26 Sep 2026",important:false,relatedUrl:"/publications/journal-of-applied-mathematics"},
 {id:3,type:"Resources",title:"New Resource: Engineering Mathematics Formula Sheets",description:"Quick-reference formula sheets have been added to study resources.",fullContent:"The resource groups commonly used engineering mathematics formulas for revision and classroom support.",publishedAt:"24 Sep 2026",important:false,relatedUrl:"/resources/formula-sheets"},
 {id:4,type:"Workshops",title:"Workshop: Smart Power Systems",description:"A prototype technical workshop listing is available for review.",fullContent:"This frontend-only listing demonstrates an event focused on grid automation, renewable integration and system operation.",publishedAt:"22 Sep 2026",important:false,relatedUrl:"/workshops"},
 {id:5,type:"Important Dates",title:"Registration Deadline Reminder",description:"Prototype registrations for the October learning session close soon.",fullContent:"This is sample notice content for frontend development and does not represent a real registration deadline.",publishedAt:"20 Sep 2026",important:true,relatedUrl:"/contact"},
 {id:6,type:"Exam Information",title:"Practice Assessment Schedule Published",description:"A sample assessment calendar is available for course planning.",fullContent:"Dates shown in this prototype are illustrative and will be replaced with verified CMS-managed information.",publishedAt:"18 Sep 2026",important:true,relatedUrl:null},
 {id:7,type:"Announcements",title:"Customer Resource Library Updated",description:"Account resource and download areas now use centralized entitlement data.",fullContent:"Customers can review prototype digital access and eligible downloads from the shared account navigation.",publishedAt:"15 Sep 2026",important:false,relatedUrl:"/account/digital-resources"},
];

export const workshops=[
 {id:1,title:"Smart Grid Technologies Workshop",type:"Workshop",status:"Upcoming",discipline:"Power Systems Engineering",date:"18 Oct 2026",time:"10:00 AM – 1:00 PM",venue:"Online prototype event",speaker:"Dr. Kavita Menon",image:"/publications/power-energy-systems.webp",description:"Explore grid automation, renewable integration and protection concepts.",details:"A frontend prototype workshop covering smart-grid architecture, monitoring, protection and renewable-energy integration. Registration is not connected to a backend."},
 {id:2,title:"Structural Engineering Design Seminar",type:"Seminar",status:"Upcoming",discipline:"Civil Engineering",date:"25 Oct 2026",time:"11:00 AM – 12:30 PM",venue:"Online prototype event",speaker:"Dr. R. Banerjee",image:"/courses/civil-engineering.webp",description:"Review structural systems, design thinking and resilient infrastructure.",details:"A sample seminar outline with discussion of load paths, material behaviour and resilient structural design."},
 {id:3,title:"Thermodynamics Applications Session",type:"Technical Session",status:"Past",discipline:"Mechanical Engineering",date:"12 Sep 2026",time:"3:00 PM – 4:30 PM",venue:"Prototype learning studio",speaker:"Prof. Meera Iyer",image:"/courses/mechanical-engineering.webp",description:"Connect thermodynamic principles with turbines and energy systems.",details:"A completed prototype session demonstrating how event summaries and technical outcomes will appear."},
 {id:4,title:"AI for Engineers Technical Session",type:"Technical Session",status:"Upcoming",discipline:"Computer Science Engineering",date:"2 Nov 2026",time:"4:00 PM – 5:30 PM",venue:"Online prototype event",speaker:"Prof. Arjun Das",image:"/courses/computer-systems.webp",description:"Understand practical AI concepts and responsible engineering applications.",details:"A planned prototype session introducing data workflows, model evaluation and engineering use cases."},
 {id:5,title:"Mining Safety & Technology Workshop",type:"Workshop",status:"Past",discipline:"Mining Engineering",date:"28 Aug 2026",time:"10:30 AM – 1:30 PM",venue:"Prototype technical centre",speaker:"Er. Kunal Verma",image:"/publications/mining-engineering.webp",description:"Study operational safety, monitoring and modern mining equipment.",details:"A prototype workshop summary covering risk awareness, monitoring systems and equipment planning."},
 {id:6,title:"Embedded Systems Design Seminar",type:"Seminar",status:"Upcoming",discipline:"Electronics Engineering",date:"9 Nov 2026",time:"2:00 PM – 3:30 PM",venue:"Online prototype event",speaker:"Dr. Nisha Rao",image:"/publications/electronics-computer-systems.webp",description:"Explore embedded architectures, sensing and instrumentation.",details:"A frontend-only seminar listing prepared for future CMS and registration integration."},
];

const gallery=(id,category,image,caption,date,alt)=>({id,category,image,thumbnail:image,caption,date,alt});
export const galleryItems=[
 gallery(1,"Workshops","/courses/electrical-engineering.webp","Prototype smart-grid workshop session","18 Sep 2026","Electrical grid equipment representing a smart-grid workshop"),
 gallery(2,"Technical Sessions","/courses/mechanical-engineering.webp","Mechanical systems technical discussion","12 Sep 2026","Turbine machinery representing a mechanical technical session"),
 gallery(3,"Events","/courses/civil-engineering.webp","Civil infrastructure learning event","6 Sep 2026","Cable-stayed bridge representing a civil engineering event"),
 gallery(4,"Classes","/courses/computer-systems.webp","Computer systems concept class","2 Sep 2026","Computer processor representing a computer systems class"),
 gallery(5,"Faculty","/publications/power-energy-systems.webp","Faculty-led power systems explanation","28 Aug 2026","Wind turbines representing a faculty power systems session"),
 gallery(6,"Students","/books/engineering-mathematics.webp","Student mathematics revision activity","22 Aug 2026","Engineering mathematics book representing student study activity"),
 gallery(7,"Activities","/publications/mining-engineering.webp","Mining technology learning activity","17 Aug 2026","Mining vehicle representing a technical learning activity"),
 gallery(8,"Workshops","/publications/electronics-computer-systems.webp","Electronics design workshop","9 Aug 2026","Electronic circuit board representing an electronics workshop"),
];

export const videos=[
 {id:1,title:"Understanding Smart Power Grids",slug:"understanding-smart-power-grids",category:"Technical Explanations",discipline:"Power Systems Engineering",thumbnail:"/publications/power-energy-systems.webp",duration:"12:40",description:"A planned visual explanation of smart-grid components and communication.",videoProvider:null,videoId:null},
 {id:2,title:"Thermodynamics for Engineering Systems",slug:"thermodynamics-engineering-systems",category:"Lectures",discipline:"Mechanical Engineering",thumbnail:"/courses/mechanical-engineering.webp",duration:"18:15",description:"A prototype lecture outline connecting thermal principles with machinery.",videoProvider:null,videoId:null},
 {id:3,title:"Structural Load Paths Explained",slug:"structural-load-paths",category:"Tutorials",discipline:"Civil Engineering",thumbnail:"/courses/civil-engineering.webp",duration:"09:25",description:"A planned tutorial on how structural loads travel through a system.",videoProvider:null,videoId:null},
 {id:4,title:"Inside a Modern Processor",slug:"inside-modern-processor",category:"Demonstrations",discipline:"Computer Science Engineering",thumbnail:"/courses/computer-systems.webp",duration:"14:05",description:"A prototype demonstration introducing processor architecture.",videoProvider:null,videoId:null},
 {id:5,title:"Electrical Transmission Fundamentals",slug:"electrical-transmission-fundamentals",category:"Lectures",discipline:"Electrical Engineering",thumbnail:"/courses/electrical-engineering.webp",duration:"16:30",description:"A planned lecture covering transmission components and operating concepts.",videoProvider:null,videoId:null},
 {id:6,title:"Mining Equipment Safety Session",slug:"mining-equipment-safety",category:"Workshops",discipline:"Mining Engineering",thumbnail:"/publications/mining-engineering.webp",duration:"21:10",description:"A prototype workshop recording entry focused on equipment safety.",videoProvider:null,videoId:null},
];

export const supportCategories=[
 ["Account Help","Profile, sign-in and account preferences","/account"],
 ["Books & Orders","Purchases, order status and invoices","/account/orders"],
 ["Payments","Transactions, failures and refunds","/account/payments"],
 ["Shipping & Delivery","Dispatch, courier and tracking help","/account/orders/E4E-10001/track"],
 ["Digital Resources","Access and entitlement assistance","/account/digital-resources"],
 ["Courses","Course access and learning information","/courses"],
 ["Publications","Journal and publication access","/publications"],
 ["Technical Assistance","Website and download troubleshooting","#support-form"],
].map(([title,description,href],index)=>({id:index+1,title,description,href}));
