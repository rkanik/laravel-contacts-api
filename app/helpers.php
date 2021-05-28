<?php

function getContactsFromVCF(string $path)
{
    $vcf = fopen($path, 'r');

    $vcards = [];

    $BEGIN = "BEGIN:VCARD";
    $VERSION = "VERSION:3.0";
    $END = "END:VCARD";

    $prev_key = null;
    while (($lines = fgetcsv($vcf)) !== false) {
        $line = trim($lines[0]);

        // Skip empty line
        if (empty($line) || $line == $VERSION) {
            continue;
        }

        // Instantiating new vcard
        if ($line == $BEGIN) {
            $vcard = new stdClass();
            continue;
        }

        // Pushing vcard on end vcard
        if ($line == $END) {
            $vcards[] = $vcard;
            continue;
        }

        // If new line concat with prev key
        if (!str_contains($line, ':') && $prev_key) {
            $vcard->{$prev_key} = $vcard->{$prev_key} . $line;
            continue;
        }

        [$key, $value] = explode(":", $line, 2);

        $key = strtolower($key);
        $value = trim(str_replace(';', ' ', $value));

        if (str_contains($key, 'email')) {
            $email = [
                'email' => $value,
            ];
            foreach (explode(';', str_replace('email;', '', $key)) as $types) {
                foreach (explode('=', $types) as $type) {
                    if (!in_array($type, ['internet', 'type'])) {
                        $email['label'] = $type;
                    }
                }
            }
            if (isset($vcard->emails)) {
                $vcard->emails[] = $email;
            } else {
                $vcard->emails = [$email];
            }
        } else if (str_contains($key, 'item') || str_contains($key, 'tel')) {
            if (str_contains($key, 'tel') && !empty($value)) {
                $phone_number = [
                    'phone_number' => $value,
                ];
                if (isset($vcard->phone_numbers)) {
                    $vcard->phone_numbers[] = $phone_number;
                } else {
                    $vcard->phone_numbers = [$phone_number];
                }
            } else if (str_contains($key, 'label') && isset($vcard->phone_numbers)) {
                $last_index = array_key_last($vcard->phone_numbers);
                $vcard->phone_numbers[$last_index]['label'] = $value;
            }

        } else {
            $vcard->{$key} = $value;
        }

        $prev_key = $key;
    }
    fclose($vcf);

    return $vcards;
}

function csv_to_array(string $path)
{
    $csv = array_map('str_getcsv', file($path));
    $headers = $csv[0];
    $rows = array_slice($csv, 1);
    $output = [];
    foreach ($rows as $row) {
        $new_row = [];
        foreach ($headers as $index => $col) {
            if (array_key_exists($index, $row)) {
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
