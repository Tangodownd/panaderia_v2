import { createRouter, createWebHistory } from "vue-router"

// Componentes de administración
import Login from "./components/admin/Login.vue"
import Dashboard from "./components/admin/Dashboard.vue"
import DashboardHome from "./components/admin/DashboardHome.vue"
import Mostrar from "./components/blog/Mostrar.vue"
import Crear from "./components/blog/Crear.vue"
import Editar from "./components/blog/Editar.vue"
import AdminUsers from "./components/admin/AdminUsers.vue"
import AdminCategories from "./components/admin/AdminCategories.vue"

const routes = [
  {
    path: "/admin/login",
    name: "adminLogin",
    component: Login,
  },
  {
    path: "/admin",
    redirect: "/admin/dashboard",
  },
  {
    path: "/admin/dashboard",
    component: Dashboard,
    children: [
      {
        path: "",
        name: "adminDashboard",
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
      },
      {
        path: "categorias",
        name: "adminCategories",
        component: AdminCategories,
      },
      {
        path: "administradores",
        name: "adminUsers",
        component: AdminUsers,
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Middleware global para proteger rutas
router.beforeEach((to, from, next) => {
  // Si la ruta incluye /admin/dashboard y el usuario no está autenticado
  if (to.path.includes("/admin/dashboard")) {
    const token = localStorage.getItem("auth_token")
    if (!token) {
      next({ name: "adminLogin" })
      return
    }
  }
  next()
})

export default router

