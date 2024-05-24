var gulp            = require('gulp');
// var concat          = require('gulp-concat');
var sass            = require('gulp-sass')(require('sass'));
var sourcemaps      = require('gulp-sourcemaps');
var prefixer        = require('gulp-autoprefixer');

gulp.task('sass', function () {
  'use strict';
  if(process.argv[3] === '--' + 'dev' || process.argv[2] === 'watch')
  {
    return gulp
      .src('./scss/*.scss')
      .pipe(sourcemaps.init())
      .pipe(sass({
        includePaths: ['node_modules']
      }))
      .pipe(prefixer({
        cascade: false
      }))
      .pipe(sourcemaps.write())
      // .pipe(concat('style.css'))
      .pipe(gulp.dest('./css'));
  }
  else {
    return gulp
      .src('./css/src/**/*')
      .pipe(sass({
        includePaths: ['node_modules']
      }))
      .pipe(prefixer({
        cascade: false
      }))
      // .pipe(concat('style.css'))
      .pipe(gulp.dest('./css'));
  }
});

gulp.task('default', function(done) {
  gulp.series('sass');
  done();
});
