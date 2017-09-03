<?php
/**
 * Задание #1
 * Дан XML файл. Сохраните его под именем data.xml.
 * Написать скрипт, который выведет всю информацию из этого файла в удобно читаемом виде.
 * Представьте, что результат вашего скрипта будет распечатан и выдан курьеру для доставки,
 * разберется ли курьер в этой информации?
 *
 * @param string $filename
 */
function task1(string $filename)
{
    if (!file_exists($filename)) {
        exit("Не удалось открыть файл $filename.");
    }

    $order = simplexml_load_file($filename);
    $order_num = $order['PurchaseOrderNumber'];
    $order_date = $order['OrderDate'];

    $shipping1 = $order->Address[0]->Name;
    $shipping2 = $order->Address[0]->Street;
    $shipping3 = $order->Address[0]->City . ', ' .
        $order->Address[0]->State . ' ' .
        $order->Address[0]->Zip . ' ' .
        $order->Address[0]->Country;

    $billing1 = $order->Address[1]->Name;
    $billing2 = $order->Address[1]->Street;
    $billing3 = $order->Address[1]->City . ', ' .
        $order->Address[1]->State . ' ' .
        $order->Address[1]->Zip . ' ' .
        $order->Address[1]->Country;

    $notes = $order->DeliveryNotes;

    echo <<<EOT
    <h1>Заказ №$order_num от $order_date</h1> 
    <table border="1" cellpadding="4" style="border-collapse: collapse; text-align: center; width: 500px">
        <tr>
            <td>
                <b>Адрес доставки</b><br>
                $shipping1<br>
                $shipping2<br>
                $shipping3
            </td>
            <td>
                <b>Адрес выставления счёта</b><br>
                $billing1<br>
                $billing2<br>
                $billing3
            </td>
        </tr>
    </table>
    <p>==== $notes ====</p>
    <table border="1" cellpadding="4" style="border-collapse: collapse; text-align: center; width: 500px">
    <tr>
        <th>№</th>
        <th>Название</th>
        <th>Кол-во</th>
        <th>Цена, USD</th>
        <th>Примечание</th>
    </tr>
EOT;
    foreach ($order->Items->Item as $item) {
        echo <<<EOT
        <tr>
            <td>{$item['PartNumber']}</td>
            <td><b>{$item->ProductName}</b></td>
            <td>{$item->Quantity}</td>
            <td>{$item->USPrice}</td>
            <td>{$item->Comment}</td>
        </tr>
EOT;
    }
    echo '</table>';
}

/**
 * Задача #2
 * Создайте массив, в котором имеется как минимум 1 уровень вложенности.
 * Преобразуйте его в JSON.
 * Сохраните как output.json
 * Откройте файл output.json. Случайным образом решите изменять данные или нет. Сохраните как output2.json
 * Откройте оба файла. Найдите разницу и выведите информацию об отличающихся элементах
 */
function task2()
{
    $books = [
        'PHP. Объекты, шаблоны и методики программирования' => [
            'author' => 'Мэтт Зандстра',
            'year' => 2016
        ],
        'Разработка веб-приложений с помощью PHP и MySQL' => [
            'author' => 'Люк Веллинг, Лаура Томсон ',
            'year' => 2017,
            'other' => [
                'first_published' => 2003
            ]
        ],
        'PHP и MySQL. От новичка к профессионалу' => [
            'author' => 'Кевин Янк',
            'year' => 2015
        ],
    ];

    $file = fopen('output.json', 'w');
    fwrite($file, json_encode($books));
    fclose($file);
    echo 'Сохраняю...<br>';

    $to_make_changes = rand(0, 1);
    echo 'Решаю... ';

    $str = file_get_contents('output.json');
    if ($to_make_changes) {
        if ($str) {
            file_put_contents('output2.json', corrupt_data_years($str));
        }
        echo 'вносить изменения<br>';
    } else {
        file_put_contents('output2.json', $str);
        echo 'не вносить изменения<br>';
    }

    $arr1 = json_decode(file_get_contents('output.json'), true);
    $arr2 = json_decode(file_get_contents('output2.json'), true);

    echo 'Проверяю...<br>';
    show_diff(array_diff_recursive($arr1, $arr2));
}

/**
 * Заменяет все года (19ХХ, 20XX) в строке на случайные (в диапазоне 1970-2017)
 *
 * @param string $str
 *
 * @return string
 */
function corrupt_data_years(string $str) : string
{
    $regex_year = '/(?:19|20)\d{2}/';

    $num = preg_match_all($regex_year, $str, $match);
    $replacements = [];
    for ($i = 0; $i < $num; $i++) {
        $replacements[] = rand(1970, 2017);
    }

    return str_replace($match[0], $replacements, $str);
}

/**
 * Принимает на вход 2 массива (в том числе многомерные) и возвращает массив с отличиями
 *
 * @param array $arr1
 * @param array $arr2
 *
 * @return array
 */
function array_diff_recursive(array $arr1, array $arr2) : array
{
    $diff = [];
    foreach ($arr1 as $key => $value) {
        if (is_array($value) && isset($arr2[$key])) {
            $new_diff = array_diff_recursive($value, $arr2[$key]);
            if (!empty($new_diff)) {
                $diff[$key] = $new_diff;
            }
        } elseif (is_string($value) && !in_array($value, $arr2)) {
            $diff[$key] = '';
        } elseif (!is_numeric($key) && !array_key_exists($key, $arr2)) {
            $diff[$key] = '';
        } elseif ($arr1[$key] != $arr2[$key]) {
            $diff[$key] = $arr2[$key];
        }
    }
    return $diff;
}

/**
 * Показывает содержание массива с отличиями в виде деревоподобного списка
 *
 * @param array $arr
 */
function show_diff(array $arr)
{
    if (empty($arr)) {
        echo 'Отличий не найдено';
        return;
    }

    /**
     * Коллбэк для итератора массива, выводящий дерево
     *
     * @param $value
     * @param $key
     */
    function print_tree($value, $key)
    {
        echo "<ul>";
        if (empty($value)) {
            echo "<li>ключ $key удалён</li>";
        } elseif (is_array($value)) {
            echo "<li>В ключе $key:";
            array_walk($value, 'print_tree');
            echo '</li>';
        } else {
            echo "<li>ключ $key изменён на $value</li>";
        }
        echo "</ul>";
    };

    array_walk($arr, 'print_tree');
}

/**
 * Задача #3
 * Программно создайте массив, в котором перечислено не менее 50 случайных числел от 1 до 100
 * Сохраните данные в файл csv
 * Откройте файл csv и посчитайте сумму четных чисел
 */
function task3()
{
    $arr = [];
    for ($i = 0; $i < 50; $i++) {
        $arr[] = rand(1, 100);
    }

    $handle = fopen('file.csv', "w");
    fputcsv($handle, $arr);
    fclose($handle);

    if (($handle = fopen('file.csv', "r")) !== false) {
        $arr = fgetcsv($handle);
        echo 'Сумма: ';
        echo array_reduce($arr, function ($res, $item) {
            return ($item % 2 === 0) ? ($res + $item) : $res;
        }, 0);
        fclose($handle);
    } else {
        echo 'Ошибка чтения файла';
    }
}


/**
 * Задача #4
 * С помощью CURL запросить данные по адресу:
 * https://en.wikipedia.org/w/api.php?action=query&titles=Main%20Page&prop=revisions&rvprop=content&format=json
 * Вывести title и page_id
 */
function task4()
{
    $url = 'https://en.wikipedia.org/w/api.php?action=query&titles=Main%20Page&prop=revisions&rvprop=content&format=json';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($result, true)['query']['pages']['15580374'];
    echo 'Title: ' . $data['title'];
    echo '<br>';
    echo 'Page ID: ' . $data['pageid'];
}


