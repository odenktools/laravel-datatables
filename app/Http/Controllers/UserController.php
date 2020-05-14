<?php

namespace App\Http\Controllers;

class UserController extends Controller
{
    /**
     * Show index page
     */
    public function index()
    {
        try {
            return view('user.index');
        } catch (\Exception $e) {
            // Error Log
            \Illuminate\Support\Facades\Log::error($e->getMessage());
            abort(500);
        }
    }

    /**
     * Build datatable json
     */
    public function getDatatable(\Illuminate\Http\Request $request)
    {
        try {
            // -- START DEFAULT DATATABLE QUERY PARAMETER
            $draw = $request->input('draw');
            $start = $request->input('start');
            $length = $request->input('length');
            $page = (int)$start > 0 ? ($start / $length) + 1 : 1;
            $limit = (int)$length > 0 ? $length : 10;
            $columnIndex = $request->input('order')[0]['column']; // Column index
            $columnName = $request->input('columns')[$columnIndex]['data']; // Column name
            $columnSortOrder = $request->input('order')[0]['dir']; // asc or desc value
            $searchValue = $request->input('search')['value']; // Search value from datatable
            //-- END DEFAULT DATATABLE QUERY PARAMETER

            //-- START DYNAMIC QUERY BINDING
            $conditions = '1 = 1';
            if (!empty($searchValue)) {
                $conditions .= " AND name LIKE '%" . trim($searchValue) . "%'";
                $conditions .= " OR email LIKE '%" . trim($searchValue) . "%'";
            }
            //-- END DYNAMIC QUERY BINDING

            //-- WE MUST HAVE COUNT ALL RECORDS WITHOUT ANY FILTERS
            $countAll = \App\User::count();

            //-- CREATE DEFAULT LARAVEL PAGING
            $paginate = \App\User::select('*')
                ->whereRaw($conditions)
                ->orderBy($columnName, $columnSortOrder)
                ->paginate($limit, ["*"], 'page', $page);

            $num = 1;
            $items = array();
            foreach ($paginate->items() as $idx => $row) {
                $items[] = array(
                    "no" => $num,
                    "id" => $row['id'],
                    "name" => $row['name'],
                    "email" => $row['email'],
                );
                $num++;
            }

            //-- START CREATE JSON RESPONSE FOR DATATABLES
            $response = array(
                "draw" => (int)$draw,
                "recordsTotal" => (int)$countAll,
                "recordsFiltered" => (int)$paginate->total(),
                "data" => $items
            );
            return response()->json($response);
            //-- END CREATE JSON RESPONSE FOR DATATABLES
        } catch (\Exception $e) {
            // Error Log
            \Illuminate\Support\Facades\Log::error($e->getMessage());
            return abort(404);
        }
    }

}
