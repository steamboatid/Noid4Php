<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 1/14/2019
 * Time: 04:47
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'NoidTestCase.php';

class DbConvertTest extends NoidTestCase{
	/**
	 * @param string $src_type type of source database
	 * @param string $dst_type type of destination database
	 *
	 * @throws Exception
	 */
	public function testAtomicConverting($src_type = 'bdb', $dst_type = 'mysql')
	{
		// create src db newly.
		$cmd = "{$this->cmd} -f {$this->dir} -t {$src_type} dbcreate >/dev/null";
		$this->_executeCommand($cmd, $status, $output, $errors);
		$this->assertEquals(0, $status);

		// Mint 10 ids (0-9) in src db.
		$cmd = "{$this->cmd} -f {$this->dir} -t {$src_type} mint 10";
		$this->_executeCommand($cmd, $status, $output, $errors);
		$this->assertEquals(0, $status);

		// create dst db.
		$cmd = "{$this->cmd} -f {$this->dir} -t {$dst_type} dbcreate >/dev/null";
		$this->_executeCommand($cmd, $status, $output, $errors);
		$this->assertEquals(0, $status);

		// import all data into dst db from src.
		$cmd = "{$this->cmd} -f {$this->dir} -t {$dst_type} dbimport {$src_type} >/dev/null";
		$this->_executeCommand($cmd, $status, $output, $errors);
		$this->assertEquals(0, $status);

		// Mint one more in dst db, and check its value.
		$cmd = "{$this->cmd} -f {$this->dir} -t {$dst_type} mint 1";
		$this->_executeCommand($cmd, $status, $output, $errors);
		$this->assertEquals(0, $status);
		# Remove leading "id: ".
		$noid = preg_replace('/^id:\s+/', '', $output);
		$this->assertNotEmpty($noid);
		# echo '"id: " precedes output of mint command for last noid';
		# Remove trailing white space.
		$noid = preg_replace('/\s+$/', '', $noid);
		$this->assertNotEmpty($noid);
		#is($noid, "10", "last noid was \"9\"");
		$this->assertEquals('10', $noid);
		# echo 'last noid was "10"';
	}

	/**
	 * @throws Exception
	 */
	public function testDatabaseConverting()
	{
		// Convert from berkeley to others.
		$this->testAtomicConverting('bdb', 'mysql');
		$this->testAtomicConverting('bdb', 'sqlite');

		// Convert from mysql to others.
		$this->testAtomicConverting('mysql', 'bdb');
		$this->testAtomicConverting('mysql', 'sqlite');

		// Convert from sqlite to others.
		$this->testAtomicConverting('sqlite', 'bdb');
		$this->testAtomicConverting('sqlite', 'mysql');
	}
}