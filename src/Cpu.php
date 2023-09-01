<?php

/**
 * NsUtil namespace.
 */

namespace NsUtil;

/**
 * Cpu class.
 *
 * This class provides functionalities related to the CPU.
 */
class Cpu
{
    /**
     * Count the number of processors in the system.
     *
     * @return int Number of processors.
     */
    public static function count(): int
    {
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            // Match processor entries in the file
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $processors = count($matches[0]);
        }

        return $processors ?? 1;
    }

    /**
     * Get the CPU usage.
     *
     * @return mixed The CPU usage percentage or null.
     */
    public static function usage()
    {
        return self::getServerLoad();
    }

    /**
     * Extracts and returns data about CPU load from the "/proc/stat" file on Linux.
     *
     * @return array|null Returns an array containing User, Nice, System, and Idle values or null if data couldn't be extracted.
     */
    private static function _getServerLoadLinuxData()
    {
        // Check if file is readable
        if (is_readable("/proc/stat")) {
            $stats = @file_get_contents("/proc/stat");
            if ($stats !== false) {
                // Normalize spaces and split into lines
                $stats = preg_replace("/[[:blank:]]+/", " ", $stats);
                $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                $stats = explode("\n", $stats);

                // Extract the main CPU load data
                foreach ($stats as $statLine) {
                    $statLineData = explode(" ", trim($statLine));

                    // If the data line is for the main CPU
                    if (
                        (count($statLineData) >= 5) &&
                        ($statLineData[0] == "cpu")
                    ) {
                        return array(
                            $statLineData[1],
                            $statLineData[2],
                            $statLineData[3],
                            $statLineData[4],
                        );
                    }
                }
            }
        }

        return null;
    }

    /**
     * Retrieves the server load.
     *
     * On Windows, it uses the "wmic" command to get the CPU load percentage.
     * On other OSes (primarily Linux), it extracts data from "/proc/stat".
     *
     * @return mixed The server load percentage as a number (without the percent sign) or null.
     */
    private static function getServerLoad()
    {
        $load = null;

        // For Windows OS
        if (stristr(PHP_OS, "win")) {
            $cmd = "wmic cpu get loadpercentage /all";
            @exec($cmd, $output);
            if ($output) {
                foreach ($output as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        $load = $line;
                        break;
                    }
                }
            }
        } else {
            // For Linux OS
            if (is_readable("/proc/stat")) {
                // Get two samples with a 1-second interval
                $statData1 = self::_getServerLoadLinuxData();
                sleep(1);
                $statData2 = self::_getServerLoadLinuxData();

                if (
                    (!is_null($statData1)) &&
                    (!is_null($statData2))
                ) {
                    // Calculate the difference between the two samples
                    $statData2[0] -= $statData1[0];
                    $statData2[1] -= $statData1[1];
                    $statData2[2] -= $statData1[2];
                    $statData2[3] -= $statData1[3];

                    // Compute the percentage of idle time
                    $cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];
                    $load = 100 - ($statData2[3] * 100 / $cpuTime);
                }
            }
        }

        return $load;
    }
}
