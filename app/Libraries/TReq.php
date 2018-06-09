<?php
/**
 * Created by PhpStorm.
 * User: erayizgi
 * Date: 22.05.2018
 * Time: 23:24
 */

namespace App\Libraries;

use Illuminate\Support\Facades\DB;

class TReq
{
    public static function multiple($request, $model)
    {

        // Get starting number of pagination
        $_st = $request->has('offset') ? (int)$request->offset : 0;
        // Get length of the pagination
        $_ln = $request->has('limit') ? (int)$request->limit : 20;
        // Get select fileds
        $_sl = $request->has('fields') ? $request->fields : false;
        // if query order by
        $_ob = $request->has('order_by') ? $request->order_by : false;
        // If with trashed data requested.
        $_ar = $request->has('archived') ? $request->archived : false;
        // If only trashed data requested.
        $_wh = $request->has("where") ? $request->where : false;
        $_bw = $request->has("between") ? $request->between: false;


        if ($_ln && $_ln > 100) {
            $_length = 100;
        }
        // create instance of query
        $query = $model::query();
        $query->skip($_st);
        $query->take($_ln);
        if ($_wh) {
            $where = explode(",", $_wh);
            foreach ($where as $w) {
                if (strpos($w, "|")) {
                    $val = explode('|', $w);
                    $query->where($val[0], $val[1]);
                }
            }

        }
        if ($_bw) {

            //?between=tarih||önce|sonra
            $between = explode(",", $_bw);

            foreach ($between as $bw) {
                if (strpos($bw, "||")) {
                    $bwx = explode("||",$bw);
                    $bwy = $bwx[0];
                    $val = explode('|', $bwx[1]);
                    $query->where($bwy,">=",$val[0]);
                    $query->where($bwy,"<=",$val[1]);
                }
            }
        }
        // if order by condition
        if ($_ob) {
            $arr = explode(',', $_ob);
            foreach ($arr as $key => $field) {
                $type = $field[0] == '-' ? 'desc' : 'asc';
                $field = $field[0] == '-' ? substr($field, 1) : $field;
                $query->orderBy($field, $type);
            }
        }
        // if select condition
        if ($_sl) {
            // explode from comma
            $arr = explode(',', $_sl);
            //iterate
            foreach ($arr as $key => $field) {
                unset($arr[$key]);
            }
            // COUNT ARRAY, IF THERE IS NO ACCEPTABLE SELECT, SELECT *, ELSE SELECT ARRAY
            $query->select($arr);
        }

        if ($_ar) {
            switch ($_ar) {
                case 'true':
                    $query->withTrashed();
                    break;
                case 'only':
                    $query->onlyTrashed();
                    break;
            }
        }

        $data = [
            'offset' => $_st,
            'limit' => $_ln,
            'query' => $query
        ];
        return $data;

    }

    public static function single($request, $model, $id)
    {
        // Get select fileds
        $_sl = $request->has('_sl') ? $request->_sl : false;
        // create instance of query
        $query = $model::query();
        // if select condition
        if ($_sl) {
            // explode from comma
            $arr = explode(',', $_sl);
            //iterate
            foreach ($arr as $key => $field) {
                if (!in_array($field, $model::selectable())) {
                    unset($arr[$key]);
                }
            }
            // COUNT ARRAY, IF THERE IS NO ACCEPTABLE SELECT, SELECT *, ELSE SELECT ARRAY
            if (count($arr) < 1) {
                $result = [
                    'status' => false,
                    'code' => 422,
                    'message' => 'No selectable field found!'
                ];
                return $result;
            } else {
                // if any of column is selectable
                $query->select($arr);
            }
        }
        // get data
        $data = $query->find($id);
        //create result
        if ($data) {
            $result = [
                'status' => true,
                'result' => [
                    'data' => [$data]
                ]
            ];
        } else {
            $result = [
                'status' => false,
                'code' => 404,
                'message' => 'Data not found!'
            ];
        }
        return $result;
    }

}
