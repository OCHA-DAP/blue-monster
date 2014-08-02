<?php
/**
 * Data access functions.
 */

/**
 * Execute a SQL query and return the statement with results.
 */
function do_query() {
  global $APP;
  $args = func_get_args();
  $query = array_shift($args);
  $statement = $APP->pdo->prepare($query);
  $statement->execute($args);
  return $statement;
}


/**
 * Fetch an import for a dataset.
 *
 * @param $dataset_param The identifier of the dataset.
 * @param $stamp_param The timestamp (latest if unspecified).
 * @return The import (from import_view).
 */
function get_import($dataset_param, $stamp_param = null) {
  if ($import_param) {
    return do_query(
      'select * from import_view' .
      ' where dataset=? and stamp=?',
      $dataset_param, $stamp_param
    )->fetch();
  } else {
    return do_query(
      'select * from import_view' .
      ' join latest_import_view using(import)' .
      ' where dataset=?',
      $dataset_param
    )->fetch();
  }
}

function get_cols($import) {
  return do_query(
    'select * from col_view' .
    ' where import=?' .
    ' order by col',
    $import->import
  )->fetchAll();
}

function get_values($import) {
  return do_query(
    'select * from value_view' .
    ' where import=?' .
    ' order by row, col',
    $import->import
  );
}