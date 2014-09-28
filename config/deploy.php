<?php defined('SYSPATH') OR die('No direct script access.');

return array(

	'default' => array(
		/**
		 * token
		 *
		 * @var string
		 *
		 * Protect the script from unauthorized access by using a secret access token.
		 * If it's not present in the access URL as a the <id> param, the script is not going to deploy.
		 */
		'token'            => '',

		/**
		 * remote
		 *
		 * @var string
		 *
		 * The address of the remote Git repository that contains the code that's being
		 * deployed.
		 * If the repository is private, you'll need to use the SSH address.
		 */
		'remote'           => '',

		/**
		 * branch
		 *
		 * @var string
		 *
		 * The branch that's being deployed.
		 * Must be present in the remote repository.
		 */
		'branch'           => 'master',

		/**
		 * target_dir
		 *
		 * @var string
		 *
		 * The location that the code is going to be deployed to.
		 * The trailing slash is added automatically.
		 */
		'target_dir'       => '/tmp',

		/**
		 * delete_files
		 *
		 * @var boolean
		 *
		 * Whether to delete the files that are not in the repository but are on the
		 * local (server) machine.
		 *
		 * !!! WARNING !!! This can lead to a serious loss of data if you're not
		 * careful. All files that are not in the repository are going to be deleted,
		 * except the ones defined in EXCLUDE section.
		 * BE CAREFUL!
		 */
		'delete_files'     => FALSE,

		/**
		 * exclude
		 *
		 * @var array
		 *
		 * The directories and files that are to be excluded when updating the code.
		 * Normally, these are the directories containing files that are not part of
		 * code base, for example user uploads or server-specific configuration files.
		 * Use rsync exclude pattern syntax for each element.
		 */
		'exclude'          => array(
			'.git'
		),

		/**
		 * clean_up
		 *
		 * @var boolean
		 *
		 * Temporary directory we'll use to stage the code before the update. If it
		 * already exists, script assumes that it contains an already cloned copy of the
		 * repository with the correct remote origin and only fetches changes instead of
		 * cloning the entire thing.
		 */
		'clean_up'         => FALSE,

		/**
		 * version
		 *
		 * @var string
		 *
		 * Output the version of the deployed code.
		 */
		'version'          => 'VERSION',

		/**
		 * time_limit
		 *
		 * @var int
		 *
		 * Time limit (seconds) for each command.
		 */
		'time_limit'       => 30,

		/**
		 * backup_dir
		 *
		 * @var boolean|string
		 *
		 * Backup the 'target_dir' into 'backup_dir' before deployment.
		 * If it is set to FALSE, no backup is performed.
		 */
		'backup_dir'       => FALSE,

		/**
		 * use_composer
		 *
		 * @var boolean
		 *
		 * Whether to invoke composer after the repository is cloned or changes are
		 * fetched. Composer needs to be available on the server machine, installed
		 * globaly (as `composer`). See http://getcomposer.org/doc/00-intro.md#globally
		 */
		'use_composer'     => FALSE,

		/**
		 * composer_options
		 *
		 * @var string
		 *
		 * The options that the composer is going to use.
		 */
		'composer_options' => '--no-dev',

		/**
		 * email_on_error
		 *
		 * @var array
		 *
		 * Email addresses to be notified on deployment failure.
		 */
		'email_on_error'   => array(),

		/**
		 * email_from
		 *
		 * @var string
		 *
		 * Email address for the sender.
		 */
		'email_from'   => '',
	)

);
