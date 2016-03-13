<?php
/**
 * @author Michael A. Russell
 * @author Daniel Berthereau (conversion to Php)
 * @package Noid
 */

/**
 * Tests for Noid (7).
 *
 * ------------------------------------
 *
 * Project: Noid
 *
 * Name:        noid7.t
 *
 * Function:    To test the noid command.
 *
 * What Is Tested:
 *      Create minter with template de, for 290 identifiers.
 *      Mint 2 noids.
 *      Bind an element/value to the first one using the ":"
 *          option.
 *      Bind an element/value, with the element length greater
 *          than 1,500 characters, and the value being
 *          10 lines, to the second one using the ":-" option.
 *      Fetch the bindings and check that they are correct.
 *
 * Command line parameters:  none.
 *
 * Author:  Michael A. Russell
 *
 * Revision History:
 *      7/20/2004 - MAR - Initial writing
 *
 * ------------------------------------
 */
class Noid7Test extends PHPUnit_Framework_TestCase
{
    public $dir;
    public $rm_cmd;
    public $noid_cmd;
    public $noid_dir;

    public function setUp()
    {
        $this->dir = getcwd();
        $this->rm_cmd = "/bin/rm -rf {$this->dir}/NOID > /dev/null 2>&1 ";
        $noid_bin = 'blib/script/noid';
        $cmd = is_executable($noid_bin) ? $noid_bin : $this->dir . DIRECTORY_SEPARATOR . 'noid';
        $this->noid_cmd = $cmd . ' -f ' . $this->dir . ' ';
        $this->noid_dir = $this->dir . DIRECTORY_SEPARATOR . 'NOID' . DIRECTORY_SEPARATOR;

        require_once dirname($cmd) . DIRECTORY_SEPARATOR . 'lib'. DIRECTORY_SEPARATOR . 'Noid.php';

        # Seed the random number generator.
        srand(time());
    }

    public function tearDown()
    {
        $dbname = $this->noid_dir . 'noid.bdb';
        if (file_exists($dbname)) {
            Noid::dbclose($dbname);
        }
    }

    public function testNoid7()
    {
        # Start off by doing a dbcreate.
        # First, though, make sure that the BerkeleyDB files do not exist.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate tst7.rde long 13030 cdlib.org noidTest >/dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Check that the "NOID" subdirectory was created.
        $this->assertFileExists($this->noid_dir, 'no minter directory created, stopped');
        # echo 'NOID was created';

        # That "NOID" is a directory.
        $this->assertTrue(is_dir($this->noid_dir), 'NOID is not a directory, stopped');
        # echo 'NOID is a directory';

        # Check for the presence of the "README" file, then "log" file, then the
        # "logbdb" file within "NOID".
        $this->assertFileExists($this->noid_dir . 'README');
        # echo 'NOID/README was created';
        $this->assertFileExists($this->noid_dir . 'log');
        # echo 'NOID/log was created';
        $this->assertFileExists($this->noid_dir . 'logbdb');
        # echo 'NOID/logbdb was created';

        # Check for the presence of the BerkeleyDB file within "NOID".
        $this->assertFileExists($this->noid_dir . 'noid.bdb', 'minter initialization failed, stopped');
        # echo 'NOID/noid.bdb was created';

        # Mint two.
        $cmd = "{$this->noid_cmd} mint 2";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Remove all newlines.
        $noid_output = explode(PHP_EOL, $output);
        $noid_output = array_map('trim', $noid_output);
        # If the last line is empty, delete it.
        $noid_output = array_filter($noid_output, 'strlen');

        $noid_output[0] = preg_replace('/^id:\s+/', '', $noid_output[0]);
        $this->assertNotEmpty($noid_output[0]);
        # echo 'first line:  "id: " preceded minted noid';
        $noid_output[1] = preg_replace('/^id:\s+/', '', $noid_output[1]);
        $this->assertNotEmpty($noid_output[1]);
        # echo 'second line:  "id: " preceded minted noid';
        $bound_noid1 = $noid_output[0];
        $bound_noid2 = $noid_output[1];
        unset($noid_output);

        # Generate what we'll bind to noid number 1.
        $element1 = $this->_random_string();
        $value1 = $this->_random_string();

        # Start the "bind set" command for noid number 1, so that we'll be
        # able to "print" the element/value.
        $cmd = "{$this->noid_cmd} bind set $bound_noid1 :- >/dev/null";
        // Save the bind stuff in a temp file in order to simulate STDIN.
        $stdinFilename = tempnam(sys_get_temp_dir(), 'noidtest');
        $stuff = "$element1: $value1" . PHP_EOL;
        $result = file_put_contents($stdinFilename, $stuff);
        $this->assertNotEmpty($result);

        $cmd .= ' < ' . escapeshellarg($stdinFilename);
        $this->_executeCommand($cmd, $status, $output, $errors);
        //unlink($stdinFilename);
        $this->assertEquals(0, $status, sprintf('open of "%s" failed, %s, stopped', $cmd, $errors));

        # Generate the stuff for noid number 2.
        $element2 = '';
        while (strlen($element2) < 1500) {
            $element2 .= $this->_random_string();
        }

        # Generate 10 lines for the value for noid number 2.
        $value2 = array();
        for ($i = 0; $i < 10; $i++) {
            $value2[] = $this->_random_string();
        }

        # Start the "bind set" command for noid number 2, so that we'll be
        # able to "print" the element/value.
        $cmd = "{$this->noid_cmd} bind set $bound_noid2 :- >/dev/null";
        // Save the bind stuff in a temp file in order to simulate STDIN.
        $stdinFilename = tempnam(sys_get_temp_dir(), 'noidtest');
        # Write the element/value pair.
        $stuff = "$element2 : $value2[0]" . PHP_EOL;
        for ($i = 1; $i < 10; $i++) {
            $stuff .= $value2[$i] . PHP_EOL;
        }
        $result = file_put_contents($stdinFilename, $stuff);
        $this->assertNotEmpty($result);

        $cmd .= ' < ' . escapeshellarg($stdinFilename);
        $this->_executeCommand($cmd, $status, $output, $errors);
        //unlink($stdinFilename);
        $this->assertEquals(0, $status, sprintf('open of "%s" failed, %s, stopped', $cmd, $errors));

        # Now, run the "fetch" command on the noid number 1.
        $cmd = "{$this->noid_cmd} fetch $bound_noid1";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);
        $noid_output = explode(PHP_EOL, $output);
        $this->assertGreaterThan(0, count($noid_output));
        # echo '"fetch" command on noid 1 generated some output';

        # Remove all newlines.
        $noid_output = array_map('trim', $noid_output);
        # Delete any trailing lines that are empty.
        $noid_output = array_filter($noid_output, 'strlen');

        # If there aren't 3 lines of output, somethings is wrong.
        $this->assertEquals(3, count($noid_output), 'something wrong with fetch output, stopped');
        #echo 'there are 3 lines of output from the "fetch" command on noid 1';

        # Check first line.
        $regex = '/^id:\s+' . preg_quote($bound_noid1, '/') . '\s+hold\s*$/';
        $this->assertNotEmpty(preg_match($regex, $noid_output[0]));
        # echo 'line 1 of "fetch" output for noid 1';

        # Check second line.
        $regex = '/^Circ:\s+/';
        $this->assertNotEmpty(preg_match($regex, $noid_output[1]));
        # echo 'line 2 of "fetch" output for noid 1';

        # Check third line.
        $regex = '/^\s*(\S+)\s*:\s*(\S+)\s*$/';
        $this->assertNotEmpty(preg_match($regex, $noid_output[2], $matches),
            'something wrong with bound value, stopped');
        # echo 'line 3 of "fetch" output for noid 1';

        $this->assertEquals($element1, $matches[1]);
        $this->assertEquals($value1, $matches[2]);
        # echo 'line 3 of "fetch" output for noid 1';

        $cmd = "{$this->noid_cmd} fetch $bound_noid2";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        $noid_output = explode(PHP_EOL, $output);
        $this->assertGreaterThan(0, count($noid_output));
        # echo '"fetch" command on noid 2 generated some output';

        # Remove all newlines.
        $noid_output = array_map('trim', $noid_output);
        # Delete any trailing lines that are empty.
        $noid_output = array_filter($noid_output, 'strlen');

        # If there aren't 12 lines of output, somethings is wrong.
        $this->assertEquals(12, count($noid_output), 'not enough lines of output, stopped');
        #echo 'there are 12 lines of output from the "fetch" command on noid 1';

        # Check first line.
        $regex = '/^id:\s+' . preg_quote($bound_noid2, '/') . '\s+hold\s*$/';
        $this->assertNotEmpty(preg_match($regex, $noid_output[0]));
        # echo 'line 1 of "fetch" output for noid 2';

        # Check second line.
        $regex = '/^Circ:\s+/';
        $this->assertNotEmpty(preg_match($regex, $noid_output[1]));
        # echo 'line 2 of "fetch" output for noid 2';

        # Check third line.
        $regex = '/^\s*(\S+)\s*:\s*(\S+)\s*$/';
        $this->assertNotEmpty(preg_match($regex, $noid_output[2], $matches),
            'something wrong with bound value, stopped');
        # echo 'line 3 of "fetch" output for noid 2';

        $this->assertEquals($element2, $matches[1]);
        $this->assertEquals($value2[0], $matches[2]);
        # echo 'line 3 of "fetch" output for noid 2';

        for ($i = 1; $i <= 9; $i++) {
            $this->assertEquals($value2[$i], $noid_output[$i + 2]);
            # echo 'line ' . $i + 3 . ' of "fetch" output for noid 2';
        }
    }

    /**
     * Subroutine to generate a random string of (sort of) random length.
     *
     * @return string
     */
    protected function _random_string()
    {
        $to_choose_from =
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ' .
            'abcdefghijklmnopqrstuvwxyz' .
            '0123456789';
        $building_string = '';

        # Calculate the string length.  First, get a fractional number that's
        # between 0 and 1 but never 1.
        $string_length = (float) mt_rand() / (float) (mt_getrandmax() - 1);
        # Multiply it by 48, so that it's between 0 and 48, but never 48.
        $string_length *= 48;
        # Throw away the fractional part, leaving an integer between 0 and 47.
        $string_length = intval($string_length);
        # Add 3 to give us a number between 3 and 50.
        $string_length += 3;

        for ($i = 0; $i < $string_length; $i++) {
            # Calculate an integer between 0 and ((length of
            # $to_choose_from) - 1).
            # First, get a fractional number that's between 0 and 1,
            # but never 1.
            $to_choose_index = (float) mt_rand() / (float) (mt_getrandmax() - 1);
            # Multiply it by the length of $to_choose_from, to get
            # a number that's between 0 and (length of $to_choose_from),
            # but never (length of $choose_from);
            $to_choose_index *= strlen($to_choose_from);
            # Throw away the fractional part to get an integer that's
            # between 0 and ((length of $to_choose_from) - 1).
            $to_choose_index = intval($to_choose_index);

            # Fetch the character at that index into $to_choose_from,
            # and append it to the end of the string we're building.
            $building_string .= substr($to_choose_from, $to_choose_index, 1);
        }

        # Return our construction.
        return $building_string;
    }

    protected function _executeCommand($cmd, &$status, &$output, &$errors)
    {
        // Using proc_open() instead of exec() avoids an issue: current working
        // directory cannot be set properly via exec().  Note that exec() works
        // fine when executing in the web environment but fails in CLI.
        $descriptorSpec = array(
            0 => array('pipe', 'r'), //STDIN
            1 => array('pipe', 'w'), //STDOUT
            2 => array('pipe', 'w'), //STDERR
        );
        if ($proc = proc_open($cmd, $descriptorSpec, $pipes, getcwd())) {
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            $status = proc_close($proc);
        } else {
            throw new Exception("Failed to execute command: $cmd.");
        }
    }
}
