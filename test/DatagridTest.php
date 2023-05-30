<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Osynapsy\Bcl\DataGrid\DataGrid;
require_once 'StringClean.php';

final class DatagridTest extends TestCase
{
    use StringClean;

    public function testDatagrid(): void
    {
        $Datagrid = new DataGrid('grid1');
        $this->assertEquals(
            '<div id="grid1" class="bcl-datagrid"><div class="d-none d-sm-block hidden-xs"><div class="row bcl-datagrid-thead"></div></div><div class="bcl-datagrid-body bg-white"><div class="row"><div class="col-lg-12 text-center bcl-datagrid-td">No data found</div></div></div></div>',
            $this->tabAndEolRemove((string) $Datagrid)
        );
    }

    public function testDatagridSetDataset(): void
    {
        $Datagrid = new DataGrid('grid1');
        $Datagrid->setDataset([['id' => '1', 'title' => 'test1', 'url1' => 'http://testurl1']]);
        $Datagrid->setDataset([['id' => '2', 'title' => 'test2', 'url1' => 'http://testurl2']]);
        $Datagrid->addColumn('id', 'id');
        $Datagrid->addColumn('Title', 'title');
        $this->assertEquals(
            '<div id="grid1" class="bcl-datagrid"><div class="d-none d-sm-block hidden-xs"><div class="row bcl-datagrid-thead"><div class="bcl-datagrid-th bcl-datagrid-th-order-by" data-idx="id"><span>id</span></div><div class="bcl-datagrid-th bcl-datagrid-th-order-by" data-idx="title"><span>Title</span></div></div></div><div class="bcl-datagrid-body bg-white"><div class="row bcl-datagrid-body-row"><div class="">2</div><div class="">test2</div></div></div></div>',
            $this->tabAndEolRemove((string) $Datagrid)
        );
    }       
}
