import { Bridge, CellTower, Cpu, Gear, Hammer, Lightning, Monitor, Sigma } from "@phosphor-icons/react";

export const disciplines = [
  { slug:"electrical-engineering", name:"Electrical Engineering", icon:Lightning, description:"Circuits, machines, power conversion and electrical systems that support modern life." },
  { slug:"mechanical-engineering", name:"Mechanical Engineering", icon:Gear, description:"Machines, thermal systems, manufacturing, design and industrial innovation." },
  { slug:"civil-engineering", name:"Civil Engineering", icon:Bridge, description:"Structures, infrastructure, transportation and resilient built environments." },
  { slug:"mining-engineering", name:"Mining Engineering", icon:Hammer, description:"Safe mineral extraction, mine planning, equipment and sustainable operations." },
  { slug:"electronics-engineering", name:"Electronics Engineering", icon:Cpu, description:"Electronic circuits, embedded systems, instrumentation and communication technologies." },
  { slug:"computer-science-engineering", name:"Computer Science Engineering", icon:Monitor, description:"Software, computing systems, algorithms, networks and intelligent applications." },
  { slug:"power-systems-engineering", name:"Power Systems Engineering", icon:CellTower, description:"Generation, transmission, distribution, smart grids and energy economics." },
  { slug:"engineering-mathematics", name:"Engineering Mathematics", icon:Sigma, description:"Mathematical methods, modelling and analysis for engineering problem-solving." },
  { slug:"metallurgy-engineering", name:"Metallurgy Engineering", icon:Gear, description:"Metals, materials processing, alloys and industrial applications." },
];

export const resources = ["Lecture Notes","Formula Sheets","Solved Question Papers","Technical Diagrams"];
export const books = [
  { title:"Electrical Power Systems", image:"/books/electrical-power-systems.webp" },
  { title:"Engineering Mathematics", image:"/books/engineering-mathematics.webp" },
  { title:"Mechanical Engineering", image:"/books/mechanical-engineering.webp" },
];

