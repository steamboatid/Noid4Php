<?php
/**
 * @author Michael A. Russell
 * @author Daniel Berthereau (conversion to Php)
 * @package Noid
 */

/**
 * Tests for Noid (Bind).
 */
class NoidBindTest extends PHPUnit_Framework_TestCase
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

    protected function _short($template, $return = 'erc')
    {
        $cmd = $this->rm_cmd;
        $this->_executeCommand($cmd, $status, $output, $errors);
        $this->assertEquals(0, $status);

        $report = Noid::dbcreate('.', 'jak', $template, 'short');
        $errmsg = Noid::errmsg(null, 1);
        if ($return == 'stdout' || $return == 'stderr') {
            $this->assertEmpty($report, 'should output an error: ' . $errmsg);
            return $errmsg;
        }

        $this->assertNotEmpty($report, $errmsg);

        Noid::dbclose($this->noid_dir . 'noid.bdb');

        // Return the erc.
        $isReadable = is_readable($this->noid_dir . 'README');
        $error = error_get_last();
        $this->assertTrue($isReadable, "can't open README: " . $error['message']);

        $erc = file_get_contents($this->noid_dir . 'README');
        return $erc;
        #return `./noid dbcreate $template short 2>&1`;
    }

    /**
     * Bind tests -- short
     */
    public function testBind()
    {
        $erc = $this->_short('.sdd');
        $regex = '/Size:\s*100\n/';
        $this->assertNotEmpty(preg_match($regex, $erc));
        # echo '2-digit sequential';

        $noid = Noid::dbopen($this->noid_dir . 'noid.bdb', 0);
        $contact = 'Fester Bestertester';

        $id = Noid::mint($noid, $contact, '');
        $id = Noid::mint($noid, $contact, '');
        $this->assertEquals('01', $id);
        # echo 'sequential mint verify';

        $result = Noid::bind($noid, $contact, 1, 'set', $id, 'myelem', 'myvalue');
        $this->assertNotEmpty(preg_match('/Status:  ok, 7/', $result));
        # echo 'simple bind';

        $result = Noid::fetch($noid, 1, $id, 'myelem');
        $this->assertNotEmpty(preg_match('/myelem: myvalue/', $result));
        # echo 'simple fetch';

        $result = Noid::fetch($noid, 0, $id, 'myelem');
        $this->assertNotEmpty(preg_match('/^myvalue$/', $result));
        # echo 'simple non-verbose (get) fetch';

        Noid::dbclose($noid);
    }

    /**
     * Queue/hold tests -- short
     */
    public function testQueueHold()
    {
        $erc = $this->_short('.sdd');
        $regex = '/Size:\s*100\n/';
        $this->assertNotEmpty(preg_match($regex, $erc));
        # echo '2-digit sequential';

        $noid = Noid::dbopen($this->noid_dir . 'noid.bdb', 0);
        $contact = 'Fester Bestertester';

        $id = Noid::mint($noid, $contact, '');
        $this->assertEquals('00', $id);
        # echo 'mint first';

        $result = Noid::hold($noid, $contact, 'set', '01');
        $this->assertEquals(1, $result);
        # echo 'hold next';

        $id = Noid::mint($noid, $contact, '');
        $this->assertEquals('02', $id);
        # echo 'mint next skips id held';

        # Shouldn't have to release hold to queue it
        $result = Noid::queue($noid, $contact, 'now', $id);
        $regex = "/id: " . preg_quote($id, '/') . '/';
        $this->assertNotEmpty(preg_match($regex, $result[0]));
        # echo 'queue previously held';

        $id = Noid::mint($noid, $contact, '');
        $this->assertEquals('02', $id);
        # echo 'mint next gets from queue';

        $id = Noid::mint($noid, $contact, '');
        $this->assertEquals('03', $id);
        # echo 'mint next back to normal';

        Noid::dbclose($noid);
    }

    # XXX
    # To do: set up a "long" minter and test the various things that
    # it should reject, eg, queue a minted Id without first doing a
    # "hold release Id"

    /**
     * Validate tests -- short
     */
    public function testValidate()
    {
        $erc = $this->_short('fk.redek');
        $regex = '/Size:\s*8410\n/';
        $this->assertNotEmpty(preg_match($regex, $erc));
        # echo '4-digit random';

        $noid = Noid::dbopen($this->noid_dir . 'noid.bdb', 0);
        $contact = 'Fester Bestertester';

        $id = Noid::mint($noid, $contact, '');
        $this->assertEquals('fk491f', $id);
        # echo 'mint first';

        $result = Noid::validate($noid, '-', 'fk491f');
        $regex = '/error: /';
        $this->assertEquals(0, preg_match($regex, $result[0]));
        # echo 'validate just minted';

        $result = Noid::validate($noid, '-', 'fk492f');
        $regex = '/iderr: /';
        $this->assertEquals(1, preg_match($regex, $result[0]));
        # echo 'detect one digit off';

        $result = Noid::validate($noid, '-', 'fk419f');
        $regex = '/iderr: /';
        $this->assertEquals(1, preg_match($regex, $result[0]));
        # echo 'detect transposition';

        Noid::dbclose($noid);
    }

    /**
     * Validate tests for unlimited sequences -- short
     */
    public function testValidateUnlimited()
    {
        $erc = $this->_short('fk.zde');
        $regex = '/Size:\s*unlimited\n/';
        $this->assertNotEmpty(preg_match($regex, $erc));
        # echo '4-digit random';

        $noid = Noid::dbopen($this->noid_dir . 'noid.bdb', 0);
        $contact = 'Fester Bestertester';

        $id = Noid::mint($noid, $contact, '');
        $this->assertEquals('fk00', $id);
        # echo 'mint first';

        $result = Noid::validate($noid, '-', 'fk9w');
        $regex = '/error: /';
        $this->assertEquals(0, preg_match($regex, $result[0]));
        # echo 'validate just minted';

        $result = Noid::validate($noid, '-', 'fkw9');
        $regex = '/iderr: /';
        $this->assertEquals(1, preg_match($regex, $result[0]));
        # echo 'validate just minted';

        $result = Noid::validate($noid, '-', 'fk9w5');
        $regex = '/iderr: /';
        $this->assertEquals(0, preg_match($regex, $result[0]));
        # echo 'detect one digit off';

        $result = Noid::validate($noid, '-', 'fk9wh');
        $regex = '/iderr: /';
        $this->assertEquals(1, preg_match($regex, $result[0]));
        # echo 'detect transposition';

        Noid::dbclose($noid);
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
