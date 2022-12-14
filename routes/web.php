<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Models\Category;
use App\Models\Order;

Route::post('/vendaParcial', 'SellController@vendaParcial');
Route::any('/searchProduct', 'ProductController@search');
Route::post('/associado','SellController@aplicarRemoverDesconto');
Route::post('/cartao','SellController@aplicarRemoverCartao');
Route::post('/admin/generateReport', 'ReportController@generateReport');
Route::post('/admin/analiticReport', 'ReportController@generateAnaliticReport');
Route::post('/admin/sellReport', 'ReportController@generateSellReport');
Route::post('/admin/userReport', 'ReportController@generateUserReport');
Route::get('/admin/report', 'ReportController@index')->name('report');
Route::post('/admin/addStock', 'ProductController@addStock');
Route::post('/admin/decreaseStock', 'ProductController@decreaseStock');
Route::get('/admin/stock', 'ProductController@stock')->name('estoque');
Route::get('/removeItem','SellController@removeItem')->name('removeItem');
Route::get('/ficha', 'SellController@printOrder')->name('admin.sells.ficha');
Route::post('/home', 'SellController@addProducts');
Route::post('/home/cod', 'SellController@codBarra');
Route::post('/criarMesa', 'SellController@criarMesa');
Route::post('/cancelarVenda', 'SellController@cancelarVenda');

Route::get('/home/{id}', function($id){
    $order = Order::find($id);
    $categories = Category::all()->where('status','=',1);
    return view('/home', compact('order', 'categories'));
});

Route::post('/concluirVenda', 'SellController@concluirVenda');

Route::get('/modal/{product_id?}',function($product_id){
    $products = App\Models\Product::all()->where('brand_id', '=', $product_id)->where('status','=', 1);
    $brand = App\Models\Brand::find($product_id);
    $sellController = new \App\Http\Controllers\SellController();
    $tableHTML = $sellController->listaProdutosPorMarca($products);
    $resposta = [
        'table' => $tableHTML,
        'name' => $brand->name,
        'id' => $product_id
    ];

    return Response::json($resposta);
});

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->group(function(){
    Auth::routes();

    Route::group([
        'as'=>'admin.',
        'middleware'=>'auth'
    ], function(){
        Route::resource('categories','CategoryController');
        Route::resource('brands','BrandController');
        Route::resource('products','ProductController');
        Route::resource('clients','ClientController');
        Route::resource('sells', 'SellController');
        Route::resource('providers', 'ProviderController');
        Route::resource('bonifications', 'BonificationController');
        Route::resource('cashes', 'CashController');
        Route::resource('company', 'CompanyController');
    });
});

Route::any('/searchOrderHistory', 'OrderHistoryController@search');
Route::get('/history', 'OrderHistoryController@index')->name('history');
Route::get('/historyDetail', 'OrderHistoryController@show')->name('historyDetail');
Route::get('/upload', 'BrandController@upload');
Route::post('/move', 'BrandController@move')->name('move');
Route::get('/home', 'HomeController@index')->name('home');
Route::get('/createDesk', 'DeskController@createDesk')->name('createDesk');
Route::get('/produtosComCodigos', 'ReportController@produtosComCodigos')->name('produtosComCodigos');
Route::post('/cashes', 'CashController@fecharCaixa')->name('fecharCaixa');
Route::post('/novaEntrada', 'CashController@novaEntrada')->name('novaEntrada');
Route::post('/novaSaida', 'CashController@novaSaida')->name('novaSaida');
Route::post('/vincularMesa', 'DeskController@vincularMesa')->name('vincularMesa');
Route::post('/criarMesaVenda', 'DeskController@criarMesaVenda')->name('criarMesaVenda');
Route::post('/excluirMesa', 'DeskController@excluirMesa')->name('excluirMesa');
Route::post('/imprimirCupom', 'SellController@imprimirCupom')->name('imprimirCupom');
Route::get('/imprimirFichas', 'SellController@imprimirFichas')->name('imprimirFichas');
Route::post('/dadosEmpresa', 'CompanyController@atualizar')->name('dadosEmpresa');
