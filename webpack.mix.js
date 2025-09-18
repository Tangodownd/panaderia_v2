// webpack.mix.js
const mix = require('laravel-mix')
const path = require('path')
const webpack = require('webpack')

mix
  .js('resources/js/app.js', 'public/js')
  .js('resources/js/client-app.js', 'public/js')
  .js('resources/js/admin-app.js', 'public/js')
  .js('resources/js/legacy-ui.js', 'public/js') // si lo usas
  .vue()
  .postCss('resources/css/app.css', 'public/css', [])
  .webpackConfig({
    resolve: {
      alias: {
        '@': path.resolve('resources/js'),
        // Asegura que Webpack use la build esm-bundler de Vue
        'vue$': 'vue/dist/vue.esm-bundler.js',
      },
    },
    plugins: [
      new webpack.DefinePlugin({
        __VUE_OPTIONS_API__: JSON.stringify(true),   // o false si no usas Options API
        __VUE_PROD_DEVTOOLS__: JSON.stringify(false),
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false),
      }),
    ],
  })
