<?php
/**
 * Created by PhpStorm.
 * User: erayizgi
 * Date: 22.05.2018
 * Time: 23:12
 */

namespace App\Libraries;


use Exception;

class Leveling
{
    /**
     * @param $request
     * @param $query
     * @param $fields
     * @return mixed
     * @throws Exception
     */
    public static function make($request, $query, $fields)
    {
        $role = $request->user()->roles;
        $operator = "";
        if (isset($fields["company_id"]["value"]) && isset($fields["branch_id"]["value"])) {
            $operator = " AND ";
        }
            switch ($role->role_level) {
                case 0:
                    $str = "";
                    $company_id = 0;
                    if(isset($fields["company_id"])){
                        $company_id = $fields["company_id"];
                        if (isset($fields["company_id"]["value"]) && ($fields["company_id"]["value"] !== null && $fields["company_id"]["value"] != 1)) {
                            $str .= $fields["company_id"]["field"] . "=" . $fields["company_id"]["value"] . $operator;
                        }
                    }
                    if(isset($fields["branch_id"])){
                        if (isset($fields["branch_id"]["value"]) && ($fields["branch_id"]["value"] !== null && $company_id != 1)) {
                            $str .= $fields["branch_id"]["field"] . " = " . $fields["branch_id"]["value"];
                        }
                    }
                    if (strlen($str) > 0) {
                        $query->orWhereRaw("({$str})");
                    }
                    break;
                case 1:
                    $str = "";
                    if (isset($fields["company_id"]["value"]) && $fields["company_id"]["value"] !== null) {
                        if ($request->user()->network_id === $fields["company_id"]["value"]) {
                            $str .= $fields["company_id"]["field"] . "=" . $fields["company_id"]["value"] . $operator;
                        } else {
                            throw new Exception("You don't have access to this data", 404);
                        }
                    } else {
                        $str .= $fields["company_id"]["field"] . "=" . $request->user()->network_id . $operator;
                    }
                    if (isset($fields["branch_id"]["value"]) && $fields["branch_id"]["value"] !== null) {
                        $str .= $fields["branch_id"]["field"] . " = " . $fields["branch_id"]["value"];
                    }
                    if (strlen($str) > 0) {
                        $query->orWhereRaw("({$str})");
                    } else {
                        throw new Exception("You don't have access to this data", 404);
                    }
                    break;
                case 2:
                    $str = "";
                    if (isset($fields["company_id"]["value"]) && $fields["company_id"]["value"] !== null) {
                        if ($request->user()->network_id === $fields["company_id"]["value"]) {
                            $str .= $fields["company_id"]["field"] . "=" . $fields["company_id"]["value"] . $operator;
                        } else {
                            throw new Exception("You don't have access to this data", 404);
                        }
                    } else {
                        $str .= $fields["company_id"]["field"] . "=" . $request->user()->network_id . $operator;
                    }
                    if (isset($fields["branch_id"]["value"]) && $fields["branch_id"]["value"] !== null) {
                        if ($request->user()->site_id === $fields["branch_id"]["value"]) {
                            $str .= $fields["branch_id"]["field"] . " = " . $fields["branch_id"]["value"];
                        } else {
                            throw new Exception("You don't have access to this data", 404);
                        }
                    } else {
                        $str .= $fields["branch_id"]["field"] . " = " . $request->user()->site_id;
                    }
                    if (strlen($str) > 0) {
                        $query->orWhereRaw("({$str})");
                    } else {
                        throw new Exception("You don't have access to this data", 404);
                    }
                    break;
                case 3:
                    $str = "";
                    if (isset($fields["company_id"]["value"]) && $fields["company_id"]["value"] !== null) {
                        if ($request->user()->network_id == $fields["company_id"]["value"]) {
                            $str .= $fields["company_id"]["field"] . "=" . $fields["company_id"]["value"] . $operator;
                        } else {
                            throw new Exception("You don't have access to this data", 404);
                        }
                    } else {
                        $str .= $fields["company_id"]["field"] . "=" . $request->user()->network_id . $operator;
                    }
                    if(isset($fields["branch_id"])){
                        if (isset($fields["branch_id"]["value"]) && $fields["branch_id"]["value"] !== null) {
                            if ($request->user()->site_id === $fields["branch_id"]["value"]) {
                                $str .= $fields["branch_id"]["field"] . " = " . $fields["branch_id"]["value"];
                            } else {
                                throw new Exception("You don't have access to this data", 404);
                            }
                        } else {
                            $str .= $fields["branch_id"]["field"] . " = " . $request->user()->site_id;
                        }
                    }
                    if(isset($fields["owner_user_id"])){
                        if (isset($fields["owner_user_id"]["field"]) && $fields["owner_user_id"]["field"] !== null) {
                            if ($request->user()->id === $fields["owner_user_id"]["field"]) {
                                $str .= $fields["owner_user_id"]["field"] . " = " . $fields["owner_user_id"]["value"];
                            } else {
                                throw new Exception("You don't have access to this data", 404);
                            }
                        } else {
                            $str .= $fields["owner_user_id"]["field"] . " = " . $request->user()->id;
                        }
                    }
                    if (strlen($str) > 0) {
                        $query->orWhereRaw("({$str})");
                    } else {
                        throw new Exception("You don't have access to this data", 404);
                    }
                    break;
            }
            return $query;

    }

}
