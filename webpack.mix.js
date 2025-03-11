const mix = require('laravel-mix');
const path = require('path'); // Import the path module

mix.js('resources/js/app.js', 'public/js')
   .js('resources/js/client-app.js', 'public/js')
   .vue()
   .postCss('resources/css/app.css', 'public/css', [
       // Aquí puedes agregar plugins de PostCSS si los usas
   ])
   .webpackConfig({
       resolve: {
           alias: {
               // Esto puede ayudar con algunas importaciones
               '@': path.resolve('resources/js'),
           },
       },
   });