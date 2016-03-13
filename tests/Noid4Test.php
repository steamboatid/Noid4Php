<?php
/**
 * @author Michael A. Russell
 * @author Daniel Berthereau (conversion to Php)
 * @package Noid
 */

/**
 * Tests for Noid (4).
 *
 * ------------------------------------
 *
 * Project: Noid
 *
 * Name:        noid4.t
 *
 * Function:    To test the noid command.
 *
 * What Is Tested:
 *      Create minter with template de, for 290 identifiers.
 *      Mint 10.
 *      Queue 3, hold 2, that would have been minted in the
 *          next 20.
 *      Mint 20 and check that they come out in the correct order.
 *
 * Command line parameters:  none.
 *
 * Author:  Michael A. Russell
 *
 * Revision History:
 *      7/19/2004 - MAR - Initial writing
 *
 * ------------------------------------
 */
class Noid4Test extends PHPUnit_Framework_TestCase
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
    }

    public function tearDown()
    {
        $dbname = $this->noid_dir . 'noid.bdb';
        if (file_exists($dbname)) {
            Noid::dbclose($dbname);
        }
    }

    public function testNoid4()
    {
        # Start off by doing a dbcreate.
        # First, though, make sure that the BerkeleyDB files do not exist.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate tst4.rde long 13030 cdlib.org noidTest >/dev/null";
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

        # Mint 10.
        $cmd = "{$this->noid_cmd} mint 10 > /dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Queue 3.
        $cmd = "{$this->noid_cmd} queue now 13030/tst43m 13030/tst47h 13030/tst44k >/dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Hold 2.
        $cmd = "{$this->noid_cmd} hold set 13030/tst412 13030/tst421 >/dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Mint 20, and check that they have come out in the correct order.
        $cmd = "{$this->noid_cmd} mint 20";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Remove trailing newlines, and delete the last line if it's empty.
        $noid_output = explode(PHP_EOL, $output);
        $noid_output = array_map('trim', $noid_output);
        # If the last one is the null string, delete it.
        $noid_output = array_filter($noid_output, 'strlen');
        $this->assertEquals(20, count($noid_output),
        # If we don't have exactly 20, something is probably very wrong.
            'wrong number of ids minted, stopped');
        # echo 'number of minted noids';

        // In the Perl script, order is different because of rand(), excepted
        // when the ids are held before.
        $this->assertEquals('id: 13030/tst43m', $noid_output[0], 'Error in 1st minted noid');
        $this->assertEquals('id: 13030/tst47h', $noid_output[1], 'Error in 2nd minted noid');
        $this->assertEquals('id: 13030/tst44k', $noid_output[2], 'Error in 3rd minted noid');
        // $this->assertEquals('id: 13030/tst48t', $noid_output[3], 'Error in 4th minted noid');
        $this->assertEquals('id: 13030/tst45p', $noid_output[3], 'Error in 4th minted noid');
        // $this->assertEquals('id: 13030/tst466', $noid_output[4], 'Error in 5th minted noid');
        $this->assertEquals('id: 13030/tst499', $noid_output[4], 'Error in 5th minted noid');
        // $this->assertEquals('id: 13030/tst44x', $noid_output[5], 'Error in 6th minted noid');
        $this->assertEquals('id: 13030/tst47t', $noid_output[5], 'Error in 6th minted noid');
        // $this->assertEquals('id: 13030/tst42c', $noid_output[6], 'Error in 7th minted noid');
        $this->assertEquals('id: 13030/tst469', $noid_output[6], 'Error in 7th minted noid');
        // $this->assertEquals('id: 13030/tst49s', $noid_output[7], 'Error in 8th minted noid');
        $this->assertEquals('id: 13030/tst400', $noid_output[7], 'Error in 8th minted noid');
        // $this->assertEquals('id: 13030/tst48f', $noid_output[8], 'Error in 9th minted noid');
        $this->assertEquals('id: 13030/tst43g', $noid_output[8], 'Error in 9th minted noid');
        // $this->assertEquals('id: 13030/tst475', $noid_output[9], 'Error in 10th minted noid');
        $this->assertEquals('id: 13030/tst424', $noid_output[9], 'Error in 10th minted noid');
        // $this->assertEquals('id: 13030/tst45v', $noid_output[10], 'Error in 11th minted noid');
        $this->assertEquals('id: 13030/tst45q', $noid_output[10], 'Error in 11th minted noid');
        // $this->assertEquals('id: 13030/tst439', $noid_output[11], 'Error in 12th minted noid');
        $this->assertEquals('id: 13030/tst498', $noid_output[11], 'Error in 12th minted noid');
        // $this->assertEquals('id: 13030/tst40q', $noid_output[12], 'Error in 13th minted noid');
        $this->assertEquals('id: 13030/tst42v', $noid_output[12], 'Error in 13th minted noid');
        // $this->assertEquals('id: 13030/tst49f', $noid_output[13], 'Error in 14th minted noid');
        $this->assertEquals('id: 13030/tst41f', $noid_output[13], 'Error in 14th minted noid');
        // $this->assertEquals('id: 13030/tst484', $noid_output[14], 'Error in 15th minted noid');
        $this->assertEquals('id: 13030/tst451', $noid_output[14], 'Error in 15th minted noid');
        // $this->assertEquals('id: 13030/tst46t', $noid_output[15], 'Error in 16th minted noid');
        $this->assertEquals('id: 13030/tst48m', $noid_output[15], 'Error in 16th minted noid');
        // $this->assertEquals('id: 13030/tst45h', $noid_output[16], 'Error in 17th minted noid');
        $this->assertEquals('id: 13030/tst477', $noid_output[16], 'Error in 17th minted noid');
        // $this->assertEquals('id: 13030/tst447', $noid_output[17], 'Error in 18th minted noid');
        $this->assertEquals('id: 13030/tst40t', $noid_output[17], 'Error in 18th minted noid');
        // $this->assertEquals('id: 13030/tst42z', $noid_output[18], 'Error in 19th minted noid');
        $this->assertEquals('id: 13030/tst49d', $noid_output[18], 'Error in 19th minted noid');
        // $this->assertEquals('id: 13030/tst41n', $noid_output[19], 'Error in 20th minted noid');
        $this->assertEquals('id: 13030/tst47w', $noid_output[19], 'Error in 20th minted noid');
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
