<?php
/**
 * Noid - Nice opaque identifiers (Php library).
 *
 * Strict conversion of the Perl module Noid-0.424 (21 April 2006) into php.
 *
 * @author Daniel Berthereau (port to php)
 * @license CeCILL-B v1.0 http://www.cecill.info/licences/Licence_CeCILL-B_V1-en.txt
 * @link https://metacpan.org/pod/distribution/Noid/noid
 * @link http://search.cpan.org/~jak/Noid/
 * @link https://github.com/Daniel-KM/Noid4Php
 * @package Noid
 * @version 1.0.0-0.424-php
 */

/*
require Exporter;
our @ISA = qw(Exporter);

use Fcntl qw(:DEFAULT :flock);
use BerkeleyDB;
*/

/**
 * Noid - Nice opaque identifiers (Perl module)
 *
 * Author:  John A. Kunze, jak@ucop.edu, California Digital Library
 *  Originally created, UCSF/CKM, November 2002
 *
 * ---------
 * Copyright (c) 2002-2006 UC Regents
 *
 * Permission to use, copy, modify, distribute, and sell this software and
 * its documentation for any purpose is hereby granted without fee, provided
 * that (i) the above copyright notices and this permission notice appear in
 * all copies of the software and related documentation, and (ii) the names
 * of the UC Regents and the University of California are not used in any
 * advertising or publicity relating to the software without the specific,
 * prior written permission of the University of California.
 *
 * THE SOFTWARE IS PROVIDED "AS-IS" AND WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS, IMPLIED OR OTHERWISE, INCLUDING WITHOUT LIMITATION, ANY
 * WARRANTY OF MERCHANTABILITY OR FITNESS FOR A PARTICULAR PURPOSE.
 *
 * IN NO EVENT SHALL THE UNIVERSITY OF CALIFORNIA BE LIABLE FOR ANY
 * SPECIAL, INCIDENTAL, INDIRECT OR CONSEQUENTIAL DAMAGES OF ANY KIND,
 * OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS,
 * WHETHER OR NOT ADVISED OF THE POSSIBILITY OF DAMAGE, AND ON ANY
 * THEORY OF LIABILITY, ARISING OUT OF OR IN CONNECTION WITH THE USE
 * OR PERFORMANCE OF THIS SOFTWARE.
 * ---------
 */

# yyy many comment blocks are very out of date -- need thorough review
# yyy make it so that http://uclibs.org/PID/foo maps to
#     ark.cdlib.org/ark:/13030/xzfoo  [ requirement from SCP meeting May 2004]
# yyy use "wantarray" function to return either number or message
#     when bailing out.
# yyy add cdlpid doc to pod ?
# yyy write about comparison with PURLs
# yyy check chars, authentication, ordinal stored in metadata
# yyy implement mod 4/8/16 distribution within large counter regions?
# yyy implement count-down counters as well as count-up?
# yyy make a shadow DB

# yyy upgrade ark-service and ERC.pm (which still use PDB.pm)

# yyy bindallow(), binddeny() ????

/**
 * Create and manage noids.
 */
class Noid
{
    const VERSION = '1.0.0-0.424-php';

    const NOLIMIT = -1;
    const SEQNUM_MIN = 1;
    const SEQNUM_MAX = 1000000;

    /**
     * The database must hold nearly arbitrary user-level identifiers
     * alongside various admin variables.  In order not to conflict, we
     * require all admin variables to start with ":/", eg, ":/oacounter".
     * We use "$R/" frequently as our "reserved root" prefix.
     *
     * Prefix for global top level of admin db variables
     *
     * @internal This is a constant unavailable from outside.
     * @var string
     */
    protected $_R = ':';

    /**
     * Global %opendbtab is a hash that maps a hashref (as key) to a database
     * reference.  At a minimum, we need opendbtab so that we avoid passing a
     * db reference to dbclose, which cannot do the final "untie" (see
     * "untie gotcha" documentation) while the caller's db reference is
     * still defined.
     *
     * @var array
     */
    protected $_opendbtab = array();

    /**
     * To iterate over all Noids in the database, use
     *
     * each %hash
     * return $db or null
     * $flags one of O_RDONLY, O_RDWR, O_CREAT
     */

    /**
     * Legal string should be 0123456789bcdfghjkmnpqrstvwxz
     *
     * @var string
     */
    public $legalstring;

    /**
     * Extended digits count.
     *
     * @var integer
     */
    public $alphacount;

    /**
     * Pure digit count.
     *
     * @var integer
     */
    public $digitcount;
    public $locktest = 0;

    /**
     * Legal values of $how for the bind() function.
     *
     * @var array
     */
    protected $_valid_hows = array(
        'new', 'replace', 'set',
        'append', 'prepend', 'add', 'insert',
        'delete', 'purge', 'mint', 'peppermint',
    );

    /**
     * Primes:
     *   2        3        5        7
     *  11       13       17       19
     *  23       29       31       37
     *  41       43       47       53
     *  59       61       67       71
     *  73       79       83       89
     *  97      101      103      107
     * 109      113      127      131
     * 137      139      149      151
     * 157      163      167      173
     * 179      181      191      193
     * 197      199      211      223
     * 227      229      233      239
     * 241      251      257      263
     * 269      271      277      281
     * 283      293      307      311
     * 313      317      331      337
     * 347      349      353      359
     * 367      373      379      383
     * 389      397      401      409
     * 419      421      431      433
     * 439      443      449      457
     * 461      463      467      479
     * 487      491      499      503  ...
     */

    /**
     * yyy other character subsets? eg, 0-9, a-z, and _  (37 chars, with 37 prime)
     *     this could be mask character 'w' ?
     * yyy there are 94 printable ASCII characters, with nearest lower prime = 89
     *     a radix of 89 would result in a huge, compact space with check chars
     *     mask character 'c' ?
     */

    /**
     * Extended digits array.  Maps ordinal value to ASCII character.
     *
     * @var array
     */
    protected $_xdig = array(
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'm', 'n',
        'p', 'q', 'r', 's', 't', 'v', 'w', 'x', 'z',
    );

    /**
     * Ordinal value hash for extended digits.  Maps ASCII characters to ordinals.
     *
     * @var array
     */
    protected $_ordxdig = array(
        '0' =>  0,  '1' =>  1,  '2' =>  2,  '3' =>  3,  '4' =>  4,
        '5' =>  5,  '6' =>  6,  '7' =>  7,  '8' =>  8,  '9' =>  9,

        'b' => 10,  'c' => 11,  'd' => 12,  'f' => 13,  'g' => 14,
        'h' => 15,  'j' => 16,  'k' => 17,  'm' => 18,  'n' => 19,

        'p' => 20,  'q' => 21,  'r' => 22,  's' => 23,  't' => 24,
        'v' => 25,  'w' => 26,  'x' => 27,  'z' => 28,
    );

    public function __construct()
    {
        // Initialize some public values.
        $this->legalstring = join('', $this->_xdig);
        $this->alphacount = strlen($this->legalstring);
        $this->digitcount = 10;
    }

    /**
     * Adds an error message for a database pointer/object.  If the message
     * pertains to a failed open, the pointer is null, in which case the
     * message gets saved to what essentially acts like a global (possible
     * threading conflict).
     *
     * @param string $noid
     * @param string $message
     * @return integer 1
     */
    public function addmsg($noid, $message)
    {
        $noid = $noid ?: ''; # act like a global in case $noid undefined
        $this->_opendbtab["msg/$noid"] .= $message . PHP_EOL;
        return 1;
    }

    /**
     * Returns accumulated messages for a database pointer/object.  If the
     * second argument is non-zero, also reset the message to the empty string.
     *
     * @param string $noid
     * @param string $reset
     * @return string
     */
    public function errmsg($noid, $reset)
    {
        $noid = $noid ?: ''; # act like a global in case $noid undefined
        $s = $this->_opendbtab["msg/$noid"];
        $reset and
            $this->_opendbtab["msg/$noid"] = '';
        return $s;
    }

    /**
     * Logs a message.
     *
     * @param string $noid
     * @param string $message
     * @return integer 1
     */
    public function logmsg($noid, $message)
    {
        $noid = $noid ?: ''; # act like a global in case $noid undefined
        $logfhandle = $this->_opendbtab["log/$noid"];
        defined($logfhandle) and
            print($logfhandle . ' ' . $message . PHP_EOL);
        # yyy file was opened for append -- hopefully that means always
        #     append even if others have appended to it since our last append;
        #     possible sync problems...
        return 1;
    }

    /**
     * Store a string in a file.
     *
     * @param string $fname
     * @param string $contents
     * @return integer 0 (error) or 1 (success)
     */
    protected function storefile($fname, $contents)
    {
        ! open(OUT, ">$fname") and
            return 0;
        print OUT $contents;
        close(OUT);
        return 1;
    }

    //=======================================================================
    // --- begin alphabetic listing (with a few exceptions) of functions ---
    //=======================================================================

    /**
     * Returns ANVL message on success, null on error.
     *
     * @param string $noid
     * @param string $contact
     * @param string $validate
     * @param string $how
     * @param string $id
     * @param string $elem
     * @param string $value
     * @return array|null
     */
     public function bind($noid, $contact, $validate, $how, $id, $elem, $value)
     {
        $R = &$this->_R;

        # yyy to add: incr, decr for $how;  possibly other ops (* + - / **)

        # Validate identifier and element if necessary.
        #
        # yyy to do: check $elem against controlled vocab
        #     (for errors more than for security)
        # yyy should this genonly setting be so capable of contradicting
        #     the $validate arg?
        $$noid["$R/genonly"] && $validate
            && ! validate($noid, '-', $id) and
                return
        or
        ! defined($id) || $id === '' and
            $this->addmsg($noid, 'error: bind needs an identifier specified.'),
            return
        ;
        ! defined($elem) || $elem === '' and
            $this->addmsg($noid, sprintf('error: "bind %s" requires an element name.', $how)),
            return;

        # Transform and place a "hold" (if "long" term and we're not deleting)
        # on a special identifier.  Right now that means a user-entrered Id
        # of the form :idmap/Idpattern.  In this case, change it to a database
        # Id of the form "$R/idmap/$elem", and change $elem to hold Idpattern;
        # this makes lookup faster and easier.
        #
        # First save original id and element names in $oid and $oelem to
        # use for all user messages; we use whatever is in $id and $elem
        # for actual database operations.
        #
        $oid = $id;
        $oelem = $elem;
        $hold = 0;
        if (substr($id, 0, 1) === ':') {
            if (!preg_match('|^:idmap/(.+)|', $id, $matches)) {
                $this->addmsg($noid, sprintf('error: %s: id cannot begin with ":" unless of the form ":idmap/Idpattern".', $oid));
                return;
            }
            ($id, $elem) = ("$R/idmap/$oelem", $matches[1]);
            $$noid["$R/longterm"] and
                $hold = 1;
        }
        # yyy transform other ids beginning with ":"?

        # Check circulation status.  Error if term is "long" and the id
        # hasn't been issued unless a hold was placed on it.
        #
        # If no circ record and no hold...
        if (! defined($$noid["$id\t$R/c"]) && ! exists($$noid["$id\t$R/h"])) {
            $$noid["$R/longterm"] and
                $this->addmsg($noid, sprintf('error: %s: "long" term disallows binding an unissued identifier unless a hold is first placed on it.', $oid)),
                return
            or
                $this->logmsg($noid, sprintf('warning: %s: binding an unissued identifier that has no hold placed on it.', $oid));
        }
        if (!in_array($how, $this->_valid_hows)) {
            $this->addmsg($noid, sprintf('error: bind how?  What does %s mean?', $how));
            return;
        }
        $peppermint = $how === 'peppermint';
        $peppermint and
            # yyy to do
            $this->addmsg($noid, 'error: bind "peppermint" not implemented.'),
            return;
        # YYY bind mint file Elem Value     -- put into FILE by itself
        # YYY bind mint stuff_into_big_file Elem Value -- cat into file
        if ($how === 'mint' || $how === 'peppermint') {
            $id !== 'new' and
                $this->addmsg($noid, 'error: bind "mint" requires id to be given as "new".'),
                return;
            ! ($id = $oid = mint($noid, $contact, $peppermint)) and
                return;
        }
        $how === 'delete' || $how === 'purge' and
            (defined($value) && $value === '' and
                $this->addmsg($noid, sprintf('error: why does "bind %s" have a supplied value (%s)?"', $how, $value)),
                return),
            $value = '',
            1
        or
        ! defined($value) and
            $this->addmsg($noid,
                sprintf('error: "bind %s %s" requires a value to bind.', $how, $elem),
            return
        ;
        # If we get here, $value is defined and we can use with impunity.

        dblock();
        if (! defined($$noid["$id\t$elem"])) {      # currently unbound
            if (in_array($how, array('replace', 'append', 'prepend', 'delete'))) and
                $this->addmsg($noid, sprintf('error: for "bind %s", "%s %s" must already be bound.', $how, $oid, $oelem)),
                dbunlock(),
                return;
            $$noid["$id\t$elem"] = '';  # can concatenate with impunity
        }
        else {                      # currently bound
            if (in_array($how, array('new', 'mint', 'peppermint'))) and
                $this->addmsg($noid, sprintf('error: for "bind %s", "%s %s" cannot already be bound.', $how, $oid, $oelem)),
                dbunlock(),
                return;
        }
        # We don't care about bound/unbound for:  set, add, insert, purge

        $oldlen = strlen($$noid["$id\t$elem"]);
        $newlen = strlen($value);
        $statmsg = sprintf('%s bytes written', $newlen);

        $how === 'delete' || $how === 'purge' and
            delete($$noid["$id\t$elem"]),
            $statmsg = "$oldlen bytes removed"
        or
        $how === 'add' || $how === 'append' and
            $$noid["$id\t$elem"] .= $value,
            $statmsg .= " to the end of $oldlen bytes",
        or
        $how === 'insert' || $how === 'prepend' and
            $$noid["$id\t$elem"] = $value . $$noid["$id\t$elem"],
            $statmsg .= " to the beginning of $oldlen bytes",
        or
            $$noid["$id\t$elem"] = $value,
            $statmsg .= ", replacing $oldlen bytes",
        ;
        $hold and exists($$noid["$id\t$elem"]) and ! hold_set($noid, $id) and
            $hold = -1; # don't just bail out -- we need to unlock

        # yyy $contact info ?  mainly for "long" term identifiers?
        dbunlock();

        return(
            # yyy should this $id be or not be $oid???
            # yyy should labels for Id and Element be lowercased???
            "Id:      $id
            Element: $elem
            Bind:    $how
            Status:  " . ($hold == -1 ? $this->errmsg($noid) : 'ok, ' . $statmsg) . PHP_EOL
        );
    }

    /**
     * Compute check character for given identifier.  If identifier ends in '+'
     * (plus), replace it with a check character computed from the preceding chars,
     * and return the modified identifier.  If not, isolate the last char and
     * compute a check character using the preceding chars; return the original
     * identifier if the computed char matches the isolated char, or null if not.
     *
     * User explanation:  check digits help systems to catch transcription
     * errors that users might not be aware of upon retrieval; while users
     * often have other knowledge with which to determine that the wrong
     * retrieval occurred, this error is sometimes not readily apparent.
     * Check digits reduce the chances of this kind of error.
     *
     * @todo ask Steve Silberstein (of III) about check digits?
     *
     * @param string $id
     * @return string
     */
    public function checkchar($id)
    {
        return
            if (! $id );
        $lastchar = substr($id, -1);
        $id = substr($id, 0, -1);
        $pos = 1;
        $sum = 0;
        foreach (str_split($id) as $c) {
            # if character is null, it's ordinal value is zero
            $sum += $pos * (defined($this->_ordxdig[$c]) ? $this->_ordxdig[$c] : 0);
            $pos++;
        }
        $checkchar = $this->_xdig[$sum % $this->alphacount];
        #print "RADIX=$this->alphacount, mod=", $sum % $this->alphacount, PHP_EOL;
        return $id . $checkchar
            if ($lastchar === '+' || $lastchar === $checkchar);
        return;       # must be request to check, but failed match
        # xxx test if check char changes on permutations
        # XXX include test of length to make sure < than 29 (R) chars long
        # yyy will this work for doi/handles?
    }

    /**
     * Returns an array of cleared ids and byte counts if $verbose is set,
     * otherwise returns an empty array.  Set $verbose when we want to report what
     * was cleared.  Admin bindings aren't touched; they must be cleared manually.
     *
     * We always check for bindings before issuing, because even a previously
     * unissued id may have been bound (unusual for many minter situations).
     *
     * Use dblock() before and dbunlock() after calling this routine.
     *
     * @param string $noid
     * @param string $id
     * @param string $verbose
     * @return array
     */
    protected function clear_bindings($noid, $id, $verbose)
    {
        $R = &$this->_R;

        $retvals = array();
        $db = $this->_opendbtab["bdb/$noid"];
        $cursor = $db->db_cursor();

        # yyy right now "$id\t" defines how we bind stuff to an id, but in the
        #     future that could change.  in particular we don't bind (now)
        #     anything to just "$id" (without a tab after it)
        $first = $id . "\t";
        $skip = 0;
        $done = 0;
        $key = $first;
        $value = 0;
        $status = $cursor->c_get($key, $value, DB_SET_RANGE);
        $status == 0 and
            $skip = preg_match("|^$first$R/|", $key),
            $done = !preg_match("|^$first|", $key),
        1 or
            $done = 1
        ;
        while (! $done) {
            ! $skip and $verbose and
                # if $verbose (ie, fetch), include label and
                # remember to strip "Id\t" from front of $key
                $key = preg_match('/^[^\t]*\t(.*)/', $key, $matches) ? $matches[1] : $key;
                $retvals[] = $key
                    . ': ' . sprintf('clearing %d bytes', strlen($value))),
                delete($$noid[$key]);
            $status = $cursor->c_get($key, $value, DB_NEXT);
            $status != 0 || !preg_match("/^$first/", $key) and
                $done = 1   # no more elements under id
            or
                $skip = preg_match("|^$first$R/|", $key)
            ;
        }
        unset($cursor);
        return $verbose ? $retvals : array();
    }

    /**
     * Returns a short printable message on success, null on error.
     *
     * @param string $dbdir
     * @param string $contact
     * @param string $template
     * @param string $term
     * @param string $naan
     * @param string $naa
     * @param string $subnaa
     * @return string|null
     */
    public function dbcreate ($dbdir, $contact, $template, $term, $naan, $naa, $subnaa)
    {
        $R = &$this->_R;

        $total = null;
        $noid = null;
        $dir = "$dbdir/NOID";
        $dbname = "$dir/noid.bdb";
        # yyy try to use "die" to communicate to caller (graceful?)
        # yyy how come tie doesn't complain if it exists already?

        if (file_exists($dbname)) {
            self::addmsg(null, sprintf('error: a NOID database already exists in %s.',
                    ($dbdir === '.' ? 'the current directory' : '"' . $dbdir . '"')). PHP_EOL
                . "\t" . 'To permit creation of a new minter, rename' . PHP_EOL
                . "\t" . 'or remove the entire NOID subdirectory.'),
            return;
        ! -d $dir && ! mkdir($dir) and
            $this->addmsg(null, sprintf("error: couldn't create database directory %s: %s", $dir, $!)),
            return;

        $prefix = null;
        $mask = null;
        $gen_type = null;
        $msg = null;
        $genonly = null;
        ! defined($template) and
            $genonly = 0,
            $template = '.zd'
        or
            $genonly = 1,           # not generated ids only
        ;
        $total = parse_template($template, $prefix, $mask, $gen_type, $msg);
        ! $total and
            $this->addmsg($noid, $msg),
            return;
        $synonym = 'noid' . ($genonly ? '_' . $msg : 'any');

        # Type check various parameters.
        #
        ! defined($contact) || trim($contact) == '' and
            $this->addmsg($noid, sprintf('error: contact (%s) must be non-empty.', $contact),
            return;

        $term = $term ?: '-';
        $term !== 'long' && $term !== 'medium'
                && $term !== '-' && $term !== 'short' and
            $this->addmsg($noid, sprintf('error: term (%s) must be either "long", "medium", "-", or "short".', $term)),
            return;

        ! defined($naa) and $naa = '';
        ! defined($naan) and $naan = '';
        ! defined($subnaa) and $subnaa = '';

        $term === 'long' &&
            (!strlen(trim($naan)) || !strlen(trim($naa)) || !strlen(trim($subnaa))) and
                $this->addmsg($noid, sprintf('error: longterm identifiers require an NAA Number, NAA, and SubNAA.')),
                return;
        # xxx should be able to check naa and naan live against registry
        # yyy code should invite to apply for NAAN by email to ark@cdlib.org
        # yyy ARK only? why not DOI/handle?
        $term === 'long' && !preg_match('/\d\d\d\d\d/', $naan) and
            $this->addmsg($noid, sprintf('error: term of "long" requires a 5-digit NAAN (00000 if none), and non-empty string values for NAA and SubNAA.')),
            return;

        # Create log and logbdb files from scratch and make them writable
        # before calling dbopen().
        #
        ! storefile("$dir/log", '') || ! chmod(0666, "$dir/log") and
            $this->addmsg(null, sprintf("Couldn't chmod log file: %s", $!)),
            return;
        ! storefile("$dir/logbdb", '') || ! chmod(0666, "$dir/logbdb") and
            $this->addmsg(null, sprintf("Couldn't chmod logbdb file: %s", $!)),
            return;
        ! ($noid = dbopen($dbname, DB_CREATE)) and
            $this->addmsg(null, sprintf("can't create database file: %s", $!)),
            return;
        $this->logmsg($noid, $template
            ? sprintf('Creating database for template "%s".', $template)
            : sprintf('Creating database for bind-only minter.'));

        # Database info
        # yyy should be using db-> ops directly (for efficiency and?)
        #     so we can use DB_DUP flag
        $$noid["$R/naa"] = $naa;
        $$noid["$R/naan"] = $naan;
        $$noid["$R/subnaa"] = $subnaa || '';

        $$noid["$R/longterm"] = ($term === 'long');
        $$noid["$R/wrap"] = ($term === 'short');     # yyy follow through

        $$noid["$R/template"] = $template;
        $$noid["$R/prefix"] = $prefix;
        $$noid["$R/mask"] = $mask;
        $$noid["$R/firstpart"] = ($naan ? $naan . '/' : '') . $prefix;
        $$noid["$R/addcheckchar"] = (boolean) preg_match('/k$/', $mask);    # boolean answer

        $$noid["$R/generator_type"] = $gen_type;
        $$noid["$R/genonly"] = $genonly;

        $$noid["$R/total"] = $total;
        $$noid["$R/padwidth"] = ($total == self::NOLIMIT ? 16 : 2) + strlen($mask);
            # yyy kludge -- padwidth of 16 enough for most lvf sorting

        # Some variables:
        #   oacounter   overall counter's current value (last value minted)
        #   oatop   overall counter's greatest possible value of counter
        #   held    total with "hold" placed
        #   queued  total currently in the queue
        $$noid["$R/oacounter"] = 0;
        $$noid["$R/oatop"] = $total;
        $$noid["$R/held"] = 0;
        $$noid["$R/queued"] = 0;

        $$noid["$R/fseqnum"] = self::SEQNUM_MIN;  # see queue() and mint()
        $$noid["$R/gseqnum"] = self::SEQNUM_MIN;  # see queue()
        $$noid["$R/gseqnum_date"] = 0;      # see queue()

        $$noid["$R/version"] = self::VERSION;

        # yyy should verify that a given NAAN and NAA are registered,
        #     and should offer to register them if not.... ?

        # Capture the properties of this minter.
        #
        # There are seven properties, represented by a string of seven
        # capital letters or a hyphen if the property does not apply.
        # The maximal string is GRANITE (we first had GRANT, then GARNET).
        # We don't allow 'l' as an extended digit (good for minimizing
        # visual transcriptions errors), but we don't get a chance to brag
        # about that here.
        #
        # Note that on the Mohs mineral hardness scale from 1 - 10,
        # the hardest is diamonds (which are forever), but granites
        # (combinations of feldspar and quartz) are 5.5 to 7 in hardness.
        # From http://geology.about.com/library/bl/blmohsscale.htm ; see also
        # http://www.mineraltown.com/infocoleccionar/mohs_scale_of_hardness.htm
        #
        # These are far from perfect measures of identifier durability,
        # and of course they are only from the assigner's point of view.
        # For example, an alphabetical restriction doesn't guarantee
        # opaqueness, but it indicates that semantics will be limited.
        #
        # yyy document that (I)mpressionable has to do with printing, does
        #     not apply to general URLs, but does apply to phone numbers and
        #     ISBNs and ISSNs
        # yyy document that the opaqueness test is English-centric -- these
        #     measures work to some extent in English, but not in Welsh(?)
        #     or "l33t"
        # yyy document that the properties are numerous enough to look for
        #     a compact acronym, that the choice of acronym is sort of
        #     arbitrary, so (GRANITE) was chosen since it's easy to remember
        #
        # $pre and $msk are in service of the letter "A" below.
        $pre = preg_replace('/[a-z]/i', 'e', $prefix);
        $msk = preg_replace('/k/', 'e', $mask);
        $msk = preg_replace('/^ze/', 'zeeee', $msk);       # initial 'e' can become many later on

        $properties = ($naan !== '' && $naan !== '00000' ? 'G' : '-')
            . ($gen_type === 'random' ? 'R' : '-')
            # yyy substr is supposed to cut off first char
            . ($genonly && !preg_match('/eee/', $pre . substr($msk, 1)) ? 'A' : '-')
            . ($term === 'long' ? 'N' : '-')
            . ($genonly && !preg_match('/-/', $prefix) ? 'I' : '-')
            . ($$noid["$R/addcheckchar"] ? 'T' : '-')
            # yyy "E" mask test anticipates future extensions to alphabets
            . ($genonly && (preg_match('/[aeiouy]/i', $prefix) || preg_match('/[^rszdek]/', $mask))
                ? '-' : 'E')        # Elided vowels or not
        ;
        $$noid["$R/properties"] = $properties;

        # Now figure out "where" element.
        #
        $host = gethostname();

        #   $child_process_id = null;
        #   if (!isset($child_process_id = open(CHILD, '-|'))) {
        #       die "unable to start child process, $!, stopped";
        #   }
        #   if ($child_process_id == 0) {
        #       # We are in the child.  Set the PATH environment variable.
        #       $ENV["PATH"] = "/bin:/usr/bin";
        #       # Run the command we want, with its STDOUT redirected
        #       # to the pipe that goes back to the parent.
        #       exec "/bin/hostname";
        #       die "unable to execute \"/bin/hostname\", $!, stopped";
        #   }
        #   else {
        #       # We are in the parent, and the CHILD file handle is
        #       # the read end of the pipe that has its write end as
        #       # STDOUT of the child.
        #       $host = <CHILD>;
        #       close(CHILD);
        #       chomp $host;
        #   }

        $cwd = $dbdir;   # by default, assuming $dbdir is absolute path
        if (substr($dbdir, 0, 1) !== '/') {
            $cwd = getcwd() . '/' . $dbdir;
        }

        # Adjust some empty values for short-term display purposes.
        #
        $naa = $naa ?: 'no Name Assigning Authority';
        $subnaa = $subnaa ?: 'no sub authority';
        $naan = $naan ?: 'no NAA Number';

        # Create a human- and machine-readable report.
        #
        $p = str_split($properties);         # split into letters
        $p = array_map(
            function ($v) { return $v == '-' ? '_ not' : '_____'; },
            $p);
        $random_sample = null;          # null on purpose
        $total == self::NOLIMIT and
            $random_sample = intval(rand(0, 10)); # first sample less than 10
        $sample1 = $this->sample($noid, $random_sample);
        $total == self::NOLIMIT and
            $random_sample = intval(rand(0, 100000)); # second sample bigger
        $sample2 = $this->sample($noid, $random_sample);

        $htotal = $total == self::NOLIMIT ? 'unlimited' : human_num($total);
        $what = ($total == self::NOLIMIT ? 'unlimited' : $total)
            . ' ' . sprintf('%s identifiers of form %s', $gen_type, $template) . PHP_EOL
            . '       ' . 'A Noid minting and binding database has been created that will bind ' . PHP_EOL
            . '       ' . ($genonly ? '' : 'any identifier ') . 'and mint ' . ($total == self::NOLIMIT
                ? sprintf('an unbounded number of identifiers') . PHP_EOL
                    . '       '
                : sprintf('%s identifiers', $htotal) . ' ')
            . sprintf('with the template "%s".', $template) . PHP_EOL
            . '       ' . sprintf('Sample identifiers would be "%s" and "%s".', $sample1, $sample2) . PHP_EOL
            . '       ' . sprintf('Minting order is %s.', $gen_type);

        $$noid["$R/erc"] =
"# Creation record for the identifier generator in NOID/noid.bdb.
#
erc:
who:       $contact
what:      $what
when:      " . temper() . "
where:     $host:$cwd
Version:   Noid " . self::VERSION . "
Size:      " . ($total == self::NOLIMIT ? "unlimited" : $total) . "
Template:  " . (!$template
                 ? '(:none)'
                 : $template . "
       A suggested parent directory for this template is \"$synonym\".  Note:
       separate minters need separate directories, and templates can suggest
       short names; e.g., the template \"xz.redek\" suggests the parent directory
       \"noid_xz4\" since identifiers are \"xz\" followed by 4 characters.") . "
Policy:    (:$properties)
       This minter's durability summary is (maximum possible being \"GRANITE\")
         \"$properties\", which breaks down, property by property, as follows.
          ^^^^^^^
          |||||||_$p[6] (E)lided of vowels to avoid creating words by accident
          ||||||_$p[5] (T)ranscription safe due to a generated check character
          |||||_$p[4] (I)mpression safe from ignorable typesetter-added hyphens
          ||||_$p[3] (N)on-reassignable in life of Name Assigning Authority
          |||_$p[2] (A)lphabetic-run-limited to pairs to avoid acronyms
          ||_$p[1] (R)andomly sequenced to avoid series semantics
          |_$p[0] (G)lobally unique within a registered namespace (currently
                     tests only ARK namespaces; apply for one at ark@cdlib.org)
Authority: $naa | $subnaa
NAAN:      $naan
";

        ! storefile("$dir/README", $$noid["$R/erc"])
            and return;
        # yyy useful for quick info on a minter from just doing 'ls NOID'??
        #          storefile("$dir/T=$prefix.$mask", "foo\n");

        $report = sprintf('Created:   minter for %s', $what)
            . '  ' . sprintf('See %s/README for details.', $dir) . PHP_EOL;

        ! $template and
            dbclose($noid),
            return($report);

        init_counters($noid);
        dbclose($noid);
        return($report);
    }

    /**
     * Report values according to level.
     *
     * @param string $noid
     * @param string $level Possible values:
     * - "brief" (default): user vals and interesting admin vals
     * - "full": user vals and all admin vals
     * - "dump": all vals, including all identifier bindings
     * @return integer 0 (error) or 1 (success)
     */
    public function dbinfo ($noid, $level)
    {
        $R = &$this->_R;

        $db = $this->_opendbtab["bdb/$noid"];
        $cursor = $db->db_cursor();
        $key = $R . '/';
        $value = 0;
        if ($level === 'dump') {
            print "$key: $value" . PHP_EOL
                while ($cursor->c_get($key, $value, DB_NEXT) == 0);
            return 1;
        }
        $status = $cursor->c_get($key, $value, DB_SET_RANGE);
        if ($status) {
            $this->addmsg($noid, sprintf('c_get status/errno (%s/%s)', $status, $!));
            return 0;
        }
        if (strpos($key, "$R/$R") === 0) {
            print 'User Assigned Values' . PHP_EOL;
            print "  $key: $value" . PHP_EOL;
            while ($cursor->c_get($key, $value, DB_NEXT) == 0) {
                last
                    if (strpos($key, "$R/$R") !== 0);
                print "  $key: $value" . PHP_EOL;
            }
            print PHP_EOL;
        }
        print 'Admin Values' . PHP_EOL;
        print "  $key: $value" . PHP_EOL;
        while ($cursor->c_get($key, $value, DB_NEXT) == 0) {
            last
                if (strpos($key, "$R/") !== 0);
            print "  $key: $value" . PHP_EOL
                if ($level === 'full'
                    or !preg_match("|^$R/c\d|", $key)
                    && strpos($key, "$R/saclist") !== 0
                    && strpos($key, "$R/recycle/") !== 0;
        }
        print PHP_EOL;
        unset($cursor);
        return 1;
    }

    /**
     * BerkeleyDB features.  For now, lock before tie(), unlock after untie().
     *
     * @todo eventually we would like to do fancy fine-grained locking with
     * @return integer 1.
     */
    protected function dblock()
    {
        // Placeholder.
        return 1;
    }

    /**
     * BerkeleyDB features.  For now, lock before tie(), unlock after untie().
     *
     * @todo eventually we would like to do fancy fine-grained locking with
     * @return integer 1.
     */
    protected function dbunlock()
    {
        // Placeholder.
        return 1;
    }

    /**
     * Returns noid: a listref
     *
     * @param string $dbname
     * @param integer $flags
     * Can be DB_RDONLY, DB_CREATE, or 0 (for read/write, the default)
     * @return
     */
    public function dbopen($dbname, $flags)
    {
        # yyy to test: can we now open more than one noid at once?

        $env = null;
        $envhome = null;
        $envhome = preg_replace('|[^/]+$|', '', $dbname); # path ending in "NOID/"
        ! -d $envhome and
            $this->addmsg(null, sprintf('%s not a directory', $envhome)),
            return;
        # yyy probably these envflags are overkill right now
        $envflags = DB_INIT_LOCK | DB_INIT_TXN | DB_INIT_MPOOL;
        #my $envflags = DB_INIT_CDB | DB_INIT_MPOOL;
        ($flags & DB_CREATE) and
            $envflags |= DB_CREATE;
        $envargs = array(
            '-Home' => $envhome,
            '-Flags' => $envflags,
            '-Verbose' => 1,
        );

        # If it exists and is writable, use log file to inscribe BDB errors.
        #
        $logfile = null;
        $logfhandle = null;
        $log_opened = null;
        $logbdb = null;

        $logfile = $envhome . 'log';
        $log_opened = open($logfhandle, '>>' . $logfile);
        $logbdb = $envhome . 'logbdb';
        -w $logbdb and
            $envargs['-ErrFile'] = $logbdb;
        # yyy should we complain if can't open log file?

        $env = new BerkeleyDB::Env($envargs);
        ! defined($env) and
            $this->addmsg(null, sprintf('no "Env" object (%s)', $BerkeleyDB::Error)),
            return;

        #=for deleting
        #
        #   print "OK so far\n"; exit(0);
        #   if ($flags && DB_CREATE) {
        #       # initialize environment files
        #       print sprintf('envhome=%s', $envhome) . PHP_EOL;
        #       $env = new BerkeleyDB::Env($envargs);
        #       ! isset($env) and
        #           $this->addmsg(null,
        #               sprintf('no "Env" object (%s)', $BerkeleyDB::Error)),
        #           return;
        #   }
        #   else {
        #       print sprintf('flags=%s', $flags) . PHP_EOL;
        #   }
        #   print 'OK so far' . PHP_EOL; exit(0);
        #   $env = new BerkeleyDB::Env($envargs);
        #   if (!isset($env)) {
        #       die sprintf('unable to get a "BerkeleyDB::Env" object (%s), stopped', $BerkeleyDB::Error);
        #   }
        #
        #=cut

        $noid = array();      # eventual minter database handle

        # For now we use simple database-level file locking with a timeout.
        # Unlocking is implicit when the NOIDLOCK file handle is closed
        # either explicitly or upon process termination.
        #
        $lockfile = $envhome . "lock";
        $timeout = 5;    # max number of seconds to wait for lock
        $locktype = ($flags & DB_RDONLY) ? LOCK_SH : LOCK_EX;

        ! sysopen(NOIDLOCK, $lockfile, O_RDWR | O_CREAT) and
            $this->addmsg(null, sprintf('cannot open "%s": %s', $lockfile, $!),
            return;
        eval {

            local $SIG[ALRM] = sub {
                 die(sprintf('lock timeout after %s seconds; consider removing "%s"', $timeout, $lockfile) . PHP_EOL);
            };
            alarm $timeout;     # alarm goes off in $timeout seconds
            eval {  # yyy if system has no flock, say in dbcreate profile?
                flock(NOIDLOCK, $locktype)  # blocking lock
                    or die(sprintf('cannot flock: %s'; $!));
            };
            alarm 0;        # cancel the alarm
            die $@ if $@;       # re-raise the exception
        }

        alarm 0;            # race condition protection
        if ($@) {           # re-raise the exception
            $this->addmsg(null, sprintf('error: %s', $@));
            return;
        }

        $db = tie(%$noid, "BerkeleyDB::Btree", array(
                '-Filename' => "noid.bdb",    # env has path to it
                '-Flags' => $flags,
        ## yyy  '-Property' => DB_DUP,
                '-Env' => $env))
            or $this->addmsg(null, sprintf('tie failed on %s: %s', $dbname, $BerkeleyDB::Error))
                and return;
        # yyy how to set error code or return string?
        #   or die("Can't open database file: $!\n");
        #print "dbopen: returning hashref=$noid, db=$db\n";
        $this->_opendbtab["bdb/$noid"] = $db;
        $this->_opendbtab["msg/$noid"] = '';
        $this->_opendbtab["log/$noid"] = ($log_opened ? $logfhandle : null);

        $locktest and
            print(sprintf('locktest: holding lock for %s seconds...', $locktest) . PHP_EOL),
            sleep($locktest);

        return $noid;
    }

    /**
     * Call with number of seconds to sleep at end of each open.
     * This exists only for the purpose of testing the locking mechanism.
     *
     * @param string $sleepvalue
     * @return integer 1
     */
    public function locktest($sleepvalue)
    {
        // Set global variable for locktest.
        $locktest = $sleepvalue;
        return 1;
    }

    /**
     * Close database.
     *
     * @param string $noid
     * @return void
     */
    public function dbclose($noid)
    {
        undef $this->_opendbtab["msg/$noid"];
        defined($this->_opendbtab["log/$noid"]) and
            close($this->_opendbtab["log/$noid"]);
        undef $this->_opendbtab["bdb/$noid"];
        untie %$noid;
        // Let go of lock.
        close NOIDLOCK;
    }

    /**
     * get next value and, if no error, change the 2nd and 3rd parameters and
     * return 1, else return 0.  To start at the beginning, the 2nd parameter,
     * key (key), should be set to zero by caller, who might do this:
     * $key = 0; while (each($noid, $key, $value)) { ... }
     * The 3rd parameter will contain the corresponding value.
     *
     * @todo is this needed? in present form?
     * @param string $noid
     * @param string $key
     * @param string $value
     * @return integer 0 (error) or 1 (success)
     */
    protected function eachnoid($noid, $key, $value)
    {
        # yyy check that $db is tied?  this is assumed for now
        # yyy need to get next non-admin key/value pair
        $db = $this->_opendbtab["bdb/$noid"];
        #was: $flag = ($key ? R_NEXT : R_FIRST);
        # fix from Jim Fullton:
        $flag = ($key ? DB_NEXT : DB_FIRST);
        $cursor = $db->db_cursor();
        if ($cursor->c_get($key, $value, $flag)) {
            return 0;
        }
        $_[1] = $key;
        $_[2] = $value;
        return 1;
    }

    /**
     * A no-op function to call instead of checkchar().
     */
    protected function echo()
    {
        return $_[0];
    }

    /**
     * Fetchs.
     *
     * @todo do we need to be able to "get/fetch" with a discriminant,
     *       eg, for smart multiple resolution??
     *
     * @param string $noid
     * @param integer $verbose is 1 if we want labels, 0 if we don't
     * @param string $id
     * @param array $elems
     * @return string
     */
    public function fetch($noid, $verbose, $id, $elems)
    {
        $R = &$this->_R;

        ! defined($id) and
            $this->addmsg($noid, sprintf('error: %s requires that an identifier be specified.', $verbose ? 'fetch' : 'get')),
            return;

        $hdr = '';
        $retval = '';
        $verbose and $hdr = "id:    $id"
            . (exists($$noid["$id\t$R/h"]) ? ' hold ': '') . PHP_EOL
            . (validate($noid, '-', $id) ? '' : $this->errmsg($noid) . PHP_EOL)
            . "Circ:  "
            . ($$noid["$id\t$R/c"]
                ? $$noid["$id\t$R/c"]
                : 'uncirculated')
            . PHP_EOL;

        $db = $this->_opendbtab["bdb/$noid"];
        $cursor = $db->db_cursor();

        if (empty($elems)) {  # No elements were specified, so find them.
            $first = $id . "\t";
            $skip = 0;
            $done = 0;
            $key = $first;
            $value = 0;
            $status = $cursor->c_get($key, $value, DB_SET_RANGE);
            $status == 0 and
                $skip = strpos($key, "$first$R/") === 0,
                $done = strpos($key, $first) !== 0,
            1 or
                $done = 1
            ;
            while (! $done) {
                ! $skip and
                    # if $verbose (ie, fetch), include label and
                    # remember to strip "Id\t" from front of $key
                    $retval .= ($verbose
                            ? (preg_match('/^[^\t]*\t(.*)/', $key, $matches) ? $matches[1] : $key) . ': '
                            : '')
                        . $value . PHP_EOL;
                $status = $cursor->c_get($key, $value, DB_NEXT);
                $status != 0 || strpos($key, $first) !== 0 and
                    $done = 1   # no more elements under id
                or
                    $skip = strpos($key, "$first$R/") === 0,
                ;
            }
            unset($cursor);
            ! $retval and
                $this->addmsg($noid, $hdr
                    . "note: no elements bound under $id."),
                return;
            return($hdr . $retval);
        }

        # yyy should this work for elem names with regexprs in them?
        # XXX idmap won't bind with longterm ???
        $idmapped = null;
        foreach ($elems as $elem) {
            $$noid["$id\t$elem"] and
                ($verbose and
                    $retval .= "$elem: "),
                $retval .= $$noid["$id\t$elem"] . "\n"
            or
                $idmapped = id2elemval($cursor, $verbose, $id, $elem),
                ($verbose and
                    $retval .= ($idmapped
                            ? $dmapped . PHP_EOL . 'note: previous result produced by :idmap'
                            : sprintf('error: "%s %s" is not bound.', $id, $elem)
                        . PHP_EOL
                or
                    $retval .= $idmapped . PHP_EOL
                )
            ;
        }
        unset($cursor);
        return $hdr . $retval;
    }

    /**
     * Generate the actual next id to give out.  May be randomly or sequentially
     * selected.  This routine should not be called if there are ripe recyclable
     * identifiers to use.
     *
     * This routine and n2xdig comprise the real heart of the minter software.
     *
     * @param string $noid
     * @return string
     */
    protected function genid($noid)
    {
        $R = &$this->_R;

        dblock();

        # Variables:
        #   oacounter   overall counter's current value (last value minted)
        #   oatop   overall counter's greatest possible value of counter
        #   saclist (sub) active counters list
        #   siclist (sub) inactive counters list
        #   c$n/value   subcounter name's ($scn) value

        $oacounter = $$noid["$R/oacounter"];

        # yyy what are we going to do with counters for held? queued?

        if ($$noid["$R/oatop"] != self::NOLIMIT && $oacounter >= $$noid["$R/oatop"]) {

            # Critical test of whether we're willing to re-use identifiers
            # by re-setting (wrapping) the counter to zero.  To be extra
            # careful we check both the longterm and wrap settings, even
            # though, in theory, wrap won't be set if longterm is set.
            #
            if ($$noid["$R/longterm"] || ! $$noid["$R/wrap"]) {
                dbunlock();
                $m = sprintf('error: identifiers exhausted (stopped at %s).', $$noid["$R/oatop"]);
                $this->addmsg($noid, $m);
                $this->logmsg($noid, $m);
                return;
            }
            # If we get here, term is not "long".
            $this->logmsg($noid, sprintf('%s: Resetting counter to zero; previously issued identifiers will be re-issued', temper()));
            if ($$noid["$R/generator_type"] === 'sequential') {
                $$noid["$R/oacounter"] = 0;
            }
            else {
                init_counters($noid);   # yyy calls dblock -- problem?
            }
            $oacounter = 0;
        }
        # If we get here, the counter may actually have just been reset.

        # Deal with the easy sequential generator case and exit early.
        #
        if ($$noid["$R/generator_type"] === 'sequential') {
            $id = &n2xdig($$noid["$R/oacounter"], $$noid["$R/mask"]);
            $$noid["$R/oacounter"]++;   # incr to reflect new total
            dbunlock();
            return $id;
        }

        # If we get here, the generator must be of type "random".
        #
        $saclist = explode(' ', $$noid["$R/saclist"]);
        $len = count($saclist);
        if ($len < 1) {
            dbunlock();
            $this->addmsg($noid, sprintf('error: no active counters panic, but %s identifiers left?', $oacounter));
            return;
        }
        $randn = intval(rand(0, $len));    # pick a specific counter name
        $sctrn = $saclist[$randn];   # at random; then pull its $n
        $n = substr($sctrn, 1);  # numeric equivalent from the name
        #print "randn=$randn, sctrn=$sctrn, counter n=$n\t";
        $sctr = $$noid["$R/${sctrn}/value"]; # and get its value
        $sctr++;                # increment and
        $$noid["$R/${sctrn}/value"] = $sctr;    # store new current value
        $$noid["$R/oacounter"]++;       # incr overall counter - some
                            # redundancy for sanity's sake

        # deal with an exhausted subcounter
        if ($sctr >= $$noid["$R/${sctrn}/top"]) {
            $c = '';
            $modsaclist ='';
            # remove from active counters list
            foreach ($saclist as $c) {     # drop $sctrn, but add it to
                next if ($c === $sctrn);     # inactive subcounters
                $modsaclist .= $c . ' ';
            }
            $$noid["$R/saclist"] = $modsaclist;     # update saclist
            $$noid["$R/siclist"] .= $sctrn;      # and siclist
            #print "===> Exhausted counter $sctrn\n";
        }

        # $sctr holds counter value, $n holds ordinal of the counter itself
        $id = &n2xdig(
                $sctr + ($n * $$noid["$R/percounter"]),
                $$noid["$R/mask"]);
        dbunlock();
        return $id;
    }

    /**
     * Identifier admin info is stored in three places:
     *
     *    id\t:/h    hold status: if exists = hold, else no hold
     *    id\t:/c    circulation record, if it exists, is
     *           circ_status_history_vector|when|contact(who)|oacounter
     *           where circ_status_history_vector is a string of [iqu]
     *           and oacounter is current overall counter value, FWIW;
     *           circ status goes first to make record easy to update
     *    id\t:/p    pepper
     *
     * @param string $noid
     * @param string $id
     * @return string
     * Returns a single letter circulation status, which must be one
     * of 'i', 'q', or 'u'.  Returns the empty string on error.
     */
    protected function get_circ_svec($noid, $id)
    {
        $R = &$this->_R;

        $circ_rec = $$noid["$id\t$R/c"];
        ! defined($circ_rec) and
            return '';

        # Circulation status vector (string of letter codes) is the 1st
        # element, elements being separated by '|'.  We don't care about
        # the other elements for now because we can find everything we
        # need at the beginning of the string (without splitting it).
        # Let errors hit the log file rather than bothering the caller.
        #
        $circ_svec = explode('|', $circ_rec);
        $circ_svec = reset($circ_svec);

        ! defined($circ_svec) || $circ_svec === '' and
            $this->logmsg($noid, sprintf('error: id %s has no circ status vector -- circ record is %s', $id, $circ_rec)),
            return '';
        !preg_match('/^([iqu])[iqu]*$/', $circ_svec, $matches) and
            $this->logmsg($noid, sprintf('error: id %s has a circ status vector containing letters other than "i", "q", or "u" -- circ record is %s', $id, $circ_rec)),
            return '';
        return $matches[1];
    }

    /**
     * As a last step of issuing or queuing an identifier, adjust the circulation
     * status record.  We place a "hold" if we're both issuing an identifier and
     * the minter is for "long" term ids.  If we're issuing, we also purge any
     * element bindings that exist; this means that a queued identifier's bindings
     * will by default last until it is re-minted.
     *
     * The caller must know what they're doing because we don't check parameters
     * for errors; this routine is not externally visible anyway.  Returns the
     * input identifier on success, or null on error.
     *
     * @param string $noid
     * @param string $id
     * @param string $circ_svec
     * @param string $date
     * @param string $contact
     * @return string|null
     */
    protected function set_circ_rec($noid, $id, $circ_svec, $date, $contact)
    {
        $R = &$this->_R;

        $status = 1;
        $circ_rec = "$circ_svec|$date|$contact|" . $$noid["$R/oacounter"];

        # yyy do we care what the previous circ record was?  since right now
        #     we just clobber without looking at it

        dblock();

        # Check for and clear any bindings if we're issuing an identifier.
        # We ignore the return value from clear_bindings().
        # Replace or clear admin bindings by hand, including pepper if any.
        #       yyy pepper not implemented yet
        # If issuing a longterm id, we automatically place a hold on it.
        #
        substr($circ_svec, 0, 1) == 'i' and
            clear_bindings($noid, $id, 0),
            delete($$noid["$id\t$R/p"]),
            ($$noid["$R/longterm"] and
                $status = hold_set($noid, $id)),
        ;
        $$noid["$id\t$R/c"] = $circ_rec;

        dbunlock();

        # This next logmsg should account for the bulk of the log when
        # longterm identifiers are in effect.
        #
        $$noid["$R/longterm"] and
            $this->logmsg($noid, sprintf('m: %s%s', $circ_rec, $status ? '' : ' -- hold failed'));

        ! $status and           # must be an error in hold_set()
            return;
        return $id;
    }

    /**
     * Get the value of any named internal variable (prefaced by $R)
     * given an open database reference.
     *
     * @param string $noid
     * @param string $varname
     * @return string
     */
    public function getnoid($noid, $varname)
    {
        $R = &$this->_R;

        return $$noid["$R/$varname"];
    }

    #=for deleting
    /**
     * Simple ancillary counter that we currently use to pair a sequence number
     * with each minted identifier.  However, these are independent actions.
     * The direction parameter is negative, zero, or positive to count down,
     * reset, or count up upon call.  Returns the current counter value.
     *
     * @internal should we make it do zero-padding on the left to a fixed width
     * determined by number of digits in the total?
     *
     * @param string $noid
     * @param string $direction
     * @return string
     */
    /*
    protected function count($noid, $direction)
    {
        $R = &$this->_R;

       $direction > 0
           and return ++$$noid["$R/seqnum"];
       $direction < 0
           and return --$$noid["$R/seqnum"];
       # $direction must == 0
       return $$noid["$R/seqnum"] = 0;
    }
    */
    #=cut

    /**
     * A hold may be placed on an identifier to keep it from being minted/issued.
     *
     * @param string $noid
     * @param string $contact
     * @param string $on_off
     * @param array $ids
     * @return integer 0 (error) or 1 (success)
     * Sets errmsg() in either case.
     */
    public function hold($noid, $contact, $on_off, array $ids)
    {
        $R = &$this->_R;

        # yyy what makes sense in this case?
        #! $$noid["$R/template"] and
        #   $this->addmsg($noid,
        #       'error: holding makes no sense in a bind-only minter.'),
        #   return 0;
        ! defined($contact) and
            $this->addmsg($noid, "error: contact undefined"),
            return 0;
        ! defined($on_off) and
            $this->addmsg($noid, 'error: hold "set" or "release"?'),
            return 0;
        ! empty($ids) and
            $this->addmsg($noid, 'error: no Id(s) specified'),
            return 0;
        $on_off !== 'set' && $on_off !== 'release' and
            $this->addmsg($noid, sprintf('error: unrecognized hold directive (%s)', $on_off)),
            return 0;

        $release = $on_off === 'release';
        # yyy what is sensible thing to do if no ids are present?
        $iderror = '';
        $$noid["$R/genonly"] and
            $iderror = validate($noid, '-', $ids) and
            if (substr($iderror, 0, 6) != 'error:') {
                $iderror = '';
            }
        $iderror and
            $this->addmsg($noid, sprintf('error: hold operation not started -- one or more ids did not validate: %s', PHP_EOL . $iderror),
            return 0;
        $status;
        $n = 0;
        foreach ($ids as $id) {
            if ($release) {     # no hold means key doesn't exist
                $this->logmsg($noid, sprintf('%s %s: releasing hold', temper(), $id))
                    if ($$noid["$R/longterm"]);
                dblock();
                $status = hold_release($noid, $id);
            }
            else {          # "hold" means key exists
                $this->logmsg($noid, sprintf('%s %s: placing hold', temper(), $id))
                    if ($$noid["$R/longterm"]);
                dblock();
                $status = hold_set($noid, $id);
            }
            dbunlock();
            ! $status and
                return 0;
            $n++;           # xxx should report number

            # Incr/Decrement for each id rather than by count($ids);
            # if something goes wrong in the loop, we won't be way off.

            # XXX should we refuse to hold if "long" and issued?
            #     else we cannot use "hold" in the sense of either
            #     "reserved for future use" or "reserved, never issued"
            #
        }
        $this->addmsg($noid, $n == 1 ? sprintf('ok: 1 hold placed') : sprintf('ok: %s holds placed', $n));
        return 1;
    }

    /**
     * Returns 1 on success, 0 on error.  Use dblock() before and dbunlock()
     * after calling this routine.
     *
     * @todo don't care if hold was in effect or not
     *
     * @param string $noid
     * @param string $id
     * @return integer 0 (error) or 1 (success)
     */
    public function hold_set($noid, $id)
    {
        $R = &$this->_R;

        $$noid["$id\t$R/h"] = 1;        # value doesn't matter
        $$noid["$R/held"]++;
        if ($$noid["$R/total"] != self::NOLIMIT   # ie, if total is non-zero
                && $$noid["$R/held"] > $$noid["$R/oatop"]) {
            $m = sprintf('error: hold count (%s) exceeding total possible on id %s', $$noid["$R/held"], $id);
            $this->addmsg($noid, $m);
            $this->logmsg($noid, $m);
            return 0;
        }
        return 1;
    }

    /**
     * Returns 1 on success, 0 on error.  Use dblock() before and dbunlock()
     * after calling this routine.
     *
     * @todo don't care if hold was in effect or not
     *
     * @param string $noid
     * @param string $id
     * @return integer 0 (error) or 1 (success)
     */
    public function hold_release($noid, $id)
    {
        $R = &$this->_R;

        delete($$noid["$id\t$R/h"]);
        $$noid["$R/held"]--;
        if ($$noid["$R/held"] < 0) {
            $m = sprintf('error: hold count (%s) going negative on id %s', $$noid["$R/held"], $id);
            $this->addmsg($noid, $m);
            $this->logmsg($noid, $m);
            return 0;
        }
        return 1;
    }

    /**
     * Return printable form of an integer after adding commas to separate
     * groups of 3 digits.
     *
     * @param integer $num
     * @return string
     */
    protected function human_num($num)
    {
        return number_format($num);
    }

    /**
     * Return $elem: $val or error string.
     *
     * @param integer $cursor
     * @param string $verbose
     * @param string id
     * @param string $elem
     * @return string
     */
    protected function id2elemval($cursor, $verbose, $id, $elem)
    {
        $R = &$this->_R;

        $first = "$R/idmap/$elem\t";
        $key = $first;
        $value = 0;
        $status = $cursor->c_get($key, $value, DB_SET_RANGE);
        $status and
            return sprintf('error: id2elemval: c_get status/errno (%s/%s)', $status, $!);
        strpos($key, $first) !== 0 and
            return '';
        $pattern = null;
        $newval = null;
        while (1) { # exhaustively visit all patterns for this element
            $pattern = preg_match("|$first(.+)|", $key) ? $key : null;
            $newval = $id;
            if (!is_null($pattern)) {
                try {
                    # yyy kludgy use of unlikely delimiters (ascii 05: Enquiry)
                    $newval = preg_replace(chr(5) . $pattern . chr(5), $value, $newval);
                } catch (Exception $e) {
                    return sprintf('error: id2elemval eval: %s', $e->getMessage());
                }
                # replaced, so return
                return ($verbose ? $elem . ': ' : '') . $newval;
            }
            $cursor->c_get($key, $value, DB_NEXT) != 0 and
                return '';
            strpos($key, $first) !== 0 and       # no match and ran out of rules
                return '';
        }
    }

    /**
     * Initialize counters.
     *
     * @param string $noid
     * @return void
     */
    protected function init_counters($noid)
    {
        $R = &$this->_R;

        # Variables:
        #   oacounter   overall counter's current value (last value minted)
        #   saclist (sub) active counters list
        #   siclist (sub) inactive counters list
        #   c$n/value   subcounter name's ($n) value
        #   c$n/top subcounter name's greatest possible value

        dblock();

        $$noid["$R/oacounter"] = 0;
        $total = $$noid["$R/total"];

        $maxcounters = 293;      # prime, a little more than 29*10
        #
        # Using a prime under the theory (unverified) that it may help even
        # out distribution across the more significant digits of generated
        # identifiers.  In this way, for example, a method for mapping an
        # identifier to a pathname (eg, fk9tmb35x -> fk/9t/mb/35/x/, which
        # could be a directory holding all files related to the named
        # object), would result in a reasonably balanced filesystem tree
        # -- no subdirectories too unevenly loaded.  That's the hope anyway.

        $$noid["$R/percounter"] =   # max per counter, last has fewer
            intval($total / $maxcounters + 1);     # round up to be > 0

        $n = 0;
        $t = $total;
        $pctr = $$noid["$R/percounter"];
        $saclist = '';
        while ($t > 0) {
            $$noid["$R/c${n}/top"] = ($t >= $pctr ? $pctr : $t);
            $$noid["$R/c${n}/value"] = 0;       # yyy or 1?
            $saclist .= "c$n ";
            $t -= $pctr;
            $n++;
        }
        $$noid["$R/saclist"] = $saclist;
        $$noid["$R/siclist"] = '';
        $n--;

        dbunlock();

        #print "saclist: $$noid["$R/saclist"]\nfinal top: "
        #   . $$noid["$R/c${n}/top"] . PHP_EOL . "percounter=$pctr" . PHP_EOL;
        #foreach $c ($$saclist) {
        #   print "$c, ";
        #}
        #print "\n";
    }

    /**
     * This routine produces a new identifier by taking a previously recycled
     * identifier from a queue (usually, a "used" identifier, but it might
     * have been pre-recycled) or by generating a brand new one.
     *
     * The $contact should be the initials or descriptive string to help
     * track who or what was happening at time of minting.
     *
     * Returns null on error.
     *
     * @param string $noid
     * @param string $contact
     * @param string $pepper
     * @return string|null
     */
    public function mint($noid, $contact, $pepper = 0)
    {
        $R = &$this->_R;

        ! defined($contact) and
            $this->addmsg($noid, 'contact undefined'),
            return;

        ! $$noid["$R/template"] and
            $this->addmsg($noid, 'error: this minter does not generate identifiers (it does accept user-defined identifier and element bindings).'),
            return;
        # Check if the head of the queue is ripe.  See comments under queue()
        # for an explanation of how the queue works.
        #
        $currdate = temper();        # fyi, 14 digits long
        $first = "$R/q/";
        $db = $this->_opendbtab["bdb/$noid"];
        ! ($cursor = $db->db_cursor()) and
            $this->addmsg($noid, "couldn't create cursor"),
            return;

        # The following is not a proper loop.  Normally it should run once,
        # but several cycles may be needed to weed out anomalies with the id
        # at the head of the queue.  If all goes well and we found something
        # to mint from the queue, the last line in the loop exits the routine.
        # If we drop out of the loop, it's because the queue wasn't ripe.
        #
        $id = null;
        $status = null;
        $key = null;
        $qdate = null;
        $circ_svec = null;
        while (1) {
            $key = $first;
            $status = $cursor->c_get($key, $id, DB_SET_RANGE);
            $status and
                $this->addmsg($noid, sprintf('mint: c_get status/errno (%s/%s)', $status, $!)),
                return;
            # The cursor, key and value are now set at the first item
            # whose key is greater than or equal to $first.  If the
            # queue was empty, there should be no items under "$R/q/".
            #
            $qdate = preg_match("|$R/q/(\d{14})|", $key) ? $key : null;
            ! defined($qdate) and           # nothing in queue
                # this is our chance -- see queue() comments for why
                ($$noid["$R/fseqnum"] > self::SEQNUM_MIN and
                    $$noid["$R/fseqnum"] = self::SEQNUM_MIN),
                last;               # so move on
            # If the date of the earliest item to re-use hasn't arrived
            $currdate < $qdate and
                last;               # move on

            # If we get here, head of queue is ripe.  Remove from queue.
            # Any "next" statement from now on in this loop discards the
            # queue element.
            #
            $db->db_del($key);
            if ($$noid["$R/queued"]-- <= 0) {
                $m = sprintf('error: queued count (%s) going negative on id %s', $$noid["$R/queued"], $id);
                $this->addmsg($noid, $m);
                $this->logmsg($noid, $m);
                return;
            }

            # We perform a few checks first to see if we're actually
            # going to use this identifier.  First, if there's a hold,
            # remove it from the queue and check the queue again.
            #
            exists($$noid["$id\t$R/h"]) and     # if there's a hold
                $$noid["$R/longterm"] && $this->logmsg($noid,
                    sprintf('warning: id %s found in queue with a hold placed on it -- removed from queue.', $id)),
                next;
            # yyy this means id on "hold" can still have a 'q' circ status?

            $circ_svec = get_circ_svec($noid, $id);

            substr($circ_svec, 0, 1) === 'i' and
                $this->logmsg($noid,
                    sprintf('error: id %s appears to have been issued while still in the queue -- circ record is %s',
                        $id, $$noid["$id\t$R/c"])),
                next
            ;
            substr($circ_svec, 0, 1) === 'u' and
                $this->logmsg($noid, sprintf('note: id %s, marked as unqueued, is now being removed/skipped in the queue -- circ record is %s',
                    $id, $$noid["$id\t$R/c"])),
                next
            ;
            preg_match('/^([^q])/', $circ_svec, $matches) and
                $this->logmsg($noid, sprintf('error: id %s found in queue has an unknown circ status (%s) -- circ record is %s',
                    $id, $matches[1], $$noid["$id\t$R/c"])),
                next
            ;

            # Finally, if there's no circulation record, it means that
            # it was queued to get it minted earlier or later than it
            # would normally be minted.  Log if term is "long".
            #
            $circ_svec === '' and
                ($$noid["$R/longterm"] && $this->logmsg($noid,
                    sprintf('note: queued id %s coming out of queue on first minting (pre-cycled)', $id))
            ;

            # If we get here, our identifier has now passed its tests.
            # Do final identifier signoff and return.
            #
            return(set_circ_rec($noid,
                $id, 'i' . $circ_svec, $currdate, $contact));
        }

        # If we get here, we're not getting an id from the queue.
        # Instead we have to generate one.
        #
        # As above, the following is not a proper loop.  Normally it should
        # run once, but several cycles may be needed to weed out anomalies
        # with the generated id (eg, there's a hold on the id, or it was
        # queued to delay issue).
        #
        while (1) {

            # Next is the important seeding of random number generator.
            # We need this so that we get the same exact series of
            # pseudo-random numbers, just in case we have to wipe out a
            # generator and start over.  That way, the n-th identifier
            # will be the same, no matter how often we have to start
            # over.  This step has no effect when $generator_type ==
            # "sequential".
            #
            srand($$noid["$R/oacounter"]);

            # The id returned in this next step may have a "+" character
            # that n2xdig() appended to it.  The checkchar() routine
            # will convert it to a check character.
            #
            $id = genid($noid);
            ! defined($id)
                and return;

            # Prepend NAAN and separator if there is a NAAN.
            #
            $$noid["$R/firstpart"] and
                $id = $$noid["$R/firstpart"] . $id;

            # Add check character if called for.
            #
            $$noid["$R/addcheckchar"] and
                $id = &checkchar($id);

            # There may be a hold on an id, meaning that it is not to
            # be issued (or re-issued).
            #
            exists($$noid["$id\t$R/h"]) and     # if there's a hold
                next;               # do genid() again

            # It's usual to find no circulation record.  However,
            # there may be a circulation record if the generator term
            # is not "long" and we've wrapped (restarted) the counter,
            # of if it was queued before first minting.  If the term
            # is "long", the generated id automatically gets a hold.
            #
            $circ_svec = get_circ_svec($noid, $id);

            # A little unusual is the case when something has a
            # circulation status of 'q', meaning it has been queued
            # before first issue, presumably to get it minted earlier or
            # later than it would normally be minted; if the id we just
            # generated is marked as being in the queue (clearly not at
            # the head of the queue, or we would have seen it in the
            # previous while loop), we go to generate another id.  If
            # term is "long", log that we skipped this one.
            #
            substr($circ_svec, 0, 1) === 'q' and
                ($$noid["$R/longterm"] && $this->logmsg($noid,
                    sprintf("note: will not issue genid()'d %s as its status is 'q', circ_rec is %s",
                        $id, $$noid["$id\t$R/c"]))),
                next
            ;

            # If the circulation status is 'i' it means that the id is
            # being re-issued.  This shouldn't happen unless the counter
            # has wrapped around to the beginning.  If term is "long",
            # an id can be re-issued only if (a) its hold was released
            # and (b) it was placed in the queue (thus marked with 'q').
            #
            substr($circ_svec, 0, 1) === 'i' && ($$noid["$R/longterm"]
                        || ! $$noid["$R/wrap"]) and
                $this->logmsg($noid, sprintf('error: id %s cannot be re-issued except by going through the queue, circ_rec %s',
                    $id, $$noid["$id\t$R/c"])),
                next
            ;
            substr($circ_svec, 0, 1) === 'u' and
                $this->logmsg($noid, sprintf('note: generating id %s, currently marked as unqueued, circ record is %s',
                    $id, $$noid["$id\t$R/c"])),
                next
            ;
            preg_match('/^([^iqu])/', $circ_svec, $matches) and
                $this->logmsg($noid, sprintf('error: id %s has unknown circulation status (%s), circ_rec %s';
                    $id, $matches[1], $$noid["$id\t$R/c"])),
                next
            ;
            #
            # Note that it's OK/normal if $circ_svec was an empty string.

            # If we get here, our identifier has now passed its tests.
            # Do final identifier signoff and return.
            #
            return(set_circ_rec($noid,
                $id, 'i' . $circ_svec, $currdate, $contact));
        }
        # yyy
        # Note that we don't assign any value to the very important key=$id.
        # What should it be bound to?  Let's decide later.

        # yyy
        # Often we want to bind an id initially even if the object or record
        # it identifies is "in progress", as this gives way to begin tracking,
        # eg, back to the person responsible.
        #
    }

    /**
     * Record user (":/:/...") values in admin area.
     *
     * @param string $noid
     * @param string $contact
     * @param string $key
     * @param string $value
     * @return integer 0 (error) or 1 (success)
     */
    public function note($noid, $contact, $key, $value)
    {
        $R = &$this->_R;

        $db = $this->_opendbtab["bdb/$noid"];
        dblock();
        $status = $db->db_put("$R/$R/$key", $value);
        dbunlock();
        $$noid["$R/longterm"] and
            $this->logmsg($noid, sprintf('note: note attempt under %s by %s', $key, $contact)
                . ($status ? '' : ' -- note failed'));
        if ($status) {
            $this->addmsg($noid, sprintf('db->db_put status/errno (%s/%s)', $status, $!);
            return 0;
        }
        return 1;
    }

    /**
     * Convert a number to an extended digit according to $mask and $generator_type
     * and return (without prefix or NAAN).  A $mask character of 'k' gets
     * converted to '+' in the returned string; post-processing will eventually
     * turn it into a computed check character.
     *
     * @param integer $num
     * @param string $mask
     * @return string
     */
    public function n2xdig($num, $mask)
    {
        $s = '';
        $div = null;
        $remainder = null;
        $c = null;

        # Confirm well-formedness of $mask before proceeding.
        #
        !preg_match('/^[rsz][de]+k?$/', $mask) and
            return;

        $varwidth = 0;   # we start in fixed width part of the mask
        $rmask = array_reverse(str_split($mask));  # process each char in reverse
        while ($num != 0 || ! $varwidth) {
            if (! $varwidth) {
                $c = array_shift($rmask);  # check next mask character,
                ! defined($c)
                    || $c === 'r' || $c === 's' # terminate on r or s even if
                    and last;   # $num is not all used up yet
                $c === 'e' and
                    $div = $this->alphacount
                or
                $c === 'd' and
                    $div = $this->digitcount
                or
                $c === 'z' and
                    $varwidth = 1   # re-uses last $div value
                    and next
                or
                $c === 'k' and
                    next
                ;
            }
            $remainder = $num % $div;
            $num = intval($num / $div);
            $s = $this->_xdig[$remainder] . $s;
        }
        substr($mask, -1) === 'k' and       # if it ends in a check character
            $s .= '+';      # represent it with plus in new id
        return $s;
    }

    /**
     * Reads template looking for errors and returns the total number of
     * identifiers that it is capable of generating, using NOLIMIT to mean
     * indefinite (unbounded).  Returns 0 on error.  Variables $prefix,
     * $mask, and $generator_type are output parameters.
     *
     * $message will always be set; 0 return with error, 1 return with synonym
     *
     * @todo templates should probably have names, eg, jk##.. could be jk4
     * or jk22, as in "./noid testdb/jk4 <command> ... "
     *
     * @param string $template
     * @param string $prefix
     * @param string $mask
     * @param string $gen_type
     * @param string $message
     * @return integer -1 for no limit, 0 for error, else the total.
     */
    public function parse_template( $template, $prefix, $mask, $gen_type, $message)
    {
        $dirname;
        $msg = \$_[4];   # so we can modify $message argument easily
        $$msg = '';

        # Strip final spaces and slashes.  If there's a pathname,
        # save directory and final component separately.
        #
        $template = $template ?: '';
        $template = preg_replace('|[/\s]+$|', '', $template);       # strip final spaces or slashes
        preg_match('|^(.*/)?([^/]+)$|', $template, $matches);
        $dirname = isset($matches[1]) ? $matches[1] : '';            # make sure $dirname is defined
        $template = isset($matches[2]) ? $matches[2] : '';

        ! $template || $template === '-' and
            $$msg = 'parse_template: no minting possible.',
            $_[1] = $_[2] = $_[3] = '',
            return self::NOLIMIT;
        !preg_match('/^([^\.]*)\.(\w+)/', $template, $matches) and
            $$msg = "parse_template: no template mask - can't generate identifiers.",
            return 0;
        $prefix = isset($matches[1]) ? $matches[1] : '';
        $mask = isset($matches[2]) ? $matches[2] : '';

        !preg_match('/^[rsz]/', $mask) and
            $$msg = 'parse_template: mask must begin with one of the letters' . PHP_EOL
                . '"r" (random), "s" (sequential), or "z" (sequential unlimited).',
            return 0;

        !preg_match('/^.[^k]+k?$/', $mask) and
            $$msg = 'parse_template: exactly one check character (k) is allowed, and it may only appear at the end of a string of one or more mask characters.',
            return 0;

        !preg_match('/^.[de]+k?$/', $mask) and
            $$msg = 'parse_template: a mask may contain only the letters "d" or "e".',
            return 0;

        # Check prefix for errors.
        #
        $has_cc = substr($mask, -1) === 'k';
        foreach (str_split($prefix) as $c) {
            if ($has_cc && $c !== '/' && ! exists($this->_ordxdig[$c])) {
                $$msg = sprintf('parse_template: with a check character at the end, a mask may contain only characters from "%s".',
                    $this->legalstring);
                return 0;
            }
        }

        # If we get here, the mask is well-formed.  Now try to come up with
        # a short synonym for the template; it should start with the
        # template's prefix and then an integer representing the number of
        # letters in identifiers generated by the template.  For example,
        # a template of "ft.rddeek" would be "ft5".
        #
        $masklen = strlen($mask) - 1;    # subtract one for [rsz]
        $$msg = $prefix . $masklen;
        substr($mask, 0, 1) === 'z' and           # "+" indicates length can grow
            $$msg .= '+';

        # r means random;
        # s means sequential, limited;
        # z means sequential, no limit, and repeat most significant mask
        #   char as needed;

        $total = 1;
        foreach (str_split($mask) as $c) {
            # Mask chars it could be are: d e k
            $c === 'e' and
                $total *= $this->alphacount
            or
            $c === 'd' and
                $total *= $this->digitcount
            or
            preg_match('/[krsz]/', $c) and
                next
            ;
        }

        $_[1] = $prefix;
        $_[2] = $mask;
        $_[3] = $gen_type = (substr($mask, 0, 1) === 'r' ? 'random' : 'sequential');
        # $_[4] was set to the synonym already
        return substr($mask, 0, 1) === 'z' ? self::NOLIMIT : $total;
    }

    /**
     * An identifier may be queued to be issued/minted.  Usually this is used
     * to recycle a previously issued identifier, but it may also be used to
     * delay or advance the birth of an identifier that would normally be
     * issued in its own good time.  The $when argument may be "first", "lvf",
     * "delete", or a number and a letter designating units of seconds ('s',
     * the default) or days ('d') which is a delay added to the current time;
     * a $when of "now" means use the current time with no delay.
     *
     * The queue is composed of keys of the form $R/q/$qdate/$seqnum/$paddedid,
     * with the correponding values being the actual queued identifiers.  The
     * Btree allows us to step sequentially through the queue in an ordering
     * that is a side-effect of our key structure.  Left-to-right, it is
     *
     *   :/q/        $R/q/, 4 characters wide
     *   $qdate      14 digits wide, or 14 zeroes if "first" or "lvf"
     *   $seqnum     6 digits wide, or 000000 if "lvf"
     *   $paddedid   id "value", zero-padded on left, for "lvf"
     *
     * The $seqnum is there to help ensure queue order for up to a million queue
     * requests in a second (the granularity of our clock).  [ yyy $seqnum would
     * probably be obviated if we were using DB_DUP, but there's much conversion
     * involved with that ]
     *
     * We base our $seqnum (min is 1) on one of two stored sources:  "fseqnum"
     * for queue "first" requests or "gseqnum" for queue with a real time stamp
     * ("now" or delayed).  To implement queue "first", we use an artificial
     * time stamp of all zeroes, just like for "lvf"; to keep all "lvf" sorted
     * before "first" requests, we reset fseqnum and gseqnum to 1 (not zero).
     * We reset gseqnum whenever we use it at a different time from last time
     * since sort order will be guaranteed by different values of $qdate.  We
     * don't have that guarantee with the all-zeroes time stamp and fseqnum,
     * so we put off resetting fseqnum until it is over 500,000 and the queue
     * is empty, so we do then when checking the queue in mint().
     *
     * This key structure should ensure that the queue is sorted first by date.
     * As long as fewer than a million queue requests come in within a second,
     * we can make sure queue ordering is fifo.  To support "lvf" (lowest value
     * first) recycling, the $date and $seqnum fields are all zero, so the
     * ordering is determined entirely by the numeric "value" of identifier
     * (really only makes sense for a sequential generator); to achieve the
     * numeric sorting in the lexical Btree ordering, we strip off any prefix,
     * right-justify the identifier, and zero-pad on the left to create a number
     * that is 16 digits wider than the Template mask [yyy kludge that doesn't
     * take any overflow into account, or bigints for that matter].
     *
     * Returns the array of corresponding strings (errors and "id:" strings)
     * or an empty array on error.
     *
     * @param string $noid
     * @param string $contact
     * @param string $when
     * @param array $ids
     * @return array
     */
    public function queue($noid, $contact, $when, array $ids)
    {
        $R = &$this->_R;

        ! $$noid["$R/template"] and
            $this->addmsg($noid, 'error: queuing makes no sense in a bind-only minter.'),
            return array();
        ! defined($contact) and
            $this->addmsg($noid, 'error: contact undefined'),
            return array();
        ! defined($when) || trim($when) === '' and
            $this->addmsg($noid, 'error: queue when? (eg, first, lvf, 30d, now)'),
            return array();
        # yyy what is sensible thing to do if no ids are present?
        empty($ids) and
            $this->addmsg($noid, 'error: must specify at least one id to queue.'),
            return array();
        $seqnum = 0;
        $delete = 0;
        # purposely null
        $fixsqn = null;
        $qdate = null;

        # You can express a delay in days (d) or seconds (s, default).
        #
        if (preg_match('/^(\d+)([ds]?)$/', $when, $matches)) {    # current time plus a delay
            # The number of seconds in one day is 86400.
            $multiplier = isset($matches[2]) && $matches[2] === 'd' ? 86400 : 1;
            $qdate = temper(time() + $matches[1] * $multiplier);
        }
        elseif ($when === 'now') {    # a synonym for current time
            $qdate = temper(time());
        }
        elseif ($when === 'first') {
            # Lowest value first (lvf) requires $qdate of all zeroes.
            # To achieve "first" semantics, we use a $qdate of all
            # zeroes (default above), which means this key will be
            # selected even earlier than a key that became ripe in the
            # queue 85 days ago but wasn't selected because no one
            # minted anything in the last 85 days.
            #
            $seqnum = $$noid["$R/fseqnum"];
            #
            # NOTE: fseqnum is reset only when queue is empty; see mint().
            # If queue never empties fseqnum will simply keep growing,
            # so we effectively truncate on the left to 6 digits with mod
            # arithmetic when we convert it to $fixsqn via sprintf().
        }
        elseif ($when=== 'delete') {
            $delete = 1;
        }
        elseif ($when !== 'lvf') {
            $this->addmsg($noid, sprintf('error: unrecognized queue time: %s', $when));
            return array();
        }

        defined($qdate) and     # current time plus optional delay
            ($qdate > $$noid["$R/gseqnum_date"] and
                $seqnum = $$noid["$R/gseqnum"] = self::SEQNUM_MIN,
                $$noid["$R/gseqnum_date"] = $qdate,
            1 or
                $seqnum = $$noid["$R/gseqnum"]),
        1 or
            $qdate = '00000000000000',  # this needs to be 14 zeroes
        1;

        $iderror = '';
        $$noid["$R/genonly"] and
            $iderror = validate($noid, '-', $ids) and
            if (substr($iderror, 0, 6) != 'error:') {
                $iderror = '';
            }
        $iderror and
            $this->addmsg($noid, sprintf('error: queue operation not started -- one or more ids did not validate: %s', PHP_EOL . $iderror)),
            return array();
        $firstpart = $$noid["$R/firstpart"];
        $padwidth = $$noid["$R/padwidth"];
        $currdate = temper();
        $retvals = array();
        $m = null;
        $idval = null;
        $paddedid = null;
        $circ_svec = null;
        foreach ($ids as $id) {
            exists($$noid["$id\t$R/h"]) and     # if there's a hold
                $m = sprintf('error: a hold has been set for "%s" and must be released before the identifier can be queued for minting.', $id),
                $this->logmsg($noid, $m),
                $retvals[] = $m,
                next
            ;

            # If there's no circulation record, it means that it was
            # queued to get it minted earlier or later than it would
            # normally be minted.  Log if term is "long".
            #
            $circ_svec = get_circ_svec($noid, $id);

            substr($circ_svec, 0, 1) === 'q' && ! $delete and
                $m = sprintf('error: id %s cannot be queued since it appears to be in the queue already -- circ record is %s',
                    $id, $$noid["$id\t$R/c"]),
                $this->logmsg($noid, $m),
                $retvals[] = $m,
                next
            ;
            substr($circ_svec, 0, 1) === 'u' && $delete and
                $m = sprintf('error: id %s has been unqueued already -- circ record is %s',
                    $id, $$noid["$id\t$R/c"]),
                $this->logmsg($noid, $m),
                $retvals[] = $m,
                next
            ;
            substr($circ_svec, 0, 1) !== 'q' && $delete and
                $m = sprintf('error: id %s cannot be unqueued since its circ record does not indicate its being queued, circ record is %s',
                    $id, $$noid["$id\t$R/c"]),
                $this->logmsg($noid, $m),
                $retvals[] = $m,
                next
            ;
            # If we get here and we're deleting, circ_svec must be 'q'.

            $circ_svec === '' and
                ($$noid["$R/longterm"] && $this->logmsg($noid,
                    sprintf('note: id %s being queued before first minting (to be pre-cycled)', $id))),
            1 or
            substr($circ_svec, 0, 1) === 'i' and
                ($$noid["$R/longterm"] && $this->logmsg($noid, sprintf('note: longterm id %s being queued for re-issue', $id)))
            ;

            # yyy ignore return OK?
            set_circ_rec($noid, $id,
                    ($delete ? 'u' : 'q') . $circ_svec,
                    $currdate, $contact);

            $idval = preg_replace("/^$firstpart/", '', $id);
            $paddedid = sprintf("%0$padwidth" . "s", $idval);
            $fixsqn = sprintf("%06d", $seqnum % self::SEQNUM_MAX);

            dblock();

            $$noid["$R/queued"]++;
            if ($$noid["$R/total"] != self::NOLIMIT   # if total is non-zero
                    && $$noid["$R/queued"] > $$noid["$R/oatop"]) {

                dbunlock();

                $m = sprintf('error: queue count (%s) exceeding total possible on id %s.  Queue operation aborted.', $$noid["$R/queued"], $id);
                $this->logmsg($noid, $m);
                $retvals[] = $m;
                last;
            }
            $$noid["$R/q/$qdate/$fixsqn/$paddedid"] = $id;

            dbunlock();

            $$noid["$R/longterm"] and
                $this->logmsg($noid, sprintf('id: %s added to queue under %s',
                    $$noid["$R/q/$qdate/$fixsqn/$paddedid"], $R/q/$qdate/$seqnum/$paddedid));
            $retvals[] = sprintf('id: %s', $id);
            $seqnum and     # it's zero for "lvf" and "delete"
                $seqnum++;
        }
        dblock();
        $when === 'first' and
            $$noid["$R/fseqnum"] = $seqnum,
        1 or
        $qdate > 0 and
            $$noid["$R/gseqnum"] = $seqnum,
        1;
        dbunlock();
        return $retvals;
    }

    /**
     * Generate a sample id for testing purposes.
     *
     * @param string $noid
     * @param integer $num
     * @return string
     */
    public function sample($noid, $num)
    {
        $R = &$this->_R;

        $upper = null;
        ! defined($num) and
            $upper = $$noid["$R/total"],
            ($upper == self::NOLIMIT and $upper = 100000),
            $num = intval(rand(0, $upper));
        $mask = $$noid["$R/mask"];
        $firstpart = $$noid["$R/firstpart"];
        $func = ($$noid["$R/addcheckchar"] ? \&checkchar : \&echo);
        return &$func($firstpart . n2xdig($num, $mask));
    }

    /**
     * Scopes.
     *
     * @param string $noid
     * @return integer 1
     */
    public function scope($noid)
    {
        $R = &$this->_R;

        ! $$noid["$R/template"] and
            print('This minter does not generate identifiers, but it does accept user-defined identifier and element bindings.') . PHP_EOL;
        $total = $$noid["$R/total"];
        $totalstr = human_num($total);
        $naan = $$noid["$R/naan"] ?: '';
        $naan and
            $naan .= '/';

        $prefix = $$noid["$R/prefix"];
        $mask = $$noid["$R/mask"];
        $gen_type = $$noid["$R/generator_type"];

        print sprintf('Template %s will yield %s %s unique ids',
            $$noid["$R/template"], $total < 0 ? 'an unbounded number of' : $totalstr, $gen_type) . PHP_EOL;
        $tminus1 = $total < 0 ? 987654321 : $total - 1;

        # See if we need to compute a check character.
        $func = ($$noid["$R/addcheckchar"] ? \&checkchar : \&echo);
        print 'in the range ' . &$func($naan . &n2xdig( 0, $mask))
            . ', ' . &$func($naan . &n2xdig( 1, $mask))
            . ', ' . &$func($naan . &n2xdig( 2, $mask));
        28 < $total - 1 and print
            ', ..., ' . &$func($naan . &n2xdig(28, $mask));
        29 < $total - 1 and print
            ', ' . &$func($naan . &n2xdig(29, $mask));
        print ', ... up to '
            . &$func($naan . &n2xdig($tminus1, $mask))
            . ($total < 0 ? ' and beyond.' : '')
            . PHP_EOL;
        substr($mask, 0, 1) !== 'r' and
            return 1;
        print 'A sampling of random values (may already be in use): ';
        for ($i = 0; $i < 5; $i++) {
            print sample($noid) . ' ';
        }
        print PHP_EOL;
        return 1;
    }

    /**
     * Return local date/time stamp in TEMPER format.  Use supplied time (in seconds)
     * if any, or the current time.
     *
     * @param integer $time
     * @return string
     */
    protected function temper($time = null)
    {
        return strftime('%Y%m%d%H%M%S', $time ?: time());
    }

    /**
     * Check that identifier matches a given template, where "-" means the
     * default template for this generator.  This is a complete check of all
     * characteristics _except_ whether the identifier is stored in the
     * database.
     *
     * Returns an array of strings that are messages corresponding to any ids
     * that were passed in.  Error strings # that pertain to identifiers
     * begin with "iderr: ".
     *
     * @param string $noid
     * @param string $template
     * @param array $ids
     * @return array
     */
    public function validate($noid, $template, array $ids)
    {
        $R = &$this->_R;

        $first = null;
        $prefix = null;
        $mask = null;
        $gen_type = null;
        $msg = null;
        $retvals = array();

        ! $ids and
            $this->addmsg($noid, 'error: must specify a template and at least one identifier.'),
            return array();
        ! defined($template) and
            # If $noid is null, the caller looks in $this->errmsg(null).
            $this->addmsg($noid, 'error: no template given to validate against.'),
            return array();

        if ($template === '-') {
            $prefix = $$noid["$R/prefix"];
            $mask = $$noid["$R/mask"];
            # $retvals[] = sprintf('template: %s', $$noid["$R/template"]));
            if (! $$noid["$R/template"]) {  # do blanket validation
                $nonulls = array_filter(preg_replace('/^(.)/', 'id: $1', $ids));
                if (empty($nonulls) {
                    return array();
                }
                $retvals += $nonulls;
                return $retvals;
            }
        }
        elseif (! parse_template($template, $prefix, $mask, $gen_type, $msg)) {
            $this->addmsg($noid, sprintf('error: template %s bad: %s', $template, $msg));
            return array();
        }

        $id = null;
        $maskchars = array();
        $c = null;
        $m = null;
        $varpart = null;
        $m = substr($mask, -1) === 'k' ? substr($mask, -1) : $mask;
        $should_have_checkchar = $m !== $mask;
        $naan = $$noid["$R/naan"];
        foreach ($ids as $id) {
            ! defined($id) || trim($id) == '' and
                $retvals[] = "iderr: can't validate an empty identifier",
                continue;

            # Automatically reject ids starting with "$R/", unless it's an
            # "idmap", in which case automatically validate.  For an idmap,
            # the $id should be of the form $R/idmap/ElementName, with
            # element, Idpattern, and value, ReplacementPattern.
            #
            strpos("$R/", $id) === 0 and
                $retvals[] = preg_match("|^$R/idmap/.+|", $id)
                    ? sprintf('id: %s', $id)
                    : sprintf('iderr: identifiers must not start with "%s".', "$R/"),
                continue;

            $first = $naan;             # ... if any
            $first and
                $first .= '/';
            $first .= $prefix;          # ... if any
            $varpart = preg_replace('/^$first/', '', $id);
            strpos($id, $first) !== 0 and
            # yyy            ($varpart = $id) !~ s/^$prefix// and
                $retvals[] = sprintf('iderr: %s should begin with %s.', $id, $first),
                continue;
            # yyy this checkchar algorithm will need an arg when we
            #     expand into other alphabets
            $should_have_checkchar && ! checkchar($id) and
                $retvals[] = sprintf('iderr: %s has a check character error', $id),
                continue;
            ## xxx fix so that a length problem is reported before (or
            # in addition to) a check char problem

            # yyy needed?
            #strlen($first) + strlen($mask) - 1 != strlen($id)
            #   and $retvals[] = sprintf('error: %s has should have length %s',
            #           $id, (strlen($first) + strlen($mask) - 1))
            #   and next;

            # Maskchar-by-Idchar checking.
            #
            $maskchars = str_split($mask);
            array_shift($maskchars);       # toss 'r', 's', or 'z'
            foreach (str_split($varpart) as $c) {
                ! defined($m = array_shift($maskchars)) and
                    $retvals[] = sprintf('iderr: %s longer than specified template (%s)', $id, $template)),
                    continue 2;
                $m === 'e' && !preg_match("/$c/", $this->legalstring) and
                    $retvals[] = sprintf('iderr: %s char "%s" conflicts with template (%s) char "%s" (extended digit)',
                        $id, $c, $template, $m)),
                    continue 2
                or
                $m === 'e' && !preg_match("/$c/", '0123456789') and
                    $retvals[] = sprintf('iderr: %s char "%s" conflicts with template (%s) char "%s" (digit)',
                         $id, $c, $template, $m)),
                    continue 2
                ;       # or $m === 'k', in which case skip
            }
            defined($m = array_shift($maskchars))) and
                $retvals[] = sprintf('iderr: %s shorter than specified template (%s)', $id, $template),
                continue 2;

            # If we get here, the identifier checks out.
            $retvals[] = sprintf('id: %s', $id);
        }
        return $retvals;
    }
}

/**
__END__

=head1 NAME

Noid - routines to mint and manage nice opaque identifiers

=head1 SYNOPSIS

 use Noid;			    # import routines into a Perl script

 $dbreport = Noid::dbcreate(	    # create minter database & printable
 		$dbdir, $contact,   # report on its properties; $contact
		$template, $term,   # is string identifying the operator
		$naan, $naa, 	    # (authentication information); the
		$subnaa );          # report is printable

 $noid = Noid::dbopen( $dbname, $flags );    # open a minter, optionally
 	$flags = 0 | DB_RDONLY;		     # in read only mode

 Noid::mint( $noid, $contact, $pepper );     # generate an identifier

 Noid::dbclose( $noid );		     # close minter when done

 Noid::checkchar( $id );      # if id ends in +, replace with new check
 			      # char and return full id, else return id
			      # if current check char valid, else return
			      # 'undef'

 Noid::validate( $noid,	      # check that ids conform to template ("-"
 		$template,    # means use minter's template); returns
		@ids );	      # array of corresponding strings, errors
			      # beginning with "iderr:"

 $n = Noid::bind( $noid, $contact,	# bind data to identifier; set
		$validate, $how,	# $validate to 0 if id. doesn't
		$id, $elem, $value );	# need to conform to a template

 Noid::note( $noid, $contact, $key, $value );	# add an internal note

 Noid::fetch( $noid, $verbose,		# fetch bound data; set $verbose
 		$id, @elems );		# to 1 to return labels

 print Noid::dbinfo( $noid,		# get minter information; level
 		$level );		# brief (default), full, or dump
 Noid::getnoid( $noid, $varname );	# get arbitrary named internal
 					# variable

 Noid::hold( $noid, $contact,		# place or release hold; return
 		$on_off, @ids );	# 1 on success, 0 on error
 Noid::hold_set( $noid, $id );
 Noid::hold_release( $noid, $id );

 Noid::parse_template( $template,  # read template for errors, returning
 		$prefix, $mask,	   # namespace size (NOLIMIT=unbounded)
		$gen_type,	   # or 0 on error; $message, $gen_type,
		$message );	   # $prefix, & $mask are output params

 Noid::queue( $noid, $contact,	   # return strings for queue attempts
 		$when, @ids );	   # (failures start "error:")

 Noid::n2xdig( $num, $mask );	   # show identifier matching ord. $num

 Noid::sample( $noid, $num );	   # show random ident. less than $num

 Noid::scope( $noid );		   # show range of ids inside the minter

 print Noid::errmsg( $noid, $reset );   # print message from failed call
 	$reset = undef | 1;	   # use 1 to clear error message buffer

 Noid::addmsg( $noid, $message );  # add message to error message buffer

 Noid::logmsg( $noid, $message );  # write message to minter log

=head1 DESCRIPTION

This is very brief documentation for the B<Noid> Perl module subroutines.
For this early version of the software, it is indispensable to have the
documentation for the B<noid> utility (the primary user of these routines)
at hand.  Typically that can be viewed with

	perldoc noid

while the present document can be viewed with

	perldoc Noid

The B<noid> utility creates minters (identifier generators) and accepts
commands that operate them.  Once created, a minter can be used to produce
persistent, globally unique names for documents, databases, images,
vocabulary terms, etc.  Properly managed, these identifiers can be used as
long term durable information object references within naming schemes such
as ARK, PURL, URN, DOI, and LSID.  At the same time, alternative minters
can be set up to produce short-lived names for transaction identifiers,
compact web server session keys (cf. UUIDs), and other ephemera.

In general, a B<noid> minter efficiently generates, tracks, and binds
unique identifiers, which are produced without replacement in random or
sequential order, and with or without a check character that can be used
for detecting transcription errors.  A minter can bind identifiers to
arbitrary element names and element values that are either stored or
produced upon retrieval from rule-based transformations of requested
identifiers; the latter has application in identifier resolution.  Noid
minters are very fast, scalable, easy to create and tear down, and have a
relatively small footprint.  They use BerkeleyDB as the underlying database.

Identifiers generated by a B<noid> minter are also known as "noids" (nice
opaque identifiers).  While a minter can record and bind any identifiers
that you bring to its attention, often it is used to generate, bringing
to your attention, identifier strings that carry no widely recognizable
meaning.  This semantic opaqueness reduces their vulnerability to era-
and language-specific change, and helps persistence by making for
identifiers that can age and travel well.

=begin later

=head1 HISTORY

Since 2002 Sep 3:
- seeded (using srand) the generator so that the same exact sequence of
    identifiers would be minted if we started over from scratch (limited
    disaster recovery assistance)
- changed module name from PDB.pm to Noid.pm
- changed variable names from pdb... to noid...
- began adding support for sequentially generated numbers as part of
    generalization step (eg, for use as session ids)
- added version number
- added copyright to code
- slightly improved comments and error messages
- added extra internal (admin) symbols "$R/..." (":/..."),
    eg, "template" broken into "prefix", "mask", and "generator_type"
- changed the number of counters from 300 to 293 (a prime) on the
    theory that it will improve the impression of randomness
- added "scope" routine to print out sample identifiers upon db creation

Since 2004 Jan 18:
- changed var names from b -> noid throughout
- create /tmp/errs file public write
- add subnaa as arg to dbopen
- changed $R/authority to $R/subnaa
- added note feature
- added dbinfo
- added (to noid) short calling form: noi (plus NOID env var)
- changed dbcreate to take term, naan, and naa
- added DB_DUP flag to enable duplicate keys

Plus many, many more changes...

=end

=head1 BUGS

Probably.  Please report to jak at ucop dot edu.

=head1 COPYRIGHT AND LICENSE

Copyright 2002-2006 UC Regents.  BSD-type open source license.

=head1 SEE ALSO

L<dbopen(3)>, L<perl(1)>, L<http://www.cdlib.org/inside/diglib/ark/>

=head1 AUTHOR

John A. Kunze

=cut
*/
