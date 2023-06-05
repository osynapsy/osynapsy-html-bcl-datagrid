<?php
namespace Osynapsy\Bcl\DataGrid;

use Osynapsy\Html\Tag;
use Osynapsy\Html\Component\Base;
use Osynapsy\Html\Component\Hidden;
use Osynapsy\Html\Component\ComboBox;
use Osynapsy\Html\DOM;


/**
 * Description of Pagination
 *
 * @author Pietro Celeste
 */
class DataGridPaginator extends Base
{    
    private $entity = 'Record';
    protected $data = [];
    protected $pageDimensionPalceholder = '- Dimensione pagina -';    
    private $filters = [];
    private $fields = [];
    private $loaded = false;
    private $dbPaginator;    
    private $orderBy = [];
    private $parentComponent;
    private $position = 'center';
    private $statistics = [
        //Dimension of the pag in row;
        'pageDimension' => 10,
        'totalPages' => 1,
        'pageCurrent' => 1,
        'rowsTotal' => 0
    ];
    private $pageDimensions = [
        1 => ['10', '10 righe'],
        2 => ['20', '20 righe'],
        5 => ['50', '50 righe'],
        10 => ['100', '100 righe'],
        20 => ['200', '200 righe']
    ];
    /**
     * Costructor of pager component.
     *
     * @param type $id Identify of component
     * @param type $pageDimension Page dimension in number of row
     * @param type $tag Tag of container
     * @param type $infiniteContainer Enable infinite scroll?
     */
    public function __construct($id, $pageDimension = 10, $tag = 'div')
    {
        DOM::requireJs('bcl/datagrid/paginator.js');
        parent::__construct($tag, $id);        
        $this->addClass('bcl-paginator');        
        $this->setPageDimension($pageDimension);
    }

    public function preBuild()
    {
        if (!$this->loaded) {
            $this->loadData();
        }
        $this->add($this->fieldCurrentPageFactory());
        $this->add($this->fieldOrderByFactory());        
        list($pageMin, $pageMax) = $this->calcPageMinMax();
        $this->add($this->ulFactory($pageMin, $pageMax));
    }
    
    protected function fieldCurrentPageFactory()
    {
        $Hidden = new Hidden($this->id);
        $Hidden->addClass('bcl-paginator-current-page');
        $Hidden->setValue($_REQUEST[$this->id] ?? '');
        return $Hidden;
    }
    
    protected function fieldOrderByFactory()
    {
        $fieldId = $this->id . 'OrderBy';
        $Hidden = new Hidden($fieldId);
        $Hidden->addClass('bcl-paginator-order-by');
        $Hidden->setValue($_REQUEST[$fieldId] ?? '');
        return $Hidden;
    }
    
    public function loadData($defaultPage = null)
    {
        $requestPage = filter_input(\INPUT_POST, $this->id) ?? $defaultPage;        
        $sortString = filter_input(\INPUT_POST, $this->id.'OrderBy');        
        $pageDimension = $this->statistics['pageDimension'];
        $this->data = $this->dbPaginator->get($requestPage, $pageDimension, $this->sortStringToArray($sortString));
        $this->statistics = $this->dbPaginator->getAllMeta();        
        $this->loaded = true;
        return $this->data;
    }
    
    protected function sortStringToArray($rawsort)
    {
        return str_replace(['][','[',']'], [',', ''], $rawsort);        
    }

    protected function calcPageMinMax()
    {
        $dim = min(7, $this->statistics['totalPages']);
        $app = floor($dim / 2);
        $pageMin = max(1, $this->statistics['pageCurrent'] - $app);
        $pageMax = max($dim, min($this->statistics['pageCurrent'] + $app, $this->statistics['totalPages']));
        $pageMin = min($pageMin, $this->statistics['totalPages'] - $dim + 1);
        return [$pageMin, $pageMax];
    }

    protected function ulFactory($pageMin, $pageMax)
    {
        $ul = new Tag('ul', null, 'pagination pagination-sm justify-content-'.$this->position);
        $ul->add($this->liFactory('&laquo;', 'first', $this->statistics['pageCurrent'] < 2 ? 'disabled' : ''));
        for ($i = $pageMin; $i <= $pageMax; $i++) {
            $ul->add($this->liFactory($i, $i, $i == $this->statistics['pageCurrent'] ? 'active' : ''));
        }
        $ul->add($this->liFactory('&raquo;', 'last', $this->statistics['pageCurrent'] >= $this->statistics['totalPages'] ? 'disabled' : ''));
        return $ul;
    }

    protected function liFactory($label, $value, $class)
    {
        $li = new Tag('li', null, trim('page-item '.$class));
        $li->add(new Tag('a', null, 'page-link'))
           ->attribute('data-value', $value)
           ->attribute('href','#')
           ->add($label);
        return $li;
    }

    public function addField($field)
    {
        $this->fields[] = $field;
    }

    public function addFilter($field, $value = null)
    {
        $this->filters[$field] = $value;
    }   

    public function getSort($requestSort)
    {
        $this->orderBy = empty($requestSort) ? $this->orderBy : str_replace(['][', '[', ']'], [',' ,'' ,''], $requestSort);
        return $this->orderBy;
    }

    public function getPageDimensionsCombo()
    {
        $fieldId = $this->pageDimensionFieldIdFactory();
        $Combo = new ComboBox($fieldId);
        $Combo->setPlaceholder(false);
        $Combo->attribute('onchange',"Osynapsy.refreshComponents(['{$this->parentComponent}'])")
              ->setDataset($this->pageDimensions);
        $Combo->setValue($_REQUEST[$fieldId] ?? $this->pageDimensions[0]);
        return $Combo;
    }

    protected function pageDimensionFieldIdFactory()
    {
        $postfix = strpos($this->id, '_') ? '_page_dimension' : 'PageDimension';
        return $this->id . $postfix;
    }

    public function getInfo()
    {
        $end = min($this->getStatistic('pageCurrent') * $this->getStatistic('pageDimension'), $this->getStatistic('totalRows'));
        $start = ($this->getStatistic('pageCurrent') - 1) * $this->getStatistic('pageDimension') + 1;        
        return sprintf(' %s - %s di %s %s', min($start, $end), $end, $this->getStatistic('totalRows'), strtolower($this->entity));
    }

    public function getOrderBy()
    {
        return $this->orderBy;
    }

    public function getTotal($key)
    {
        return $this->getStatistic('total'.ucfirst($key));
    }

    public function getStatistic($key = null)
    {
        return array_key_exists($key, $this->statistics) ? $this->statistics[$key] : null;
    }   

    public function setOrder($field)
    {
        $this->orderBy = str_replace(['][', '[', ']'], [',' ,'' ,''], $field);
        return $this;
    }

    public function setPageDimension($pageDimension)
    {
        $comboId = $this->pageDimensionFieldIdFactory();
        if (!empty($_REQUEST[$comboId])) {
            $this->statistics['pageDimension'] = $_REQUEST[$comboId];
        } else {
            $_REQUEST[$comboId] = $this->statistics['pageDimension'] = $pageDimension;
        }
        if ($pageDimension === 10) {
            return;
        }
        foreach(array_keys($this->pageDimensions) as $key) {
            $dimension = $pageDimension * $key;
            $this->pageDimensions[$key] = [$dimension, "{$dimension}"];
        }
    }

    public function setPageDimensionPlaceholder($label)
    {
        $this->pageDimensionPalceholder = $label;
    }

    public function setParentComponent($componentId)
    {
        $this->parentComponent = $componentId;
        $this->attribute('data-parent', $componentId);
        return $this;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function setDbPaginator($dbPaginator)
    {
        $this->dbPaginator = $dbPaginator;
        return $this;
    }

    public function getStatistics()
    {
        return $this->page;
    }
}
