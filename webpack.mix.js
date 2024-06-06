const mix = require('laravel-mix');

mix.js('client/src/js/cookieconsent.js', 'client/dist/js/cookieconsent.js')
  .sass('client/src/styles/cookieconsent.scss', 'client/dist/styles/cookieconsent.css');
