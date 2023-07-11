<?php

namespace Aclips\Components;

use Bitrix\Main\Grid;
use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\UI;

class BaseGridComponent extends \CBitrixComponent
{
    const GRID_ID = 'BASE_GRID';

    public function executeComponent()
    {
        $grid_id = self::GRID_ID;
        $grid_options = new Grid\Options($grid_id);

        $grid_filter = $this->getFilterFields();

        $entityRepository = $this->getEntityRepository();

        $filter = $this->getEntityFilter($grid_id, $grid_filter);

        $select = $this->getEntitySelect();

        $sort = $this->getSorting($grid_options);

        $nav = $this->initNav($grid_options);

        $action_panel = $this->getActionPanel();

        $elements = $entityRepository::getList([
            'filter' => $filter,
            'select' => $select,
            "order" => $sort,
            "count_total" => true,
            "offset" => $nav->getOffset(),
            "limit" => $nav->getLimit()
        ]);

        $nav->setRecordCount($elements->getCount());

        $grid_rows = [];

        foreach ($elements as $element) {
            $prepared_element = $this->getPreparedElement($element);

            $actions = $this->getElementActions($element);

            $row = [
                'id' => $element['ID'],
                'data' => $element,
                'columns' => $prepared_element,
                'editable' => 'Y',
                'actions' => $actions
            ];

            $grid_rows[] = $row;
        }

        $this->arResult['NAV'] = $nav;

        $this->arResult['GRID_ID'] = $grid_id;
        $this->arResult['GRID_FILTER'] = $grid_filter;
        $this->arResult['GRID_COLUMNS'] = $this->getGridColumns();
        $this->arResult['ROWS'] = $grid_rows;
        $this->arResult["ACTION_PANEL"] = $action_panel;

        $this->includeComponentTemplate();
    }

    public function getEntityRepository()
    {
        $entityReporitory = new \Bitrix\Main\UserTable();

        return $entityReporitory;
    }

    public function initNav($grid_options)
    {
        $navParams = $grid_options->GetNavParams();

        $grid_id = $grid_options->getid();

        $nav = new UI\PageNavigation($grid_id);

        $pageSizes = [];
        foreach (["5", "10", "20", "30", "50", "100"] as $index) {
            $pageSizes[] = ['NAME' => $index, 'VALUE' => $index];
        }

        $nav->allowAllRecords(true)
            ->setPageSize($navParams['nPageSize'])
            ->setPageSizes($pageSizes)
            ->initFromUri();

        return $nav;
    }

    public function getSorting($grid)
    {
        $sort = $grid->GetSorting([
            'sort' => [
                'ID' => 'DESC'
            ],
            'vars' => [
                'by' => 'by',
                'order' => 'order'
            ]
        ]);

        return $sort['sort'];
    }

    public function getEntityFilter($grid_id, $grid_filter)
    {
        return $this->prepareFilter($grid_id, $grid_filter);
    }

    public function getEntitySelect()
    {
        return ['*'];
    }

    public function getPreparedElement($fields)
    {
        return $fields;
    }

    public function getElementActions($fields)
    {
        $actions = [];

        $actions[] = [
            'text' => 'Base Action',
            'onclick' => "BX.Aclips.Base.List.baseAction('${fields['NAME']}')",
            'default' => true
        ];

        return $actions;
    }

    private function getFilterFields(): array
    {
        $filterFields = [
            [
                'id' => 'NAME',
                'name' => 'Имя',
                'type' => 'string',
                'default' => true
            ],
            [
                'id' => 'DATE_REGISTER',
                'name' => 'Дата регистрации',
                'type' => 'date',
                'default' => true
            ],
            [
                'id' => 'USER',
                'name' => "Пользователь",
                'type' => 'dest_selector',
                'default' => true,
            ],
            [
                'id' => 'UF_DEPARTMENT',
                'name' => 'Подразделение',
                'type' => 'entity_selector',
                'params' => [
                    'multiple' => 'Y',
                    'dialogOptions' => [
                        'height' => 240,
                        'context' => 'filter',
                        'entities' => [
                            [
                                'id' => 'department',
                                'options' => [
                                    "selectMode" => "departmentsOnly",
                                    "allowFlatDepartments" => true,
                                ],
                            ],
                        ]
                    ],
                ],
                'default' => true,
            ],
            [
                'id' => 'ACTIVE',
                'name' => 'Активность',
                'type' => 'list',
                'items' => ["" => "Не указана", "Y" => "Да", "N" => "Нет"],
                'default' => true
            ],
        ];

        return $filterFields;
    }

    private function getGridColumns(): array
    {
        $columns = [
            [
                'id' => 'NAME',
                'name' => 'Имя',
                'sort' => 'NAME',
                'default' => true
            ],
            [
                'id' => 'DATE_REGISTER',
                'name' => 'Дата регистрации',
                'sort' => 'DATE_REGISTER',
                'default' => true
            ]
        ];

        return $columns;
    }

    protected function getActionPanel(): array
    {
        $panel = [
            'GROUPS' => [
                [
                    'ITEMS' => [
                        [
                            'TYPE' => Types::BUTTON,
                            'ID' => "group_action_button",
                            'CLASS' => "apply",
                            'TEXT' => "Base Group Action",
                            'ONCHANGE' => [[
                                'ACTION' => 'CALLBACK',
                                'DATA' => [
                                    ['JS' => 'BX.Aclips.Base.List.baseGroupAction()'],
                                ],
                            ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $panel;
    }

    private function prepareFilter($grid_id, $grid_filter): array
    {
        $filter = [];

        $filterOption = new \Bitrix\Main\UI\Filter\Options($grid_id);
        $filterData = $filterOption->getFilter([]);

        foreach ($filterData as $k => $v) {
            $filter[$k] = $v;
        }

        $filterPrepared = \Bitrix\Main\UI\Filter\Type::getLogicFilter($filter, $grid_filter);

        if (!empty($filter['FIND'])) {
            $findFilter = [
                'LOGIC' => 'OR',
                [
                    '%NAME' => $filter['FIND']
                ]
            ];

            if (!empty($filterPrepared)) {
                $filterPrepared[] = $findFilter;
            } else {
                $filterPrepared = $findFilter;
            }
        }

        if (!empty($filterPrepared['USER'])) {
            $filterPrepared["ID"] = str_replace("U", "", $filterData['USER']);
            unset($filterPrepared["USER"]);
        }

        return $filterPrepared;
    }
}
