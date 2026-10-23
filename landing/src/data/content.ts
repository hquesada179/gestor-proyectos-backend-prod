import type { LucideIcon } from "lucide-react";
import {
  Activity,
  Bot,
  CalendarDays,
  CheckCircle2,
  ClipboardList,
  CreditCard,
  FileInput,
  Gauge,
  KanbanSquare,
  Layers3,
  LockKeyhole,
  MessageSquare,
  Rocket,
  ShieldCheck,
  Sparkles,
  UsersRound,
} from "lucide-react";

export type Locale = "es" | "en";
export type Theme = "light" | "dark";
export type Currency = "cop" | "usd";

/** URL de la app SaaS — destino de todos los CTAs de conversión */
export const SIGNUP_URL = "https://scrumter.io/";

export type IconName =
  | "activity"
  | "bot"
  | "calendar"
  | "check"
  | "clipboard"
  | "credit"
  | "file"
  | "gauge"
  | "kanban"
  | "layers"
  | "lock"
  | "message"
  | "rocket"
  | "shield"
  | "sparkles"
  | "users";

export const iconMap: Record<IconName, LucideIcon> = {
  activity: Activity,
  bot: Bot,
  calendar: CalendarDays,
  check: CheckCircle2,
  clipboard: ClipboardList,
  credit: CreditCard,
  file: FileInput,
  gauge: Gauge,
  kanban: KanbanSquare,
  layers: Layers3,
  lock: LockKeyhole,
  message: MessageSquare,
  rocket: Rocket,
  shield: ShieldCheck,
  sparkles: Sparkles,
  users: UsersRound,
};

export const content = {
  es: {
    nav: {
      features: "Funcionalidades",
      how: "Como funciona",
      pricing: "Precios",
      resources: "Recursos",
      signIn: "Iniciar sesion",
      cta: "Crear cuenta gratis",
      language: "Idioma",
      theme: "Tema",
      openMenu: "Abrir menu",
      closeMenu: "Cerrar menu",
      toTop: "Volver arriba",
    },
    hero: {
      badge: "IA para Scrum - OpenAI + Ollama",
      title: "Gestiona proyectos Scrum más rápido con IA",
      accent: "de insumo a sprint sin perder control",
      lead:
        "Scrumter convierte ideas, documentos y solicitudes en backlog priorizado, historias INVEST, tareas y sprints listos para ejecutar.",
      primary: "Crear cuenta gratis",
      secondary: "Ver flujo demo",
      checks: ["Sin tarjeta requerida", "Créditos IA incluidos", "Pago local con Wompi"],
      metrics: [
        { value: "2.500+", label: "Proyectos gestionados" },
        { value: "4.8/5", label: "Satisfacción promedio" },
        { value: "99.9%", label: "Uptime garantizado" },
      ],
    },
    trust: {
      eyebrow: "CON LA CONFIANZA DE EQUIPOS AGILES",
      logos: ["Lumina", "Northwind", "Brightpath", "Aster Labs", "Nexa"],
      badges: ["4.8/5 en G2","99.9% uptime"],
    },
    how: {
      eyebrow: "COMO FUNCIONA",
      title: "De idea a sprint en",
      accent: "4 pasos",
      subtitle: "Un flujo liviano para Product Owners, Scrum Masters y Dev Leads.",
      steps: [
        {
          icon: "file" as IconName,
          title: "Registra insumos",
          text: "Captura objetivos, casos de uso, restricciones y solicitudes del negocio en un solo lugar.",
        },
        {
          icon: "sparkles" as IconName,
          title: "Genera con IA",
          text: "Scrumter propone requerimientos, historias, criterios de aceptacion y story points.",
        },
        {
          icon: "kanban" as IconName,
          title: "Planifica sprints",
          text: "Ordena prioridades, ajusta capacidad y visualiza el avance con tablero y burndown.",
        },
        {
          icon: "users" as IconName,
          title: "Entrega en equipo",
          text: "Coordina roles, comentarios, cambios y auditoria para mantener trazabilidad.",
        },
      ],
    },
    features: {
      eyebrow: "FUNCIONALIDADES",
      title: "Todo lo que necesitas para",
      accent: "entregar mejor",
      subtitle: "Backlog, sprints, equipo, IA y pagos en una experiencia clara y enfocada.",
      items: [
        {
          icon: "layers" as IconName,
          title: "Insumos a backlog",
          text: "Clasifica entradas y genera requerimientos funcionales y no funcionales listos para priorizar.",
        },
        {
          icon: "bot" as IconName,
          title: "Historias con IA",
          text: "Historias con formato claro, criterios de aceptacion y estimacion inicial automatizada.",
        },
        {
          icon: "activity" as IconName,
          title: "Sprints medibles",
          text: "Capacidad, progreso, burndown y estados configurables para cada ciclo de trabajo.",
        },
        {
          icon: "message" as IconName,
          title: "Colaboracion trazable",
          text: "Chat, menciones, decisiones y auditoria para no perder contexto entre cambios.",
        },
        {
          icon: "shield" as IconName,
          title: "Roles y seguridad",
          text: "Permisos por rol, invitaciones y controles pensados para equipos en crecimiento.",
        },
        {
          icon: "credit" as IconName,
          title: "Pagos Wompi",
          text: "Planes en COP y USD con una ruta simple para PSE, tarjeta y suscripcion mensual.",
        },
      ],
    },
    scale: {
      eyebrow: "ESCALA SIN FRICCION",
      title: "Crece de un proyecto piloto a una operacion completa",
      text:
        "Empieza gratis, suma usuarios cuando el equipo lo necesite y mantiene una vista clara de creditos, proyectos y entregas.",
      checks: ["De 1 a 15+ usuarios", "Seguridad enterprise", "Soporte 24/7", "Exportacion de artefactos"],
      cta: "Crear cuenta gratis",
    },
    testimonials: {
      eyebrow: "TESTIMONIOS",
      title: "Equipos agiles que",
      accent: "ya trabajan mas claro",
      subtitle: "Historias cortas de equipos que redujeron ruido y aceleraron discovery.",
      items: [
        {
          quote:
            "Scrumter reemplazo cuatro herramientas. Generamos backlog con IA en minutos y ahora tenemos una unica fuente de verdad.",
          name: "Sofia Martinez",
          role: "Head of Product, Lumina",
          initials: "SM",
        },
        {
          quote:
            "El burndown y las metricas nos dieron conversaciones mas objetivas. Recortamos el tiempo de entrega sin aumentar carga.",
          name: "David Weber",
          role: "COO, Northwind Labs",
          initials: "DW",
        },
        {
          quote:
            "El onboarding tomo minutos. Las automatizaciones nos ahorran horas cada sprint y mantienen al equipo alineado.",
          name: "Amara Okafor",
          role: "Founder, Brightpath",
          initials: "AO",
        },
      ],
    },
    pricing: {
      eyebrow: "PLANES",
      title: "Precios simples y",
      accent: "transparentes",
      subtitle: "Empieza gratis y escala cuando necesites mas IA, usuarios o proyectos.",
      monthly: "/mes",
      toggleCop: "COP - Wompi",
      toggleUsd: "USD",
      popular: "Mas popular",
      wompi: "Pagos seguros con Wompi Colombia - PSE - Tarjeta - Nequi - Bancolombia",
      plans: [
        {
          name: "Gratis",
          price: { cop: "$0", usd: "$0" },
          description: "Para explorar la gestion basica.",
          features: ["50 creditos IA / mes", "1 usuario", "2 proyectos", "Soporte comunitario"],
          cta: "Crear cuenta gratis",
          highlighted: false,
        },
        {
          name: "Personal",
          price: { cop: "$39.900", usd: "$9.99" },
          description: "Optimiza tu flujo individual.",
          features: ["300 creditos IA / mes", "1 usuario", "Hasta 10 proyectos", "Historial IA completo"],
          cta: "Elegir Personal",
          highlighted: false,
        },
        {
          name: "Equipo",
          price: { cop: "$119.900", usd: "$29.99" },
          description: "Colaboracion avanzada.",
          features: ["1.500 creditos IA / mes", "Hasta 5 usuarios", "Hasta 30 proyectos", "Chat y roles"],
          cta: "Elegir Equipo",
          highlighted: true,
        },
        {
          name: "Empresa",
          price: { cop: "$319.900", usd: "$79.99" },
          description: "Potencia total para organizaciones.",
          features: ["5.000 creditos IA / mes", "Hasta 15 usuarios", "Hasta 100 proyectos", "Reportes y auditoria"],
          cta: "Elegir Empresa",
          highlighted: false,
        },
        {
          name: "Enterprise",
          price: { cop: "A medida", usd: "Custom" },
          description: "Soluciones para operaciones grandes.",
          features: ["Creditos ilimitados", "Usuarios ilimitados", "Proyectos ilimitados", "Gestor dedicado"],
          cta: "Contactar ventas",
          highlighted: false,
        },
      ],
    },
    faq: {
      eyebrow: "PREGUNTAS FRECUENTES",
      title: "Dudas claras,",
      accent: "respuestas directas",
      subtitle: "Lo esencial antes de empezar con Scrumter.",
      items: [
        {
          question: "Como funcionan los creditos IA?",
          answer:
            "Cada plan incluye creditos mensuales y limites de uso. Puedes ver el consumo en tiempo real y escalar cuando el equipo lo necesite.",
        },
        {
          question: "Puedo cambiar de plan luego?",
          answer: "Si. Puedes pasar de Gratis a Personal, Equipo o Empresa cuando necesites mas usuarios o capacidad.",
        },
        {
          question: "Mis datos estan seguros?",
          answer:
            "La landing comunica una arquitectura pensada para cifrado, permisos por rol, auditoria y buenas practicas de operacion.",
        },
        {
          question: "Tienen plan Enterprise?",
          answer:
            "Si. Enterprise esta pensado para creditos, usuarios y proyectos ilimitados, con acompanamiento dedicado.",
        },
        {
          question: "Puedo exportar mis artefactos?",
          answer: "Si. La propuesta incluye exportar backlog, historias y sprints para que el equipo conserve su informacion.",
        },
      ],
    },
    cta: {
      title: "Listo para convertir ideas en sprints?",
      subtitle: "Deja tu correo y prepara el camino para probar Scrumter con tu equipo.",
      placeholder: "Ingresa tu correo de trabajo",
      button: "Crear cuenta gratis",
      note: "Gratis para empezar - Sin tarjeta - Cancela cuando quieras",
      error: "Ingresa un email valido.",
      success: "Revisa tu bandeja para activar tu cuenta.",
    },
    mockup: {
      ariaLabel: "Vista previa de Scrumter — dashboard con modulos Sprints, Tablero Tareas, Requerimientos e Insumos",
      sidebarSections: [
        { label: null, items: ["Dashboard", "Proyectos", "Mis Tareas"] },
        { label: "Modulos", items: ["Requerimientos", "Scrum Board", "Sprints", "Tablero Tareas", "Insumos", "Calendario"] },
        { label: "Inteligencia", items: ["Asistente IA", "Planes"] },
      ],
      newProject: "Nuevo proyecto",
      profile: "Perfil",
      signOut: "Cerrar sesion",
      dashboard: "Dashboard",
      welcome: "Bienvenido",
      welcomeText: "Crea tu primer proyecto y empieza a planificar con IA.",
      createWithAI: "Crear con IA",
      statsLabels: ["Mis Proyectos", "Tareas Pendientes", "Sprints Activos"],
      modulesLabels: ["Sprints", "Tablero Tareas", "Requerimientos", "Insumos"],
      emptyTitle: "Aun no tienes proyectos.",
      emptyCta: "Crear el primero",
    },
    footer: {
      description: "La plataforma inteligente para gestionar Scrum con IA, del insumo al sprint.",
      made: "Hecho para equipos agiles",
      rights: "2026 Scrumter. Todos los derechos reservados.",
      columns: [
        { title: "Producto", links: ["Funcionalidades", "Precios", "Como funciona", "Novedades"] },
        { title: "Compania", links: ["Nosotros", "Carreras", "Blog", "Prensa"] },
        { title: "Recursos", links: ["Documentacion", "Centro de ayuda", "API", "Estado"] },
        { title: "Legal", links: ["Privacidad", "Terminos", "Seguridad", "Cookies"] },
      ],
    },
  },
  en: {
    nav: {
      features: "Features",
      how: "How it works",
      pricing: "Pricing",
      resources: "Resources",
      signIn: "Sign in",
      cta: "Create free account",
      language: "Language",
      theme: "Theme",
      openMenu: "Open menu",
      closeMenu: "Close menu",
      toTop: "Back to top",
    },
    hero: {
      badge: "AI for Scrum - OpenAI + Ollama",
      title: "Manage Scrum projects faster with AI",
      accent: "from input to sprint with control",
      lead:
        "Scrumter turns ideas, documents and requests into prioritized backlog, INVEST stories, tasks and sprints ready to execute.",
      primary: "Create free account",
      secondary: "Watch demo flow",
      checks: ["No credit card required", "AI credits included", "Local payments with Wompi"],
      metrics: [
        { value: "2,500+", label: "Projects managed" },
        { value: "4.8/5", label: "Average rating" },
        { value: "99.9%", label: "Guaranteed uptime" },
      ],
    },
    trust: {
      eyebrow: "TRUSTED BY AGILE TEAMS",
      logos: ["Lumina", "Northwind", "Brightpath", "Aster Labs", "Nexa"],
      badges: ["4.8/5 on G2", "2,500+ teams", "99.9% uptime"],
    },
    how: {
      eyebrow: "HOW IT WORKS",
      title: "From idea to sprint in",
      accent: "4 steps",
      subtitle: "A lightweight flow for Product Owners, Scrum Masters and Dev Leads.",
      steps: [
        {
          icon: "file" as IconName,
          title: "Log inputs",
          text: "Capture goals, use cases, constraints and business requests in one place.",
        },
        {
          icon: "sparkles" as IconName,
          title: "Generate with AI",
          text: "Scrumter proposes requirements, stories, acceptance criteria and initial story points.",
        },
        {
          icon: "kanban" as IconName,
          title: "Plan sprints",
          text: "Sort priorities, tune capacity and visualize progress with board and burndown.",
        },
        {
          icon: "users" as IconName,
          title: "Deliver as a team",
          text: "Coordinate roles, comments, changes and audit history to keep traceability.",
        },
      ],
    },
    features: {
      eyebrow: "FEATURES",
      title: "Everything you need to",
      accent: "deliver better",
      subtitle: "Backlog, sprints, team, AI and payments in one focused experience.",
      items: [
        {
          icon: "layers" as IconName,
          title: "Inputs to backlog",
          text: "Classify entries and generate functional and non-functional requirements ready to prioritize.",
        },
        {
          icon: "bot" as IconName,
          title: "AI stories",
          text: "Clear user stories, acceptance criteria and automated first-pass estimation.",
        },
        {
          icon: "activity" as IconName,
          title: "Measurable sprints",
          text: "Capacity, progress, burndown and configurable states for every work cycle.",
        },
        {
          icon: "message" as IconName,
          title: "Traceable collaboration",
          text: "Chat, mentions, decisions and audit history so context survives changes.",
        },
        {
          icon: "shield" as IconName,
          title: "Roles and security",
          text: "Role-based permissions, invitations and controls for growing teams.",
        },
        {
          icon: "credit" as IconName,
          title: "Wompi payments",
          text: "COP and USD plans with a simple path for PSE, card and monthly subscription.",
        },
      ],
    },
    scale: {
      eyebrow: "SCALE SMOOTHLY",
      title: "Grow from a pilot project to a complete operation",
      text:
        "Start free, add users when the team needs it and keep a clear view of credits, projects and delivery.",
      checks: ["From 1 to 15+ users", "Enterprise-grade security", "24/7 support", "Artifact exports"],
      cta: "Create free account",
    },
    testimonials: {
      eyebrow: "TESTIMONIALS",
      title: "Agile teams that",
      accent: "already work clearer",
      subtitle: "Short stories from teams reducing noise and accelerating discovery.",
      items: [
        {
          quote:
            "Scrumter replaced four tools. We generate backlog with AI in minutes and now have a single source of truth.",
          name: "Sofia Martinez",
          role: "Head of Product, Lumina",
          initials: "SM",
        },
        {
          quote:
            "Burndown and metrics gave us more objective conversations. We reduced delivery time without adding load.",
          name: "David Weber",
          role: "COO, Northwind Labs",
          initials: "DW",
        },
        {
          quote:
            "Onboarding took minutes. Automations save hours each sprint and keep the team aligned.",
          name: "Amara Okafor",
          role: "Founder, Brightpath",
          initials: "AO",
        },
      ],
    },
    pricing: {
      eyebrow: "PLANS",
      title: "Simple, transparent",
      accent: "pricing",
      subtitle: "Start free and scale when you need more AI, users or projects.",
      monthly: "/mo",
      toggleCop: "COP - Wompi",
      toggleUsd: "USD",
      popular: "Most popular",
      wompi: "Secure payments with Wompi Colombia - PSE - Card - Nequi - Bancolombia",
      plans: [
        {
          name: "Free",
          price: { cop: "$0", usd: "$0" },
          description: "For exploring basic management.",
          features: ["50 AI credits / mo", "1 user", "2 projects", "Community support"],
          cta: "Create free account",
          highlighted: false,
        },
        {
          name: "Personal",
          price: { cop: "$39,900", usd: "$9.99" },
          description: "Optimize your individual flow.",
          features: ["300 AI credits / mo", "1 user", "Up to 10 projects", "Full AI history"],
          cta: "Choose Personal",
          highlighted: false,
        },
        {
          name: "Team",
          price: { cop: "$119,900", usd: "$29.99" },
          description: "Advanced collaboration.",
          features: ["1,500 AI credits / mo", "Up to 5 users", "Up to 30 projects", "Chat and roles"],
          cta: "Choose Team",
          highlighted: true,
        },
        {
          name: "Business",
          price: { cop: "$319,900", usd: "$79.99" },
          description: "Full power for organizations.",
          features: ["5,000 AI credits / mo", "Up to 15 users", "Up to 100 projects", "Reports and audit"],
          cta: "Choose Business",
          highlighted: false,
        },
        {
          name: "Enterprise",
          price: { cop: "A medida", usd: "Custom" },
          description: "Solutions for larger operations.",
          features: ["Unlimited credits", "Unlimited users", "Unlimited projects", "Dedicated manager"],
          cta: "Contact sales",
          highlighted: false,
        },
      ],
    },
    faq: {
      eyebrow: "FAQ",
      title: "Clear questions,",
      accent: "direct answers",
      subtitle: "The essentials before getting started with Scrumter.",
      items: [
        {
          question: "How do AI credits work?",
          answer:
            "Each plan includes monthly credits and usage limits. You can track consumption in real time and scale when the team needs it.",
        },
        {
          question: "Can I change plans later?",
          answer: "Yes. Move from Free to Personal, Team or Business whenever you need more users or capacity.",
        },
        {
          question: "Is my data secure?",
          answer:
            "The landing communicates an architecture designed for encryption, role permissions, audit history and operating best practices.",
        },
        {
          question: "Do you offer Enterprise?",
          answer:
            "Yes. Enterprise is intended for unlimited credits, users and projects, with dedicated support.",
        },
        {
          question: "Can I export my artifacts?",
          answer: "Yes. The proposal includes exporting backlog, stories and sprints so your team keeps its information.",
        },
      ],
    },
    cta: {
      title: "Ready to turn ideas into sprints?",
      subtitle: "Leave your email and prepare the path to try Scrumter with your team.",
      placeholder: "Enter your work email",
      button: "Create free account",
      note: "Free to start - No card - Cancel anytime",
      error: "Please enter a valid email address.",
      success: "Check your inbox to activate your account.",
    },
    mockup: {
      ariaLabel: "Scrumter preview — dashboard with Sprints, Task Board, Requirements and Inputs modules",
      sidebarSections: [
        { label: null, items: ["Dashboard", "Projects", "My Tasks"] },
        { label: "Modules", items: ["Requirements", "Scrum Board", "Sprints", "Task Board", "Inputs", "Calendar"] },
        { label: "Intelligence", items: ["AI Assistant", "Plans"] },
      ],
      newProject: "New project",
      profile: "Profile",
      signOut: "Sign out",
      dashboard: "Dashboard",
      welcome: "Welcome",
      welcomeText: "Create your first project and start planning with AI.",
      createWithAI: "Create with AI",
      statsLabels: ["My Projects", "Pending Tasks", "Active Sprints"],
      modulesLabels: ["Sprints", "Task Board", "Requirements", "Inputs"],
      emptyTitle: "You have no projects yet.",
      emptyCta: "Create your first",
    },
    footer: {
      description: "The intelligent platform to manage Scrum with AI, from input to sprint.",
      made: "Made for agile teams",
      rights: "2026 Scrumter. All rights reserved.",
      columns: [
        { title: "Product", links: ["Features", "Pricing", "How it works", "Changelog"] },
        { title: "Company", links: ["About", "Careers", "Blog", "Press"] },
        { title: "Resources", links: ["Documentation", "Help Center", "API", "Status"] },
        { title: "Legal", links: ["Privacy", "Terms", "Security", "Cookies"] },
      ],
    },
  },
} as const;
