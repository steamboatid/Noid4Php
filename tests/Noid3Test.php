<?php
/**
 * @author Michael A. Russell
 * @author Daniel Berthereau (conversion to Php)
 * @package Noid
 */

/**
 * Tests for Noid (3).
 *
 * ------------------------------------
 *
 * Project: Noid
 *
 * Name:        noid3.t
 *
 * Function:    To test the noid command.
 *
 * What Is Tested:
 *      Create minter.
 *      Hold identifiers that would normally be first and second.
 *      Mint 1 and check that it is what would normally be third.
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
class Noid3Test extends PHPUnit_Framework_TestCase
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

    public function testNoid3()
    {
        # Start off by doing a dbcreate.
        # First, though, make sure that the BerkeleyDB files do not exist.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate tst3.rde long 13030 cdlib.org noidTest >/dev/null";
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

        # Hold first and second identifiers.
        $cmd = "{$this->noid_cmd} hold set 13030/tst31q 13030/tst30f > /dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Mint 1.
        $cmd = "{$this->noid_cmd} mint 1";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Verify that it's the third one.
        $noid_output = trim($output);
        $this->assertEquals('id: 13030/tst394', $noid_output);
        # echo 'held two, minted one, got the third one';
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
