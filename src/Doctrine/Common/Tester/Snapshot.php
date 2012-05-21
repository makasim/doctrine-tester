<?php
namespace Doctrine\Common\Tester;

use Doctrine\ORM\EntityManager;

class Snapshot
{
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create($snapshotName)
    {
        $query = $this->getTables();
        while($table = $query->fetchColumn()) {
            if (strpos($table, $this->getSnaphotTablePrefix()) !== false) continue;

            $snapshop_table = "{$this->getSnaphotTablePrefix()}_{$snapshotName}__{$table}";
            $this->exec("DROP TABLE IF EXISTS {$snapshop_table}");
            $this->exec("CREATE TABLE {$snapshop_table} SELECT * FROM {$table}");
        }

        return $this;
    }

    public function load($snapshotName, $append = false)
    {
        $query = $this->getTables();
        $queriesToExec = array();
        
        $queriesToExec[] = "SET FOREIGN_KEY_CHECKS = 0";
        
        if (false == $append) {
            $queriesToExec = array_merge($queriesToExec, $this->truncateQueries());
        }
        while($table = $query->fetchColumn()) {
            if (strpos($table, $this->getSnaphotTablePrefix()) !== false) continue;
        
            $snapshop_table = "{$this->getSnaphotTablePrefix()}_{$snapshotName}__{$table}";
            $queriesToExec[] = "INSERT HIGH_PRIORITY INTO {$table} SELECT * FROM {$snapshop_table}";
        }

        $queriesToExec[] = "SET FOREIGN_KEY_CHECKS = 1";
        
        $this->exec(implode('; ', $queriesToExec));
    }
    
    /**
     * @return void
     */
    public function truncate()
    {
        $queriesToExec = array();
        $queriesToExec[] = "SET FOREIGN_KEY_CHECKS = 0";
        $queriesToExec = array_merge($queriesToExec, $this->truncateQueries());
        $queriesToExec[] = "SET FOREIGN_KEY_CHECKS = 1";

        $this->exec(implode('; ', $queriesToExec));
    }
    
    /**
     * @return array
     */
    protected function truncateQueries()
    {
        $query = $this->getTables();
        
        $queriesToExec = array();
        while($table = $query->fetchColumn()) {
            if (strpos($table, $this->getSnaphotTablePrefix()) !== false) continue;

            $queriesToExec[] = "TRUNCATE TABLE {$table}";
        }
        
        return $queriesToExec;
    }

    public function removeAll()
    {
        $query = $this->getTables();
        while($table = $query->fetchColumn()) {
            if (strpos($table, $this->getSnaphotTablePrefix()) === false) continue;

            $this->exec("DROP TABLE IF EXISTS {$table}");
        }

        return $this;
    }

    protected function getSnaphotTablePrefix()
    {
        return '_snapshot';
    }

    protected function getTables()
    {
        return $this->query("SHOW TABLES");
    }

    protected function exec($query)
    {
        return $this->entityManager->getConnection()->exec($query);
    }

    /**
     * @param string $query
     *
     * @return \Doctrine\DBAL\Doctrine\DBAL\Driver\Statement
     */
    protected function query($query)
    {
        return $this->entityManager->getConnection()->query($query);
    }
}