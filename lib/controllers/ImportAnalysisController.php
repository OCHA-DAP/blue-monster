<?php
/**
 * Controller to analyse an imported dataset.
 */
class ImportAnalysisController extends AbstractController {

  function doGET(HttpRequest $request, HttpResponse $response) {

    // Output format
    $format = $request->get('format', 'html');

    // Focus tag
    $tag = $request->get('tag');

    //
    // Get the import record
    //
    $source_ident = $request->get('source');
    $dataset_ident = $request->get('dataset');
    $stamp = $request->get('import');

    $import = $this->doQuery(
      'select * from import_view ' .
      ' where source_ident=? and dataset_ident=? and stamp=?',
      $source_ident, $dataset_ident, $stamp
    )->fetch();

    //
    // Set the filters
    //
    $filter_map = array(
      'country' => 'country',
      'adm1' => 'adm1',
      'adm2' => 'adm2',
      'adm3' => 'adm3',
      'adm4' => 'adm4',
      'adm5' => 'adm5',
      'sector' => 'sector',
      'subsector' => 'subsector',
      'org' => 'org',
      'loctype' => 'loctype',
    );
    list($sql_filter, $active_filters) = self::process_filters($request, $import->id, $filter_map);

    //
    // Early cutoff for focus on a single tag.
    // TODO refactor code to make this more modular
    //
    if ($tag) {
      header('Content-type: text/plain;charset=utf-8');
      $result = $this->get_value_preview($tag, $sql_filter);
      $output = fopen('php://output', 'w');
      fputcsv($output, array("#$tag", 'count'));
      foreach ($result as $row) {
        fputcsv($output, array($row->value, $row->count));
      }
      fclose($output);
      exit;
    }

    //
    // Get the output
    //
    $cols = $this->doQuery('select * from col_view where import=? order by col', $import->id)->fetchAll();
    $values = $this->doQuery('select * from value_view where row in ' . $sql_filter . ' order by row, col');

    //
    // Early cut-off if it's CSV
    // TODO refactor code to make this more modular
    //
    if ($format == 'csv') {
      header('Content-type: text/csv;charset=utf-8');
      dump_csv($cols, $values, fopen('php://output', 'w'));
      exit;
    }

    //
    // Metrics
    //
    $total = $this->get_total_count($sql_filter);

    // Get the preview counts

    foreach ($filter_map as $key => $tag) {
      if (!isset($active_filters[$tag])) {
        $tag_totals[$tag]  = $this->get_value_count($tag, $sql_filter);
        $tag_values[$tag] = $this->get_value_preview($tag, $sql_filter);
      }
    }

    //
    // Set the response parameters for the template
    //
    $response->setParameter('import', $import);
    $response->setParameter('total', $total);
    $response->setParameter('filters', $active_filters);

    $response->setParameter('tag_totals', $tag_totals);
    $response->setParameter('tag_values', $tag_values);

    $response->setParameter('cols', $cols);
    $response->setParameter('values', $values);

    $response->setTemplate('import-analysis');
  }

  /**
   * Count the total number of rows matching the filter.
   */
  private function get_total_count($sql_filter) {
    return 0 + $this->doQuery(
      'select count(distinct R.row) from (' . $sql_filter . ') R'
    )->fetchColumn();
  }

  /**
   * Count the number of distinct values for a column.
   *
   * @param $import The import identifier (long integer).
   * @param $tag The HXL tag.
   * @param $sql_filter The SQL filter subquery (optional).
   * @return The number of distinct values (integer).
   */
  private function get_value_count($tag, $sql_filter) {
    return 0 + $this->doQuery(
      'select count(distinct V.value) as count' .
      ' from value_view V ' .
      ' where V.tag_tag=? and V.row in ' . $sql_filter,
      $tag
    )->fetchColumn();
  }

  /**
   * Get the top values for a column.
   *
   * The result objects will have a "count" property with the number
   * of matches, and a "value" property with the cell value.
   *
   * @param $import The import identifier (long integer).
   * @param $tag The HXL tag.
   * @param $sql_filter The SQL filter subquery (optional).
   * @return A list of result objects.
   */
  private function get_value_preview($tag, $sql_filter) {
    return $this->doQuery(
      'select V.value, count(distinct V.row) as count ' .
      ' from value_view V' .
      ' where V.tag_tag=? and V.row in ' . $sql_filter .
      ' group by V.value' .
      ' order by count(distinct V.row) desc, V.value',
      $tag
    );
  }

  /**
   * Static: process the requested filters, and create a SQL (sub)query.
   *
   * @param $request The incoming HTTP request object.
   * @param $filter_map An associative array of request parameters mapped to HXL tags.
   * @return A list containing the SQL fragment and an array of the actual filters selected.
   */
  private static function process_filters(HttpRequest $request, $import_id, $filter_map) {

    // Return values
    $sql_filter = '';
    $active_filters = array();

    // Iterate through the filter map and construct the SQL query
    $n = 0;
    foreach ($filter_map as $http => $hxl) {
      $value = $request->get($http);
      if ($value !== null) {
        $n++;
        if (is_array($value)) {
          $value = array_pop($value);
        }
        $active_filters[$http] = $value;

        // Different treatment for the first one
        if ($n == 1) {
          $sql_filter = 'select V1.row from value_view V1';
          $where_clause = sprintf(" where V1.import=%d and V1.tag_tag='%s' and V1.value='%s'", $import_id, self::escape_sql($hxl), self::escape_sql($value));
        } else {
        $sql_filter .= sprintf(
          ' join value_view V%d on V1.row=V%d.row and V%d.tag_tag=\'%s\' and V%d.value=\'%s\'',
          $n, $n, $n, self::escape_sql($hxl), $n, self::escape_sql($value)
        );
        }
      }
    }

    if ($sql_filter) {
      $sql_filter = sprintf('(%s %s)', $sql_filter, $where_clause);
    } else {
      // count all rows
      $sql_filter = sprintf('(select row from row where import=%d)', $import_id);
    }

    // Return the results
    return array($sql_filter, $active_filters);
  }


}