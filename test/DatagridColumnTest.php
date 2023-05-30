<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Osynapsy\Bcl\DataGrid\DataGridColumn;
use Osynapsy\Html\Tag;
require_once 'StringClean.php';

final class DatagridColumnTest extends TestCase
{
    use StringClean;

    public function testDataGridColumn(): void
    {
        $DatagridColumn = new DataGridColumn('Test', 'id', 'columnClass');
        $this->assertEquals(
            '<div class="columnClass">1</div>',
            $this->tabAndEolRemove((string) $DatagridColumn->buildTd(new Tag('tr'), ['id' => '1', 'test' => 'test']))
        );
    }     
}
