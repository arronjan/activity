<?php
function generate(int $rows, int $cols, int $min_value = 1, int $max_value = 100)
{
    $dynamicArray = [];
    for ($i = 0; $i < $rows; $i++) {
        $rowArray = [];
        for ($j = 0; $j < $cols; $j++) {
            $rowArray[] = rand($min_value, $max_value);
        }
        $dynamicArray[] = $rowArray;
    }
    return $dynamicArray;
}

function render(array $data)
{
    $stayl = '<table border="1" style="border-collapse: collapse;">';
    foreach ($data as $rowIndex => $row) {
        $stayl .= '<tr>';
        foreach ($row as $colIndex => $value) {
            $stayl .= '<td>' . htmlspecialchars($value) . '</td>';
        }
        $stayl .= '</tr>';
    }
    $stayl .= '</table>';
    return $stayl;
}


$numRows = isset($_GET['rows']) ? (int)$_GET['rows'] : 2;
$numCols = isset($_GET['cols']) ? (int)$_GET['cols'] : 5;


$my2DArray = generate($numRows, $numCols);
$tableHtml = render($my2DArray);
echo $tableHtml;

?>
