<?php

namespace AppBundle\Doctrine\Hydrator;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use PDO;

class KeyValueListHydrator extends AbstractHydrator
{
    protected function hydrateAllData()
    {
        $results = array();
        foreach ($this->_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->hydrateRowData($row, $results);
        }
        return $results;
    }

    protected function hydrateRowData(array $data, array &$result)
    {
        $keys     = array_keys($data);
        $keyCount = count($keys);
        // La première colonne est considérée comme étant la clé
        $key = $data[$keys[0]];
        if ($keyCount == 2) {
            // Si deux colonnes alors
            // la seconde colonne est considéré comme la valeur
            $value = $data[$keys[1]];
        } else {
            // Sinon  le reste des colonnes excepté la premières
            // sont considréré comme la talbeau de valeur
            array_shift($data);
            $value = array_values($data);
        }
        $result[$key] = $value;
    }
}