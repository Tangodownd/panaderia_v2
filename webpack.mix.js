const mix = require("laravel-mix")
const path = require("path")

mix
  .js("resources/js/app.js", "public/js")
  .js("resources/js/client-app.js", "public/js")
  .js("resources/js/admin-app.js", "public/js")
  .vue()
  .postCss("resources/css/app.css", "public/css", [
    // Aquí puedes agregar plugins de PostCSS si los usas
  ])
  .webpackConfig({
    resolve: {
      alias: {
        "@": path.resolve("resources/js"),
      },
    },
  })

