var gulp = require('gulp'),
	sass = require('gulp-sass'),
	livereload = require('gulp-livereload'),
	streamqueue = require('streamqueue'),
	autoprefixer = require('gulp-autoprefixer'),
	sourcemaps = require('gulp-sourcemaps'),
	uglify = require('gulp-uglify'),
	jshint = require('gulp-jshint'),
	header = require('gulp-header'),
	concat = require('gulp-concat'),
	rename = require('gulp-rename'),
	cssnano = require('gulp-cssnano'),
	merge = require('merge-stream'),
	clone = require('gulp-clone'),
	filter = require('gulp-filter');
	package = require('./package.json');


var banner = [
	'/*!\n' +
	' * <%= package.name %>\n' +
	' * <%= package.title %>\n' +
	' * <%= package.url %>\n' +
	' * @author <%= package.author %>\n' +
	' * @version <%= package.version %>\n' +
	' * Copyright ' + new Date().getFullYear() + '. <%= package.license %> licensed.\n' +
	' */',
	'\n'
].join('');

/**
 * Copy assets to dedicated directories inside nws_municipal_status
 */
gulp.task('install-assets', function () {
	// Glyphicons
	var fonts_fontawesome = gulp.src(
		'node_modules/@neos21/bootstrap3-glyphicons/assets/fonts/*',
		{base: 'node_modules/@neos21/bootstrap3-glyphicons/assets/fonts'})
		.pipe(gulp.dest('Resources/Public/Fonts/vendor/bootstrap3-glyphicons'));

});
/**
* @deprecated Use install-assets
*/
gulp.task('bowercopy', ['install-assets']);

gulp.task('css', function () {
	var source = gulp.src('Resources/Private/Source/Sass/locallaw.scss')
		.pipe(sourcemaps.init())
		.pipe(
			sass({
				includePaths: ['node_modules'],
				outputStyle: 'expanded',
				precision: 8,
				indentType: 'tab',
				indentWidth: 1
			}).on('error', sass.logError)
		);
	var pipe1 = source.pipe(clone())
		.pipe(sourcemaps.write(''))
		.pipe(gulp.dest('Resources/Public/Stylesheets'))

	var pipe2 = source.pipe(clone())
		.pipe(cssnano())
		.pipe(rename({suffix: '.min'}))
		.pipe(header(banner, {package: package}))
		.pipe(gulp.dest('Resources/Public/Stylesheets'))
		// make sure livereload will only reload the css and not the entire page when passing .map files in
		.pipe(filter('**/*.css'))
		.pipe(livereload());

	return merge(pipe1, pipe2);
});

gulp.task('js', function () {
	streamqueue({objectMode: true},
		gulp.src([
			// force jquery on top
			'Resources/Private/Source/Javascript/jquery-*.js',
			'Resources/Private/Source/Javascript/*.js',
			// scripts.js is handled separately
			'!Resources/Private/Source/Javascript/scripts.js'
		]),
		gulp.src('Resources/Private/Source/Javascript/scripts.js')
			.pipe(jshint('.jshintrc'))
			.pipe(jshint.reporter('default'))
			.pipe(header(banner, {package: package}))
	)
		.pipe(uglify({preserveComments: 'license'}))
		.pipe(concat('locallaw.js'))
		.pipe(rename({suffix: '.min'}))
		.pipe(gulp.dest('Resources/Public/Javascripts'))
		.pipe(livereload());
});

gulp.task('default', ['js','css'], function () {
	livereload.listen();
	gulp.watch("Resources/Private/Source/Javascript/*.js", ['js']);
	gulp.watch("Resources/Private/Source/Sass/*/*/*.scss", ['css']);
	gulp.watch([
		'typo3conf/ext/nws_municipal_statutes/Resources/Public/Javascripts/**/*',
		'typo3conf/ext/nws_municipal_statutes/Resources/Public/Stylesheets/**/*'
	], function (event) {
		livereload.changed(event.path);
	});
});

gulp.task('build:dev', ['js','css']);
