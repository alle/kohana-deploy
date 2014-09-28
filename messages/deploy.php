<?php defined('SYSPATH') OR die('No direct script access.');

return array(

	'invalid_params'           => 'Some params are invalid.',
	'invalid_environment'      => 'Environment prerequisite are not valid.',

	'backup_dir_unwritable'    => '\':backup_dir\' does not exists or is not writeable.',
	'invalid_command'          => '\':command\' not available. It needs to be installed on the server for this script to work.',

	'run_error'                => 'Error encountered!'
		.PHP_EOL.'Stopping the script to prevent possible data loss.'
		.PHP_EOL.'CHECK THE DATA IN YOUR TARGET DIR!',

	'cleaning_up_tmp_files'    => 'Cleaning up temporary files...',

	'deployment_error_subject' => 'Error during deployment',

	'failed_emails'            => 'Following email address/es reports errors on sending: :recipients'

);
