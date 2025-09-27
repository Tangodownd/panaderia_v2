// resources/js/routes-admin.js
import { createRouter, createWebHistory } from "vue-router"

// Admin components
import Login from "./components/admin/Login.vue"
import Dashboard from "./components/admin/Dashboard.vue"
import DashboardHome from "./components/admin/DashboardHome.vue"
import Mostrar from "./components/blog/Mostrar.vue"
import Crear from "./components/blog/Crear.vue"
import Editar from "./components/blog/Editar.vue"
import AdminUsers from "./components/admin/AdminUsers.vue"
import AdminCategories from "./components/admin/AdminCategories.vue"

// Servicio de auth centralizado (en lugar de leer localStorage aquí)
import auth from "./services/auth"

const routes = [
  { path: "/admin/login", name: "adminLogin", component: Login },
  { path: "/admin", redirect: "/admin/dashboard" },
  {
    path: "/admin/dashboard",
    component: Dashboard,
    children: [
      { path: "", name: "adminDashboard", component: DashboardHome },
      { path: "productos", name: "mostrarBlogs", component: Mostrar },
      { path: "productos/crear", name: "crearBlog", component: Crear },
      { path: "productos/editar/:id", name: "editarBlog", component: Editar, props: true },
      { path: "categorias", name: "adminCategories", component: AdminCategories },
      { path: "administradores", name: "adminUsers", component: AdminUsers },
    ],
  },
      {
      path: '/admin/orders',
      name: 'adminOrders',
      component: () => import('@/components/admin/AdminOrders.vue'),
      meta: { requiresAuth: true }
    },

]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Guard global: protege todo lo que sea /admin excepto el login
router.beforeEach((to, from, next) => {
  if (to.path.startsWith("/admin") && to.name !== "adminLogin") {
    if (!auth.isAuthenticated()) return next({ name: "adminLogin" })
  }
  return next()
})

export default router
