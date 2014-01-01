/**
 * Grunt file for the WordPress plugin Arlima
 *
 * @usage
 *
 *  $ grunt                 Creates a new release version, the version number will automatically
 *                          be increased (add --new-verions="3.0.dev" to define your own version)
 *  $ grunt localization    Translates pot files
 *  $ grunt phpunit         Runs php-unit tests
 *  $ grunt validate        Validates the readme file
 *
 * @requirements
 *  - nodejs and npm
 *  - mval has to be installed globally (npm install -g mval)
 *  - grunt has to be installed globally  (npm install -g grunt-cli)
 *  - msgfmt and phpunit.phar has to be installed and added to your $PATH
 *
 */
module.exports = function(grunt) {

    var fs = require('fs'),
        sys = require('sys'),
        wrench = require('./node_modules/wrench'),
        exec = require('child_process').exec,

        readFile = function(file) {
            return fs.readFileSync(file, 'utf-8');
        },
        replaceInFile = function(path, from, to) {
            fs.writeFileSync(path, readFile(path).replace(from, to));
        },
        getCurrentVersion = function() {
            var versionParts = readFile(config.mainScript).split('Version: ')[1].split('\n')[0].trim().split('.');
            return versionParts.join('.');
        },
        handleProcessError = function(grunt, stderr, error ) {
            var errorMess = error || stderr;
            if( errorMess ) {
                grunt.log.write(errorMess).error(stdout);
                grunt.fail.warn(errorMess, 3);
                return true;
            } else {
                return false;
            }
        },
        config = JSON.parse(readFile('./package.json')).gruntConfig
        config.distVersion = false;



    /* * * * * * * * Config * * * * * * * * */

    var filesToMinify = {};
    config.minify.every(function(file) {
        var filePath = 'dist/release-<%= config.distVersion %>/' + file;
        filesToMinify[filePath] = filePath;
        return true;
    });

    grunt.initConfig({
        config : config,
        uglify: {
            options: {
                banner: '/*! Arlima v<%= config.distVersion %> */\n'
            },
            build :  {
                files : filesToMinify
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');



    /* * * * * * * * Tasks * * * * * * * * */


    /*
     * Get current version
     */
    grunt.registerTask('current-version', 'Get current version', function() {
        console.log(grunt);
        grunt.log.writeln('Current version: ' + getCurrentVersion());
    });

    /*
     * Change to new version or the next version number in all files
     * containing the version definition
     */
    grunt.registerTask('change-version', 'Move up the version number', function() {
        var currentVersion = getCurrentVersion(),
            newVersion = grunt.option('new-version');

        if( !newVersion ) {
            var versionParts = currentVersion.split('.');
            var newSubVersion = parseInt(versionParts.splice(versionParts.length-1, 1)[0]) + 1;
            newVersion = versionParts.join('.') + '.' + newSubVersion.toString();
            config.distVersion = newVersion;
        }

        grunt.log.writeln('* Moving from version '+currentVersion+' to '+newVersion);

        replaceInFile(config.mainScript, 'Version: '+currentVersion, 'Version: '+newVersion);
        replaceInFile('readme.txt', 'Stable tag: '+currentVersion, 'Stable tag: '+newVersion);
        replaceInFile('constants.php', "'ARLIMA_FILE_VERSION', '"+currentVersion, "'ARLIMA_FILE_VERSION', '"+newVersion);
    });

    /*
     * Run PHP-unit
     */
    grunt.registerTask('phpunit', 'Run phpUnit tests', function() {

        var finishedTests = 0,
            done = this.async();

        config.phpunit.every(function(file) {
            exec('phpunit.phar  --no-globals-backup '+file, function (error, stdout, stderr) {
                if( handleProcessError(grunt, stderr, error) ) {
                    done();
                } else {
                    grunt.log.writeln('* Successfully ran php-unit file '+file);
                }

                finishedTests++;
                if( finishedTests == config.phpunit.length ) {
                    done();
                }
            });
            return true;
        });
    });

    /*
     * Localization
     */
    grunt.registerTask('localization', function() {
        var done = this.async();
        exec('msgfmt -o lang/arlima-sv_SE.mo lang/arlima.pot', function (error, stdout, stderr) {
            if( !handleProcessError(grunt, stderr, error) ) {
                grunt.log.writeln('* Pot-files translated');
            }
            done();
        });
    });

    /*
     * Validate the readme file
     */
    grunt.registerTask('validate', function() {
        var done = this.async();
        exec('mval ./readme.txt', function(error, stdout, stderr) {
            if( !handleProcessError(grunt, stderr, error) ) {
                grunt.log.writeln('* readme.txt valid');
            }
            done();
        });
    });

    /*
     * Create release directory with copy of source code
     */
    grunt.registerTask('create-dist', function() {
        if( !config.distVersion ) {
            config.distVersion = getCurrentVersion();
        }

        var buildDir = 'dist/release-'+config.distVersion;

        // Create dist directory
        try {
            var distStats = fs.statSync('dist');
            if( !distStats.isDirectory() ) {
                fs.mkdirSync('dist');
            }
        } catch(err) {
            fs.mkdirSync('dist');
        }

        // Copy files to build dir
        wrench.copyDirSyncRecursive('../arlima/', buildDir, {
            forceDelete: true,
            excludeHiddenUnix: true,
            preserveFiles: false,
            exclude: function( file ) {
                return config.excludeFromDist.indexOf(file) > -1;
            }
        });

        grunt.log.writeln('* Release directory created '+buildDir);

    });


    /*
     * Default task - creates a new release version
     */
    var defaultTasks = [
        'phpunit',
        'validate',
        'change-version',
        'localization',
        'create-dist',
        'uglify'
    ];
    grunt.registerTask('default', defaultTasks);
};
