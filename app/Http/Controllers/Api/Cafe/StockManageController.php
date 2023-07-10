<?php

namespace App\Http\Controllers\Api\Cafe;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\StockManage;
use App\Models\Menu;
use App\Models\Unit;
use App\Models\Product;
use DB;
use Log;
use Validator;
use App\Enums\ServerStatus;
use Illuminate\Validation\Rules\Enum;

class StockManageController extends Controller
{
	public function stockManages(Request $request)
	{
		try {
			$query = StockManage::select('stock_manages.*')->join('products', function ($join) {
				$join->on('products.id', '=', 'stock_manages.product_id');
			})
			->withoutGlobalScope('cafe_id')
			->where('stock_manages.cafe_id',auth()->user()->cafe_id)
			->with('product:id,name','unit:id,name')
			->orderBy('stock_manages.id', 'desc');
			if(!empty($request->product_id))
			{
				$query->where('stock_manages.product_id', $request->product_id);
			}   

			if(!empty($request->stock_operation))
			{
				$query->where('stock_manages.stock_operation', $request->stock_operation);
			}
			if(!empty($request->product))
			{
				$query->where('products.name', 'LIKE', '%'.$request->product.'%');
			}

			// date wise filter from here
			if(!empty($request->from_date))
			{
				$query->whereDate('stock_manages.created_at', '>=', $request->from_date);
			}

			if(!empty($request->end_date))
			{
				$query->whereDate('stock_manages.created_at', '<=', $request->end_date);
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
			return prepareResult(true,'Stock Data Fatched Successfully' ,$query, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function store(Request $request)
	{
		DB::beginTransaction();
		try {
			$validation = Validator::make($request->all(), [
				'stock_operation' => 'required',
				'product_id' => 'required|exists:products,id',
				'resource' => 'required',
				'quantity' => 'required',
				'unit_id'   => unitSimilarTypeCheck($request->unit_id,$request->product_id),
			],
			[
				'unit_id.declined' => 'Invalid Unit Type'
			]);
			if ($validation->fails()) { 
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			}
			$product_id = $request->product_id;
			// $expense_id = $request->expense_id;
			// $order_id 	= $request->order_id;
			$resource 	= $request->resource;
			$price 	= $request->price;
			$stock_operation = $request->stock_operation;
			$quantity = $request->quantity;
			$unit_id = $request->unit_id;

			// $stockManage = stockManage($stock_operation,$product_id,$unit_id,$quantity,$resource,$expense_id,$order_id);
			$stockManage = stockManageAdd($stock_operation,$product_id,$unit_id,$quantity,$resource,$price);
			// $stockManage = new StockManage;
			// $stockManage->product_id 	= $request->product_id;
			// $stockManage->order_id 		= $request->order_id;
			// $stockManage->expense_id 	= $request->expense_id;
			// $stockManage->unit_id 		= $request->unit_id;
			// $stockManage->quantity 		= $request->quantity;
			// $stockManage->stock_operation = $request->stock_operation;
			// $stockManage->resource 		= $request->resource;
			// $stockManage->save();

			// updating the productinfo table as well
			// $product = Product::find( $request->product_id);
			// $quantity = convertQuantity($unit_id,$product_id,$quantity);
			// if(strtolower($request->stock_operation) == 'in')
			// {
			// 	$product->current_quanitity = $product->current_quanitity + $quantity;
			// }
			// else
			// {
			// 	$product->current_quanitity = $product->current_quanitity - $quantity;
			// }
			// $product->save();

			DB::commit();
			return prepareResult(true,'Stock created Ssuccessfully' , $stockManage, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}


	public function update(Request $request, $id)
	{
		DB::beginTransaction();
		try {
			$validation = Validator::make($request->all(), [
				'stock_operation' => 'required',
				'product_id' => 'required|exists:products,id',
				'resource' => 'required',
				'quantity' => 'required',
				'unit_id'   => unitSimilarTypeCheck($request->unit_id,$request->product_id)
			],
			[
				'unit_id.declined' => 'Invalid Unit Type'
			]);
			if ($validation->fails()) { 
				return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
			}
			$stockManage = StockManage::find($id);
			if(empty($stockManage))
			{
				return prepareResult(false,'Stock Data Not Found' ,[], 500); 
			}

			$product_id = $request->product_id;
			// $expense_id = $request->expense_id;
			// $order_id 	= $request->order_id;
			$resource 	= $request->resource;
			$price 	= $request->price;
			$stock_operation = $request->stock_operation;
			$quantity = $request->quantity;
			$unit_id = $request->unit_id;

			$stockManage = stockManageUpdate($stockManage,$stock_operation,$product_id,$unit_id,$quantity,$resource,$price);

			DB::commit();
			return prepareResult(true,'Stock Manage Updated Successfully' , $stockManage, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function show($id)
	{
		try {
			$stockManage = StockManage::with('product:id,name','unit:id,name')->find($id);
			if($stockManage)
			{
				return prepareResult(true,'Stock Data Fatched Successfully' ,$stockManage, 200); 
			}
			return prepareResult(false,'Stock Data Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function destroy($id)
	{
		try {
			DB::beginTransaction();
			$stockManage = StockManage::find($id);
			if($stockManage)
			{
				$stockManage = stockManageDelete($stockManage);
				DB::commit();
				return prepareResult(true,'Stock Data Deleted Successfully' ,[], 200); 
			}
			return prepareResult(false,'Stock Data Not Found' ,[], 500);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}
}
