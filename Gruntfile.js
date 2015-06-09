module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    uglify: {
      batch: {
        src:  'js/batch.js',
        dest: 'js/batch.min.js'
      }
    },


    stylus: {
      compile: {

        files: {
          'css/batch.css':'css/batch.styl'
        }
      }
    },

    csso: {
      dist: {
        files: {
          'css/batch.css':'css/batch.css'
        }
      }
    }

  });



  // Load the plugin that provides the "uglify" task.
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-internal');
  grunt.loadNpmTasks('grunt-contrib-uglify');

  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-csso');
  grunt.loadNpmTasks('grunt-contrib-stylus');

  grunt.registerTask('js',['uglify']);
  grunt.registerTask('css',['stylus','csso']);

  // Default task(s).
  grunt.registerTask('default', ['css','js']);

};