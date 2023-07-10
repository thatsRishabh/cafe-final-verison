<?php

namespace App\Http\Controllers\Api\Common;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\ProductMenu;
use App\Models\Order;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\User;
use App\Models\Attendence;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;  
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;



class DashboardController extends Controller
{
	public function dashboard()
	{
		try {
			$data = [];
			$data['todays_sale_amount'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',3)->sum('payable_amount');
			$data['todays_sale_online'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',3)->where('payment_mode',2)->sum('payable_amount');
			$data['todays_sale_offline'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',3)->where('payment_mode',1)->sum('payable_amount');
			$data['todays_sale_udhari'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',3)->where('payment_mode',3)->sum('payable_amount');
			$data['todays_order_completed'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',3)->count();
			$data['todays_order_pending'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',1)->count();
			$data['todays_order_confirmed'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',2)->count();
			$data['todays_order_canceled'] = Order::whereDate('created_at', date("Y-m-d"))->where('order_status',4)->count();
			$data['todays_present_employees_count'] = Attendence::where('attendence',2)->whereDate('created_at', date("Y-m-d"))->count();
			$data['todays_half_day_employees_count'] = Attendence::where('attendence',3)->whereDate('created_at', date("Y-m-d"))->count();
			$data['todays_absent_employees_count'] = Attendence::where('attendence',1)->whereDate('created_at', date("Y-m-d"))->count();
			$data['totalEmployee'] = User::whereIn('role_id',[3,4])->count();
			$data['total_expense'] = Expense::whereDate('expense_date', date('Y-m-d'))->sum('total_expense');
			return prepareResult(true,'Dashboard data Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function dashboardGraph(Request $request)
	{
		try {
			$data = [];
			$day = !empty($request->day) ? $request->day : 7;
			$dates = [];
			$labels = [];
			if(!empty($request->start_date) && !empty($request->end_date)) 
			{
				$diffrece = dateDifference($request->start_date, $request->end_date) + 1;
				for($i = $diffrece; $i>=1; $i--)
				{
					$dates[] = date("Y-m-d", strtotime('-'.($i-1).' days', strtotime($request->end_date)));

				}
			}
			else
			{
				for($i = $day; $i>=1; $i--)
				{
					$dates[] = date('Y-m-d',strtotime('-'.($i-1).' days'));
				}
			}
			foreach ($dates as $key => $date) {
				$data['labels'][] = $date; 

				$sale_total = OrderDetail::join('orders', function ($join) {
					$join->on('order_details.order_id', '=', 'orders.id');
				})
				->withoutGlobalScope('cafe_id')
				->whereDate('orders.created_at', $date)
				->where('orders.order_status',3)
				->select([
					\DB::raw('SUM(CASE
						WHEN `orders`.`payment_mode`= 1 THEN `order_details`.`sub_total`
						ELSE 0
						END) AS sale_offline'),
					\DB::raw('SUM(CASE
						WHEN `orders`.`payment_mode`= 2 THEN `order_details`.`sub_total`
						ELSE 0
						END) AS sale_online'),
					\DB::raw('SUM(CASE
						WHEN `orders`.`payment_mode`= 3 THEN `order_details`.`sub_total`
						ELSE 0
						END) AS sale_udhari'),
					\DB::raw('SUM(order_details.sub_total) as sale_amount'),
				]);

				if(auth()->user()->cafe_id != 1)
				{
					$sale_total = $sale_total->where('orders.cafe_id',auth()->user()->cafe_id);
				}

				if(!empty($request->cafe_id))
				{
					$sale_total = $sale_total->where('orders.cafe_id',$request->cafe_id);
				}

				if(!empty($request->menu_id))
				{
					$sale_total = $sale_total->where('order_details.menu_id',$request->menu_id);
				}

				$sale_total = $sale_total->first();


				$orders_count = Order::withoutGlobalScope('cafe_id')
				->whereDate('orders.created_at', $date)
				->select([
					\DB::raw('COUNT(IF(orders.order_status = 3, 0, NULL)) as order_completed'),
					\DB::raw('COUNT(IF(orders.order_status = 2, 0, NULL)) as order_confirmed'),
					\DB::raw('COUNT(IF(orders.order_status = 1, 0, NULL)) as order_pending'),
					\DB::raw('COUNT(IF(orders.order_status = 4, 0, NULL)) as order_canceled'),
				]); 
				if(auth()->user()->cafe_id != 1)
				{
					$orders_count = $orders_count->where('orders.cafe_id',auth()->user()->cafe_id);
				}
				if(!empty($request->cafe_id))
				{
					$orders_count = $orders_count->where('orders.cafe_id',$request->cafe_id);
				}
				if(!empty($request->menu_id))
				{
					$orders_count = $orders_count->join('order_details', function ($join) {
						$join->on('orders.id', '=', 'order_details.order_id');
					})
					->where('order_details.menu_id',$request->menu_id);
				}
				$orders_count = $orders_count->first(); 


				$attendence = Attendence::whereDate('created_at', $date)
				->select([
					\DB::raw('COUNT(IF(attendence=3,0, NULL)) as half_day_employees_count'),
					\DB::raw('COUNT(IF(attendence=2,0, NULL)) as present_employees_count'),
					\DB::raw('COUNT(IF(attendence=1,0, NULL)) as absent_employees_count')
				]);
				if(!empty($request->cafe_id))
				{
					$attendence = $attendence->where('cafe_id',$request->cafe_id);
				}
				if(!empty($request->employee_id))
				{
					$attendence = $attendence->where('employee_id',$request->employee_id);
				}
				$attendence = $attendence->first(); 


				$data['sale_amount'][] = $sale_total->sale_amount;
				$data['sale_online'][] = $sale_total->sale_online;
				$data['sale_udhari'][] = $sale_total->sale_udhari;
				$data['sale_offline'][] = $sale_total->sale_offline;
				$data['order_completed'][] = $orders_count->order_completed;
				$data['order_pending'][] = $orders_count->order_pending;
				$data['order_confirmed'][] = $orders_count->order_confirmed;
				$data['order_canceled'][] = $orders_count->order_canceled;
				$data['present_employees_count'][] = $attendence->present_employees_count;
				$data['half_day_employees_count'][] = $attendence->half_day_employees_count;
				$data['absent_employees_count'][] = $attendence->absent_employees_count;
			}

			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}

	}

	public function dashboardTable(Request $request)
	{
		try {
			$day = !empty($request->day) ? $request->day : 7;
			$dates = [];
			$labels = [];
			if(!empty($request->start_date) && !empty($request->end_date)) 
			{
				$diffrece = dateDifference($request->start_date, $request->end_date) + 1;
				for($i = $diffrece; $i>=1; $i--)
				{
					$dates[] = date("Y-m-d", strtotime('-'.($i-1).' days', strtotime($request->end_date)));

				}
			}
			else
			{
				for($i = 1; $i<=$day; $i++)
				{
					$dates[] = date('Y-m-d',strtotime('-'.($i-1).' days'));
				}
			}
			foreach ($dates as $key => $date) {
				// $data['labels'][] = $date; 

				$sale_total = OrderDetail::join('orders', function ($join) {
					$join->on('order_details.order_id', '=', 'orders.id');
				})
				->withoutGlobalScope('cafe_id')
				->whereDate('orders.created_at', $date)
				->where('orders.order_status',3)
				->select([
					\DB::raw('SUM(CASE
						WHEN `orders`.`payment_mode`= 1 THEN `order_details`.`sub_total`
						ELSE 0
						END) AS sale_offline'),
					\DB::raw('SUM(CASE
						WHEN `orders`.`payment_mode`= 2 THEN `order_details`.`sub_total`
						ELSE 0
						END) AS sale_online'),
					\DB::raw('SUM(CASE
						WHEN `orders`.`payment_mode`= 3 THEN `order_details`.`sub_total`
						ELSE 0
						END) AS sale_udhari'),
					\DB::raw('SUM(order_details.sub_total) as sale_amount'),
				]);

				if(auth()->user()->cafe_id != 1)
				{
					$sale_total = $sale_total->where('orders.cafe_id',auth()->user()->cafe_id);
				}

				if(!empty($request->cafe_id))
				{
					$sale_total = $sale_total->where('orders.cafe_id',$request->cafe_id);
				}

				if(!empty($request->menu_id))
				{
					$sale_total = $sale_total->where('order_details.menu_id',$request->menu_id);
				}

				$sale_total = $sale_total->first();


				$orders_count = Order::withoutGlobalScope('cafe_id')
				->whereDate('orders.created_at', $date)
				->select([
					\DB::raw('COUNT(IF(orders.order_status = 3, 0, NULL)) as order_completed'),
					\DB::raw('COUNT(IF(orders.order_status = 2, 0, NULL)) as order_confirmed'),
					\DB::raw('COUNT(IF(orders.order_status = 1, 0, NULL)) as order_pending'),
					\DB::raw('COUNT(IF(orders.order_status = 4, 0, NULL)) as order_canceled'),
				]); 
				if(auth()->user()->cafe_id != 1)
				{
					$orders_count = $orders_count->where('orders.cafe_id',auth()->user()->cafe_id);
				}
				if(!empty($request->cafe_id))
				{
					$orders_count = $orders_count->where('orders.cafe_id',$request->cafe_id);
				}
				if(!empty($request->menu_id))
				{
					$orders_count = $orders_count->join('order_details', function ($join) {
						$join->on('orders.id', '=', 'order_details.order_id');
					})
					->where('order_details.menu_id',$request->menu_id);
				}
				$orders_count = $orders_count->first(); 

				$data[] = [
							'date' => $date, 
							'order_completed' => $orders_count->order_completed, 
							'order_pending' => $orders_count->order_pending, 
							'order_confirmed' => $orders_count->order_confirmed, 
							'order_canceled' => $orders_count->order_canceled,
							'sale_amount' => $sale_total->sale_amount, 
							'sale_online' => $sale_total->sale_online, 
							'sale_udhari' => $sale_total->sale_udhari, 
							'sale_offline' => $sale_total->sale_offline 
						];
			}

			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}

	}


	public function categoryWiseList(Request $request)
	{
		try {
			$data = getDetails($request->start_date, $request->end_date, $request->category, $request->cafe_id);
			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}
	}

	public function dashboardGraphByName(Request $request)
	{
		try {
			$data = getLast30details($request->day , $request->startDate, $request->endDate, $request->cafe_id);
			return prepareResult(true,'Record Fatched Successfully' ,$data, 200);
		} catch (\Throwable $e) {
			Log::error($e);
			return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
		}

	}

}
