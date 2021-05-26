<?php

function csv_to_array(string $path)
{
    $csv = array_map('str_getcsv', file($path));
    $headers = $csv[0];
    $rows = array_slice($csv, 1);
    $output = [];
    foreach ($rows as $row) {
        $new_row = [];
        foreach ($headers as $index => $col) {
            if(array_key_exists($index, $row)){
                $new_row[$col] = $row[$index];
            }
        }
        $output[] = $new_row;
    }
    return $output;
}

/**
 * Return non empty value from given values
 *
 */
function non_empty($value1, $value2)
{
    return empty($value1) ? $value2 : $value1;
}

/**
 * Generates username from given name
 *
 * @param   string  $name
 * @return  string
 */
function randomUsername(string $name)
{
    return substr(str_replace(' ', '', strtolower($name)), 0, 5) . rand(1, 9999);
}

/**
 * Generate select and with select from select query param
 *
 * @param   string $select_query
 * @return  array
 */
function selectify($select_query)
{
    $select = [];
    $with_select = [];
    foreach (explode('|', $select_query) as $value) {
        if (str_contains($value, ':')) {
            if (explode(':', $value)[1] == '') {
                array_push($with_select, str_replace(':', '', $value));
            } else {
                array_push($with_select, !str_contains($value, 'id') ? str_replace(':', ':id,', $value) : $value);
            }

        } else {
            array_push($select, $value);
        }

    }
    if (!in_array('id', $select)) {
        array_push($select, 'id');
    }

    return [
        'select' => $select,
        'with' => $with_select,
    ];
}
