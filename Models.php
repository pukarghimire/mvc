<?php
/**
 * Created by PhpStorm.
 * User: pukar
 * Date: 12/10/18
 * Time: 6:54 PM
 */

namespace System\Core;


use System\DB\Mysql;
use System\Exceptions\QueryBuildModeException;

abstract class Models extends Mysql
{

    protected $table = '';
    protected $pk = 'id';
    protected $sql = '';
    protected $select = '*';
    protected $conditions = null;
    protected $order = null;
    protected $offset = null;
    protected $limit = null;

    public function select($columns='*')
    {
        if (is_array($columns)) {
            $this->select = implode(',', $columns);
        } else {
            $this->select = $columns;
        }
        return $this;
    }

    public function where($column,$value,$operator = '=')
    {
            if (is_null($this->conditions)){
                $this->conditions = "{$column} {$operator} '{$value}'";
            }else{
                $this->conditions .= "AND {$column} {$operator} '{$value}'";
            }
            return $this;
    }

    public function orWhere($column,$value,$operator = '=')
    {
        $this->conditions .= "AND {$column} {$operator} '{$value}'";
        return $this;
    }

    public function order($column, $direction="ASC")
    {
        if (is_null($this->order)){
            $this->order = "{$column} {$direction}";
        }
        else{
            $this->order .= "{$column} {$direction}";
        }

        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function get()
    {
        $this->buildQuery('select');

        $this->query($this->sql);

        if ($this->num_rows()==1){
            $data = $this->fetch_assoc()[0];

            foreach ($data as $k => $v) {
                $this->{$k} = $v;
            }

            $this->reset();

            return true;
        }
        elseif ($this->num_rows() > 1){
            $data = $this->fetch_assoc();
            $ret = [];
            $class_name = get_class($this);

            foreach ($data as $item){
                $obj = new $class_name;

                foreach ($item as $k => $v){
                    $obj->{$k} = $v;
                }

                $ret[] = $obj;
            }

            $this->reset();

            return $ret;
        }
        else {
            $this->reset();

            return false;
        }
    }

    protected function buildQuery($mode)
    {
        switch($mode){
            case 'select':
                $this->buildSelectQuery();
                break;

            default:
                throw new QueryBuildModeException("Query build mode '{$mode}' does not exist.");
        }
    }

    protected function buildSelectQuery()
    {
        $this->sql = "SELECT {$this->select} FROM {$this->table}";

        if (!is_null($this->conditions)) {
            $this->sql .= "WHERE {$this->conditions}";
        }

        if (!is_null($this->order)) {
            $this->sql .= "ORDER BY {$this->conditions}";
        }

        if (!is_null($this->limit)) {
            if (is_null($this->offset)){
                $this->sql .= "LIMIT {$this->limit}";
            }
            else{
                $this->sql .= "LIMIT {$this->limit}, {$this->limit}";
            }
        }
    }

    protected function reset()
    {
         $this->$sql = '';
         $this->$select = '*';
         $this->$conditions = null;
         $this->$order = null;
         $this->$offset = null;
         $this->$limit = null;
    }
}