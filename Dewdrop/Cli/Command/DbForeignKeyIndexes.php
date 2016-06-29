<?php

/**
 * Dewdrop
 *
 * @link      https://github.com/DeltaSystems/dewdrop
 * @copyright Delta Systems (http://deltasys.com)
 * @license   https://github.com/DeltaSystems/dewdrop/LICENSE
 */

namespace Dewdrop\Cli\Command;

use Dewdrop\Env;
use Dewdrop\Exception;

/**
 * Generate a dbdeploy change script to add indexes to foreign keys that are currently
 * missing them.
 */
class DbForeignKeyIndexes extends CommandAbstract
{
    /**
     * The valid options for the action arg.
     *
     * @var array
     */
    private $validActions = [
        'dbdeploy',
        'status'
    ];

    /**
     * A reference to the CLI runner's DB connection.  Carried around so it's
     * easier to use throughout this command.
     *
     * @var \Dewdrop\Db\Adapter
     */
    private $db;

    /**
     * The action that should be run.
     *
     * @var string
     */
    private $action;

    /**
     * The path to the mysql binary.  If not specified, we'll attempt to
     * auto-detect it.
     *
     * @var string
     */
    private $mysql;

    /**
     * The path to the psql binary.  If not specified, we'll attempt to
     * auto-detect it.
     *
     * @var string
     */
    private $psql;

    /**
     * The type of RDBMS being used.  Should be either "pgsql" or "mysql".
     *
     * @var string
     */
    private $dbType;

    /**
     * Set basic command information, arguments and examples
     *
     * @inheritdoc
     */
    public function init()
    {
        $this
            ->setDescription('Add indexes to foreign keys currently missing them.')
            ->setCommand('db-foreign-key-indexes');

        $this->addPrimaryArg(
            'action',
            'Which action to execute: status or dbdeploy [default]',
            self::ARG_OPTIONAL
        );

        $this->addArg(
            'mysql',
            'The path to the mysql binary',
            self::ARG_OPTIONAL
        );

        $this->addArg(
            'psql',
            'The path to the psql binary',
            self::ARG_OPTIONAL
        );

        $this->addExample(
            'Generate a dbdeploy script for any missing foreign key indexes',
            './vendor/bin/dewdrop db-foreign-key-indexes'
        );

        $this->addExample(
            'Check your database to see if any foreign keys are missing indexes',
            './vendor/bin/dewdrop db-foreign-key-indexes status'
        );
    }

    /**
     * Set the action to run (see the $validActions property for a list)
     *
     * @param string $action
     * @return \Dewdrop\Cli\Command\Dbdeploy
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Manually set the path to the mysql binary
     *
     * @param string $mysql
     * @return \Dewdrop\Cli\Command\Dbdeploy
     */
    public function setMysql($mysql)
    {
        $this->mysql = $mysql;

        return $this;
    }

    /**
     * Manually set the path to the psql binary
     *
     * @param string $psql
     * @return \Dewdrop\Cli\Command\Dbdeploy
     */
    public function setPsql($psql)
    {
        $this->psql = $psql;

        return $this;
    }

    /**
     * Determine which action the user has selected (update or status), ensure
     * the changelog table is present and then delegate the remainder of the
     * work to the action's own method.
     *
     * @return boolean
     */
    public function execute()
    {
        if (null === $this->action) {
            $this->action = 'dbdeploy';
        }

        if (!in_array($this->action, $this->validActions)) {
            return $this->abort(
                "\"{$this->action}\" is not a valid action.  Valid actions are: "
                . implode(', ', $this->validActions)
            );
        }

        $this->db = $this->runner->connectDb();

        $method = 'execute' . ucfirst($this->action);
        return $this->$method();
    }

    /**
     * @return boolean
     */
    public function executeDbdeploy()
    {
        $sql = '';

        foreach ($this->getMissingIndexesByTable() as $tableName => $missingIndexes) {
            foreach ($missingIndexes as $foreignKeyColumns) {
                $sql .= $this->db->generateCreateIndexStatement($tableName, $foreignKeyColumns) . PHP_EOL;
            }

            $sql .= $this->db->generateAnalyzeTableStatement($tableName) . PHP_EOL;
            $sql .= PHP_EOL;
        }

        if (1 || $sql) {
            $this->writeDbdeployFile(
                '-- Generated by db-foreign-key-indexes Dewdrop CLI command'
                . PHP_EOL
                . PHP_EOL
                . $sql
            );
        }

        return true;
    }

    /**
     * @param array $changesets
     * @return boolean
     */
    public function executeStatus()
    {
        $missingIndexesByTable = $this->getMissingIndexesByTable();

        if (!count($missingIndexesByTable)) {
            $this->renderer->success('All foreign keys are indexed.');
        } else {
            $countOfTables = count($missingIndexesByTable);
            $pluralSuffix   = (1 === $countOfTables ? '' : 's');

            $this->renderer->warn(
                "You are missing foreign key indexes on {$countOfTables} table{$pluralSuffix}."
            );
        }

        foreach ($missingIndexesByTable as $table => $foreignKeys) {
            $this->renderer->subhead($table);

            $foreignKeyListItems = [];

            foreach ($foreignKeys as $columns) {
                $foreignKeyListItems[] = implode(', ', $columns);
            }

            $this->renderer->unorderedList($foreignKeyListItems);
            $this->renderer->newline();
            $this->renderer->newline();
        }

        return true;
    }

    protected function writeDbdeployFile($contents)
    {
        /* @var $dbdeploy Dbdeploy */
        $dbdeploy  = $this->runner->getCommandByName('dbdeploy');
        $changeset = $dbdeploy->getPrimaryChangeset();

        $changeset->writeNewFile('add-missing-foreign-key-indexes', $contents);
    }

    private function getMissingIndexesByTable()
    {
        $missingIndexesByTable = [];

        foreach ($this->db->listTables() as $tableName) {
            $missingIndexes = $this->db->listMissingForeignKeyIndexes($tableName);

            if (count($missingIndexes)) {
                $missingIndexesByTable[$tableName] = $missingIndexes;
            }
        }

        return $missingIndexesByTable;
    }
}