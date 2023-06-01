<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Bcl\DataGrid;

use Osynapsy\Html\Component\Base;
use Osynapsy\Html\Tag;
use Osynapsy\Html\DOM;
use Osynapsy\Bcl4\IPagination;
use Osynapsy\Bcl4\Pagination;

class DataGrid extends Base
{
    const BORDER_FULL = 'full';
    const BORDER_HORIZONTAL = 'horizontal';

    private $columns = [];
    private $emptyMessage = 'No data found';
    private $pagination;
    private $showHeader = true;
    private $title;
    private $body;
    private $rowWidth = 12;
    private $rowMinimum = 0;
    private $showPaginationPageDimension = true;
    private $showPaginationPageInfo = true;
    private $showExecutionTime = false;
    private $totalFunction;
    protected $totals = [];

    public function __construct($name)
    {
        DOM::requireCss('bcl/datagrid/style.css');
        DOM::requireJs('bcl/datagrid/script.js');
        parent::__construct('div', $name);
        $this->addClass('bcl-datagrid');        
    }

    /**
     * Internal method to build component
     */
    public function preBuild()
    {
        //If datagrid has pager get data from it.
        $executionTime = microtime(true);
        if (!empty($this->pagination)) {
            try {
                $this->setData($this->pagination->loadData(null, true));
            } catch (\Exception $e) {
                $this->printError($e->getMessage());
            }
        }
        //If Datagrid has title append and show it.
        if (!empty($this->title)) {
            $this->add($this->buildTitle($this->title));
        }
        //If showHeader === true show datagrid columns.
        if ($this->showHeader) {
            $this->add($this->buildColumnHead());
        }
        //Append Body to datagrid container.
        $this->bodyFactory();
        $this->add($this->body);
        //If datagrid has pager append to foot and show it.
        if (!empty($this->pagination)) {
            $this->add($this->buildPagination($this->pagination, microtime(true) - $executionTime));
        }
    }

    private function printError($error)
    {
        $this->setData([['error' => str_replace(PHP_EOL,'<br>',$error)]]);
        $this->columns = [];
        $this->addColumn('Error', 'error', 'col-lg-12');
    }

    /**
     * Internal method for build a Datagrid column head.
     *
     * @return Tag
     */
    private function buildColumnHead()
    {
        $container = new Tag('div', null, 'd-none d-sm-block hidden-xs');
        $tr = $container->add(new Tag('div', null, 'row bcl-datagrid-thead'));
        $orderByFields = $this->pagination ? explode(',', $this->pagination->getOrderBy()) : null;
        foreach(array_keys($this->columns) as $rawLabel) {
            $th = $this->columns[$rawLabel]->buildTh($orderByFields);
            if (empty($th)) {
                continue;
            }
            $tr->add($th);
        }
        return $container;
    }

    /**
     * Internal metod for build empty message.
     *
     * @param string $message
     * @return Void
     */
    protected function emptyRowFactory($message)
    {
        $this->body->add(
            '<div class="row"><div class="col-lg-12 text-center bcl-datagrid-td">'.$message.'</div></div>'
        );
    }

    /**
     * Internal method for build Datagrid body.
     *
     * @return Tag
     */
    protected function bodyFactory()
    {
        $this->body = new Tag('div');
        $this->body->attribute('class','bcl-datagrid-body bg-white');
        if ($this->rowWidth === 12) {
            $this->normalBodyFactory($this->dataset);
        } else {
            $this->bodyWithRowOversizeFactory();
        }
    }

    protected function normalBodyFactory($rows)
    {
        $i = 0;
        foreach ($rows as $row) {
            $this->body->add($this->bodyRowFactory($row));
            $this->execTotalFunction($row ?? []);
            $i++;
        }
        if ($i === 0) {
            $this->emptyRowFactory($this->emptyMessage);
            $i++;
        }
        for ($i; $i < $this->rowMinimum; $i++) {
            $this->emptyRowFactory('&nbsp;');
        }
        $this->execTotalFunction([false]);
    }

    protected function execTotalFunction(array $rec)
    {
        if (empty($this->totalFunction)) {
            return;
        }
        $function = $this->totalFunction;
        $tr = $function($rec, $this->totals);
        if (!empty($tr)) {
           $this->body->add($tr);
        }
    }

    protected function bodyWithRowOversizeFactory()
    {
        $rowClass =  'bcl-datagrid-body-row row col-lg-'.$this->rowWidth;
        foreach ($this->data as $recIdx => $rec) {
            if (($recIdx) % (12 / $this->rowWidth) === 0) {
                $row = $this->body->add(new Tag('div', null, 'row'));
            }
            $row->add($this->bodyRowFactory($rec, $rowClass));
            if (empty($this->totalFunction)) {
                continue;
            }
            $function = $this->totalFunction;
            $function($rec, $this->totals);
        }
    }

    /**
     * Internal method for build a Datagrid row
     *
     * @param type $row
     * @return Tag
     */
    private function bodyRowFactory($record, $class = 'row bcl-datagrid-body-row')
    {
        $tr = new Tag('div', null, $class);
        $commands = [];
        foreach ($this->columns as $column) {
            $cell = $column->buildTd($tr, $record ?? []);
            if ($column->type !== DataGridColumn::FIELD_TYPE_COMMAND) {
                $tr->add($cell);
                continue;
            }
            $commands[] = $cell;
        }
        if (!empty($commands)) {
            $tr->add($this->buildCellCommands($commands));
        }
        if (!empty($record['_url_detail'])) {
            $tr->att('data-url-detail', $record['_url_detail']);
        }
        return $tr;
    }

    protected function buildCellCommands($commands)
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
     * Build Datagrid pagination
     *
     * @return Tag
     */
    private function buildPagination($pagination, $executionTime = 0)
    {
        $row = new Tag('div', null, 'd-flex justify-content-end mt-1');
        if ($this->showExecutionTime) {
            $row->add(sprintf('<small class="p-2 mr-auto">Tempo di esecuzione : %s sec</small>', $executionTime));
        }
        if ($this->showPaginationPageDimension) {
            $row->add('<div class="p-2">Elementi per pagina</div>');
            $row->add('<div class="px-2 py-1">'.$pagination->getPageDimensionsCombo()->addClass('form-control-sm').'</div>');
        }
        if ($this->showPaginationPageInfo) {
            $row->add(new Tag('div', null, 'p-2'))->add($pagination->getInfo());
        }
        $row->add(new Tag('div', null, 'pt-1 pl-2'))->add($pagination)->setPosition('end');
        return $row;
    }

    private function buildTitle()
    {
        $tr = new Tag('div', null, 'row bcl-datagrid-title');
        $tr->add(new Tag('div', null, 'col-lg-12'))->add($this->title);
        return $tr;
    }

    /**
     * Add a data column view
     *
     * @param type $label of column (show)
     * @param type $field name of array data field to show
     * @param type $class css to apply column
     * @param type $type type of data (necessary for formatting value)
     * @param callable $function for manipulate data value
     * @return $this
     */
    public function addColumn($label, $field, $class = '', $type = 'string', callable $function = null, $fieldOrderBy = null)
    {
        if (is_callable($field)) {
            $function = $field;
            $field = '';
        } elseif ($type !== 'date' && is_callable($type)) {
            $function = $type;
            $type = 'string';
        }
        $this->columns[$label] = new DataGridColumn($label, $field, $class, $type, $function, $fieldOrderBy);
        $this->columns[$label]->setParent($this->id);
        return $this->columns[$label];
    }

    /**
     * Remove column from repo of columns
     *
     * @param string $label
     */
    public function removeColumn($label)
    {
        if (array_key_exists($label, $this->columns)) {
            unset($this->columns[$label]);
        }
    }

    /**
     * Get column by label
     *
     * @param string $label
     * @return Column
     */
    public function getColumn($label)
    {
        return $this->columns[$label];
    }

    /**
     * return pager object
     *
     * @return Pagination object
     */
    public function getPagination()
    {
        return $this->pagination;
    }

    /**
     * Get number of rows of data
     *
     * @return int
     */
    public function getRowsCount()
    {
        return count($this->data);
    }

    /**
     * Hide Header
     *
     * @return $this;
     */
    public function hideHeader()
    {
        $this->showHeader = false;
        return $this;
    }

    /**
     * Set array of columns rule
     *
     * @param type $columns
     * @return $this
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set message to show when no data found.
     *
     * @param type $message
     * @return $this
     */
    public function setEmptyMessage($message)
    {
        $this->emptyMessage = $message;
        return $this;
    }

    public function setRowMinimum($min)
    {
        $this->rowMinimum = $min;
    }

    /**
     * Set width of row in bootstrap unit grid (max width = 12)
     *
     * @param int $width
     */
    public function setRowWidth($width)
    {
        $this->rowWidth = $width;
        return $this;
    }

    /**
     * Set a pagination object
     *      *
     * @param type $db Handler db connection
     * @param string $sqlQuery Sql query
     * @param array $sqlParameters Parameters of sql query
     * @param integer $pageDimension Page dimension (in row)
     */
    public function setPagination($db, $sqlQuery, $sqlParameters, $pageDimension = 10, $showPageDimension = true, $showPageInfo = true, $showExecutionTime = false)
    {
        $paginationId = $this->id.(strpos($this->id, '_') ? '_pagination' : 'Pagination');
        $this->pagination = new Pagination($paginationId, empty($pageDimension) ? 10 : $pageDimension);
        $this->pagination->setSql($db, $sqlQuery, $sqlParameters);
        $this->pagination->setParentComponent($this->id);
        $this->showPaginationPageDimension = $showPageDimension;
        $this->showPaginationPageInfo = $showPageInfo;
        $this->showExecutionTime = $showExecutionTime;
        return $this->pagination;
    }

    public function setPaginator(IPagination $paginator, $showPageDimension = true, $showPageInfo = true)
    {
        $this->pagination = $paginator;
        $this->pagination->setParentComponent($this->id);
        $this->showPaginationPageDimension = $showPageDimension;
        $this->showPaginationPageInfo = $showPageInfo;
        return $this->pagination;
    }

    /**
     * Method for set table and rows borders visible
     *
     * return void;
     */
    public function setBorderOn($borderType = 'horizontal')
    {
        $this->addClass(sprintf('bcl-datagrid-border-on bcl-datagrid-border-on-%s', $borderType));
    }

    /**
     * Set title to show on top of datagrid
     *
     * @param type $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function setTotalFunction(callable $function)
    {
        $this->totalFunction = $function;
    }
}
