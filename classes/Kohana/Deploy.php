<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Deploy {

	/**
	 * Current loaded config
	 *
	 * @var Config_Group
	 */
	protected $_config;

	/**
	 * Default config
	 *
	 * @var Config_Group
	 */
	public static $default = array(
		'token'            => '',
		'remote'           => '',
		'branch'           => 'master',
		'target_dir'       => '/tmp',
		'delete_files'     => FALSE,
		'exclude'          => array(
			'.git'
		),
		'clean_up'         => FALSE,
		'version'          => 'VERSION',
		'time_limit'       => 30,
		'backup_dir'       => FALSE,
		'use_composer'     => FALSE,
		'composer_options' => '--no-dev',
		'email_on_error'   => array(),
		'email_from'       => ''
	);

	/**
	 * Validation instance
	 *
	 * @var Validation
	 */
	protected $_deployment;

	/**
	 * Commands to execute
	 *
	 * @var array
	 */
	protected $_commands = array();

	/**
	 * Errors
	 *
	 * @var array
	 */
	protected $_errors = array();

	/**
	 * Output messages
	 *
	 * @var array
	 */
	protected $_output = array();

	/**
	 * Bin version
	 *
	 * @var array
	 */
	protected $_version = array();

	/**
	 * List of failed email recipients
	 *
	 * @var array
	 */
	protected $_failed_emails = array();

	public static function factory($token, Config_Group $config = NULL)
	{
		return new Deploy($token, $config);
	}

	public function __construct($token, Config_Group $config = NULL)
	{
		$config_by_token = Kohana::$config->load('deploy')->get($token);

		$this->_config = Arr::merge(Deploy::$default, $config_by_token, $config);

		$this->_deploymnet = Validation::factory($this->_config->as_array())
			->rule('token', 'not_empty')
			->rule('remote', 'not_empty');
	}

	/**
	 * Getter/setter for configuration
	 *
	 * @param array $config
	 *
	 * @return $this|array|Config_Group
	 */
	public function config(array $config = NULL)
	{
		if ( ! $config)
			return $this->_config;

		$this->_config = $config;

		return $this;
	}

	/**
	 * Checks environment and run deployment
	 *
	 * @throws Exception
	 */
	public function run()
	{
		if ( ! $this->_deployment->check())
		{
			$this->_errors = $this->_deployment->errors();

			throw new Exception(Kohana::message('deploy', 'invalid_params'));

			return;
		}

		$this->_fill_params();

		if ( ! $this->_check_environment())
		{
			throw new Exception(Kohana::message('deploy', 'invalid_environment'));

			return;
		}

		$this->_set_commands();

		$this->_run();

		return ! ( (bool) $this->_errors);
	}

	/**
	 * Fill params for later use
	 *
	 * @return $this
	 */
	protected function _fill_params()
	{
		$this->_config->set('branch', $this->_config->get('branch', 'master'));

		if ($this->_config->get('target_dir') == Arr::get(Deploy::$default, 'target_dir'))
		{
			$this->_config->set('target_dir', pathinfo($this->_config->get('remote'), PATHINFO_BASENAME));
		}

		$this->_config->set('target_dir', rtrim($this->_config->get('target_dir'), '/').'/');

		$this->_config->set('tmp_dir', '/tmp/'.crc32($this->_config->get('remote')).'/');

		$this->_config->set('exclude', serialize($this->_config->get('exclude')));

		return $this;
	}

	/**
	 * Performs environment checking
	 *
	 * @return bool
	 */
	protected function _check_environment()
	{
		// Check if the required bins are available
		$required_binaries = array('git', 'rsync');

		if (($backup_dir = $this->_config->get('backup_dir')) !== FALSE)
		{
			$required_binaries[] = 'tar';

			if ( ! is_dir($backup_dir) OR ! is_writable($backup_dir))
			{
				$this->_errors[] = __(Kohana::message('deploy', 'backup_dir_unwritable'), array(
					':backp_dir' => $backup_dir
				));

				return FALSE;
			}
		}

		if ($this->_config->get('use_composer') === TRUE)
		{
			$required_binaries[] = 'composer --no-ansi';
		}

		foreach ($required_binaries as $command)
		{
			$path = trim(shell_exec($command));

			if ($path == '')
			{
				$this->_errors[] = __(Kohana::message('deploy', invalid_command), array(
					':command' => $path
				));

				break;
			}
			else
			{
				$this->_version[$path] = explode("\n", shell_exec("$command --version"));
			}
		}

		return $this->_errors ? FALSE : TRUE;
	}

	/**
	 * Configure commands to execute
	 *
	 * @return $this
	 */
	protected function _set_commands()
	{
		if ( ! is_dir($this->_config->get('tmp_dir')))
		{
			// Clones the repository into the 'tmp_dir'

			$this->_commands[] = strtr('git clone --depth=1 --branch :branch :remote :tmp_dir', array(
				':branch' => $this->_config->get('branch'),
				':remote' => $this->_config->get('remote'),
				'tmp_dir' => $this->_config->get('tmp_dir')
			));
		}
		else
		{
			// 'tmp_dir' exists and hopefully already contains the correct remote origin
			// so we'll fetch the changes and reset the contents.

			$this->_commands[] = strtr('git --git-dir=":tmp_dir.git" --work-tree=":tmp_dir" fetch origin :branch', array(
				':tmp_dir' => $this->_config->get('tmp_dir'),
				':branch'  => $this->_config->get('barnch')
			));
			$this->_commands[] = strtr('git --git-dir=":tmp_dir.git" --work-tree=":tmp_dir" reset --hard FETCH_HEAD', array(
				':tmp_dir' => $this->_config->get('tmp_dir')
			));
		}

		// Updates the submodules
		$this->_commands[] = 'git submodule update --init --recursive';

		// Compile exclude parameters
		$exclude = '';
		foreach (unserialize($this->_config->get('exclude')) as $exc)
		{
			$exclude .= ' --exclude='.$exc;
		}

		// Deployment command
		$this->_commands[] = strtr('rsync -rltgoDzvO :tmp_dir :target_dir :delete_files :exclude', array(
			':tmp_dir' => $this->_config->get('tmp_dir'),
			':target_dir' => $this->_config->get('target_dir'),
			':delete_files' => ($this->_config->get('delete_files')) ? '--delete-after' : '',
			':exclude' => $exclude
		));

		// Remove the 'tmp_dir' (depends on 'clean_up')
		if ($this->_config->get('clean_up'))
		{
			$this->_commands['cleanup'] = strtr('rm -rf :tmp_dir', array(
				':tmp_dir' => $this->_config->get('tmp_dir')
			));
		}

		return $this;
	}

	/**
	 * Executes deployment
	 */
	protected function _run()
	{
		$time_limit = $this->_config->get('time_limit');
		$tmp_dir = $this->_config->get('tmp_dir');

		foreach ($this->_commands as $command)
		{
			// Reset the time limit for each command
			set_time_limit($time_limit);

			if (file_exists($tmp_dir) && is_dir($tmp_dir))
			{
				// Ensure that we're in the right directory
				chdir($tmp_dir);
			}

			$tmp = array();

			// Execute the command
			exec($command.' 2>&1', $tmp, $return_code);

			// Get the result's output
			$this->_output[] .= strtr('$ :command :output', array(
				':command' => htmlentities(trim($command)),
				':output'  => htmlentities(trim(implode("\n", $tmp)))
			));

			if ($return_code != 0)
			{
				$this->_error[] = __(Kohana::message('deploy', 'run_error'));

				if ($this->_config->get('clean_up'))
				{
					$tmp = shell_exec($this->_commands['cleanup']);

					$this->_output[] .= __(Kohana::message('deploy', 'cleaning_up_tmp_files'));

					$this->_output[] .= strtr('$ :command :output', array(
						':command' => htmlentities(trim($this->_commands['cleanup'])),
						':output'  => htmlentities(trim(implode("\n", $tmp)))
					));
				}
			}

			if ($this->_config->get('email_on_error'))
			{
				if ( ! $this->_send_email_on_error())
				{
					Kohana::$log->add(Log::WARNING, __(Kohana::message('deploy', 'failed_emails'), array(
						':recipients' => implode(', ', $this->_failed_emails)
					)));
				}
			}

			break;
		}
	}

	/**
	 * Send email on error
	 *
	 * @return int
	 */
	protected function _send_email_on_error()
	{
		$message = implode(PHP_EOL, $this->_errors);

		$email = Email::factory(__(Kohana::message('deploy', 'deployment_error')), $message);

		foreach ($this->_config->get('email_on_error') as $recipient)
		{
			$email->to($recipient);
		}

		return $email->send($this->_failed_emails);
	}

	/**
	 * Getter for error messages
	 *
	 * @return array
	 */
	public function errors()
	{
		return $this->_errors;
	}

}