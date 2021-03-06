module.exports = function( grunt ) {
	'use strict';

	// Project configuration.
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		copy: {
			main: {
				src: [
					'includes/**',
					'languages/**',
					'composer.json',
					'CHANGELOG.md',
					'LICENSE.txt',
					'readme.txt',
					'sticky-tax.php'
				],
				dest: 'dist/'
			},
		},

		cssmin: {
			options: {
				sourceMap: true
			},
			target: {
				files: {
					'assets/css/sticky-tax.min.css': [
						'assets/css/sticky-tax.css'
					]
				}
			}
		},

		eslint: {
			options: {
				configFile: '.eslintrc'
			},
			target: [
				'assets/js/sticky-tax.js'
			]
		},

		uglify: {
			options: {
				banner: '/*! Sticky Tax - v<%= pkg.version %> */',
				sourceMap: true
			},
			main: {
				files: {
					'assets/js/sticky-tax.min.js': [
						'assets/js/sticky-tax.js'
					]
				}
			}
		},

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					mainFile: 'sticky-tax.php',
					potFilename: 'sticky-tax.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: false
				}
			}
		},
	} );

	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

	grunt.registerTask( 'build', [ 'eslint', 'i18n', 'cssmin', 'uglify', 'copy' ] );
	grunt.registerTask( 'i18n', [ 'makepot' ] );

	grunt.util.linefeed = '\n';

};
