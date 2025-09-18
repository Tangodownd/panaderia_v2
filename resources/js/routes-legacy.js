import { createRouter, createWebHistory } from "vue-router"

// Componentes de administración
import Login from "./components/admin/Login.vue"
import Dashboard from "./components/admin/Dashboard.vue"
import DashboardHome from "./components/admin/DashboardHome.vue"
import Mostrar from "./components/blog/Mostrar.vue"
import Crear from "./components/blog/Crear.vue"
import Editar from "./components/blog/Editar.vue"

// Importar correctamente los componentes de términos y condiciones
import TerminosCondiciones from "./components/client/TerminosCondiciones.vue"
import PoliticaPrivacidad from "./components/client/PoliticaPrivacidad.vue"

const routes = [
  // Rutas públicas para términos y condiciones
  {
    path: '/terminos-y-condiciones',
    name: 'TerminosCondiciones',
    component: TerminosCondiciones
  },
  {
    path: '/politica-de-privacidad',
    name: 'PoliticaPrivacidad',
    component: PoliticaPrivacidad
  },
  
  // Rutas de administración
  {
    path: "/admin/login",
    name: "adminLogin",
    component: Login,
  },
  {
    path: "/admin",
    redirect: "/admin/login", // Redirigir /admin a /admin/login si no está autenticado
  },
  {
    path: "/admin/dashboard",
    name: "adminDashboard",
    component: Dashboard,
    children: [
      {
        path: "",
        name: "adminHome",
        component: DashboardHome,
      },
      {
        path: "productos",
        name: "mostrarBlogs",
        component: Mostrar,
      },
      {
        path: "productos/crear",
        name: "crearBlog",
        component: Crear,
      },
      {
        path: "productos/editar/:id",
        name: "editarBlog",
        component: Editar,
        props: true,
      }
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else if (to.hash) {
      return {
        el: to.hash,
        behavior: "smooth",
      }
    } else {
      return { top: 0 }
    }
  },
})

// Inicializar autenticación
import auth from "./services/auth"
auth.initializeAuth()

// Middleware global para proteger rutas
router.beforeEach((to, from, next) => {
  // Si la ruta incluye /admin/dashboard y el usuario no está autenticado
  if (to.path.includes("/admin/dashboard") && !auth.isAuthenticated()) {
    next({ name: "adminLogin" })
  } else {
    next()
  }
})

export default router
export { routes }