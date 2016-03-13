<?php
/**
 * @author Michael A. Russell
 * @author Daniel Berthereau (conversion to Php)
 * @package Noid
 */

/**
 * Tests for Noid (6).
 *
 * ------------------------------------
 *
 * Project: Noid
 *
 * Name:        noid6.t
 *
 * Function:    To test the noid command.
 *
 * What Is Tested:
 *      Create minter with template de, for 290 identifiers.
 *      Mint 1 noid.
 *      Bind to it a bunch of element/value pairs.
 *      Fetch the bindings to see if everything is there.
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
class Noid6Test extends PHPUnit_Framework_TestCase
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

    public function testNoid6()
    {
        # Start off by doing a dbcreate.
        # First, though, make sure that the BerkeleyDB files do not exist.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate tst6.rde long 13030 cdlib.org noidTest >/dev/null";
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

        # Mint one.
        $cmd = "{$this->noid_cmd} mint 1";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        $noid_output = explode(PHP_EOL, $output);
        $noid_output = array_map('trim', $noid_output);
        $noid_output = array_filter($noid_output, 'strlen');

        $bound_noid = preg_replace('/^id:\s+/', '', $noid_output[0]);
        $this->assertNotEmpty($bound_noid);
        # echo '"id: " preceded minted noid';
        unset($noid_output);

        # Set up the elements and values that we'll bind to this noid.
        $bind_stuff = array();
        while (count($bind_stuff) < 100) {
            # If we create a duplicate element name (not likely), it will
            # be overwritten in the hash.  No big deal.
            $bind_stuff[$this->_random_string()] = $this->_random_string();
        }

        # Start the "bind set" command, so that we'll be able to "print" the
        # elements and values.
        $cmd = "{$this->noid_cmd} bind set $bound_noid : >/dev/null";
        // Save the bind stuff in a temp file in order to simulate STDIN.
        $stdinFilename = tempnam(sys_get_temp_dir(), 'noidtest');
        $stuff = '';
        foreach ($bind_stuff as $key => $value) {
            $stuff .= "$key: $value" . PHP_EOL;
        }
        // An empty liine means end of the input.
        $stuff .= PHP_EOL;
        $result = file_put_contents($stdinFilename, $stuff);
        $this->assertNotEmpty($result);

        $cmd .= ' < ' . escapeshellarg($stdinFilename);
        $this->_executeCommand($cmd, $status, $output, $errors);
        unlink($stdinFilename);
        $this->assertEquals(0, $status, sprintf('open of "%s" failed, %s, stopped', $cmd, $errors));

        # Now, run the "fetch" command to get it all back.
        $cmd = "{$this->noid_cmd} fetch $bound_noid";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        $noid_output = explode(PHP_EOL, $output);
        $this->assertGreaterThanOrEqual(1, count($noid_output));
        # echo '"fetch" command generated some output';

        # Remove all newlines.
        $noid_output = array_map('trim', $noid_output);
        # If the last line is empty, delete it.
        $noid_output = array_filter($noid_output, 'strlen');
        $this->assertEquals(102, count($noid_output),
            # If there aren't 102 lines of output, somethings is wrong.
            'something wrong with fetch output, stopped');
        # echo 'there are 102 lines of output from the "fetch" command';

        # Check first line.
        $regex = '/^id:\s+' . preg_quote($bound_noid, '/') . '\s+hold\s*$/';
        $this->assertNotEmpty(preg_match($regex, $noid_output[0]));
        # echo 'line 1 of "fetch" output';

        # Check seocnd line.
        $regex = '/^Circ:\s+/';
        $this->assertNotEmpty(preg_match($regex, $noid_output[1]));
        # echo 'line 2 of "fetch" output';

        # Remove the first two lines from the array.
        array_shift($noid_output);
        array_shift($noid_output);

        # Run through the rest, looking to see if they're correct.
        for ($i = 0; $i < 100; $i++) {
            $this->assertNotEmpty(preg_match('/^\s*(\S+)\s*:\s*(\S+)\s*$/', $noid_output[$i], $matches),
                sprintf('line %d of "fetch" output: ("%s") is in an unexpected format',
                    $i + 3, $noid_output[$i]));

            $element = $matches[1];
            $value = $matches[2];
            $this->assertArrayHasKey($element, $bind_stuff,
                sprintf('line %d of "fetch" output: ("%s") contained an element that was not in the group of elements bound to this noid',
                    $i + 3, $noid_output[$i]));

            $this->assertEquals($bind_stuff[$element], $value,
                sprintf('line %d of "fetch" output:  element "%s" was bound to value "%s", but "fetch" returned that it was bound to value "%s"',
                    $i + 3, $element, $bind_stuff[$element], $value));
            # echo 'line ' . $i + 3 . ' of "fetch" output';
            unset($bind_stuff[$element]);
        }

        # Everything that was bound and has been verified has been deleted from
        # the hash.  So the hash should now be empty.
        $this->assertEmpty($bind_stuff);
        # echo 'everything that was bound was returned by the "fetch" command';
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
