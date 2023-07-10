<?php

namespace App\Http\Controllers\Api\Cafe;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Recipe;
use DB;  
use Log;
use Validator;

class MenuController extends Controller
{
	public function menus(Request $request)
	{
		try {
			$query = Menu::with('category:id,name,tax','unit:id,name','recipes.product:id,name','recipes.unit:id,name');
			if(!empty($request->priority_rank)){
				$query = $query->orderBy('priority_rank', 'asc');
			}else{ 
				$query = $query->orderBy('id', 'desc');
			}
			if(!empty($request->cafe_id))
			{
				$query->where('cafe_id', $request->cafe_id);
			}
			if(!empty($request->name))
			{
				$query->where('name', 'LIKE', '%'.$request->name.'%');
			}
			if(!empty($request->category_id))
			{
				$query->where('category_id', $request->category_id);
			}
			if(!empty($request->price))
			{
				$query->where('price', $request->price);
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
			return prepareResult(true,'Menu Records Fatched Successfully' ,$query, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function store(Request $request)
	{
		$validation = Validator::make($request->all(), [
			"name"  => "required", 
			"price"  => "required|numeric", 
			"order_duration"  => "required|numeric", 
			"category_id"  => "required|numeric",
			"recipes" => 'required|array',
			"priority_rank" => 'unique:menus,priority_rank' 
		]);
		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try {    
			$menu = new Menu;
			$menu->category_id =  $request->category_id;
			$menu->unit_id =  $request->unit_id;
			$menu->quantity = $request->quantity;
			$menu->name =  $request->name;
			$menu->description =  $request->description;
			$menu->price =  $request->price;
			$menu->order_duration =  $request->order_duration;
			$menu->priority_rank =  $request->priority_rank;
			$menu->image_path =  $request->image_path;
			$menu->save();
			foreach ($request->recipes as $key => $value) 
			{
				$product = Product::find($value['product_id']);
				if (empty($product)) {
					return prepareResult(false,'Product Not Found' ,[], 500);
				}
				$validation = Validator::make($request->all(),[
					"recipes.*.unit_id"  => unitSimilarTypeCheck($value['unit_id'], $value['product_id']),
					],
					[
						'recipes.*.unit_id.declined' => 'Invalid Unit Type',
					]
				);
				if ($validation->fails()) {
					return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
				}
				$recipe = new Recipe;
				$recipe->menu_id =  $menu->id;
				$recipe->product_id = $value['product_id'];
				$recipe->quantity = $value['quantity'];
				$recipe->unit_id = $value['unit_id'];
				$recipe->save();
			}

			DB::commit();
			return prepareResult(true,'Menu Created successfully' , $menu, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function update(Request $request, $id)
	{
		$validation = Validator::make($request->all(), [
			"name"  => "required",  
			"price"  => "required|numeric", 
			"order_duration"  => "required|numeric", 
			"category_id"  => "required|numeric",
			"recipes" =>"required|array",
			"priority_rank" => 'unique:menus,priority_rank'.$id
		]);

		if ($validation->fails()) {
			return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
		} 
		DB::beginTransaction();
		try { 
			$menu = Menu::find($id);
			if(empty($menu))
			{
				return prepareResult(false,'Menu Not Found' ,[], 500);
			}
			$menu->category_id =  $request->category_id;
			$menu->unit_id =  $request->unit_id;
			$menu->quantity = $request->quantity;
			$menu->name =  $request->name;
			$menu->description =  $request->description;
			$menu->price =  $request->price;
			$menu->order_duration =  $request->order_duration;
			$menu->priority_rank =  $request->priority_rank;
			$menu->image_path =  $request->image_path;
			$menu->save();
			Recipe::where('menu_id',$id)->delete();
			foreach ($request->recipes as $key => $value) 
			{
				$recipe = new Recipe;
				$recipe->menu_id =  $menu->id;
				$recipe->product_id = $value['product_id'];
				$recipe->quantity = $value['quantity'];
				$recipe->unit_id = $value['unit_id'];
				$recipe->save();
			}
			DB::commit();
			return prepareResult(true,'Menu Updated successfully' ,$menu, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$menu = Menu::with('category:id,name,tax','unit:id,name','recipes')->find($id);
			if($menu)
			{
				return prepareResult(true,'Menu Fatched Successfully' ,$menu, 200); 
			}
			return prepareResult(false,'Menu Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function destroy($id)
	{
		try {
			$menu = Menu::find($id);
			if($menu)
			{
				Recipe::where('menu_id',$id)->delete();
				$result = $menu->delete();
				return prepareResult(true,'Menu Deleted Successfully' ,$result, 200); 
			}
			return prepareResult(false,'Menu Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function categoryWiseMenus(Request $request)
	{
		try {
			$query = Category::with('menus')
			->orderBy('id', 'desc');
			if(!empty($request->id))
			{
				$query->where('id', $request->id);
			}
			if(!empty($request->name))
			{
				$query->where('name', 'LIKE', '%'.$request->name.'%');
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
			return prepareResult(true,'Cetegory Wise Menu List Fatched Successfully' ,$query, 200);
		} 
		catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Error while fatching Records' ,$e->getMessage(), 500);
		}
	}
}
