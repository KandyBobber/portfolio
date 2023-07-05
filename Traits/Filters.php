<?php

namespace App\Http\Controllers\Traits;

trait Filters
{
    //  Приклад масиву
    //  protected $filters = [
    //     'peopleList' => [
    //         [Це буде група or
    //             ['colName' => 'last_name', 'requestName' => 'pibFind', 'type' => 'like'],
    //             ['colName' => 'first_name', 'requestName' => 'pibFind', 'type' => 'like'],
    //             ['colName' => 'middle_name', 'requestName' => 'pibFind', 'type' => 'like'],
    //         ],
    //         ['colName' => 'last_name', 'requestName' => 'last_name', 'type' => 'like'], а це просто where
    //     ],
    // ];

    /**
     * Додає фільтра до запиту, дані про фільтра бере з змінної що має знаходитись в моделі і мати назву filters, в зміній має бути іменований масив, кожна з комірок якого є масивом. Ячейки мають мати імена що застосовуться для їх пошуку та виклику по $filtersName. Масиви комірок мають мати такі параметри ['colName' => '', requestName => '', 'type' => ''] де: colName - ім'я колонки, requestName - при потребі ім'я змінної (за замовчуванням дорівнює colName), type - тип порівняння (за замовчуванням дорівнює '=')
     * @param mixed $collection
     * @param mixed $request
     * @param string|array $filtersName
     * @param mixed $connections
     * @return mixed
     */
    public function filter($collection, $request, string|array $filtersName, array $connections = [])
    {
        // dump($collection);
        foreach ($collection as $coll_key => $coll_item) {
            // dump($coll_item->id);
            if ($connections) {
                foreach ($connections as $connectionItem) {
                    $connectionArr = explode('.', $connectionItem);
                    $connection = $connectionArr[0];

                    //кількість переглядів в приєднанні, скільки записів я хочу переглянути, від 1 до всіх
                    $mainRule = (isset($connectionArr[1]) && ((is_integer($connectionArr[1]) && $connectionArr[1] > 0) || $connectionArr[1] === 'all')) 
                    ? $connectionArr[1] : 1;

                    if (is_array($coll_item->$connection) && !empty(reset($coll_item->$connection))) {
                        $notDelete = false;
                    } else {
                        // dump('пропуск');
                        $notDelete = true;
                        continue;
                    }
                    

                    if ($mainRule === 'all') {
                        foreach ($coll_item->$connection as $object) {
                            if ($this->filterBody($request, $filtersName, $object)) {
                                $notDelete = true;
                                break;
                            }
                        }
                    } else {
                        $count = 1;
                        //Пошук хоча би 1 співпадіння в заданій кількості записів
                        foreach ($coll_item->$connection as $object) {
                            if ($count > $mainRule) break;

                            $filterRes = $this->filterBody($request, $filtersName, $object, $collection[$coll_key]->id);
                            if ($mainRule > 1 && $filterRes) {
                                $notDelete = true;
                                break;
                            } else {
                                $notDelete = $filterRes;
                            }

                            $count++;
                        }
                    }

                    if (!$notDelete) break;
                }
            } else $notDelete = $this->filterBody($request, $filtersName, $coll_item);

            // dump('дійшов');

            if (!$notDelete) 
            {
                // Перевіряв чому прибирало не того
                // dump('Не під ' . $coll_key . ' ' . $collection[$coll_key]->id);
                // dump($collection[$coll_key]);
                unset($collection[$coll_key]);
            } else {
                // dump('Так ' . $collection[$coll_key]->id);
            }
        
        }
        return $collection;
    }

    /**
     * Шукає та перевіряє конкретне значення
     * @param mixed $collection
     * @param mixed $request
     * @param string|array $filtersName
     * @return bool
     */
    protected function filterBody($request, $filtersName, $object, $id = '')
    {
        if (!is_array($filtersName) && !isset($object->filters[$filtersName])) return true;
        
        $filters = is_array($filtersName) ? $filtersName : $object->filters[$filtersName];
        // dd($filters);
        //Цей foreach потрібен для або, щоб можна було шукати в декількох полях
        foreach ($filters as $filters_it) {
            $filter_arr = !empty($filters_it[0]['colName']) ? $filters_it : [$filters_it];

            $find = false;
            $notNullValue = 0;

            foreach ($filter_arr as $filter) {

                $type = !empty($filter['type']) ? $filter['type'] : '=';
                $colName = $filter['colName'];

                if (isset($filter['value'])) {
                    $filterValue = $filter['value'];
                    $notNullValue++;
                } else {
                    $requestName = !empty($filter['requestName']) ? $filter['requestName'] : $filter['colName'];
                    if (isset($request->$requestName) && (!empty($request->$requestName) || is_array($request->$requestName))) {
                        $filterValue = $request->$requestName;
                        $notNullValue++;
                    }
                    else continue;
                }

                if (isset($object->$colName)) {
                    $value = $object->$colName;
                }
                else continue;

                if (is_string($filterValue) || is_integer($filterValue)) {

                    if (is_string($filterValue)) {
                        $value = $value . '';
                        $filterValue = trim($filterValue);
                    } else {
                        $value = $value * 1;
                    }
                    
                    // dump('фільтр');
                    // dump($filterValue);
                    // dump('значення');
                    // dump($value);

                    if ($type === 'like') {
                        $filter_ii = str_replace('/', '\/', addslashes($filterValue));
                        // dump($filter_ii);
                        if (preg_match('/'. $filter_ii .'/ui', addslashes($value))) $find = true;
                    } else {
                        switch ($type) {
                            case '>':
                                if($value > $filterValue) $find = true;
                                // dump($value . ' > ' . $filterValue);
                                break;
                            
                            case '<':
                                if($value < $filterValue) $find = true;
                                // dump($value . ' < ' . $filterValue);
                                break;
                            
                            case '>=':
                                if($value >= $filterValue) $find = true;
                                // dump($value . ' >= ' . $filterValue);
                                break;
                            
                            case '<=':
                                if($value <= $filterValue) $find = true;
                                // dump($value . ' <= ' . $filterValue);
                                break;
                            
                            case '!':
                                if($value !== $filterValue) $find = true;
                                // dump($value . ' != ' . $filterValue . ' ' . $id);
                                break;
                            
                            default:
                                if($value === $filterValue) $find = true;
                                // dump($value . ' = ' . $filterValue);
                                break;
                        }
                    }
                    // dump($filterValue . ' ' . $value);

                } elseif (is_array($filterValue)) {
                    // dump('інше');
                    $filterValue = array_values($filterValue);

                    if ($type === 'between' && count($filterValue) === 2) {
                        //whereBetween
                        if ($filterValue[0] <= $value &&  $value <= $filterValue[1]) $find = true;
                    } else {
                        //whereIn
                        if (in_array($value, $filterValue)) $find = true;
                    }
                }
            }

            if (!$find && $notNullValue !== 0) return false;
        }

        return true;
    }

    // public function binding($collection, $bindWith, $roles)
    // {
    //     [
    //         'item' => ['subjects', 'rank_id'],
    //         'bind' => 'id',
    //         'needle' => 'all', ['name', 'rank'], ['name', 'rank']
    //         'bindName' => 

    //     ]
        



    // }
}