<?php

/*
 * Ajax Code loaded only ajax request.
 * Develop 2014
 */

require 'config/config.php';
require '../vendor/offboard/Class-Query/autoload.php';
require 'application/Func.php';
require 'application/GetInfo.php';
header('Content-Type: application/json');

/**
 * Description of comissionCustomRangerAjax
 *
 * @author offboard
 */
class customRangerAjax {

    /**
     * Alphabet collection
     * 
     * @static
     * @var array
     */
    private static $alphabet = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');

    public function __construct() {
        if (self::is_ajax()) {
            if (isset($_GET['start'], $_GET['end'])) {
                return print $this->doDataJson();
            }
        }
    }

    private function doDataJson_original() {
        $q = new Query();
        $q
                ->select(
                        array(
                            'id_employee',
                            'status',
                            'data',
                            'COUNT(id_employee) as total'
                        )
                )
                ->from('input_servico')
                ->where_between(
                        array(
                            'data' => array(
                                date("Y-m-d H:i:s", strtotime($_GET['start'])),
                                date("Y-m-d H:i:s", strtotime($_GET['end']))
                            )
                        )
                )
                ->where_equal_to(
                        array(
                            'status' => true
                        )
                )
                ->where_not_equal_to(
                        array(
                            'id_employee' => NULL
                        )
                )
                ->group_by('id_employee')
                ->order_by('total desc')
                ->run();
        $data = $q->get_selected();
        $total = $q->get_selected_count();
        if (!($data && $total > 0)) {
            die('erro');
            return false;
        } else {
            $arr = array();
            $i = -1;
            foreach ($data as $value) {
                $i++;
                $id = self::$alphabet[$i];
                $name = Func::array_table('funcionarios', array('id' => $value['id_employee']), 'nome');
                $color = Func::array_table('funcionarios', array('id' => $value['id_employee']), 'color');
                $period = date('d-m-Y', strtotime($value['data']));
                $v = $id !== $total - 1 ? ',' : NULL;

                $arr['data'][] = <<<EOF
{
$id:{$value['total']},
y:'{$period}'
}$v
EOF;
                $arr['labels'][] = $name;
                $arr['key'][] = $id;
                $arr['colors'][] = $color;
            }
            $data = NULL;
            $colors = NULL;
            $key = NULL;
            $name = NULL;

            foreach ($arr['data'] as $v) {
                $data.= $v;
            }

            foreach ($arr['labels'] as $v) {
                $name.= "'" . $v . "',";
            }
            foreach ($arr['key'] as $v) {
                $key.= "'" . $v . "',";
            }
            foreach ($arr['colors'] as $v) {
                $colors.= "'" . $v . "',";
            }


            $json = array('data' => $data, 'labels' => $name, 'key' => $key, 'colors' => $colors);

            return json_encode($json);
        }
    }

    private function doDataJson() {
        $period = $this->loadPeriodCollection($_GET['start'], $_GET['end']);
        $employeeIds = $this->dataIDCollectionEmployee();
        // data
        $data = array();
        $i = 0;
        foreach ($period as $v) {
            $data['data' . $i++] = $this->SUMTotal($employeeIds, $v);
        }

        $colors = array();
        $key = array();
        $name = array();
        $tbody = NULL;
        foreach ($employeeIds as $k => $v) {
            // labels
            $name[] = Func::array_table('funcionarios', array('id' => $v), 'nome');
            // keys
            $key[] = self::$alphabet[$k];
            // colors
            $colors[] = Func::array_table('funcionarios', array('id' => $v), 'color');
            // tbody
            $tbody.= $this->TbodyContain($v);
        }

        $json = array(
            'data' => $data,
            'labels' => $name,
            'keys' => $key,
            'colors' => $colors,
            'tbody' => $tbody,
            'tbodyFinal' => $this->TbodyFinalContain()
        );

        return json_encode($json);
    }

    private function TbodyFinalContain() {
        $value_comissions = Func::FloatToReal($this->SUMTotalFinalPeriod());
        $value_earning = Func::FloatToReal($this->SUMTotalFinalPeriod('input_servico'));
        $total = $this->countTotalComissions(NULL, FALSE);
        return <<<EOF
 <tr>
    <td></td>
    <td></td>
    <td><strong>$total</strong></td>
    <td></td>
    <td><strong>R$ $value_comissions</strong></td>
    <td><strong>R$ $value_earning</strong></td>
</tr>
EOF;
    }

    private function TbodyContain($employeeId) {
        $name = Func::array_table('funcionarios', array('id' => $employeeId), 'nome');
        $total_comissions = $this->countTotalComissions($employeeId);
        $value_comissions = Func::FloatToReal($this->SUMTotalPeriod($employeeId));
        $value_earning = Func::FloatToReal($this->SUMTotalPeriod($employeeId, 'input_servico'));
        $total = $this->countTotalComissions($employeeId, FALSE);
        $percent = Func::percent_nx($total_comissions, $total);
        $final_percent = isset($percent) ? $percent : 0;
        return <<<EOF
 <tr>
    <td>$employeeId</td>
    <td><a href='lic'>$name</a></td>
    <td>$total_comissions</td>
    <td>$final_percent%</td>
    <td>R$ $value_comissions</td>
    <td>R$ $value_earning</td>
</tr>
EOF;
    }

    /**
     * Calculate total comissions per employee
     * 
     * @param int $id ID employee
     * @return int
     */
    private function countTotalComissions($id, $perUser = true) {
        $where_equal['status'] = true;
        if ($perUser) {
            $where_equal['id_employee'] = $id;
        }
        $q = new Query();
        $q
                ->select(
                        array(
                            'id_employee',
                            'status',
                        )
                )
                ->from('output_servico')
                ->where_between(
                        array(
                            'data' => array(
                                date("Y-m-d H:i:s", strtotime($_GET['start'])),
                                date("Y-m-d H:i:s", strtotime($_GET['end']))
                            )
                        )
                )
                ->where_equal_to($where_equal)
                ->run();
        $data = $q->get_selected();
        $total = $q->get_selected_count();
        if (!($data && $total > 0)) {
            return 0;
        } else {
            return $total;
        }
    }

    /**
     * Calculate value per employee
     * 
     * @param string $table
     * @return int
     */
    private function SUMTotalFinalPeriod($table = 'output_servico') {
        $q = new Query();
        $q
                ->select(
                        array(
                            'id_employee',
                            'status',
                            'SUM(value) as total'
                        )
                )
                ->from($table)
                ->where_between(
                        array(
                            'data' => array(
                                date("Y-m-d H:i:s", strtotime($_GET['start'])),
                                date("Y-m-d H:i:s", strtotime($_GET['end']))
                            )
                        )
                )
                ->where_equal_to(
                        array(
                            'status' => true
                        )
                )
                ->limit(1)
                ->run();
        $data = $q->get_selected();
        $total = $q->get_selected_count();
        if (!($data && $total > 0)) {
            $result = 0;
        } else {
            $result = $data['total'];
        }
        return $result;
    }

    /**
     * Get id of all employees
     * 
     * @return boolean|array
     */
    private function dataIDCollectionEmployee() {
        $q = new Query();
        $q
                ->select()
                ->from('funcionarios')
                ->order_by('nome ASC')
                ->run();
        $data = $q->get_selected();
        $total = $q->get_selected_count();
        $data_id = array();
        if (!($data && $total > 0)) {
            return false;
        }
        foreach ($data as $value) {
            $data_id[] = $value['id'];
        }
        return $data_id;
    }

    /**
     * Calculate value
     * 
     * @param int $ids
     * @param array $date
     * @return array
     */
    private function SUMTotal($ids, array $date) {
        $collection = array();
        foreach ($ids as $k => $v) {
            $q = new Query();
            $q
                    ->select(
                            array(
                                'id_employee',
                                'status',
                                'SUM(value) as total'
                            )
                    )
                    ->from('output_servico')
                    ->where_between(
                            array(
                                'data' => array(
                                    date("Y-m-d H:i:s", strtotime($date['RangeA'])),
                                    date("Y-m-d H:i:s", strtotime($date['RangeB']))
                                )
                            )
                    )
                    ->where_equal_to(
                            array(
                                'status' => true,
                                'id_employee' => $v
                            )
                    )
                    ->limit(1)
                    ->group_by('id_employee')
                    ->run();
            $data = $q->get_selected();
            $total = $q->get_selected_count();
            if (!($data && $total > 0)) {
                $collection[self::$alphabet[$k]] = 0;
            } else {
                $collection[self::$alphabet[$k]] = $data['total'];
            }
        }
        $collection['period'] = $date['FINAL'];

        return $collection;
    }

    /**
     * Calculate value per employee
     * 
     * @param int $id ID employee
     * @return int
     */
    private function SUMTotalPeriod($id, $table = 'output_servico') {
        $q = new Query();
        $q
                ->select(
                        array(
                            'id_employee',
                            'status',
                            'SUM(value) as total'
                        )
                )
                ->from($table)
                ->where_in(
                        array(
                            'id_employee' => $id
                        )
                )
                ->where_between(
                        array(
                            'data' => array(
                                date("Y-m-d H:i:s", strtotime($_GET['start'])),
                                date("Y-m-d H:i:s", strtotime($_GET['end']))
                            )
                        )
                )
                ->where_equal_to(
                        array(
                            'status' => true
                        )
                )
                ->limit(1)
                ->group_by('id_employee')
                ->run();
        $data = $q->get_selected();
        $total = $q->get_selected_count();
        if (!($data && $total > 0)) {
            $result = 0;
        } else {
            $result = $data['total'];
        }
        return $result;
    }

    /**
     * Generate period collection
     * 
     * @param date $min Date min
     * @param date $max Date max
     * @param int $num Number of period
     * @return array
     */
    private function loadPeriodCollection($min, $max, $num = 7) {
        $data = $this->split_date($min, $max, $num);
        $arr = array();
        $i = 0;
        foreach ($data as $k) {
            $a = $i++;
            $RangeB = $i == count($data) ? $max : $data[$a + 1];
            $Final = $i == count($data) ? $max : $k;
            $arr[] = array(
                'RangeA' => $k,
                'RangeB' => $RangeB,
                'FINAL' => $Final
            );
        }
        return $arr;
    }

    /**
     * 
     * Split date in $num part equals
     * 
     * @param date $min Date min
     * @param date $max Date max
     * @param int $num Number of period
     * @return array
     */
    private function split_date($min, $max, $num = 7) {
        $begin = new DateTime($min);
        $end = new DateTime($max);
        // calculate interval in days
        $int = date_diff($begin, $end)->format('%a');
        $intv = floor(($int * 24) / $num);

        $interval = new DateInterval("PT" . $intv . "H");
        $daterange = new DatePeriod($begin, $interval, $end);
        $dataCollection = array();
        foreach ($daterange as $date) {
            $dataCollection[] = $date->format('Y-m-d H:i:s');
        }
        return $dataCollection;
    }

    /**
     * 
     * Checks is ajax request
     * 
     * @return boolean
     */
    private static function is_ajax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

}

new customRangerAjax();
