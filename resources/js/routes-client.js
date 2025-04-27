import { createRouter, createWebHistory } from "vue-router"
import HomePage from "./components/client/HomePage.vue"
import ProductCatalog from "./components/client/ProductCatalog.vue"
import TerminosCondiciones from "./components/client/TerminosCondiciones.vue"
import PoliticaPrivacidad from "./components/client/PoliticaPrivacidad.vue"
import NotFound from "./components/client/NotFound.vue"

const routes = [
  {
    path: "/",
    name: "home",
    component: HomePage,
  },
  {
    path: "/category/:id",
    name: "category",
    component: ProductCatalog,
    props: true,
  },
  {
    path: "/terminos-y-condiciones",
    name: "terminos",
    component: TerminosCondiciones,
  },
  {
    path: "/politica-de-privacidad",
    name: "privacidad",
    component: PoliticaPrivacidad,
  },
  {
    path: "/:pathMatch(.*)*",
    name: "notFound",
    component: NotFound,
  }
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

export default router