<?php namespace Imvkmark\L5Thumber\Eva\Feature;

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


use Symfony\Component\Process\ProcessBuilder;

/**
 * An external process based filter which provides a way to set a timeout on the process.
 */
abstract class AbstractProcess {

	private $timeout;

	/**
	 * Set the process timeout.
	 * @param int $timeout The timeout for the process
	 */
	public function setTimeout($timeout) {
		$this->timeout = $timeout;
	}

	/**
	 * Creates a new process builder.
	 * @param array $arguments An optional array of arguments
	 * @return ProcessBuilder A new process builder
	 */
	protected function createProcessBuilder(array $arguments = []) {
		$pb = new ProcessBuilder($arguments);

		if (null !== $this->timeout) {
			$pb->setTimeout($this->timeout);
		}

		return $pb;
	}
}
