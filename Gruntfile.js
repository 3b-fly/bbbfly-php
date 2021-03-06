module.exports = function(grunt) {
  var srcPath = 'src';
  var buildPath = 'build';

  var lib = {
    files: ['*.php','*/*.php'],
    license: 'LICENSE'
  };

  var adodb = {
    files: [
      'adodb/adodb_*/adodb.inc.php',
      'adodb/adodb_*/adodb-lib.inc.php',
      'adodb/adodb_*/adodb-php4.inc.php',
      'adodb/adodb_*/adodb-time.inc.php',
      'adodb/adodb_*/adodb-error.inc.php',
      'adodb/adodb_*/adodb-exceptions.inc.php',
      'adodb/adodb_*/drivers/adodb-*.inc.php',
      'adodb/adodb_*/adodb-iterator.inc.php'
    ],
    license: 'adodb/adodb_*/*LICENSE*'
  };

  var geoPHP = {
    files: [
      'geoPHP/geoPHP_*/geoPHP.php',
      'geoPHP/geoPHP_*/lib/*/**.php'
    ],
    license: 'geoPHP/geoPHP_*/*LICENSE*'
  };

  var packageJSON = grunt.file.readJSON('package.json');

  grunt.initConfig({
    pkg: packageJSON,
    clean: [buildPath],
    copy: {
      files: {
        files: [{
          cwd: srcPath,
          src: [].concat(lib.files,adodb.files,geoPHP.files),
          dest: buildPath,
          expand: true
        }]
      },
      licenses: {
        files: [
          {
            src: lib.license,
            dest: buildPath,
            expand: true
          },
          {
            cwd: srcPath,
            src: [].concat(adodb.license,geoPHP.license),
            dest: buildPath,
            expand: true
          }
        ]
      }
    },
    comments: {
      options: {
        keepSpecialComments: false
      },
      remove: {
        files: [{
          cwd: buildPath,
          src: lib.files,
          dest: buildPath,
          expand: true
        }]
      }
    },
    usebanner: {
      options: {
        banner: grunt.file.read('HEADER')
      },
      files: {
        cwd: buildPath,
        src: lib.files,
        dest: buildPath,
        expand: true
      }
    }
  });

  grunt.registerTask('default',[
    'clean','copy:files',
    'comments:remove',
    'usebanner',
    'copy:licenses'
  ]);

  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-stripcomments');
  grunt.loadNpmTasks('grunt-banner');
};