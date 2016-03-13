<?php
/**
 * @author Michael A. Russell
 * @author Daniel Berthereau (conversion to Php)
 * @package Noid
 */

/**
 * Tests for Noid (DBCreate).
 *
 * Note on Perl script.
 * Before `make install' is performed this script should be runnable with
 * `make test'. After `make install' it should work as `perl dbcreate.t'
 */
class NoidDbCreateTest extends PHPUnit_Framework_TestCase
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
     * Size tests
     */
    public function testSize()
    {
        $result = $this->_short('.sd');
        $regex = '/Size:\s*10\n/';
        $this->assertNotEmpty(preg_match($regex, $result));
        # echo 'single digit sequential template';

        $result = $this->_short('.sdd');
        $regex = '/Size:\s*100\n/';
        $this->assertNotEmpty(preg_match($regex, $result));
        # echo '2-digit sequential template';

        $result = $this->_short('.zded');
        $regex = '/Size:\s*unlimited\n/';
        $this->assertNotEmpty(preg_match($regex, $result));
        # echo '3-digit unbounded sequential';

        $result = $this->_short('fr.reedde');
        $regex = '/Size:\s*2438900\n/';
        $this->assertNotEmpty(preg_match($regex, $result));
        # echo '6-digit random template';
    }

    /**
     * Some template error tests
     */
    public function testTemplateError()
    {
        $result = $this->_short('ab.rddd');
        $regex = '/Size:\s*1000\n/';
        $this->assertNotEmpty(preg_match($regex, $result));
        # echo 'prefix vowels ok in general';

        $result = $this->_short('ab.rdxdk', 'stdout');
        $regex = '/parse_template: a mask may contain only the letters/';
        $this->assertNotEmpty(preg_match($regex, $result));
        # echo 'bad mask char';

        $result = $this->_short('ab.rdddk', 'stdout');
        $regex = '/a mask may contain only characters from/';
        $this->assertNotEmpty(preg_match($regex, $result));
        # echo 'prefix vowels not ok with check char';
    }

    /**
     * Set up a generator that we will test
     */
    public function testGenerator()
    {
        $erc = $this->_short('8r9.sdd');
        $regex = '/Size:\s*100\n/';
        $this->assertNotEmpty(preg_match($regex, $erc));
        # echo '2-digit sequential';

        $noid = Noid::dbopen($this->noid_dir . 'noid.bdb', 0);
        $contact = 'Fester Bestertester';

        $n = 1;
        while ($n--) {
            $id = Noid::mint($noid, $contact, '');
        };
        $this->assertEquals('8r900', $id);
        # echo 'sequential mint test first';

        $n = 99;
        while ($n--) {
            $id = Noid::mint($noid, $contact, '');
        };
        $this->assertEquals('8r999', $id);
        # echo 'sequential mint test last';

        $n = 1;
        while ($n--) {
            $id = Noid::mint($noid, $contact, '');
        };
        $this->assertEquals('8r900', $id);
        # echo 'sequential mint test wrap to first';

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
