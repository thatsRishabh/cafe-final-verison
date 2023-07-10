<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\StoreController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/




Route::namespace('App\Http\Controllers\Api\Common')->group(function () {

	Route::controller(AuthController::class)->group(function () {
        // Route::get('unauthorized', 'unauthorized')->name('unauthorized');
        Route::post('login', 'login')->name('login');
        // Route::post('forgot-password', 'forgotPassword')->name('forgot-password');
        // Route::post('update-password', 'updatePassword')->name('update-password');
        Route::post('logout', 'logout')->name('logout')->middleware('auth:api');
        Route::post('change-password', 'changePassword')->name('changePassword')->middleware('auth:api');
	});

	Route::group(['middleware' => 'auth:api'],function () {
		
		Route::controller(EmployeeController::class)->group(function () {
			Route::post('employees', 'employees');
			Route::apiResource('employee', EmployeeController::class)->only([
				'store','destroy','show','update']);
		});
		Route::controller(AssignedLeaveController::class)->group(function () {
			Route::post('assigned-leaves', 'assignedLeaves');
			Route::post('assigned-leave-update', 'update');
			Route::resource('assigned-leave', AssignedLeaveController::class)->only([
				'store','destroy','show' ]);
		});
 		// Unit
		Route::controller(UnitController::class)->group(function () {
			Route::post('units', 'units');
			Route::resource('unit', UnitController::class)->only([
				'store','destroy','show', 'update' ]);
		});
 		// product-info
		Route::controller(ProductInfoController::class)->group(function () {
			Route::post('product-infos', 'productInfos');
			Route::post('excel-import', 'excelImport');
			Route::resource('product-info', ProductInfoController::class)->only([
				'store','destroy','show', 'update' ]);
		});

 		

   		// recipe
		Route::controller(RecipeController::class)->group(function () {
			Route::post('recipes', 'recipes');
			Route::resource('recipe', RecipeController::class)->only([
				'store','destroy','show', 'update' ]);
		});
  		

        // SalaryManagement
		Route::controller(SalaryManagementController::class)->group(function () {
			Route::post('salary-managements', 'salaryManagements');
			Route::resource('salary-management', SalaryManagementController::class)->only([
				'store','destroy','show', 'update' ]);
		});      
		// EmployeeAttendence
		Route::controller(AttendenceController::class)->group(function () {
			Route::post('attendences', 'attendences');
			Route::resource('attendence', AttendenceController::class)->only([
				'store','destroy','show', 'update' ]);
			Route::get('employee-id', 'employeeID'); 
			Route::post('monthly-attendence', 'monthlyAttendence'); 
			Route::post('attendences-date-wise', 'dateWiseSearch');
			Route::post('attendence-multiple-update', 'multipleUpdate'); 
		});

 		
        // Packaging
		Route::controller(PackagingController::class)->group(function () {
			Route::post('packagings', 'packagings');
			Route::resource('packaging', PackagingController::class)->only([
				'store','destroy','show', 'update' ]);
		});
 		// dashboard        
		Route::controller(DashboardController::class)->group(function () {
			Route::post('dashboard', 'dashboard');
			Route::post('dashboard-graph', 'dashboardGraph');
			Route::post('dashboard-table', 'dashboardTable');
			// Route::post('category-wise-list', 'categoryWiseList'); 
			 
			// Route::post('dashboard-graph-list', 'dashboardGraphByName'); 
		});

		/*--------------------------File Upload---------------------------*/
        Route::controller(FileUploadController::class)->group(function () {
            Route::post('file-uploads', 'fileUploads')->name('file-uploads');
            Route::post('file-upload', 'store')->name('file-upload');
        });
	});
});


Route::namespace('App\Http\Controllers\Api\Admin')->group(function () {
	Route::group(['middleware' => 'auth:api'],function () {
		Route::group(['middleware' => 'admin'],function () {
			Route::controller(CafeController::class)->group(function () {
				Route::post('cafes', 'cafes');
				Route::apiResource('cafe', CafeController::class)->only([
					'store','destroy','show','update']);
				// Route::post('cafe-subscription/{id}', 'cafeSubscription');
				Route::post('cafe-child', 'childLogin');
			});
			Route::controller(CafeSubscriptionController::class)->group(function () {
				Route::post('cafe-subscriptions', 'cafeSubscriptions');
				Route::apiResource('cafe-subscription', CafeSubscriptionController::class)->only([
					'store','destroy','show','update']);
			});
		});
	});
});

Route::namespace('App\Http\Controllers\Api\Cafe')->group(function () {
	Route::group(['middleware' => 'auth:api'],function () {

		Route::controller(ProductController::class)->group(function () {
			Route::post('products', 'products');
			Route::resource('product', ProductController::class)->only([
				'store','destroy','show','update' ]);
		});

		Route::controller(CategoryController::class)->group(function () {
			Route::post('categories', 'categories');
			Route::apiResource('category', CategoryController::class)->only([
				'store','destroy','show','update']);
		});

		Route::controller(MenuController::class)->group(function () {
			Route::post('menus', 'menus');
			Route::resource('menu', MenuController::class)->only([
				'store','destroy','show','update' ]);
			Route::post('category-wise-menus', 'categoryWiseMenus');
		});

 		// StockManage
		Route::controller(StockManageController::class)->group(function () {
			Route::post('stock-manages', 'stockManages');
			Route::resource('stock-manage', StockManageController::class)->only([
				'store','destroy','show', 'update' ]);
		});

		// expense
		Route::controller(ExpenseController::class)->group(function () {
			Route::post('expenses', 'expenses');
			Route::resource('expense', ExpenseController::class)->only([
				'store','destroy','show', 'update']);
		});

  		// Order
		Route::controller(OrderController::class)->group(function () {
			Route::post('orders', 'orders');
			Route::get('print-order/{id?}', 'printOrder'); 
			Route::resource('order', OrderController::class)->only([
				'store','destroy','show', 'update' ]);
			Route::get('get-order-recipe/{id}', 'getOrderRecipe');
			Route::post('order-status-update/{id}', 'statusUpdate');
		});

		// Customer
		Route::controller(CustomerController::class)->group(function () {
			Route::post('customers', 'customers');
			Route::resource('customer', CustomerController::class)->only([
				'store','destroy','show', 'update' ]);
		});

 		// CustomerAccountManage
		Route::controller(CustomerAccountController::class)->group(function () {
			Route::post('customer-accounts', 'customerAccounts');
			Route::resource('customer-account', CustomerAccountController::class)->only([
				'store','destroy','show', 'update' ]);
		});

 		// PaymentQrCode
		Route::controller(PaymentQrCodeController::class)->group(function () {
			Route::post('payment-qr-codes', 'paymentQrCodes');
			Route::resource('payment-qr-code', PaymentQrCodeController::class)->only([
				'store','destroy','show', 'update' ]);
		});

	});
});

