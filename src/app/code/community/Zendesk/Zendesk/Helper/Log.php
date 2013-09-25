<?php
/**
 * Copyright 2013 Zendesk.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class Zendesk_Zendesk_Helper_Log extends Mage_Core_Helper_Abstract
{
    /**
     * Maximum size log file can grow to before we truncate output.
     * Value is arbitrary and based on trial and error for a reasonable amount of data to send back to the browser.
     */
    const MAX_LOG_SIZE = 524288;

    /**
     * Number of lines to take from the end of the log file, if it's too large.
     */
    const TAIL_SIZE = 1000;

    public function getLogPath()
    {
        return Mage::getBaseDir('log') . DS. 'zendesk.log';
    }

    public function getLogSize()
    {
        return filesize($this->getLogPath());
    }

    public function getTailSize()
    {
        return self::TAIL_SIZE;
    }

    /**
     * Retrieves the contents of the Zendesk log file, with optional truncation for sending directly back to a browser.
     *
     * NOTE: If allowing for truncation this method can still return a lot of data (too much) if you run into a
     * situation where one of the lines near the end of the file is very, very long. In practice this should
     * rarely happen since the log file should only be written to by this extension.
     *
     * @param bool $allowTruncate Whether the file should be truncated if it's too large
     *
     * @return string File contents
     */
    public function getLogContents($allowTruncate = true)
    {
        $path = $this->getLogPath();
        $content = '';

        if(file_exists($path)) {
            if($allowTruncate && $this->isLogTooLarge()) {
                $content = $this->_tail($path, self::TAIL_SIZE);
            } else {
                $content = file_get_contents($path);
            }
        }

        return $content;
    }

    /**
     * Is the Zendesk log file too large to display?
     *
     * This method doesn't map very well to the size of the tail command on the file in that it doesn't use the
     * same number of lines to determine if the file is "too large". This means that there is the possibility for
     * problems if the log files contains some lines at the end that are extremely long (millions of characters) then
     * the _tail method will still return them.
     *
     * @return bool true if the file is too large to display, false if not
     */
    public function isLogTooLarge()
    {
        $size = $this->getLogSize();

        if($size !== FALSE && $size > self::MAX_LOG_SIZE) {
            return true;
        }

        return false;
    }

    public function clear()
    {
        @unlink($this->getLogPath());
        touch($this->getLogPath());
    }

    /**
     * Runs a tail operation to retrieve the last lines of a file.
     * @param string $file  Path to the file to tail
     * @param int    $lines Number of lines to retrieve
     *
     * @return string
     */
    protected function _tail($file, $lines = 10)
    {
        $data = '';

        // If we're on a Unix-like system then run a much faster shell command to tail the file.
        // Note that this could potentially be implemented as "everything that ISN'T Windows" but
        // was done with a specific list of common kernels for safety.
        // For a larger list see: http://en.wikipedia.org/wiki/Uname#Table_of_standard_uname_output
        if(in_array(php_uname('s'), array('Linux', 'FreeBSD', 'NetBSD', 'OpenBSD', 'Darwin', 'SunOS', 'Unix'))) {
            $data = shell_exec("tail -n $lines '$file'");
        } else {
            // Fall back to a much slower (and manual) process for using PHP to tail the file.
            $fp = fopen($file, 'r');
            $position = filesize($file);
            fseek($fp, $position-1);
            $chunklen = 4096;
            $data = '';

            while($position >= 0) {
                $position = $position - $chunklen;

                if ($position < 0) {
                    $chunklen = abs($position); $position=0;
                }

                fseek($fp, $position);
                $data = fread($fp, $chunklen) . $data;

                if (substr_count($data, "\n") >= $lines + 1) {
                    preg_match("!(.*?\n){".($lines-1)."}$!", $data, $match);
                    return $match[0];
                }
            }
            fclose($fp);
        }

        return $data;
    }
}