import { createRouter, createWebHistory } from "vue-router"
import HomePage from "./components/client/HomePage.vue"
import ProductCatalog from "./components/client/ProductCatalog.vue"

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

