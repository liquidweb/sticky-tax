module.exports = function( grunt ) {
	'use strict';

	// Project configuration.
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		addtextdomain: {
			options: {
				textdomain: 'sticky-tax',
			},
			update_all_domains: {
				options: {
					updateDomains: true
				},
				src: [
					'*.php',
					'**/*.php',
					'!node_modules/**',
					'!tests/**',
					'!bin/**'
				]
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

	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.registerTask( 'i18n', [ 'makepot' ] );

	grunt.util.linefeed = '\n';

};
