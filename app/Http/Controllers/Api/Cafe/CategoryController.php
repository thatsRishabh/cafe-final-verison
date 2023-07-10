<?php

namespace App\Http\Controllers\Api\Cafe;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Recipe;
use App\Models\Menu;
use DB;
use Log;
use Validator;


class CategoryController extends Controller
{
	public function categories(Request $request)
	{
		try {

			$query = Category::select('*')
			->orderBy('id', 'desc');
			if(!empty($request->name))
			{
				$query->where('name','like','%'.$request->name.'%');
			}

			if(!empty($request->per_page_record))
			{
				$perPage = $request->per_page_record;
				$page = $request->input('page', 1);
				$total = $query->count();
				$result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

				$pagination =  [
					'data' => $result,
					'total' => $total,
					'current_page' => $page,
					'per_page' => $perPage,
					'last_page' => ceil($total / $perPage)
				];
				$query = $pagination;
			}
			else
			{
				$query = $query->get();
			}

			return prepareResult(true,'Categories List Fatched Successfully' ,$query, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function store(Request $request)
	{
		$nameCheck = Category::where('name', $request->name)->first();
        $validation = Validator::make($request->all(),  [
            'name' => $nameCheck ? 'required|declined:false' : 'required',
        ],
        [
            'name.declined' => 'Name already exists',
        ]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try { 
			$category = new Category;
			$category->image = $request->image;
			$category->name = $request->name;
			$category->tax = $request->tax;
			$category->save();
			DB::commit();
			return prepareResult(true,'Category created successfully' , $category, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
    
	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			'name' => 'required',
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try { 
			$dublicate = Category::where('name',$request->name)->where('id','!=',$id)->count();
			if($dublicate > 0)
			{
				return prepareResult(false,'The name has already been taken.' ,[], 500);
			}     
			$category = Category::find($id);
			if (empty($category)) {
				return prepareResult(false,'Category not found' ,[], 500);
			}
			$category->name = $request->name;
			$category->tax = $request->tax;
			$category->image = $request->image;
			$category->save();
			DB::commit();
			return prepareResult(true,'Category Updated successfully' ,$category, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$category = Category::find($id);
			if($category)
			{
				return prepareResult(true,'Category Detail Fatched Successfully' ,$category, 200); 
			}
			return prepareResult(false,'Category not found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function destroy($id)
	{
		try {
			$category = Category::find($id);
			if($category)
			{
				$menus = Menu::where('category_id',$id)->get();
				foreach ($menus as $key => $menu) {
					Recipe::where('menu_id',$menu->id)->delete();
				}
				Menu::where('category_id',$id)->delete();
				$category->delete();
				return prepareResult(true,'Category Deleted Successfully' ,[], 200); 
			}
			return prepareResult(false,'Category Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
}
