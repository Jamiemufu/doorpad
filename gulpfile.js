/*
 * Gulp v4.0 
 * Dependencies
 */
const { src, dest, series, parallel, watch } = require('gulp');
const css_minify   = require('gulp-cssnano');
const js_minify    = require('gulp-uglify');
const less_compile = require('gulp-less');
const rename       = require('gulp-rename');

/*
 * Compile LESS
 */
function compile_less()
{

    return src('public_html/src/css/styles.less')
           .pipe(less_compile())
           .pipe(css_minify({discardComments: {removeAll: true}}))
           .pipe(rename({ extname: '.min.css' }))
           .pipe(dest('public_html/_public/css'));

}


/*
 * Minify JavaScript
 */
function minify_js()
{

    return src('public_html/src/js/scripts.js')
           .pipe(js_minify())
           .pipe(rename({ extname: '.min.js' }))
           .pipe(dest('public_html/_public/js'));
};


/*
 * Default task is to run a build
 */
exports.default = parallel(compile_less, minify_js);


/*
 * Watch for changes in source files
 */
watch('public_html/src/js/scripts.js', minify_js);

watch('public_html/src/css/**/*.less', compile_less);
