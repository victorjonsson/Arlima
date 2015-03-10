/**
 * Grunt file for the WordPress plugin Arlima
 *
 * @usage
 *
 *  $ grunt                 Creates a new release version, the version number will automatically
 *                          be increased (add --new-verions="3.0.dev" to define your own version)
 *  $ grunt change-version  Bump up version number, also supports --new-version
 *  $ grunt localization    Translates pot files
 *  $ grunt phpunit         Runs php-unit tests
 *  $ grunt validate-readme Validates the readme file
 *  $ grunt build-js        Concatenate and minify all js-files in js/arlima/dev/ into arlima.js
 *  $ grunt create-release  Copies current source code into the release directory
 *
 * @requirements
 *  - nodejs and npm
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
        mval = require('./node_modules/mval'),
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
            newSubVersion = newSubVersion < 10 && newSubVersion > 0 ? '0'+newSubVersion : newSubVersion.toString();
            newVersion = versionParts.join('.') + '.' + newSubVersion;
        }

        config.releaseVersion = newVersion;

        grunt.log.writeln('* Moving from version '+currentVersion+' to '+newVersion);

        replaceInFile(config.mainScript, 'Version: '+currentVersion, 'Version: '+newVersion);
        replaceInFile('readme.txt', 'Stable tag: '+currentVersion, 'Stable tag: '+newVersion);
        replaceInFile('constants.php', "'ARLIMA_PLUGIN_VERSION', '"+currentVersion, "'ARLIMA_PLUGIN_VERSION', '"+newVersion);
    });

    /*
     * Validate our javascripts
     */
    grunt.registerTask('validate-js', "Check that we're not doing anything wrong in our javascripts", function() {
        for(var x in config.filesToConcat ) {
            Object.keys(config.filesToConcat[x]).every(function(i) {
                var fileName = config.filesToConcat[x][i],
                    filePath = __dirname +'/'+ fileName,
                    code = readFile(filePath);
                if( fileName != 'js/arlima/dev/ArlimaUtils.js' && code.indexOf('console.') > -1 ) {
                    throw new Error('Javascript '+fileName+' invoked the console object, you must remove it to build the scripts!');
                }
                return true;
            });
        }
    });

    /*
     * Build javascript
     */
    grunt.registerTask('build-js', ['validate-js', 'current-version', 'concat', 'uglify', 'change-version']);

    /*
     * Create class documentation
     */
    var docsPath = 'classes/docs.md';
    grunt.registerTask('create-docs', 'Create markdown-formatted class documentation in '+docsPath, function() {
        var bin = __dirname+'/vendor/victorjonsson/markdowndocs/bin/phpdoc-md',
            done = this.async();

        exec(bin+' generate --bootstrap=classes/tests/setup.php --ignore=mustache,tests classes > '+docsPath, function(err, stdout, stderr) {
            if( err || stderr ) {
                grunt.log.writeln('!! '+(err || stderr));
                throw new Error('phpdocs-md is missing, please run $ composer update');
            } else {
                grunt.log.writeln('* Class docs written to '+docsPath);
            }
            done();
        });
    });

    /*
     * Run PHP-unit
     */
    grunt.registerTask('phpunit', 'Run phpUnit tests', function() {

        var done = this.async();

        exec('phpunit', function(err, stdout, stderr) {
            if( !handleProcessError(grunt, stderr, err, stdout) ) {
                if( stdout.indexOf('<span style="') > -1 ) {
                    // h4ck.. in case of a php-error the output will not be sent
                    // to stderr...
                    handleProcessError(grunt, stdout);
                } else if(stdout.indexOf('PHPUnit') > -1 ) {
                    grunt.log.writeln('* Successfully ran all unit tests ');
                    grunt.log.writeln(stdout);
                } else {
                    handleProcessError(grunt, 'Unexpected output from phpunit: '+stdout);
                }
            }
            done();
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
    grunt.registerTask('validate-readme', 'Validate readme.txt', function() {
        var faults = mval.validate('./readme.txt', mval.MANIFEST.WORDPRESS);
        if( faults.length > 0 ) {
            throw new Error('Validation of readme failed: \n'+faults.join('\n'));
        }
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
        'validate-js',
        'validate-readme',
        'change-version',
        'localization',
        'create-docs',
        'less',
        'concat',
        'uglify'
    ];
    grunt.registerTask('default', defaultTasks);
};
