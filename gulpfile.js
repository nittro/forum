const gulp = require('gulp'),
    pump = require('pump'),
    filter = require('gulp-filter'),
    nittro = require('gulp-nittro'),
    uglify = require('gulp-uglify'),
    less = require('gulp-less'),
    postcss = require('gulp-postcss'),
    autoprefixer = require('autoprefixer'),
    cssnano = require('cssnano'),
    sourcemaps = require('gulp-sourcemaps'),
    concat = require('gulp-concat');


const publicBuilder = new nittro.Builder({
    vendor: {
        js: [
            'node_modules/jquery/dist/jquery.slim.min.js',
            'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
            'node_modules/keyboardevent-key-polyfill/index.js',
            'src/assets/js/init-key-polyfill.js',
            'node_modules/mdarea/mdarea.js',
            'src/assets/js/init-prism.js',
            'node_modules/prismjs/prism.js',
            'node_modules/prismjs/components/prism-markup-templating.min.js',
            'node_modules/prismjs/components/prism-php.min.js',
            'node_modules/prismjs/components/prism-latte.min.js',
            'node_modules/prismjs/components/prism-neon.min.js'
        ],
        css: [
            'node_modules/bootstrap/dist/css/bootstrap.min.css',
            'node_modules/prismjs/themes/prism.css'
        ]
    },
    base: {
        core: true,
        datetime: true,
        neon: true,
        di: true,
        ajax: true,
        forms: true,
        page: true,
        flashes: true,
        routing: false
    },
    extras: {
        checklist: false,
        dialogs: true,
        confirm: true,
        dropzone: true,
        paginator: true,
        keymap: true,
        storage: false
    },
    libraries: {
        js: [
            'src/assets/js/Forms/BootstrapErrorRenderer.js',
            'src/assets/js/ClassSwitcher.js',
            'src/assets/js/MentionSuggester.js',
            'src/assets/js/PaginatorHelper.js',
            'src/assets/js/autogrow.js',
            'src/PublicModule/assets/js/PageWidgets.js'
        ],
        css: [
            'src/assets/css/bootstrap-bridge.less',
            'src/assets/css/common.less',
            'src/PublicModule/assets/css/styles.less'
        ]
    },
    bootstrap: {
        params: {
            page: {
                scroll: {
                    target: 0,
                    scrollDown: true
                }
            }
        },
        services: {
            formErrorRenderer: 'App.Forms.BootstrapErrorRenderer()',
            classSwitcher: 'App.ClassSwitcher()!',
            pageWidgets: 'App.PageWidgets()!',
            paginatorHelper: 'App.PaginatorHelper()'
        }
    },
    stack: true
});




const adminBuilder = new nittro.Builder({
    vendor: {
        js: [
            'node_modules/jquery/dist/jquery.slim.min.js',
            'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js'
        ],
        css: [
            'node_modules/bootstrap/dist/css/bootstrap.min.css'
        ]
    },
    base: {
        core: true,
        datetime: true,
        neon: true,
        di: true,
        ajax: true,
        forms: false,
        page: true,
        flashes: true,
        routing: false
    },
    extras: {
        checklist: true,
        dialogs: true,
        confirm: true,
        dropzone: true,
        paginator: false,
        keymap: true,
        storage: false
    },
    libraries: {
        js: [
            'src/assets/js/Forms/BootstrapErrorRenderer.js',
            'src/assets/js/ClassSwitcher.js',
            'src/AdminModule/assets/js/scripts.js'
        ],
        css: [
            'src/assets/css/bootstrap-bridge.less',
            'src/assets/css/common.less',
            'src/AdminModule/assets/css/styles.less'
        ]
    },
    bootstrap: {
        services: {
            formErrorRenderer: 'App.Forms.BootstrapErrorRenderer()',
            classSwitcher: 'App.ClassSwitcher()!'
        }
    },
    stack: true
});


function exclude(pattern, ...queue) {
    let f = filter(file => !pattern.test(file.path), {restore: true});
    queue.unshift(f);
    queue.push(f.restore);
    return queue;
}

function createTaskQueue(outputFile, builder) {
    let type = /\.js$/.test(outputFile) ? 'js' : 'css',
        queue = [
            nittro(type, builder),
            sourcemaps.init({loadMaps: true})
        ];

    if (type === 'js') {
        queue.push(... exclude(/\.min\.[^.]+$/,
            uglify({compress: true, mangle: false})
        ));
    } else {
        queue.push(... exclude(/\.min\.[^.]+$/,
            ... exclude(/\.css$/, less()),
            postcss([ autoprefixer(), cssnano() ])
        ));
    }

    queue.push(
        concat(outputFile),
        sourcemaps.write('.', {mapFile: (path) => path.replace(/\.[^.]+(?=\.map$)/, '')}),
        gulp.dest('public/' + type)
    );

    return queue;
}


gulp.task('public:js', function (cb) {
    pump(createTaskQueue('public.min.js', publicBuilder), cb);
});


gulp.task('public:css', function (cb) {
    pump(createTaskQueue('public.min.css', publicBuilder), cb);
});


gulp.task('public:fonts', function () {
    return gulp.src([
        'src/PublicModule/assets/fonts/*'
    ]).pipe(gulp.dest('public/fonts'));
});


gulp.task('admin:js', function (cb) {
    pump(createTaskQueue('admin.min.js', adminBuilder), cb);
});


gulp.task('admin:css', function (cb) {
    pump(createTaskQueue('admin.min.css', adminBuilder), cb);
});


gulp.task('admin:fonts', function () {
    return gulp.src([
        'src/AdminModule/assets/fonts/*'
    ]).pipe(gulp.dest('public/fonts'));
});

gulp.task('watch:public:css', function () {
    return gulp.watch([
        'src/PublicModule/assets/css/**',
        'src/assets/css/**'
    ], gulp.parallel('public:css'));
});

gulp.task('watch:public:js', function () {
    return gulp.watch([
        'src/PublicModule/assets/js/**',
        'src/assets/js/**'
    ], gulp.parallel('public:js'));
});

gulp.task('watch:admin:css', function () {
    return gulp.watch([
        'src/AdminModule/assets/css/**',
        'src/assets/css/**'
    ], gulp.parallel('admin:css'));
});

gulp.task('watch:admin:js', function () {
    return gulp.watch([
        'src/AdminModule/assets/js/**',
        'src/assets/js/**'
    ], gulp.parallel('admin:js'));
});

gulp.task('public', gulp.parallel('public:js', 'public:css', 'public:fonts'));
gulp.task('admin', gulp.parallel('admin:js', 'admin:css', 'admin:fonts'));
gulp.task('css', gulp.parallel('public:css', 'admin:css'));
gulp.task('js', gulp.parallel('public:js', 'admin:js'));
gulp.task('watch:public', gulp.parallel('watch:public:css', 'watch:public:js'));
gulp.task('watch:admin', gulp.parallel('watch:admin:css', 'watch:admin:js'));
gulp.task('watch:css', gulp.parallel('watch:public:css', 'watch:admin:css'));
gulp.task('watch:js', gulp.parallel('watch:public:js', 'watch:admin:js'));
gulp.task('watch', gulp.parallel('watch:public', 'watch:admin'));
gulp.task('default', gulp.parallel('public', 'admin'));
