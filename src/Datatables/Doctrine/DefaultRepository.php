<?php

namespace Datatables\Doctrine;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class DefaultRepository extends EntityRepository {

    /**
     *
     * @var integer
     */
    private $iDisplayStart = 0;

    /**
     *
     * @var integer 
     */
    private $iDisplayLength = 10;

    /**
     *
     * @var array 
     * array[]['campo']
     * array[]['type']
     */
    private $aColumns = null;

    /**
     *
     * @var array 
     * array[]['campo']
     * array[]['html']
     */
    private $aColumnsArray = null;

    public function getDatatables(QueryBuilder $qb, array $params) {

        if ($this->getAColumns() != '' && $this->getAColumnsArray() != '') {
            if ($_GET['sSearch'] != "") {
                for ($i = 0; $i < count($this->getAColumns()); $i++) {
                    if ($this->getAColumns()[$i]['type'] == "string") {
                        $qb->orWhere("LOWER(remove_accents(" . $this->getAColumns()[$i]['campo'] . ")) LIKE LOWER(remove_accents(:busca" . $i . "))")
                        ->setParameter("busca" . $i, '%' . $params['sSearch'] . '%');
                    } else {
                        if (is_int($params['sSearch'])) {
                            $qb->orWhere($this->getAColumns()[$i]['campo'] . " = '" . $params['sSearch'] . "'");
                        }
                    }
                }
            }

            // Select total
            $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare("SELECT count(a) FROM (" . $this->get_raw_sql($qb) . ") AS a");

            $stmt->execute();


            if (isset($params['iSortCol_0'])) {
                $primeiro = true;
                for ($i = 0; $i < intval($params['iSortingCols']); $i++) {
                    if ($params['bSortable_' . intval($params['iSortCol_' . $i])] == "true") {
                        if ($primeiro) {
                            $primeiro = false;
                            $qb->orderBy($this->getAColumns()[intval($params['iSortCol_' . $i])]['campo'], $params['sSortDir_' . $i]);
                        } else {
                            $qb->add($this->getAColumns()[intval($params['iSortCol_' . $i])]['campo'], $params['sSortDir_' . $i]);
                        }
                    }
                }
            }

            for ($i = 0; $i < count($this->getAColumns()); $i++) {
                if ($_GET['bSearchable_' . $i] == "true" && $params['sSearch_' . $i] != '') {
                    if ($this->getAColumns()[$i]['type'] == "string") {
                        $qb->orWhere("LOWER(remove_accents(" . $this->getAColumns()[$i]['campo'] . ")) LIKE LOWER(remove_accents(:busca" . $i . "))")
                        ->setParameter("busca" . $i, '%' . $params['sSearch_' . $i] . '%');
                    } else {
                        if (is_int($params['sSearch'])) {
                            $qb->andWhere($this->getAColumns()[$i]['campo'] . " = '" . $params['sSearch_' . $i] . "'");
                        }
                    }
                }
            }


            $qb->setMaxResults($params['iDisplayLength']);
            $qb->setFirstResult($params['iDisplayStart']);

            $rows = array();
            foreach ($qb->getQuery()->getResult() as $key => $value) {
                $aRow = $value->toArray();

                $row = array();
                for ($i = 0; $i < count($this->getAColumns()); $i++) {
                    if (isset($this->getAColumnsArray()[$i]['html'])) {
                        $html = $this->getAColumnsArray()[$i]['html'];
                        foreach ($aRow as $key => $value) {
                            $html = str_replace("{" . $key . "}", $value, $html);
                        }
                        
                        $row[] = $html;
                    } else {
                        $row[] = $aRow[$this->getAColumnsArray()[$i]['campo']];
                    }
                }
                $rows[] = $row;
            }


            $total = $stmt->fetchAll()[0]['count'];
            $output = array(
                "sEcho" => $params['sEcho'],
                "iTotalRecords" => $total,
                "iTotalDisplayRecords" => $total,
                "iDisplayLength" => $params['iDisplayLength'],
                "aaData" => $rows
                );

            return $output;
        } else {
            throw new \Exception("The value(getAColumns, getAColumnsArray) is required", 1);
        }
    }

    public function getIDisplayStart() {
        return $this->iDisplayStart;
    }

    public function setIDisplayStart($iDisplayStart) {
        $this->iDisplayStart = $iDisplayStart;
    }

    public function getIDisplayLength() {
        return $this->iDisplayLength;
    }

    public function setIDisplayLength($iDisplayLength) {
        $this->iDisplayLength = $iDisplayLength;
    }

    public function getAColumns() {
        return $this->aColumns;
    }

    public function setAColumns($aColumns) {
        $this->aColumns = $aColumns;
    }

    public function getAColumnsArray() {
        return $this->aColumnsArray;
    }

    public function setAColumnsArray($aColumnsArray) {
        $this->aColumnsArray = $aColumnsArray;
    }

    function get_raw_sql($query) {

        if (!($query instanceof QueryBuilder)) {
            throw new \Exception('Not an instanse of a Doctrine Query');
        }

        if (is_callable(array($query, 'buildSqlQuery'))) {
            $queryString = $query->buildSqlQuery();
            $query_params = $query->getParams();
            $params = $query_params['where'];
        } else {
            $queryString = $query->getQuery()->getSQL();
            ;
            $params = $query->getQuery()->getParameters();
        }

        $queryStringParts = split('\?', $queryString);
        $iQC = 0;

        $queryString = "";

        foreach ($params as $param) {

            if (is_numeric($param->getValue())) {
                $queryString .= $queryStringParts[$iQC] . $param->getValue();
            } elseif (is_bool($param->getValue())) {
                $queryString .= $queryStringParts[$iQC] . $param->getValue() * 1;
            } else {
                $queryString .= $queryStringParts[$iQC] . '\'' . $param->getValue() . '\'';
            }

            $iQC++;
        }
        for ($iQC; $iQC < count($queryStringParts); $iQC++) {
            $queryString .= $queryStringParts[$iQC];
        }

        return $queryString;
    }

}