<?php

namespace App\Http\Controllers\Traits;

trait Filters
{
    // Приклад масиву що отримує модель для фільтрації, прописана властивість моелі
    // Де colName-назва колонки в таблиці, requestName-ім'я властивості в request,  type-тип співставлення
    // Якщо параметр додати в додатковий масив, вони стають OR
    //  protected $filters = [
        // 'statesFilters' => [
        //     [
        //         ['colName' => 'position', 'requestName' => 'stateName', 'type' => 'like'],
        //         ['colName' => 'squad', 'requestName' => 'stateName', 'type' => 'like'],
        //         ['colName' => 'platoon', 'requestName' => 'stateName', 'type' => 'like'],
        //     ],
        //     ['colName' => 'base'], 
        //     ['colName' => 'company'], 
        //     ['colName' => 'num'],
        //     ['colName' => 'stateRound'],
        //     ['colName' => 'stateRank'],
        //     ['colName' => 'delete'],
        // ],
    // ];

    /**
     * Додає фільтра до запиту, дані про фільтра бере з змінної що має знаходитись в моделі і мати назву filters, в зміній має бути іменований масив, кожна з комірок якого є масивом. Ячейки мають мати імена що застосовуться для їх пошуку та виклику по $filtersName. Масиви комірок мають мати такі параметри ['colName' => '', requestName => '', 'type' => ''] де: colName - ім'я колонки, requestName - при потребі ім'я змінної (за замовчуванням дорівнює colName), type - тип порівняння (за замовчуванням дорівнює '=')
     * @param mixed $collection колекція що отримана з моделі
     * @param mixed $request запит з якого фільтрується
     * @param string|array $filtersName ім'я фільтрів що будуть отримуватись
     * @param mixed $connections назви підключень по яким фільтрувати, якщо нема то по основному запиту
     * @return mixed
     */
    public function filter($collection, $request, string|array $filtersName, array $connections = [])
    {
        foreach ($collection as $coll_key => $coll_item) {
            // Розбираю колекцію по елементам
            if ($connections) {
                // якщо є підключення то працюю по ним
                foreach ($connections as $connectionItem) {
                    // Розбиваю підключеняння по крапці, щоб знати скільки елементів перевіряти
                    $connectionArr = explode('.', $connectionItem);
                    $connection = $connectionArr[0];

                    //кількість переглядів в приєднанні, скільки записів я хочу переглянути, від 1 до всіх
                    $mainRule = (isset($connectionArr[1]) && ((is_integer($connectionArr[1]) && $connectionArr[1] > 0) || $connectionArr[1] === 'all')) 
                    ? $connectionArr[1] : 1;

                    // Перевіряю чи є підключення пустим, якщо так то пропускаю
                    if ((get_class($coll_item->$connection) === 'Illuminate\Database\Eloquent\Collection'
                    && !empty($coll_item->$connection->first()))
                    || (is_array($coll_item->$connection) && !empty(reset($coll_item->$connection)))) {
                        $notDelete = false;
                    } else {
                        $notDelete = true;
                        continue;
                    }
                    
                    // Розбиваю по елементам
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

                            $filterRes = $this->filterBody($request, $filtersName, $object);
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

            if (!$notDelete) unset($collection[$coll_key]);
        }
        return $collection;
    }

    /**
     * Тіло фільтра, шукає та перевіряє конкретне значення
     * @param mixed $collection
     * @param mixed $request
     * @param string|array $filtersName
     * @return bool
     */
    protected function filterBody($request, $filtersName, $object)
    {
        // Перевірка відповідності фільтрів
        if (!is_array($filtersName) && !isset($object->filters[$filtersName])) return true;
        
        // Замість імені фільтрів що є в моделі можна передати фільтра в самому методі
        $filters = is_array($filtersName) ? $filtersName : $object->filters[$filtersName];

        foreach ($filters as $filters_it) {
            // Якщо це не OR обертаю в масив
            $filter_arr = !empty(reset($filters_it)['colName']) ? $filters_it : [$filters_it];

            // Ініціалізація прапорців
            $find = false;
            $notNullValue = 0;

            //Цей foreach потрібен для або, щоб можна було шукати в декількох полях як OR
            foreach ($filter_arr as $filter) {

                // Отримую значення фільтрів
                $type = !empty($filter['type']) ? $filter['type'] : '=';
                if (!empty($filter['colName'])) $colName = $filter['colName'];
                else continue;

                if (isset($filter['value'])) {
                    $filterValue = $filter['value'];
                    $notNullValue++;
                } else {
                    $requestName = !empty($filter['requestName']) ? $filter['requestName'] : $filter['colName'];
                    if ($request->has($requestName) && (!empty($request->$requestName) || is_array($request->$requestName))) {
                        $filterValue = $request->$requestName;
                        $notNullValue++;
                    }
                    else continue;
                }

                if (isset($object->$colName)) {
                    $value = $object->$colName;
                }
                else continue;

                // Сама перевірка за умовами
                if (is_string($filterValue) || is_integer($filterValue) || is_bool($filterValue)) {

                    if (is_string($filterValue)) {
                        $value = $value . '';
                        $filterValue = trim($filterValue);
                    } else {
                        $value = $value * 1;
                    }

                    if ($type === 'like') {
                        $filter_ii = str_replace('/', '\/', addslashes($filterValue));
                        if (preg_match('/'. $filter_ii .'/ui', addslashes($value))) $find = true;
                    } else {
                        switch ($type) {
                            case '>':
                                if($value > $filterValue) $find = true;
                                // dump($value . ' > ' . $filterValue . ' ' . $find);
                                break;
                            
                            case '<':
                                if($value < $filterValue) $find = true;
                                // dump($value . ' < ' . $filterValue . ' ' . $find);
                                break;
                            
                            case '>=':
                                if($value >= $filterValue) $find = true;
                                // dump($value . ' >= ' . $filterValue . ' ' . $find);
                                break;
                            
                            case '<=':
                                if($value <= $filterValue) $find = true;
                                // dump($value . ' <= ' . $filterValue . ' ' . $find);
                                break;
                            
                            case '!':
                                if($value !== $filterValue) $find = true;
                                // dump($value . ' != ' . $filterValue . ' ' . $find);
                                break;
                            
                            default:
                                if($value === $filterValue) $find = true;
                                // dump($value . ' = ' . $filterValue . ' ' . $find);
                                break;
                        }
                    }

                } elseif (is_array($filterValue)) {
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
}