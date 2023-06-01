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

use Osynapsy\Html\Tag;

/**
 * Description of DataGridColumn
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class DataGridColumn
{
    const FIELD_TYPE_DATE_EU = 'date';
    const FIELD_TYPE_MONEY = 'money';
    const FIELD_TYPE_EMPTY  = 'empty';
    const FIELD_TYPE_EURO  = 'euro';
    const FIELD_TYPE_INTEGER = 'integer';
    const FIELD_TYPE_DOLLAR  = 'dollar';
    const FIELD_TYPE_CHECKBOX = 'check';
    const FIELD_TYPE_COMMAND = 'command';

    private $properties = [
        'dimension' => [
            'xs' => 12,
            'sm' => 12,
            'md' => 2,
            'lg' => 2,
            'xl' => 2
        ],
        'type' => 'string',
        'function' => null,
        'class' => null,
        'classTd' => [],
        'label' => '&nbsp;',
    ];
    private $parentId;

    public function __construct($label, $field, $class = '', $type = 'string', callable $function = null, $fieldOrderBy = null)
    {
        $this->properties['label'] = $label;
        $this->properties['field'] = $field;
        $this->properties['type'] = $type;
        $this->properties['class'] = $class;
        $this->properties['function'] = $function;
        $this->properties['fieldOrderBy'] = empty($fieldOrderBy) ? $field : $fieldOrderBy;
        $this->addClassTd([$class]);
    }

    private function builCheckBoxLabel()
    {
        return '<span class="fa fa-check bcl-datagrid-th-check-all" data-field-class="'.$this->parentId.''.$this->properties['field'].'"></span>';
    }

    /**
     * Build a head cell of DataGrid2 component
     *
     * @param array $orderedFields
     * @return Tag
     */
    public function buildTh($orderedFields)
    {
        $rawLabel = $this->properties['label'];
        if (empty($rawLabel)) {
            return;
        } elseif ($rawLabel[0] == '_') {
            return;
        }
        if ($this->properties['type'] === self::FIELD_TYPE_CHECKBOX) {
            $rawLabel = $this->builCheckBoxLabel();
        }
        $th = new Tag('div', null, $this->properties['class'].' bcl-datagrid-th');
        $th->add(new Tag('span'))->add($rawLabel);
        if ($this->properties['type'] !== self::FIELD_TYPE_CHECKBOX) {
            $this->buildThOrderByDummy($th, $orderedFields);
        }
        return $th;
    }

    public function buildThOrderByDummy($th, $orderedFields)
    {
        $orderByField = $this->properties['fieldOrderBy'];
        $th->attribute('data-idx', $orderByField)->addClass('bcl-datagrid-th-order-by');
        if (empty($orderedFields)) {
            return;
        }
        foreach ([$orderByField, $orderByField.' DESC'] as $i => $token) {
            $key = array_search($token, $orderedFields);
            if ($key !== false) {
                $icon = ($key + 1).' <i class="fa fa-arrow-'.(empty($i) ? 'up' : 'down').'"></i>';
                $th->add('<span class="bcl-datagrid-th-order-label">'.$icon.' </span>');
            }
        }
    }

    /**
     * Build a body cell of DataGrid2 component
     *
     * @param Tag $tr
     * @param type $record
     * @return Tag
     */
    public function buildTd(Tag $tr, array $record)
    {
        $properties = $this->properties;
        if (is_callable($properties['field'])) {
            $properties['function'] = $properties['field'];
            $value = null;
        } elseif (!array_key_exists($properties['field'], $record)) {
            $value = '<label class="label label-warning">No data found</label>';
        } else {
            $value = $record[$properties['field']];
        }
        $td = new Tag('div', null, 'bcl-datagrid-td');
        $td->add($this->valueFormatting($value, $td, $properties, $record, $tr));
        return $td;
    }

    /**
     * Format a value of cell for correct visualization
     *
     * @param string $value to format.
     * @param object $cell container of value
     * @param type $properties
     * @param type $rec record which contains value.
     * @param type $tr row container object
     * @return string
     */
    public function valueFormatting($value, &$cell, $properties, $rec, &$tr)
    {
        if (!empty($properties['function'])) {
            $value = $properties['function']($value, $rec, $cell, $tr);
        }
        if (!empty($properties['type']) && is_callable($properties['type'])) {
            $value = $properties['type']($value, $rec, $cell, $tr);
        }
        switch($properties['type']) {
            case self::FIELD_TYPE_CHECKBOX:
                if (empty($value)) {
                    break;
                }
                $value = $this->buildCheckBox($value);
                break;
            case self::FIELD_TYPE_DATE_EU:
                $datetime = \DateTime::createFromFormat('Y-m-d', $value);
                $value = $datetime === false ? $value : $datetime->format('d/m/Y');
                $properties['classTd'][] = 'text-center';
                break;
            case self::FIELD_TYPE_INTEGER:
                $properties['classTd'][] = 'text-right';
                break;
            case self::FIELD_TYPE_EMPTY:
                $value = '&nbsp;';
                break;
            case self::FIELD_TYPE_EURO:
            case self::FIELD_TYPE_MONEY:
            case self::FIELD_TYPE_DOLLAR;
                $value = $this->formatCurrencyValue($value, $properties['type']);
                $properties['classTd'][] = 'text-right';
                break;
            case self::FIELD_TYPE_COMMAND:
                $properties['classTd'][] = 'cmd-row';
                break;
        }
        if (!empty($properties['classTd'])) {
            $cell->attribute('class', implode(' ', $properties['classTd']), true);
        }
        return ($value != '0' && empty($value)) ? '&nbsp;' : $value;
    }

    private function formatCurrencyValue($rawValue, $type)
    {
        $value = '';
        switch($type){
            case self::FIELD_TYPE_EURO:
                $value = '&euro; ';
                break;
            case self::FIELD_TYPE_DOLLAR;
                $value = '$ ';
                break;
        }
        if (!empty($rawValue) && is_numeric($rawValue)) {
            $value .= number_format($rawValue, 2, ',', '.');
        } else {
            $value = $rawValue;
        }
        return $value;
    }

    private function buildCheckBox($value)
    {
        $class = $this->parentId.''.$this->properties['field'];
        $checkbox = new Tag('input');
        $checkbox->att([
            'type' => 'checkbox',
            'name' => $class.'['.$value.']',
            'class' => $class,
            'value' => $value
        ]);
        if (!empty($_POST[$class]) && !empty($_POST[$class][$value])) {
            $checkbox->att('checked','checked');
        }
        return $checkbox->get();
    }

    public function setParent($id)
    {
        $this->parentId = $id;
    }

    public function addClassTd(array $class)
    {
        $this->properties['classTd'] = array_merge($this->properties['classTd'], $class);
    }

    public function setClass($class)
    {
        $this->properties['class'] = $class;
        $this->properties['classTd'] = [$class];
    }

    public function __get($name)
    {
        return $this->properties[$name];
    }

    public function __set($name, $value)
    {
        $this->properties[$name] = $value;
    }
}
