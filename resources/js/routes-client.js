// resources/js/routes-client.js
import { createRouter, createWebHistory } from "vue-router"
import HomePage from "./components/client/HomePage.vue"
import ProductCatalog from "./components/client/ProductCatalog.vue"
import TerminosCondiciones from "./components/client/TerminosCondiciones.vue"
import PoliticaPrivacidad from "./components/client/PoliticaPrivacidad.vue"
import NotFound from "./components/client/NotFound.vue"
import CustomerLogin from "./components/client/CustomerLogin.vue"
import CustomerRegister from "./components/client/CustomerRegister.vue"
import MyOrders from "./components/client/MyOrders.vue"
import auth from "./services/auth"

const routes = [
  { path: "/", name: "customerLogin", component: CustomerLogin },
  { path: "/registro", name: "customerRegister", component: CustomerRegister },
  { path: "/home", name: "home", component: HomePage, meta: { requiresAuth: true } },
  
  { path: "/mis-compras", name: "myOrders", component: MyOrders },
  { path: "/category/:id", name: "category", component: ProductCatalog, props: true },
  { path: "/terminos-y-condiciones", name: "terminos", component: TerminosCondiciones },
  { path: "/politica-de-privacidad", name: "privacidad", component: PoliticaPrivacidad },
  { path: "/:pathMatch(.*)*", name: "notFound", component: NotFound },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) return { el: to.hash, behavior: "smooth" }
    return { top: 0 }
  },
})
router.beforeEach((to, from, next) => {
  const logged = auth.isAuthenticated()
  if (to.meta?.requiresAuth && !logged) return next({ name: "customerLogin" })
  if ((to.name === "customerLogin" || to.name === "customerRegister") && logged) {
    return next({ name: "home" })
  }
  next()
})

export default router
