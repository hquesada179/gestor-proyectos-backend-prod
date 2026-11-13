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
  Zap,
} from "lucide-react";

export type Locale = "es" | "en";
export type Theme = "light" | "dark";
export type Currency = "cop" | "usd";

/** Rutas relativas de la app Laravel en VPS (`routes/web.php` y `routes/auth.php`) para funcionar bajo cualquier dominio. */
export const LOGIN_URL = "/login";
export const SIGNUP_URL = "/register";
export const SUPPORT_EMAIL = "soporte@scrumter.io";
export const CONTACT_SALES_URL = `mailto:${SUPPORT_EMAIL}?subject=Plan%20Enterprise%20Scrumter`;

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
  | "users"
  | "zap";

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
  zap: Zap,
};

export const content = {
  es: {
    nav: {
      features: "Funcionalidades",
      how: "Cómo funciona",
      pricing: "Precios",
      resources: "Preguntas",
      signIn: "Iniciar sesión",
      cta: "Crear cuenta gratis",
      language: "Idioma",
      theme: "Tema",
      openMenu: "Abrir menú",
      closeMenu: "Cerrar menú",
      toTop: "Volver arriba",
      skipToContent: "Saltar al contenido",
      homeAria: "Scrumter — Inicio",
      mainNav: "Navegación principal",
    },
    hero: {
      badgeTag: "NUEVO",
      badge: "IA para Scrum · OpenAI + Ollama",
      title: "Gestiona proyectos Scrum más rápido con IA",
      accent: "de insumo a sprint sin perder control",
      lead:
        "Scrumter convierte ideas, documentos y solicitudes en backlog priorizado, historias INVEST, tareas y sprints listos para ejecutar.",
      primary: "Crear cuenta gratis",
      secondary: "Cómo funciona",
      checks: ["Sin tarjeta requerida", "Créditos IA incluidos", "Pago local con Wompi"],
      metrics: [
        { to: 2, decimals: 0, prefix: "", suffix: "", display: "2", label: "Motores IA (OpenAI + Ollama)" },
        { to: 4, decimals: 0, prefix: "", suffix: "", display: "4", label: "Pasos de insumo a sprint" },
        { to: 50, decimals: 0, prefix: "", suffix: "+", display: "50+", label: "Créditos IA gratis al mes" },
      ],
    },
    trust: {
      eyebrow: "TECNOLOGÍA Y CAPACIDADES VERIFICADAS",
      logos: ["OpenAI", "Ollama", "Wompi Colombia", "PSE · Nequi", "Android + Web", "Brevo"],
      badges: ["OpenAI + Ollama", "Pagos Wompi COP y USD", "Web + App Android"],
    },
    how: {
      eyebrow: "CÓMO FUNCIONA",
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
          text: "Scrumter propone requerimientos, historias, criterios de aceptación y story points para revisar antes de aplicar.",
        },
        {
          icon: "kanban" as IconName,
          title: "Planifica sprints",
          text: "Ordena prioridades, ajusta capacidad y visualiza el avance en tablero Kanban y calendario.",
        },
        {
          icon: "users" as IconName,
          title: "Entrega en equipo",
          text: "Coordina roles, chat, exportación de tareas y registro de actividad para mantener trazabilidad.",
        },
      ],
    },
    features: {
      eyebrow: "FUNCIONALIDADES",
      title: "Todo lo que necesitas para",
      accent: "entregar mejor",
      subtitle: "Backlog, sprints, equipo, IA y pagos en una experiencia clara y enfocada.",
      learnMore: "Saber más",
      learnMorePrefix: "Saber más sobre",
      aiPromptSample: "> genera 5 historias INVEST del insumo #12",
      wompiMethods: ["COP / USD", "PSE", "Tarjeta", "Nequi", "Bancolombia"],
      items: [
        {
          icon: "layers" as IconName,
          title: "Insumos a backlog",
          text: "Clasifica entradas y genera requerimientos funcionales y no funcionales listos para priorizar.",
        },
        {
          icon: "bot" as IconName,
          title: "Historias con IA",
          text: "Historias con formato claro, criterios de aceptación y estimación inicial lista para ajustar.",
        },
        {
          icon: "activity" as IconName,
          title: "Sprints medibles",
          text: "Tablero Scrum, calendario, estados configurables y seguimiento de tareas por ciclo de trabajo.",
        },
        {
          icon: "message" as IconName,
          title: "Colaboración trazable",
          text: "Chat grupal y privado, invitaciones y registro de actividad para no perder contexto entre cambios.",
        },
        {
          icon: "shield" as IconName,
          title: "Roles y acceso",
          text: "Permisos por rol, acceso por proyecto autorizado y control de sesiones web y móvil.",
        },
        {
          icon: "credit" as IconName,
          title: "Pagos Wompi",
          text: "Planes en COP y USD con una ruta simple para PSE, tarjeta, Nequi y suscripción mensual.",
        },
      ],
    },
    scale: {
      eyebrow: "ESCALA SIN FRICCIÓN",
      title: "Crece de un proyecto piloto a una operación completa",
      text:
        "Empieza gratis, suma usuarios cuando el equipo lo necesite y mantén una vista clara de créditos, proyectos y entregas.",
      checks: ["De 1 a 15+ usuarios", "Permisos por rol", "Chat y calendario integrados", "Exportación de tareas"],
      cta: "Crear cuenta gratis",
      metrics: [
        { icon: "users" as IconName, to: 15, decimals: 0, prefix: "", suffix: "+", display: "15+", label: "Usuarios por equipo", tone: "brand" as const },
        { icon: "zap" as IconName, to: 5000, decimals: 0, prefix: "", suffix: "", display: "5.000", label: "Créditos IA / mes (Empresa)", tone: "amber" as const },
        { icon: "gauge" as IconName, to: 100, decimals: 0, prefix: "", suffix: "", display: "100", label: "Proyectos (Plan Empresa)", tone: "emerald" as const },
        { icon: "shield" as IconName, to: 100, decimals: 0, prefix: "", suffix: "%", display: "100%", label: "Confirmación previa en IA", tone: "slate" as const },
      ],
    },
    testimonials: {
      eyebrow: "PARA CADA ROL",
      title: "Equipos ágiles con",
      accent: "roles bien definidos",
      subtitle: "Cada perfil encuentra herramientas concretas para reducir ruido en su entrega diaria.",
      items: [
        {
          quote:
            "Centraliza insumos del negocio, genera borradores de requerimientos e historias INVEST con IA y ajusta prioridades antes de confirmar al backlog.",
          name: "Product Owner",
          role: "Insumos y backlog priorizado",
          initials: "PO",
        },
        {
          quote:
            "Organiza sprints con capacidad clara, visualiza el avance en el Scrum Board y mantén fechas clave y entregas visibles en el calendario del equipo.",
          name: "Scrum Master",
          role: "Sprints, Kanban y calendario",
          initials: "SM",
        },
        {
          quote:
            "Coordina tareas por estado, conversa en tiempo real por chat grupal o privado y revisa la trazabilidad de actividad en cada proyecto.",
          name: "Tech Lead",
          role: "Ejecución, chat y trazabilidad",
          initials: "TL",
        },
      ],
    },
    pricing: {
      eyebrow: "PLANES",
      title: "Precios simples y",
      accent: "transparentes",
      subtitle: "Empieza gratis y escala cuando necesites más IA, usuarios o proyectos.",
      currencyLabel: "Moneda",
      monthly: "/mes",
      toggleCop: "COP - Wompi",
      toggleUsd: "USD",
      popular: "Más popular",
      wompi: "Pagos seguros con Wompi Colombia · PSE · Tarjeta · Nequi · Bancolombia",
      plans: [
        {
          name: "Gratis",
          price: { cop: "$0", usd: "$0" },
          isCustom: false,
          description: "Para explorar la gestión básica.",
          features: ["50 créditos IA / mes", "1 usuario", "2 proyectos", "Soporte comunitario"],
          cta: "Crear cuenta gratis",
          highlighted: false,
        },
        {
          name: "Personal",
          price: { cop: "$39.900", usd: "$9.99" },
          isCustom: false,
          description: "Optimiza tu flujo individual.",
          features: ["300 créditos IA / mes", "1 usuario", "Hasta 10 proyectos", "Historial IA completo"],
          cta: "Elegir Personal",
          highlighted: false,
        },
        {
          name: "Equipo",
          price: { cop: "$119.900", usd: "$29.99" },
          isCustom: false,
          description: "Colaboración avanzada.",
          features: ["1.500 créditos IA / mes", "Hasta 5 usuarios", "Hasta 30 proyectos", "Chat y roles"],
          cta: "Elegir Equipo",
          highlighted: true,
        },
        {
          name: "Empresa",
          price: { cop: "$319.900", usd: "$79.99" },
          isCustom: false,
          description: "Potencia total para organizaciones.",
          features: ["5.000 créditos IA / mes", "Hasta 15 usuarios", "Hasta 100 proyectos", "Reportes y auditoría"],
          cta: "Elegir Empresa",
          highlighted: false,
        },
        {
          name: "Enterprise",
          price: { cop: "A medida", usd: "A medida" },
          isCustom: true,
          description: "Soluciones para operaciones grandes.",
          features: ["Créditos ilimitados", "Usuarios ilimitados", "Proyectos ilimitados", "Gestor dedicado"],
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
          question: "¿Cómo funcionan los créditos IA?",
          answer:
            "Cada plan incluye créditos mensuales y límites diarios de uso. Solo se descuentan tras una respuesta exitosa de la IA y puedes consultar tu saldo en todo momento.",
        },
        {
          question: "¿Puedo cambiar de plan luego?",
          answer: "Sí. Puedes pasar de Gratis a Personal, Equipo o Empresa cuando necesites más usuarios, proyectos o créditos de IA.",
        },
        {
          question: "¿Cómo protege Scrumter el acceso a mis proyectos?",
          answer:
            "Cada proyecto restringe el acceso a su propietario y miembros activos con permisos por rol. Las contraseñas se almacenan cifradas con hashing seguro y la app móvil autentica vía tokens Sanctum.",
        },
        {
          question: "¿Tienen plan Enterprise?",
          answer:
            "Sí. Enterprise está pensado para organizaciones que requieren capacidad a medida en créditos, usuarios y proyectos, coordinado directamente por correo con nuestro equipo.",
        },
        {
          question: "¿Puedo exportar tareas de mis proyectos?",
          answer: "Sí. Puedes exportar las tareas de cada proyecto directamente desde la plataforma para compartir avances o respaldar tu información.",
        },
      ],
    },
    cta: {
      title: "¿Listo para convertir ideas en sprints?",
      subtitle: "Crea tu cuenta gratuita y empieza a estructurar tu backlog con ayuda de la IA.",
      primary: "Crear cuenta gratis",
      secondary: "Ver planes",
      note: "Gratis para empezar · Sin tarjeta · Escala cuando lo necesites",
    },
    mockup: {
      ariaLabel: "Vista previa del panel de Scrumter con proyectos activos, módulos y sprint en curso",
      sidebarSections: [
        { label: null, items: ["Dashboard", "Proyectos", "Mis Tareas"] },
        { label: "Módulos", items: ["Requerimientos", "Scrum Board", "Sprints", "Tablero Tareas", "Insumos", "Calendario"] },
        { label: "Inteligencia", items: ["Asistente IA", "Planes"] },
      ],
      newProject: "Nuevo proyecto",
      profile: "Perfil",
      signOut: "Cerrar sesión",
      dashboard: "Dashboard",
      welcome: "Panel activo",
      welcomeText: "Revisa tus sprints en curso o genera un backlog con IA.",
      createWithAI: "Crear con IA",
      statsValues: ["4", "12", "2"],
      statsLabels: ["Mis Proyectos", "Tareas Pendientes", "Sprints Activos"],
      modulesLabels: ["Sprints", "Tablero Tareas", "Requerimientos", "Insumos"],
      recentTitle: "Sprint 04 · Portal Clientes — 8 de 12 tareas completadas",
      recentCta: "Abrir Scrum Board",
    },
    footer: {
      description: "La plataforma inteligente para gestionar Scrum con IA, del insumo al sprint.",
      made: "Hecho para equipos ágiles",
      rights: "2026 Scrumter. Todos los derechos reservados.",
      columns: [
        {
          title: "Navegación",
          links: [
            { label: "Funcionalidades", href: "#features" },
            { label: "Cómo funciona", href: "#how" },
            { label: "Precios", href: "#pricing" },
            { label: "Preguntas frecuentes", href: "#faq" },
          ],
        },
        {
          title: "Plataforma y soporte",
          links: [
            { label: "Crear cuenta gratis", href: SIGNUP_URL },
            { label: "Iniciar sesión", href: LOGIN_URL },
            { label: SUPPORT_EMAIL, href: `mailto:${SUPPORT_EMAIL}` },
          ],
        },
      ],
    },
  },
  en: {
    nav: {
      features: "Features",
      how: "How it works",
      pricing: "Pricing",
      resources: "FAQ",
      signIn: "Sign in",
      cta: "Create free account",
      language: "Language",
      theme: "Theme",
      openMenu: "Open menu",
      closeMenu: "Close menu",
      toTop: "Back to top",
      skipToContent: "Skip to content",
      homeAria: "Scrumter — Home",
      mainNav: "Main navigation",
    },
    hero: {
      badgeTag: "NEW",
      badge: "AI for Scrum · OpenAI + Ollama",
      title: "Manage Scrum projects faster with AI",
      accent: "from input to sprint with control",
      lead:
        "Scrumter turns ideas, documents and requests into prioritized backlog, INVEST stories, tasks and sprints ready to execute.",
      primary: "Create free account",
      secondary: "How it works",
      checks: ["No credit card required", "AI credits included", "Local payments with Wompi"],
      metrics: [
        { to: 2, decimals: 0, prefix: "", suffix: "", display: "2", label: "AI engines (OpenAI + Ollama)" },
        { to: 4, decimals: 0, prefix: "", suffix: "", display: "4", label: "Steps from input to sprint" },
        { to: 50, decimals: 0, prefix: "", suffix: "+", display: "50+", label: "Free AI credits per month" },
      ],
    },
    trust: {
      eyebrow: "VERIFIED STACK & INTEGRATIONS",
      logos: ["OpenAI", "Ollama", "Wompi Colombia", "PSE · Nequi", "Android + Web", "Brevo"],
      badges: ["OpenAI + Ollama", "Wompi COP & USD payments", "Web + Android App"],
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
          text: "Scrumter proposes requirements, stories, acceptance criteria and story points to review before applying.",
        },
        {
          icon: "kanban" as IconName,
          title: "Plan sprints",
          text: "Sort priorities, tune capacity and track progress across Kanban board and calendar.",
        },
        {
          icon: "users" as IconName,
          title: "Deliver as a team",
          text: "Coordinate roles, chat, task exports and activity logs to preserve full traceability.",
        },
      ],
    },
    features: {
      eyebrow: "FEATURES",
      title: "Everything you need to",
      accent: "deliver better",
      subtitle: "Backlog, sprints, team, AI and payments in one focused experience.",
      learnMore: "Learn more",
      learnMorePrefix: "Learn more about",
      aiPromptSample: "> generate 5 INVEST stories from input #12",
      wompiMethods: ["COP / USD", "PSE", "Card", "Nequi", "Bancolombia"],
      items: [
        {
          icon: "layers" as IconName,
          title: "Inputs to backlog",
          text: "Classify entries and generate functional and non-functional requirements ready to prioritize.",
        },
        {
          icon: "bot" as IconName,
          title: "AI stories",
          text: "Clear user stories, acceptance criteria and initial estimation ready for your review.",
        },
        {
          icon: "activity" as IconName,
          title: "Measurable sprints",
          text: "Scrum board, calendar, configurable task statuses and progress tracking per sprint cycle.",
        },
        {
          icon: "message" as IconName,
          title: "Traceable collaboration",
          text: "Group and private chat, invitations and activity log so context survives changes.",
        },
        {
          icon: "shield" as IconName,
          title: "Roles and access",
          text: "Role-based permissions, authorized project scoping and web + mobile session security.",
        },
        {
          icon: "credit" as IconName,
          title: "Wompi payments",
          text: "COP and USD plans with a simple path for PSE, card, Nequi and monthly subscriptions.",
        },
      ],
    },
    scale: {
      eyebrow: "SCALE SMOOTHLY",
      title: "Grow from a pilot project to a complete operation",
      text:
        "Start free, add users when the team needs it and keep a clear view of credits, projects and delivery.",
      checks: ["From 1 to 15+ users", "Role-based permissions", "Built-in chat & calendar", "Task exports"],
      cta: "Create free account",
      metrics: [
        { icon: "users" as IconName, to: 15, decimals: 0, prefix: "", suffix: "+", display: "15+", label: "Users per team", tone: "brand" as const },
        { icon: "zap" as IconName, to: 5000, decimals: 0, prefix: "", suffix: "", display: "5,000", label: "AI credits / mo (Business)", tone: "amber" as const },
        { icon: "gauge" as IconName, to: 100, decimals: 0, prefix: "", suffix: "", display: "100", label: "Projects (Business plan)", tone: "emerald" as const },
        { icon: "shield" as IconName, to: 100, decimals: 0, prefix: "", suffix: "%", display: "100%", label: "Pre-apply AI confirmation", tone: "slate" as const },
      ],
    },
    testimonials: {
      eyebrow: "FOR EVERY ROLE",
      title: "Agile teams with",
      accent: "clearer workflows",
      subtitle: "Each team role gets dedicated tools inside Scrumter to cut delivery noise.",
      items: [
        {
          quote:
            "Centralize business inputs, draft requirements and INVEST stories with AI, and refine priorities before confirming changes to the backlog.",
          name: "Product Owner",
          role: "Inputs & prioritized backlog",
          initials: "PO",
        },
        {
          quote:
            "Organize sprints with clear capacity, monitor daily work on the Scrum Board and keep milestones visible on the team calendar.",
          name: "Scrum Master",
          role: "Sprints, Kanban & calendar",
          initials: "SM",
        },
        {
          quote:
            "Coordinate tasks by status, message teammates in group or private chat and review project activity logs at any point.",
          name: "Tech Lead",
          role: "Execution, chat & traceability",
          initials: "TL",
        },
      ],
    },
    pricing: {
      eyebrow: "PLANS",
      title: "Simple, transparent",
      accent: "pricing",
      subtitle: "Start free and scale when you need more AI, users or projects.",
      currencyLabel: "Currency",
      monthly: "/mo",
      toggleCop: "COP - Wompi",
      toggleUsd: "USD",
      popular: "Most popular",
      wompi: "Secure payments with Wompi Colombia · PSE · Card · Nequi · Bancolombia",
      plans: [
        {
          name: "Free",
          price: { cop: "$0", usd: "$0" },
          isCustom: false,
          description: "For exploring basic management.",
          features: ["50 AI credits / mo", "1 user", "2 projects", "Community support"],
          cta: "Create free account",
          highlighted: false,
        },
        {
          name: "Personal",
          price: { cop: "$39,900", usd: "$9.99" },
          isCustom: false,
          description: "Optimize your individual flow.",
          features: ["300 AI credits / mo", "1 user", "Up to 10 projects", "Full AI history"],
          cta: "Choose Personal",
          highlighted: false,
        },
        {
          name: "Team",
          price: { cop: "$119,900", usd: "$29.99" },
          isCustom: false,
          description: "Advanced collaboration.",
          features: ["1,500 AI credits / mo", "Up to 5 users", "Up to 30 projects", "Chat and roles"],
          cta: "Choose Team",
          highlighted: true,
        },
        {
          name: "Business",
          price: { cop: "$319,900", usd: "$79.99" },
          isCustom: false,
          description: "Full power for organizations.",
          features: ["5,000 AI credits / mo", "Up to 15 users", "Up to 100 projects", "Reports and audit"],
          cta: "Choose Business",
          highlighted: false,
        },
        {
          name: "Enterprise",
          price: { cop: "Custom", usd: "Custom" },
          isCustom: true,
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
            "Each plan includes monthly credits and daily rate limits. Credits are only charged after a successful AI response and you can check your balance anytime.",
        },
        {
          question: "Can I change plans later?",
          answer: "Yes. Move from Free to Personal, Team or Business whenever you need more users, projects or AI credits.",
        },
        {
          question: "How does Scrumter protect project access?",
          answer:
            "Every project restricts access to its owner and active members according to role permissions. Passwords use secure hashing and the mobile API authenticates via Sanctum tokens.",
        },
        {
          question: "Do you offer Enterprise?",
          answer:
            "Yes. Enterprise is designed for organizations requiring custom credit, user and project limits, coordinated directly with our team via email.",
        },
        {
          question: "Can I export project tasks?",
          answer: "Yes. You can export project tasks directly from the platform to share progress or back up your records.",
        },
      ],
    },
    cta: {
      title: "Ready to turn ideas into sprints?",
      subtitle: "Create your free account and start structuring your backlog with AI.",
      primary: "Create free account",
      secondary: "View plans",
      note: "Free to start · No card required · Scale when you need",
    },
    mockup: {
      ariaLabel: "Scrumter workspace preview showing active projects, modules and ongoing sprint",
      sidebarSections: [
        { label: null, items: ["Dashboard", "Projects", "My Tasks"] },
        { label: "Modules", items: ["Requirements", "Scrum Board", "Sprints", "Task Board", "Inputs", "Calendar"] },
        { label: "Intelligence", items: ["AI Assistant", "Plans"] },
      ],
      newProject: "New project",
      profile: "Profile",
      signOut: "Sign out",
      dashboard: "Dashboard",
      welcome: "Active workspace",
      welcomeText: "Review active sprints or generate a backlog with AI.",
      createWithAI: "Create with AI",
      statsValues: ["4", "12", "2"],
      statsLabels: ["My Projects", "Pending Tasks", "Active Sprints"],
      modulesLabels: ["Sprints", "Task Board", "Requirements", "Inputs"],
      recentTitle: "Sprint 04 · Client Portal — 8 of 12 tasks completed",
      recentCta: "Open Scrum Board",
    },
    footer: {
      description: "The intelligent platform to manage Scrum with AI, from input to sprint.",
      made: "Built for agile teams",
      rights: "2026 Scrumter. All rights reserved.",
      columns: [
        {
          title: "Navigation",
          links: [
            { label: "Features", href: "#features" },
            { label: "How it works", href: "#how" },
            { label: "Pricing", href: "#pricing" },
            { label: "FAQ", href: "#faq" },
          ],
        },
        {
          title: "Platform & support",
          links: [
            { label: "Create free account", href: SIGNUP_URL },
            { label: "Sign in", href: LOGIN_URL },
            { label: SUPPORT_EMAIL, href: `mailto:${SUPPORT_EMAIL}` },
          ],
        },
      ],
    },
  },
} as const;
