<?php

namespace Gaw508\Worker;

/**
 * Class Worker
 *
 * Turns current process into an exclusive worker process
 *
 * @author George Webb <george@webb.uno>
 * @package Gaw508\Worker
 */
abstract class Worker
{
    /**
     * Path to the process ID file
     *
     * @var
     */
    private $process_file_path;

    /**
     * Handle to process file
     *
     * @var
     */
    private $process_file_handle;

    /**
     * Worker constructor.
     *
     * @param string $process_file_path     Path to the process ID file
     */
    public function __construct($process_file_path)
    {
        $this->process_file_path = $process_file_path;
    }

    /**
     * Open the process file and get an exclusive lock
     *
     * @return bool     True if opened successfully, false otherwise
     */
    private function openProcessFile()
    {
        $handle = fopen($this->process_file_path, 'r+');
        if (!$handle || !flock($handle, LOCK_EX)) {
            fclose($handle);
            return false;
        }
        $this->process_file_handle = $handle;
        return true;
    }

    /**
     * Release the lock and close the process file
     */
    private function closeProcessFile()
    {
        flock($this->process_file_handle, LOCK_UN);
        fclose($this->process_file_handle);
    }

    /**
     * Get the PID of running matching process, if any
     *
     * @return string   The PID of matching process
     */
    private function getRunningPid()
    {
        fseek($this->process_file_handle, 0);
        return fgets($this->process_file_handle);
    }

    /**
     * Check if matching process is running
     *
     * @return bool     True if matching process is already running
     */
    private function isRunning()
    {
        $current_pid = $this->getRunningPid();
        if (!empty($current_pid)) {
            // Check if process exists
            exec('pgrep php', $php_pids);
            array_map('trim', $php_pids);
            if (in_array($current_pid, $php_pids)) {
                return true;
            }
        }
        return false;
    }

    /**
     * If a matching process isn't already running, a new one is started
     *
     * @return bool     False if process already running, or on error
     */
    private function registerProcess()
    {
        if (!$this->openProcessFile()) {
            return false;
        }

        if ($this->isRunning()) {
            $this->closeProcessFile();
            return false;
        }

        // Current PID doesn't exist or isn't actually running, empty file and write out PID
        ftruncate($this->process_file_handle, 0);
        fwrite($this->process_file_handle, getmypid());

        $this->closeProcessFile();

        return true;
    }

    /**
     * Start the worker. Will return false if a php process is running using the same process ID file or if
     * the file couldn't be written to.
     *
     * @return bool     False if the worker fails to start, finally returns true when work completed
     */
    public function start()
    {
        if (!$this->registerProcess()) {
            return false;
        }

        $this->work();

        return true;
    }

    /**
     * This function should be implemented with the worker code
     *
     * @return void
     */
    abstract protected function work();

    /**
     * Kills any running matching process
     */
    public function kill()
    {
        if (!$this->openProcessFile()) {
            return false;
        }

        if (!$this->isRunning()) {
            $this->closeProcessFile();
            return false;
        }

        exec('kill ' . $this->getRunningPid());
        return true;
    }
}
