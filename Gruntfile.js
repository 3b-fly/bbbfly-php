module.exports = function(grunt) {
  var srcPath = 'src';
  var buildPath = 'build';

  var src = {
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

  var normalizeLinebreak = function(text){
    return text.replace(/( |\t)*(\r\n|\n\r|\r|\n)/g,'\n');
  };

  grunt.initConfig({
    pkg: packageJSON,
    clean: [buildPath],
    copy: {
      files: {
        options: {
          process: function(content){
            return normalizeLinebreak(content);
          }
        },
        files: [{
          cwd: srcPath,
          src: src.files,
          dest: buildPath,
          expand: true
        }]
      },
      license: {
        options: {
          process: function(content){
            return normalizeLinebreak(content);
          }
        },
        files: [{
          src: src.license,
          dest: buildPath,
          expand: true
        }]
      },
      libs_files: {
        files: [{
          cwd: srcPath,
          src: [].concat(adodb.files,geoPHP.files),
          dest: buildPath,
          expand: true
        }]
      },
      libs_license: {
        files: [{
          cwd: srcPath,
          src: [].concat(adodb.license,geoPHP.license),
          dest: buildPath,
          expand: true
        }]
      }
    },
    comments: {
      options: {
        keepSpecialComments: false
      },
      remove: {
        files: [{
          cwd: buildPath,
          src: src.files,
          dest: buildPath,
          expand: true
        }]
      }
    },
    usebanner: {
      options: {
        linebreak: false,
        process: function(){
          var banner = grunt.file.read('HEADER');
          banner = grunt.template.process(banner);
          return normalizeLinebreak(banner+'\n');
        }
      },
      files: {
        cwd: buildPath,
        src: src.files,
        dest: buildPath,
        expand: true
      }
    }
  });

  grunt.registerTask('build',[
    'clean',
    'copy:files','copy:libs_files',
    'comments:remove','usebanner',
    'copy:license','copy:libs_license'
  ]);

  grunt.registerTask('default','build');

  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-stripcomments');
  grunt.loadNpmTasks('grunt-banner');
};