<?php
namespace Osynapsy\Bcl\DataGrid;

use Osynapsy\Html\Tag;

/**
 * Description of DataGridBuilder
 *
 * @author peter
 */
class DataGridBuilder
{
    public static function build(DataGrid $Datagrid)
    {       
        $executionTime = microtime(true);                
        $title = $Datagrid->getTitle();
        $Paginator = $Datagrid->getPaginator();                
        if (!empty($Paginator)) {
            $Datagrid->setDataset(self::getDatasetFromPaginator($Paginator));
        }                
        if (!empty($title)) {
            $Datagrid->add(self::buildTitle($title));
        }        
        if ($Datagrid->showHeader) {
            $Datagrid->add(self::buildColumnHead($Datagrid->getColumns(), $Paginator ? $Paginator->getOrderBy() : ''));
        }        
        $Datagrid->add(self::bodyFactory($Datagrid->getColumns(), $Datagrid->getDataset(), $Datagrid->emptyMessage));
        //If datagrid has pager append to foot and show it.
        if (!empty($Paginator)) {
            $Datagrid->add(self::buildPagination($Paginator, $Datagrid, microtime(true) - $executionTime));
        }
    }
    
    protected static function buildTitle($title)
    {
        $tr = new Tag('div', null, 'row bcl-datagrid-title');
        $tr->add(new Tag('div', null, 'col-lg-12'))->add($title);
        return $tr;
    }
   
    protected static function getDatasetFromPaginator($Paginator)
    {        
        try {
            return $Paginator->loadData(null, true);
        } catch (\Exception $e) {
            return [['Error' => $e->getMessage()]];
        }
    }
    
    /**
     * Internal method for build a Datagrid column head.
     *
     * @return Tag
     */
    protected static function buildColumnHead(array $columns, $orderByFields)
    {
        $container = new Tag('div', null, 'd-none d-sm-block hidden-xs');
        $tr = $container->add(new Tag('div', null, 'row bcl-datagrid-thead'));        
        foreach(array_keys($columns) as $rawLabel) {
            $th = $columns[$rawLabel]->buildTh($orderByFields);
            if (empty($th)) {
                continue;
            }
            $tr->add($th);
        }
        return $container;
    }
    
    /**
     * Internal method for build Datagrid body.
     *
     * @return Tag
     */
    protected static function bodyFactory(array $columns, array $dataset, $emptyMessage, $rowMinimum = 0)
    {        
        $body = new Tag('div', null, 'bcl-datagrid-body bg-white');        
        $iRow = 0;
        foreach ($dataset as $row) {            
            $body->add(self::bodyRowFactory($columns, $row));            
            $iRow++;
        }
        if ($iRow === 0) {
            $body->add(self::emptyRowFactory($emptyMessage));
            $iRow++;
        }
        for ($iRow; $iRow < $rowMinimum; $iRow++) {
            $body->add(self::emptyRowFactory('&nbsp;'));
        }
        return $body;
    }
    
    /**
     * Internal method for build a Datagrid row
     *
     * @param type $row
     * @return Tag
     */
    protected static function bodyRowFactory($columns, $row)
    {
        $tr = new Tag('div', null, 'row bcl-datagrid-body-row');
        $commands = [];
        foreach ($columns as $column) {            
            $cell = $column->buildTd($tr, $row ?? []);
            if ($column->type !== DataGridColumn::FIELD_TYPE_COMMAND) {
                $tr->add($cell);
                continue;
            }
            $commands[] = $cell;
        }
        if (!empty($commands)) {
            $tr->add(self::buildCellCommands($commands));
        }        
        return $tr;
    }
    
    protected static function buildCellCommands($commands)
    {
        $cell = null;
        foreach ($commands as $i => $command) {
            if (empty($i)) {
                $cell = $command;
                continue;
            }
            $cell->add($command->child(0));
        }
        return $cell;
    }
    
    /**
     * Internal metod for build empty message.
     *
     * @param string $message
     * @return Void
     */
    protected static function emptyRowFactory($message)
    {        
        return '<div class="row"><div class="col-lg-12 text-center bcl-datagrid-td">'.$message.'</div></div>';        
    }
    
    /**
     * Build Datagrid pagination
     *
     * @return Tag
     */
    protected static function buildPagination($Paginator, $DataGrid, $executionTime = 0)
    {
        $row = new Tag('div', null, 'd-flex justify-content-end mt-1');
        if ($DataGrid->showExecutionTime) {
            $row->add(sprintf('<small class="p-2 me-auto">Execution time : %s sec</small>', $executionTime));
        }
        if ($DataGrid->showPaginationPageDimension) {
            $row->add('<div class="p-2">Elementi per pagina</div>');
            $row->add('<div class="px-2 py-1">'.$Paginator->getPageDimensionsCombo()->addClass('form-control-sm').'</div>');
        }
        if ($DataGrid->showPaginationPageInfo) {
            $row->add(new Tag('div', null, 'p-2'))->add($Paginator->getInfo());
        }
        $row->add(new Tag('div', null, 'pt-1 pl-2'))->add($Paginator)->setPosition('end');
        return $row;
    }   
}
