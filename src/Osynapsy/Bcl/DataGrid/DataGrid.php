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
use Osynapsy\Html\DOM;

class DataGrid extends Base
{
    const BORDER_FULL = 'full';
    const BORDER_HORIZONTAL = 'horizontal';

    private $columns = [];
    public $emptyMessage = 'No data found';
    private $paginator;    
    private $title;    
    private $rowWidth = 12;
    private $rowMinimum = 0;
    public $showHeader = true;
    public $showPaginationPageDimension = true;
    public $showPaginationPageInfo = true;
    public $showExecutionTime = false;        

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
        return DataGridBuilder::build($this);
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

    public function getColumns()
    {
        return $this->columns;
    }
    
    /**
     * return pager object
     *
     * @return Pagination object
     */
    public function getPaginator()
    {
        return $this->paginator;
    }

    /**
     * Get number of rows of data
     *
     * @return int
     */
    public function getRowsCount()
    {
        return count($this->dataset);
    }
    
    public function getTitle()
    {
        return $this->title;
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

    public function setDbPaginator($dbPaginator, $rowForPage = 5, $showPageDimension = true, $showPageInfo = true, $showExecutionTime = true)
    {
        $paginatorId = sprintf('%s%s', $this->id, strpos($this->id, '_') ? '_paginator' : 'Paginator');
        $DataGridPaginator = new DataGridPaginator($paginatorId, $rowForPage);
        $DataGridPaginator->setDbPaginator($dbPaginator);
        $DataGridPaginator->setParentComponent($this->id);
        $this->paginator = $DataGridPaginator;
        $this->showPaginationPageDimension = $showPageDimension;
        $this->showPaginationPageInfo = $showPageInfo;
        $this->showExecutionTime = $showExecutionTime;
        return $this->paginator;
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
