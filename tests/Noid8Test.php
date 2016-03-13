<?php
/**
 * @author Michael A. Russell
 * @author Daniel Berthereau (conversion to Php)
 * @package Noid
 */

/**
 * Tests for Noid (8).
 *
 * ------------------------------------
 *
 * Project: Noid
 *
 * Name:        noid8.t
 *
 * Function:    To test the noid command.
 *
 * What Is Tested:
 *      Do a "dbcreate" using a variety of options to
 *      test that the various options in the policy can
 *      be turned on and off.
 *
 * Command line parameters:  none.
 *
 * Author:  Michael A. Russell
 *
 * Revision History:
 *      7/21/2004 - MAR - Initial writing
 *
 * ------------------------------------
 */
class Noid8Test extends PHPUnit_Framework_TestCase
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

    public function testNoid8()
    {
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate .rde long 13030 cdlib.org noidTest";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Do dbcreate.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate .rddk long 13030 cdlib.org noidTest";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Get and check the policy.
        $policy = $this->_get_policy($this->noid_dir . 'README');
        $this->assertNotEmpty($policy, 'unable to get policy');
        $this->assertEquals('GRANITE', $policy);
        # echo 'policy "GRANITE"';

        # Do dbcreate.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate .rddk long 00000 cdlib.org noidTest >/dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Get and check the policy.
        $policy = $this->_get_policy($this->noid_dir . 'README');
        $this->assertNotEmpty($policy, 'unable to get policy');
        $this->assertEquals('-RANITE', $policy);
        # echo 'policy "-RANITE"';

        # Do dbcreate.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate .sddk long 13030 cdlib.org noidTest >/dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Get and check the policy.
        $policy = $this->_get_policy($this->noid_dir . 'README');
        $this->assertNotEmpty($policy, 'unable to get policy');
        $this->assertEquals('G-ANITE', $policy);
        # echo 'policy "G-ANITE"';

        # Do dbcreate.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate tst8.rdek long 13030 cdlib.org noidTest >/dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Get and check the policy.
        $policy = $this->_get_policy($this->noid_dir . 'README');
        $this->assertNotEmpty($policy, 'unable to get policy');
        $this->assertEquals('GR-NITE', $policy);
        # echo 'policy "GR-NITE"';

        # Do dbcreate.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate .rddk medium 13030 cdlib.org noidTest >/dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Get and check the policy.
        $policy = $this->_get_policy($this->noid_dir . 'README');
        $this->assertNotEmpty($policy, 'unable to get policy');
        $this->assertEquals('GRA-ITE', $policy);
        # echo 'policy "GRA-ITE"';

        # Do dbcreate.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate r-r.rdd long 13030 cdlib.org noidTest >/dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Get and check the policy.
        $policy = $this->_get_policy($this->noid_dir . 'README');
        $this->assertNotEmpty($policy, 'unable to get policy');
        $this->assertEquals('GRAN--E', $policy);
        # echo 'policy "GRAN--E"';

        # Do dbcreate.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate .rdd long 13030 cdlib.org noidTest >/dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Get and check the policy.
        $policy = $this->_get_policy($this->noid_dir . 'README');
        $this->assertNotEmpty($policy, 'unable to get policy');
        $this->assertEquals('GRANI-E', $policy);
        # echo 'policy "GRANI-E"';

        # Do dbcreate.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate a.rdd long 13030 cdlib.org noidTest >/dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Get and check the policy.
        $policy = $this->_get_policy($this->noid_dir . 'README');
        $this->assertNotEmpty($policy, 'unable to get policy');
        $this->assertEquals('GRANI--', $policy);
        # echo 'policy "GRANI--"';

        # Do dbcreate.
        $cmd = "{$this->rm_cmd} ; " .
            "{$this->noid_cmd} dbcreate a-a.seeeeee medium 00000 cdlib.org noidTest >/dev/null";
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        # Get and check the policy.
        $policy = $this->_get_policy($this->noid_dir . 'README');
        $this->assertNotEmpty($policy, 'unable to get policy');
        $this->assertEquals('-------', $policy);
        # echo 'policy "-------"';
    }

    /**
     * Subroutine to get the policy out of the README file.
     *
     * @param string $filename
     * @return string
     */
    protected function _get_policy($file_name)
    {
        $fh = fopen($file_name, 'r');
        $error = error_get_last();
        $this->assertTrue(is_resource($fh),
            sprintf('open of "%s" failed, %s', $file_name, $error['message']));
        if ($fh === false) {
            return;
        }

        $regex = '/^Policy:\s+\(:((G|-)(R|-)(A|-)(N|-)(I|-)(T|-)(E|-))\)\s*$/';
        while ($line = fgets($fh)) {
            $result = preg_match($regex, $line, $matches);
            if ($result) {
                fclose($fh);
                return $matches[1];
            }
        }
        fclose($fh);
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
