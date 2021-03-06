<?php
/**
 * Custom Menu
 * Module for EvolutionCMS (Modx)
 *
 * @author      delphinpro <delphinpro@gmail.com>
 * @copyright   copyright © 2018 delphinpro
 * @license     licensed under the MIT license
 *
 */

if (empty($modx) || !($modx instanceof DocumentParser)) {
    die('Please use the MODX Content Manager instead of accessing this file directly.');
}

/**
 * Class DatabaseUtils
 */
class DatabaseUtils
{
    public $tables = [];

    private $modx = null;

    public function __construct(\DocumentParser $modx)
    {
        $this->modx = $modx;
    }

    public function addTableDefinition($tableName, $def)
    {
        $fullTableName = $this->modx->getFullTableName($tableName);
        $this->tables[$tableName] = [];
        $this->tables[$tableName]['fullName'] = $fullTableName;
        $this->tables[$tableName]['createCode'] = $this->getCreateCode($fullTableName, $def);
        $this->tables[$tableName]['def'] = $def;
    }

    public function createTables()
    {
        foreach ($this->tables as $table) {
            $this->modx->db->query($table['createCode']);
        }
    }

    public function getTableName($tableName)
    {
        return $this->tables[$tableName]['fullName'];
    }

    public function castTypes($tableName, array $data, $keysToCamelCase = false)
    {
        $fields =& $this->tables[$tableName]['def']['fields'];
        $result = [];

        foreach ($data as $key => $value) {
            $_key = camelCase2Shake($key);
            if (array_key_exists($_key, $fields)) {
                switch ($fields[$_key]['type']) {
                    case 'INT':
                        if (!is_null($value)) {
                            $value = (int)$value;
                        }
                        break;
                    case 'VARCHAR':
                        break;
                    default:
                }

                if ($keysToCamelCase) {
                    $_key = shake2CamelCase($_key);
                }

                $result[$_key] = $value;
            }
        }

        return $result;
    }

    public function keysConvertToShake(array $array)
    {
        $result = [];
        foreach ($array as $key => $item) {
            $convertedKey = camelCase2Shake($key);
            $result[$convertedKey] = $item;
        }
        return $result;
    }

    public function keysConvertToCamelCase(array $array)
    {
        $result = [];
        foreach ($array as $key => $item) {
            $convertedKey = shake2CamelCase($key);
            $result[$convertedKey] = $item;
        }
        return $result;
    }

    private function getCreateCode($fullTableName, $def)
    {
        $sql = "\nCREATE" . " TABLE IF NOT EXISTS {$fullTableName} (";

        $fields = [];

        foreach ($def['fields'] as $fieldName => $field) {
            $str = "\n\t`{$fieldName}` {$field['type']}";
            if (array_key_exists('length', $field)) $str .= "({$field['length']})";
            $str .= ($field['isNull'] ? " NULL" : " NOT NULL");
            if (array_key_exists('attrs', $field)) $str .= " {$field['attrs']}";
            if (array_key_exists('default', $field)) $str .= " DEFAULT " . self::getDefaultPresentValue($field['default']);

            $fields[] = $str;
        }

        $fields[] = "\n\tPRIMARY KEY (`{$def['primary']}`)";

        foreach ($def['indexes'] as $indexName => $index) {
            $fields[] = "\n\t{$index['type']} `{$indexName}` (`{$index['field']}`)";
        }

        $sql .= join(',', $fields);
        $sql .= "\n)";

        foreach ($def['params'] as $param => $value) {
            $sql .= "\n{$param}={$value}";
        }

        $sql .= ";";

        return $sql;
    }

    private static function getDefaultPresentValue($val)
    {
        if (is_null($val)) return 'NULL';
        if (is_string($val) && empty($val)) return '\'\'';
        if (is_int($val)) return "'" . $val . "'";
        return (string)$val;
    }
}

/**
 * @param DocumentParser $modx
 * @return DatabaseUtils
 */
function initDatabaseUtils(\DocumentParser $modx)
{
    $dbUtils = new DatabaseUtils($modx);

    $dbUtils->addTableDefinition('menus', [
        'fields'  => [
            'id'    => [
                'type'   => 'INT',
                'length' => 11,
                'isNull' => false,
                'attrs'  => 'AUTO_INCREMENT',
            ],
            'name'  => [
                'type'    => 'VARCHAR',
                'length'  => 45,
                'isNull'  => true,
                'default' => null,
            ],
            'title' => [
                'type'    => 'VARCHAR',
                'length'  => 45,
                'isNull'  => false,
                'default' => '',
            ],
        ],
        'primary' => 'id',
        'indexes' => [
            'name' => ['type' => 'UNIQUE INDEX', 'field' => 'name'],
        ],
        'params'  => [
            'COLLATE'        => "'utf8_general_ci'",
            'ENGINE'         => 'MyISAM',
            'AUTO_INCREMENT' => 1,
        ],
    ]);

    $dbUtils->addTableDefinition('menu_items', [
        'fields'  => [
            'id'          => [
                'type'   => 'INT',
                'length' => 11,
                'isNull' => false,
                'attrs'  => 'AUTO_INCREMENT',
            ],
            'parent_id'   => [
                'type'    => 'INT',
                'length'  => 11,
                'isNull'  => true,
                'default' => null,
            ],
            'menu_id'     => [
                'type'   => 'INT',
                'length' => 11,
                'isNull' => false,
            ],
            'order_index' => [
                'type'    => 'INT',
                'length'  => 5,
                'isNull'  => false,
                'default' => 0,
            ],
            'is_hide'     => [
                'type'    => 'INT',
                'length'  => 1,
                'isNull'  => false,
                'default' => 0,
            ],
            'replaced'    => [
                'type'    => 'INT',
                'length'  => 1,
                'isNull'  => false,
                'default' => 0,
            ],
            'doc_id'      => [
                'type'   => 'INT',
                'length' => 11,
                'isNull' => false,
            ],
            'doc_title'   => [
                'type'   => 'VARCHAR',
                'length' => 255,
                'isNull' => false,
            ],
            'title'       => [
                'type'    => 'VARCHAR',
                'length'  => 255,
                'isNull'  => true,
                'default' => null,
            ],
            'url'         => [
                'type'    => 'VARCHAR',
                'length'  => 255,
                'isNull'  => true,
                'default' => '',
            ],
            'alias'       => [
                'type'    => 'VARCHAR',
                'length'  => 255,
                'isNull'  => true,
                'default' => null,
            ],
        ],
        'primary' => 'id',
        'indexes' => [],
        'params'  => [
            'COLLATE'        => "'utf8_general_ci'",
            'ENGINE'         => 'MyISAM',
            'AUTO_INCREMENT' => 1,
        ],
    ]);

    return $dbUtils;
}
