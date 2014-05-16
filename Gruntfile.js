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
 *  $ grunt build-js        Concat and minify all js-files in js/arlima/dev/ into arlima.js
 *  $ grunt create-release  Copies current source code into the release directory
 *
 * @requirements
 *  - nodejs and npm
 *  - mval has to be installed globally (npm install -g mval)
 *  - grunt has to be installed globally  (npm install -g grunt-cli)
 *  - msgfmt and phpunit.phar has to be installed and added to your $PATH
 *
 * @todo
 *  - Look into using https://npmjs.org/package/node-gettext instead of msgfmt
 *  - Make it optional to run unit tests when building release
 *  - Make the translation task more dynamic, no hardcoded en -> sv
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
        handleProcessError = function(grunt, stderr, error, stdout) {
            var errorMess = error || stderr;
            if( errorMess ) {
                grunt.log.write(errorMess).error(stdout);
                grunt.fail.warn(errorMess, 3);
                return true;
            } else {
                return false;
            }
        },
        config = JSON.parse(readFile('./package.json')).gruntConfig;

        config.releaseVersion = false;


    /* * * * * * * * Config * * * * * * * * */

    grunt.initConfig({
        config : config,
        concat: {
            options: {
                stripBanners: true,
                banner: '/*! Arlima v<%= config.releaseVersion %> */\n'
            },
            dist: {
                files: config.filesToConcat
            }
        },
        uglify: {
            options: {
                banner: '/*! Arlima v<%= config.releaseVersion %> */\n'
            },
            build :  {
                files : config.uglifyjs
            }
        },
        less: {
            development: {
                options: {
                    compress: true,
                    yuicompress: true,
                    optimization: 2
                },
                files: config.lessFiles
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-less');


    /* * * * * * * * Tasks * * * * * * * * */


    /*
     * Get current version
     */
    grunt.registerTask('current-version', 'Get current version', function() {
        config.releaseVersion = getCurrentVersion();
        grunt.log.writeln('Current version: ' + getCurrentVersion());
    });

    /*
     * Change to new version or the next version number in all files
     * containing the version definition
     */
    grunt.registerTask('change-version', 'Bump up the version number, or change version name by adding --new-version=3.1.0', function() {
        var currentVersion = getCurrentVersion(),
            newVersion = grunt.option('new-version');

        if( !newVersion ) {
            var versionParts = currentVersion.split('.');
            var newSubVersion = parseInt(versionParts.splice(versionParts.length-1, 1)[0]) + 1;
            newVersion = versionParts.join('.') + '.' + newSubVersion.toString();
        }

        config.releaseVersion = newVersion;

        grunt.log.writeln('* Moving from version '+currentVersion+' to '+newVersion);

        replaceInFile(config.mainScript, 'Version: '+currentVersion, 'Version: '+newVersion);
        replaceInFile('readme.txt', 'Stable tag: '+currentVersion, 'Stable tag: '+newVersion);
        replaceInFile('constants.php', "'ARLIMA_FILE_VERSION', '"+currentVersion, "'ARLIMA_FILE_VERSION', '"+newVersion);
    });

    /*
     * Build javascript
     */
    grunt.registerTask('build-js', ['current-version', 'concat', 'uglify', 'change-version']);

    /*
     * Run PHP-unit
     */
    grunt.registerTask('phpunit', 'Run phpUnit tests', function() {

        var finishedTests = 0,
            done = this.async();

        config.phpunit.every(function(file) {
            if( file.indexOf('#') !== 0 ) {
                exec('phpunit  --no-globals-backup '+file, function (error, stdout, stderr) {
                    if( handleProcessError(grunt, stderr, error, stdout) ) {
                        done();
                    } else {

                        if( stdout.indexOf('<span style="') > -1 ) {
                            // h4ck.. in case of a php-error the output will not be sent
                            // to stderr...
                            handleProcessError(grunt, stdout);
                            done();
                        } else {
                            grunt.log.writeln('* Successfully ran php-unit file '+file);
                        }
                    }

                    finishedTests++;
                    if( finishedTests == config.phpunit.length ) {
                        done();
                    }
                });
            }
            return true;
        });
    });

    /*
     * Localization
     */
    grunt.registerTask('localization', 'Translate .pot-files', function() {
        var done = this.async();
        exec('msgfmt -o lang/arlima-sv_SE.mo lang/arlima.pot', function (error, stdout, stderr) {
            if( !handleProcessError(grunt, stderr, error, stdout) ) {

                grunt.log.writeln(stdout);
                grunt.log.writeln('* Pot-files translated');
            }
            done();
        });
    });

    /*
     * Validate the readme file
     */
    grunt.registerTask('validate', 'Validate readme.txt', function() {
        var done = this.async();
        exec('mval ./readme.txt', function(error, stdout, stderr) {
            if( !handleProcessError(grunt, stderr, error, stdout) ) {
                grunt.log.writeln('* readme.txt valid');
            }
            done();
        });
    });

    /*
     * Create release directory with copy of source code
     */
    grunt.registerTask('create-release', 'Copy source code to release directory', function() {
        if( !config.releaseVersion ) {
            config.releaseVersion = getCurrentVersion();
        }

        var buildDir = 'release/v-'+config.releaseVersion;

        // Create release directory
        try {
            var distStats = fs.statSync('release');
            if( !distStats.isDirectory() ) {
                fs.mkdirSync('release');
            }
        } catch(err) {
            fs.mkdirSync('release');
        }

        config.excludeFromRelease.push('release');

        // Copy files to build dir
        wrench.copyDirSyncRecursive(__dirname, buildDir, {
            forceDelete: true,
            excludeHiddenUnix: true,
            preserveFiles: false,
            exclude: function( file ) {
                return config.excludeFromRelease.indexOf(file) > -1;
            }
        });
    });

    /*
     * Default task - creates a new release version
     */
    var defaultTasks = [
        'phpunit',
        'validate',
        'change-version',
        'localization',
        'less',
        'concat',
        'uglify'
    ];
    grunt.registerTask('default', defaultTasks);
};
