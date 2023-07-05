<?php
    function inputColls($inputColl)
    {
        // '' => ['object' => , 'col' => '', 'dataType' => '', 'divClass' => '', 'placeholder' => '', 'multy' => '', 'label' => ['class' => '', 'text' => '', 'labelIn' => 1]],
        foreach ($inputColl as $collKey => $collSettings) {
            $label = '';
            $col = !empty($collSettings['col']) ? $collSettings['col'] : $collKey;
            if (!empty($collSettings['label']['text'])) {
                $label = '<label for="'.$collKey.'" class="'.$collSettings['label']['class'].'">'.$collSettings['label']['text'].'</label>';
            }
            
            if (empty($collSettings['label']['labelIn'])) echo $label;
            echo '<div class="'.$collSettings['divClass'].'">';
            if (!empty($collSettings['label']['labelIn'])) echo $label;  
            echo '<input type="'.$collSettings['dataType'].'" class="form-control" id="'.$collKey.'" name="'.$collKey;
            if (isset($collSettings['multy'])) echo "[{$collSettings['multy']}]";
            echo '" placeholder="'.$collSettings['placeholder'].'" ';
            
            if (isset($collSettings['multy'])) {
                $old = old($collKey);
                echo 'value="'.(isset($old[$collSettings['multy']]) ? $old[$collSettings['multy']] : (!empty($collSettings['object']->$col) ? $collSettings['object']->$col : null)).'">';
                unset($old);
            } else {
                echo 'value="'.(old($collKey) !== NULL ? old($collKey) : (!empty($collSettings['object']->$col) ? $collSettings['object']->$col : null)).'">';
            }
            echo '</div>';
        }
    }

    /**
     * @param name - ім'я під яким буде зберігатись зміна
     * @param collSettings - налаштування
     * @param data - дані
     */
    function simpleColls($name, $collSettings, $data)
    {
        // ['dataType' => '', 'placeholder' => '', 'multy' => '']
        echo '<input type="'.$collSettings['dataType'].'" class="form-control" id="'.$name.'" name="'.$name;
        if (isset($collSettings['multy'])) echo "[{$collSettings['multy']}]";
        echo '" placeholder="'.$collSettings['placeholder'].'" ';
        if (isset($collSettings['multy'])) {
            $old = old($name);
            echo 'value="'.(isset($old[$collSettings['multy']]) ? $old[$collSettings['multy']] : (!empty($data) ? $data : null)).'">';
            unset($old);
        } else {
            echo 'value="'.(old($name) !== NULL ? old($name) : (!empty($data) ? $data : null)).'">';
        }
    }

    /**
     * @param name - ім'я під яким буде зберігатись зміна
     * @param collSettings - налаштування
     * @param dataArr - масив з вибором
     */
    function selectColls($name, $collSettings, $dataArr)
    {
        // ['object' => 'object', 'data' => 'string value', 'divClass' => '', 'notDiv' => 1, 'keyValue' => 1, 'multy' => '']
        $selectData = NULL;
        if (isset($collSettings['object'])) {
            $selectData = !empty($collSettings['object']->$name) ? $collSettings['object']->$name : $selectData;
        } elseif (isset($collSettings['data'])) {
            $selectData = $collSettings['data'];
        }

        if(empty($collSettings['notDiv'])) echo '<div class="'.$collSettings['divClass'].'">';
            echo '<select class="form-select" id="'.$name.'" name="'.$name;
            if (isset($collSettings['multy'])) echo "[{$collSettings['multy']}]";
            echo '">';

            if (isset($collSettings['multy']) && !empty($collSettings['multy'])) {
                $old = old($name);
                $data = isset($old[$collSettings['multy']]) ? $old[$collSettings['multy']] : (!empty($selectData) ? $selectData : null);
                unset($old);
            } else {
                $data = old($name) !== NULL ? old($name) : (!empty($selectData) ? $selectData : null);
            }
            foreach ($dataArr as $key => $item) {
                if (!empty($collSettings['keyValue'])) {
                    echo '<option value="'.$key.'"'
                    .($data === $key ? 'selected' : '')
                    .'>'.$item.'</option>';
                } else {
                    echo '<option value="'.$item.'"'
                    .($data === $item ? 'selected' : '')
                    .'>'.$item.'</option>';
                }
            }
            echo '</select>';
        if(empty($collSettings['notDiv'])) echo '</div>';
    }

    /**
     * @param name - ім'я під яким буде зберігатись зміна
     * @param collSettings - налаштування
     * @param dataArr - масив з вибором
     */
    function checkColls($name, $collSettings, $dataArr)
    {
        // ['object' => , 'divClass' => '', 'notDiv' => 1, 'multy' => '', 'keyValue' => 1]
        $oldDataArr = old($name);
  
        if(empty($collSettings['notDiv'])) echo '<div class="'.$collSettings['divClass'].'">';
            echo '<div class="list-group">
                <div class="row">';
                foreach ($dataArr as $checkkey => $item) {
                    $checkkey = (string)$checkkey;
                    echo '<div class="col mb-1">
                        <label class="list-group-item rounded d-flex gap-2 justify-content-center">
                        <input class="form-check-input flex-shrink-0" type="checkbox" name="';
                    if (isset($collSettings['multy'])) echo $name . "[{$checkkey}]";

                    if (!empty($collSettings['keyValue'])) {
                        $data = $checkkey;
                    } else {
                        $data = $item;
                    }
                    echo '" value="'.$data.'" id="'.$name.'__'.$checkkey.'"';
                    echo (!empty($oldDataArr)
                        ? ((in_array($data, $oldDataArr)) ? 'checked' : '')
                        : ((!empty($collSettings['object']->$name) && in_array($data, $collSettings['object']->$name)) ? 'checked' : '')).'>';
                        
                    echo '<span>'.$item.'</span>
                    </label></div>';
                }
            echo '</div></div>';
        if(empty($collSettings['notDiv'])) echo '</div>';
    }