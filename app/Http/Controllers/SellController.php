<?php

namespace App\Http\Controllers;

use App\CashMoves;
use App\Company;
use App\Desk;
use App\DeskHistory;
use App\Models\Cash;
use App\Models\Client;
use App\Models\Item;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sell;
use App\Models\PartialOrder;
use function array_key_exists;
use function array_push;
use Auth;
use Barryvdh\DomPDF\Facade as PDF;
use Bootstrapper\Facades\Button;
use Bootstrapper\Facades\Icon;
use Bootstrapper\Facades\Modal;
use function compact;
use function dd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use function MongoDB\BSON\toJSON;
use function redirect;
use function Sodium\compare;
use function view;

class SellController extends Controller
{

    private $STATUS_CANCELADA = 1;
    private $STATUS_MESA = 2;
    private $STATUS_PAGA = 3;
	private $STATUS_EM_ABERTO = 4;
	private $STATUS_PAGA_PARCIALMENTE = 5;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Category::all()->where('status','=',1);
        return view('admin.sells.index', compact('categories'));
    }

    public function printOrder(Request $request){
        $string = $request->get('string');

        return view('admin.sells.ficha', compact('string'));
    }

	//aplica ou remove preço de associado a uma venda
	public function aplicarRemoverDesconto(Request $request){
		$order = Order::find($request->get('order_id'));
		if(!$order->associated) {
			$this->recebeDesconto($order, true);
		}
		else{
			$this->recebeDesconto($order, false);
		}

		$order->update();
		$categories = Category::all()->where('status','=', 1);

		return view('home', compact('order', 'categories'));
	}

	//aplica ou remove preço de cartao de credito a uma venda
	public function aplicarRemoverCartao(Request $request){
		$order = Order::find($request->get('order_id'));
		$aplica = $request->get('aplica');
		if($aplica) {
			$this->aplicaCartao($order, true);
		}
		else{
			$this->aplicaCartao($order, false);
		}

		$order->update();
		$categories = Category::all()->where('status','=', 1);

		return view('home', compact('order', 'categories'));
	}

    //exclui item do pedido e devolve a quantidade ao estoque;
    public function removeItem(Request $request){
		$item = Item::find($request->toArray()['item']);
	    $product = Product::find($item->product_id);
	    $categories = Category::all()->where('status','=', 1);

	    $product->qtd += $item->qtd;
	    $product->update();
	    $order = $this->atualizaPedido($item);
	    $item->delete();

	    return view('home', compact('order', 'categories'));
    }

    public function criarMesa(Request $request){
        $order = new Order();
        $order->client_id = $request->toArray()['client_id'];
        $order->total = 0;
        $order->absolut_total = 0;
        $order->discount = 0;
        $order->status = $this->STATUS_EM_ABERTO;
        $order->associated = $request->toArray()['associated'];
        $order->user_id = Auth::user()->id;
        $order->save();

        $categories = Category::all()->where('status','=',1);
        return redirect('/home/'.$order->id)->with(compact('order', 'categories'));
    }

    public function codBarra(Request $request){
//        dd($request->toArray());
    	$product = $request->get('product_barcode');
    	$qtd = $request->get('qtd');

    	$product = Product::where('barcode', '=', $product)->whereNotNull('barcode')->first();
	    $order = Order::find( $request->toArray()['order_id']);
	    if($product != null && $qtd != null){
	    	$product->qtd -= $qtd;

			//verifica se ja existe um item com esse produto nesse pedido;
		    $item = Item::where('order_id', '=', $order->id)->where('product_id','=', $product->id)->first();
//		    if($product->qtd <= 0)
////		        PARAMETROS GERAIS todo
//			    return redirect()->back()->with('semEstoque', $order)->with(compact('order'));

            $product->update();
		    if($item != null) {
				//adiciona mais 1 quantidade do produto ao item;

				$item->qtd += $qtd;
				if($order->associated) {
					$item->total += $product->price_discount * $qtd;
					$order->total += $product->price_discount * $qtd;
					$order->absolut_total += $product->price_discount * $qtd;
				}
				else {
					$item->total += $product->price_resale * $qtd;
					$order->total += $product->price_resale * $qtd;
					$order->absolut_total += $product->price_resale * $qtd;
				}
				$item->update();
				$order->update();
			}
			else{
				$item = new Item();
				$item->product_id = $product->id;
				if($order->associated == 0)
					$item->total = $product->price_resale * $qtd;
				else
					$item->total = $product->price_discount * $qtd;
				$item->qtd = $qtd;
				$item->order_id = $order->id;
				$item->save();
				$order->total += $item->total;
				$order->absolut_total += $item->total;
				$order->update();
			}
	    }else{
		    if ( $order != null ) {
			    return redirect()->back()->with( 'inexistente', $order )->with( compact( 'order' ) );
		    } else {
			    return redirect()->back()->with( 'inexistente', '' );
		    }
	    }
	    $categories = Category::all()->where('status','=', 1);
	    return view('home', compact('order', 'categories'));
    }

    public function addProducts(Request $request)
    {
        $order = Order::find($request->toArray()['order_id']);
        foreach ($request->toArray() as $produto => $quantidade){
            if($produto != "_token" && $produto != "order_id" && $quantidade > 0) {
            	$produto = Product::find($produto);
            	$produto->qtd -= $quantidade;
            	$produto->update();
            	$item = $this->verificaItemExistente($order->id, $produto->id);
            	if($item == null) {
            		$item = new Item();
					$item->product_id = $produto->id;
					$item->qtd = $quantidade;

					//preço de atacado/associado
		            if($order->associated == 0 && $order->pay_method != 3)
			            $item->total = $quantidade * $produto->price_resale;

		            //preço de cartão de crédito
		            else if($order->pay_method == 3)
			            $item->total = $quantidade * $produto->price_card;

		            //preço padrão
		            else
			            $item->total = $quantidade * $produto->price_discount;

		            $item->order_id = $order->id;
		            $item->save();
		            $order->total += $item->total;
		            $order->absolut_total += $item->total;
	            }else{
            		$item->qtd += $quantidade;
		            if($order->associated == 0 && $order->pay_method != 3) {
			            $item->total += $quantidade * $produto->price_resale;
			            $order->total += $quantidade * $produto->price_resale;
			            $order->absolut_total += $quantidade * $produto->price_resale;
		            }
		            else if($order->pay_method == 3) {
			            $item->total += $quantidade * $produto->price_card;
			            $order->total += $quantidade * $produto->price_card;
			            $order->absolut_total += $quantidade * $produto->price_card;
		            }
		            else {
			            $item->total += $quantidade * $produto->price_discount;
			            $order->total += $quantidade * $produto->price_discount;
			            $order->absolut_total += $quantidade * $produto->price_discount;
		            }
		            $item->update();
	            }
            }
        }
	    $order->update();
        $categories = Category::all()->where('status','=', 1);
	    return view('home', compact('order', 'categories'));
    }

    public function listaProdutosPorMarca($products = array())
    {
        $divs = [];
        $divHeader = '<table class="table table-bordered" style="font-size: 10px; color:black">
                    <tr>
                        <th>Cód - Nome</th>
                        <th style="text-align: center">Estoque</th>
                        <th style="text-align: center">Preço</th>
                        <th style="text-align: center">Associado</th>
                        <th style="text-align: center">Crédito</th>
                        <th style="text-align: center">Quantidade</th>
                    </tr>';
        $divFooter = '<input name="_token" type="hidden" value="'. csrf_token().'"/></table>';
        foreach ($products as $product){
            if($product->barcode != null && $product->barcode != "")
                $productWithCode = $product->barcode.' - '.$product->name;
            else
                $productWithCode = $product->name;
            $divCont = '<tr>
                        <td>'.$productWithCode.'
                        </td>
                        <td style="text-align: center">
                        '.$product->qtd.'
                        </td>
                        <td style="text-align: center">R$ '.number_format($product->price_resale, 2,',', '.').'
                        </td>
                        <td style="text-align: center">R$ '.number_format($product->price_discount, 2,',', '.').'
                        </td>
                        <td style="text-align: center">R$ '.number_format($product->price_card, 2,',', '.').'
                        </td>
                        <td style="text-align: center" form="form-add-order">'.
                \Bootstrapper\Facades\Button::appendIcon(\Bootstrapper\Facades\Icon::plus())->withAttributes(
                    ['class' => 'btn btn-xs', 'onclick' => 'incrementaProduto("produto'.$product->id.'")']).
                       '&nbsp;&nbsp;<input id="produto'.$product->id.'"  min="0" style="width:60px" class="form" name="'.$product->id.'" type="number" value="0">&nbsp;&nbsp;'.
               \Bootstrapper\Facades\Button::appendIcon(\Bootstrapper\Facades\Icon::minus())->withAttributes(
                    ['class' => 'btn btn-xs', 'onclick' => 'decrementaProduto("produto'.$product->id.'")']).'
                                
                        </td>
                        </tr>';
            array_push($divs, $divCont);
        }
        $string = implode($divs);
        $table = $divHeader.$string.$divFooter;

        return $table;
    }

    public function concluirVenda(Request $request){
//        dd($request->toArray());
        $dinheiro = Sell::converteMoedaParaDecimal($request->toArray()['dinheiroT']);
        $debito = Sell::converteMoedaParaDecimal($request->toArray()['debitoT']);
        $credito = Sell::converteMoedaParaDecimal($request->toArray()['creditoT']);
        $order = Order::find($request->toArray()['order_id']);

        $order->pay_method = $request->toArray()['formaPagamento'];
//        $order->total = $debito + $credito + $dinheiro;
        $order->debit = $debito;
        $order->credit = $credito;
        $order->money = $dinheiro;

        if($request->get('user_id') != null)
        	$order->user_id = $request->get('user_id');

        $cash = CashController::buscaCaixaPorUsuario(Auth::id());
        $cashMoves = new CashMoves();
        $cashMoves->type = $cashMoves->getTIPOVENDA();
        $cashMoves->cash_id = $cash->id;
        $cashMoves->order_id = $order->id;
        $cashMoves->user_id = Auth::id();
        switch ($order->pay_method){
            case 1:
                $cashMoves->money = $order->total;
                $cashMoves->total = $order->total;
                $order->money = $order->total;
                break;
            case 2:
                $cashMoves->debit = $order->total;
                $cashMoves->total = $order->total;
                $order->debit = $order->total;
                break;
            case 3:
                $cashMoves->credit = $order->total;
                $cashMoves->total = $order->total;
                $order->credit = $order->total;
                break;
            case 4:
                $cashMoves->money = $order->money;
                $cashMoves->debit = $order->debit;
                $cashMoves->credit = $order->credit;
                $cashMoves->total = $cashMoves->money + $cashMoves->credit + $cashMoves->debit;
                break;
            default:
                break;
        }
        $cashMoves->save();

	    if(array_key_exists('valorDesconto', $request->toArray())) {
            if($request->toArray()['valorDesconto'] != "") {
                $discount = Sell::converteMoedaParaDecimal($request->toArray()['valorDesconto']);
                $order->total -= $discount;
                $order->discount = $discount;
                $cashMovesDiscount = new CashMoves();
                $cashMovesDiscount->type = $cashMoves->getTIPODESCONTO();
                $cashMovesDiscount->cash_id = $cash->id;
                $cashMovesDiscount->order_id = $order->id;
                $cashMovesDiscount->user_id = Auth::id();
                $cashMovesDiscount->total = $discount;
                $cashMovesDiscount->obs = 'Desconto aplicado na venda '.$order->id;
                $cashMovesDiscount->save();
            }
	    }

        $order->status = $this->STATUS_PAGA;
        $order->update();

        if($order->type == 2){
            $desk = Desk::all()->where('order_id','=',$order->id)->where('status','=',1)->first();
            $history = new DeskHistory();
            $history->desk_id = $desk->id;
            $history->user_id = Auth::id();
            $history->order_id = $order->id;
            $deskcontroller = new DeskHistoryController();
            $history->status = $deskcontroller->getSTATUSPAGAMENTO();
            $history->save();

            $history = new DeskHistory();
            $history->desk_id = $desk->id;
            $history->user_id = Auth::id();
            $history->order_id = $order->id;
            $deskcontroller = new DeskHistoryController();
            $history->status = $deskcontroller->getSTATUSDESVINCULANDO();
            $history->save();
        }

        $moveController = new MoveController();
	    $moveController->registraBaixaTotal($order, 2);
        return Redirect::to('/home')->with('vendaRealizada', 'Venda realizada com sucesso!');
    }

    public function cancelarVenda(Request $request){
        $order = Order::find($request->toArray()['order_id']);
        $order->status = $this->STATUS_CANCELADA;
        $order->update();
        $this->devolveProdutoEstoque($order->id);

        if($order->type == 2){
            $desk = Desk::all()->where('order_id','=',$order->id)->where('status','=',1)->first();
            $history = new DeskHistory();
            $history->desk_id = $desk->id;
            $history->user_id = Auth::id();
            $history->order_id = $order->id;
            $deskcontroller = new DeskHistoryController();
            $history->status = $deskcontroller->getSTATUSDESVINCULANDO();
            $history->save();
        }

        $categories = Category::all()->where('status','=', 1);
        return redirect()->to('/home')->with(compact('categories'));
    }

    private function devolveProdutoEstoque($id)
    {
        $itens = Item::all()->where('order_id', '=', $id);

        foreach ($itens as $item){
            $product = Product::find($item->product_id);
            $product->qtd += $item->qtd;
            $product->update();
        }

    }

    private function recebeDesconto($order, $aplica)
    {
    	$total = 0;
	    foreach ($order->itens()->get() as $item){
		    if($aplica) {
			    $item->total = $item->qtd * Product::find( $item->product_id )->price_discount;
			    $order->associated = 1;
		    }
		    else{
			    $item->total = $item->qtd * Product::find( $item->product_id )->price_resale;
			    $order->associated = 0;
		    }
		    $item->update();
		    $total += $item->total;
	    }
	    $order->absolut_total = $total;
	    $order->absolut_total += OrderController::valorTotalSubVendas($order);

	    $order->total = $total;

        return $order;
    }

    private function atualizaPedido($item){
	    $order = Order::find($item->order_id);
	    $order->total -= $item->total;
	    $order->absolut_total -= $item->total;

	    $order->update();
    	return $order;
    }

	public static function buscaProdutosPorVenda(Order $order)
	{
		$itens = Item::all()->where('order_id', '=', $order->id);
		$divs = [];
		$divHeader = '<table class="table table-bordered" style="font-size: 10px; color:black">
                        <tr>
                            <th style="text-align: center">Descrição</th>
                            <th style="text-align: center">Quantidade</th>
                            <th style="text-align: center">Valor Unidade</th>
                            <th style="text-align: center">A Pagar</th>
                        </tr>';
		$divFooter = '<input name="_token" type="hidden" value="'. csrf_token().'"/></table>';
		foreach ($itens as $item){
			$product = Product::find($item->product_id);
			$divCont = '<tr>
                        <td>'.$product->name.'
                        </td>
                        <td style="text-align: center">'.$item->qtd.'
                        </td>
                        <td style="text-align: center">R$'.number_format($item->total/$item->qtd, 2,',', '.').'
                        </td>
                        <td style="text-align: center; min-width: 170px" form="form-add-order">'.
			           \Bootstrapper\Facades\Button::appendIcon(\Bootstrapper\Facades\Icon::plus())->withAttributes(
				           ['class' => 'btn btn-xs', 'onclick' => 'incrementavenda("venda'.$product->id.'")']).
			           '&nbsp;<input id="venda'.$product->id.'"  min="0" max="'.$item->qtd.'" style="width:60px" class="form" name="'.$item->id.'" type="number" value="0">&nbsp;'.
			           \Bootstrapper\Facades\Button::appendIcon(\Bootstrapper\Facades\Icon::minus())->withAttributes(
				           ['class' => 'btn btn-xs', 'onclick' => 'decrementavenda("venda'.$product->id.'")']).'
                                
                        </td>
                        </tr>';
			array_push($divs, $divCont);
		}
		$string = implode($divs);
		$table = $divHeader.$string.$divFooter;

		return $table;
	}

	public function vendaParcial(Request $request){
		//verificar se o item só contem um produto, se for unico troca o order_id do item,
		//     senão cria um novo item para essa order e subtrai a quantidade de produtos do item da ordem antiga
        $dinheiro = Sell::converteMoedaParaDecimal($request->toArray()['dinheiroP']);
        $debito = Sell::converteMoedaParaDecimal($request->toArray()['debitoP']);
        $credito = Sell::converteMoedaParaDecimal($request->toArray()['creditoP']);
        $orderOriginal = Order::find($request->get('order_id'));

		$parcial = new Order();
		//setar forma de pagamento, e valor total da ordem derivada,
		$parcial->pay_method = $request->get('formaPagamento');
//		$parcial->total = $debito + $credito + $dinheiro;
        $parcial->debit = $debito;
        $parcial->credit = $credito;
        $parcial->money = $dinheiro;
		$parcial->status = $this->STATUS_PAGA;
		$parcial->client_id = $orderOriginal->client_id;
		$parcial->user_id = $orderOriginal->user_id;
		$parcial->associated = $orderOriginal->associated;
		$parcial->original_order = $orderOriginal->id;

        if($parcial->pay_method == 4) {
            $orderOriginal->total -= $debito + $credito + $dinheiro;

            if($orderOriginal->total < 0.01){
                $orderOriginal->status = $this->STATUS_PAGA;
                $orderOriginal->update();
                $parcial->save();
                $cash = CashController::buscaCaixaPorUsuario(Auth::id());
                $cashMoves = new CashMoves();
                $cashMoves->type = $cashMoves->getTIPOVENDA();
                $cashMoves->cash_id = $cash->id;
                $cashMoves->order_id = $parcial->id;
                $cashMoves->user_id = Auth::id();
                $cashMoves->money += $parcial->money;
                $cashMoves->debit += $parcial->debit;
                $cashMoves->credit += $parcial->credit;
                $cashMoves->total += $cashMoves->money + $cashMoves->credit + $cashMoves->debit;
                $cashMoves->save();

                if($orderOriginal->type == 2){
                    $desk = Desk::all()->where('order_id','=',$orderOriginal->id)->where('status','=',1)->first();
                    $history = new DeskHistory();
                    $history->desk_id = $desk->id;
                    $history->user_id = Auth::id();
                    $history->order_id = $orderOriginal->id;
                    $deskcontroller = new DeskHistoryController();
                    $history->status = $deskcontroller->getSTATUSPAGAMENTO();
                    $history->save();

                    $history = new DeskHistory();
                    $history->desk_id = $desk->id;
                    $history->user_id = Auth::id();
                    $history->order_id = $orderOriginal->id;
                    $deskcontroller = new DeskHistoryController();
                    $history->status = $deskcontroller->getSTATUSDESVINCULANDO();
                    $history->save();
                }
                return Redirect::to('/home')->with('vendaRealizada', 'Venda realizada com sucesso!');
            }else
                $orderOriginal->status = $this->STATUS_PAGA_PARCIALMENTE;

                $orderOriginal->update();
                $parcial->save();
                $cash = CashController::buscaCaixaPorUsuario(Auth::id());
                $cashMoves = new CashMoves();
                $cashMoves->type = $cashMoves->getTIPOVENDA();
                $cashMoves->cash_id = $cash->id;
                $cashMoves->order_id = $parcial->id;
                $cashMoves->user_id = Auth::id();
                $cashMoves->money += $parcial->money;
                $cashMoves->debit += $parcial->debit;
                $cashMoves->credit += $parcial->credit;
                $cashMoves->total += $cashMoves->money + $cashMoves->credit + $cashMoves->debit;
                $cashMoves->save();
                $order = $orderOriginal;
                $categories = Category::all()->where('status','=', 1);

                if($orderOriginal->type == 2){
                    $desk = Desk::all()->where('order_id','=',$orderOriginal->id)->where('status','=',1)->first();
                    $history = new DeskHistory();
                    $history->desk_id = $desk->id;
                    $history->user_id = Auth::id();
                    $history->order_id = $orderOriginal->id;
                    $deskcontroller = new DeskHistoryController();
                    $history->status = $deskcontroller->getSTATUSPAGAMENTO();
                    $history->save();
                }
                return view('home', compact('order', 'categories'));
        }

		$totalItensRemovidos = 0;
		foreach ($request->toArray() as $item => $quantidade){
			if($item != "valorPago" && $item != "user_id" && $item != "obsParcial" && $item != "_token" && $item != "order_id" && $item != "formaPagamento" && $quantidade != 0) {
				$itemOriginal = Item::find($item);
				$valorDoItem = $itemOriginal->total / $itemOriginal->qtd;
				if($itemOriginal->qtd > $quantidade){

					//verificar o valor total de cada order de acordo com a quantidade de produtos de cada item
					$itemOriginal->qtd -= $quantidade;
					$itemOriginal->total = $itemOriginal->qtd * $valorDoItem;

					$itemDerivado = new Item();
					$itemDerivado->product_id = $itemOriginal->product_id;
					$itemDerivado->qtd = $quantidade;
					$itemDerivado->total = $quantidade * $valorDoItem;
					$parcial->save();
					$itemDerivado->order_id = $parcial->id;

					$itemOriginal->update();
					$itemDerivado->save();
					$totalItensRemovidos += $itemDerivado->total;

					$parcial->total += $itemDerivado->total;
				} else{
					//item aponta para a partialOrder
					$parcial->total += $itemOriginal->total;
					$parcial->save();
					$itemOriginal->order_id = $parcial->id;
					$totalItensRemovidos += $itemOriginal->total;
					$itemOriginal->update();
				}
			}
		}

		$moveController = new MoveController();
		$moveController->registraBaixaTotal($parcial, 2);

		$orderOriginal->total -= $totalItensRemovidos;

		if($request->get('user_id') != null) {
			$parcial->user_id = $request->get( 'user_id' );
			$orderOriginal->user_id = $request->get( 'user_id' );
		}

		$parcial->save();
		$orderOriginal->status = $this->STATUS_PAGA_PARCIALMENTE;
		$orderOriginal->update();

		//Adicionar as vendas ao caixa
        $cash = CashController::buscaCaixaPorUsuario(Auth::id());
        $cashMoves = new CashMoves();
        $cashMoves->cash_id = $cash->id;
        $cashMoves->order_id = $parcial->id;
        $cashMoves->user_id = Auth::id();
        $cashMoves->type = $cashMoves->getTIPOVENDA();
        switch ($parcial->pay_method){
            case 1:
                $cashMoves->money += $parcial->total;
                $parcial->money += $parcial->total;
                $cashMoves->total += $parcial->total;
                break;
            case 2:
                $cashMoves->debit += $parcial->total;
                $parcial->debit += $parcial->total;
                $cashMoves->total += $parcial->total;
                break;
            case 3:
                $cashMoves->credit += $parcial->total;
                $parcial->credit += $parcial->total;
                $cashMoves->total += $parcial->total;
                break;
            default:
                break;
        }
        $parcial->update();
        $cashMoves->save();

        if($orderOriginal->type == 2){
            $desk = Desk::all()->where('order_id','=',$orderOriginal->id)->where('status','=',1)->first();
            $history = new DeskHistory();
//            dd($desk);
            $history->desk_id = $desk->id;
            $history->user_id = Auth::id();
            $history->order_id = $orderOriginal->id;
            $deskcontroller = new DeskHistoryController();
            $history->status = $deskcontroller->getSTATUSPAGAMENTO();
            $history->save();
        }

		//verificar se a ordem de origem ainda possui itens, senão deve-se colocá-la como paga
		if(Item::all()->where('order_id', '=', $orderOriginal->id)->isEmpty()){
			$orderOriginal->status = $this->STATUS_PAGA;
			$orderOriginal->update();

            if($orderOriginal->type == 2){
                $desk = Desk::all()->where('order_id','=',$orderOriginal->id)->where('status','=',1)->first();
                $history = new DeskHistory();
                $history->desk_id = $desk->id;
                $history->user_id = Auth::id();
                $history->order_id = $orderOriginal->id;
                $deskcontroller = new DeskHistoryController();
                $history->status = $deskcontroller->getSTATUSDESVINCULANDO();
                $history->save();
            }

			return Redirect::to('/home')->with('vendaRealizada', 'Venda realizada com sucesso!');
		}

        $order = $orderOriginal;
		$categories = Category::all()->where('status','=', 1);
		return view('home', compact('order', 'categories'));
	}

	private function verificaItemExistente($order_id, $product_id) {
		$item = Item::where('order_id', '=', $order_id)->where('product_id','=', $product_id)->first();
		return $item;
	}

	private function aplicaCartao(Order $order, $aplica){
		$total = 0;
		foreach ($order->itens()->get() as $item){
			if($aplica) {
				$item->total = $item->qtd * Product::find( $item->product_id )->price_card;
				$order->pay_method = 3;
			}
			else{
				$item->total = $item->qtd * Product::find( $item->product_id )->price_resale;
				$order->pay_method = null;
			}
			$item->update();
			$total += $item->total;
		}
		$order->absolut_total = $total;
		$order->absolut_total += OrderController::valorTotalSubVendas($order);

		$order->total = $total;
		$order->associated = 0;
		return $order;
	}

    /**
     * @return int
     */
    public function getSTATUSMESA()
    {
        return $this->STATUS_MESA;
    }

    public function imprimirCupom(Request $request)
    {
//        // start printer
//        $handle = printer_open();
//        printer_start_doc($handle, "My Document");
//        printer_start_page($handle);
//        // create content here
//        // print
//        $font = printer_create_font("Arial", 72, 48, 400, false, false, false, 0);
//        printer_select_font($handle, $font);
//        printer_draw_text($handle, 'the text that will be printed', 100, 100);
//        printer_delete_font($font);
//        printer_end_page($handle);
//        printer_end_doc($handle);
//        printer_close($handle);


        $order_id = $request->get('order_id');
        $order = Order::find($order_id);
        $itens = DB::table('itens')->select('*')->where('order_id','=',$order_id)->orderByDesc('updated_at')->get()->toArray();
        $itens = Item::hydrate($itens);

        $company = Company::find(1);
        if(isset($company->name))
            $cabecalhoLoja = '<h1 style=" line-height: 10%; text-align: center;">'.$company->name.'</h1>';
        if(isset($company->phone))
            $cabecalhoLoja .= '<h5 style="line-height: 80%;width: 250px; text-align: center;">'.$company->phone.'</h5>';
        if(isset($company->address))
            $cabecalhoLoja .= '<p style="font-size: 10px; line-height: 80%;width: 250px;">'.$company->address.'</p>';
        if(isset($company->cnpj))
            $cabecalhoLoja .= '<p style="font-size: 10px;line-height: 10%;">CNPJ: '.$company->cnpj.'</p>';
        $cabecalhoLoja .= '<hr><p style="font-weight: bold; font-size: 12px; text-align: center; margin-top: -8px; margin-bottom: -5px">*** sem valor fiscal ***</p>';


        $tableHeader = '<table class="table table-condensed" style="width: 280px;font-size: 10px">
                            <tr>
                                <th style="text-align: left;">Cod.</th>
                                <th style="text-align: left;">Prod.</th>
                                <th style="text-align: left;">Qtd.</th>
                                <th style="text-align: right;">Vlr Unid.</th>
                                <th style="text-align: right;">Vlr Total</th>
                            </tr>';
        $tableCont = [];
        $total = 0;
        foreach ($itens as $item){

            $product = Product::find($item->product_id);
            if($order->associated == 1)
                $price = $product->price_discount;
            else if($order->pay_method == 3)
                $price = $product->price_card;
            else
                $price = $product->price_resale;


            $tupla = '      <tr>
                                <td style="vertical-align: middle" align="left">'.$product->barcode.'</td>
                                <td style="vertical-align: middle" align="left">'.$product->name.'</td>
                                <td align="left" style="vertical-align: middle">'.$item->qtd.'</td>
                                <td align="right" style="vertical-align: middle;  color: #000000;">'.number_format($price, 2, ',', '.').'</td>
                                <td align="right" style="vertical-align: middle;  color: #000000;">'.number_format($item->total, 2, ',', '.').'</td>
                            </tr>';
            array_push($tableCont, $tupla);
            $total += $item->total;
        }
        $tableFooter = '</table>';
        $tableCont = implode($tableCont);

        $valorPago = 0;
        if(\App\Http\Controllers\OrderController::possuiPagamento($order))
            $valorPago = \App\Http\Controllers\OrderController::valorPago($order);

        if($valorPago > 0){
            $valorTotal = '<TABLE CLASS="table table-condensed" style="width: 250px;font-size: 10px">
                                   <TR>
                                       <TH  style="text-align: left;">VALOR TOTAL:</TH>
                                       <TH style="text-align: right;">R$'.number_format($total, 2, ',', '.').'</TH>
                                   </TR>
                                   <TR>
                                       <TH  style="text-align: left;">VALOR PAGO:</TH>
                                       <TH style="text-align: right;">R$'.number_format($valorPago, 2, ',', '.').'</TH>
                                   </TR>
                                   <TR>
                                       <TH  style="text-align: left;">VALOR RESTANTE:</TH>
                                       <TH style="text-align: right;">R$'.number_format($total - $valorPago, 2, ',', '.').'</TH>
                                   </TR>
                               </TABLE>';
        }else{
            $valorTotal = '<TABLE CLASS="table table-condensed" style="width: 250px;font-size: 10px"><TR><TH  style="text-align: left;">VALOR TOTAL:</TH><TH style="text-align: right;">R$'.number_format($total, 2, ',', '.').'</TH></TR></TABLE>';
        }

        $msgAmistosa = '<p style="text-align: center; font-size: 10px">'.$company->msg.'</p>';

        $bonificacoesTable = '';
        if(OrderController::possuiBonificacao($order)) {
            $bonificacoes = OrderController::bonificacoes($order);
            $bonificaDescricao = '<BR><p style="font-size: 10px; line-height: 10%;">BONIFICAÇÕES</p>';
            $bonificaTableHead = '<table class="table table-condensed" style="width: 250px;font-size: 10px">
                            <tr>
                                <th>Cod.</th>
                                <th>Prod.</th>
                                <th style="text-align: center;">Qtd.</th>
                                <th style="text-align: center;">Vlr Unid.</th>
                                <th style="text-align: center;">Vlr Total</th>
                            </tr>';
            $bonificaTableCont = [];
            foreach ($bonificacoes as $bon){
                $item = Item::  find($bon->item_id);
                $product = Product::find($item->product_id);
                $tupla = '      <tr>
                                <td style="vertical-align: middle" align="left">'.$product->barcode.'</td>
                                <td style="vertical-align: middle" align="left">'.$product->name.'</td>
                                <td align="center" style="vertical-align: middle">'.$item->qtd.'</td>
                                <td align="center" style="vertical-align: middle;  color: #000000;">'.number_format(0.00, 2, ',', '.').'</td>
                                <td align="center" style="vertical-align: middle;  color: #000000;">'.number_format(0.00, 2, ',', '.').'</td>
                            </tr>';
                array_push($bonificaTableCont, $tupla);
                $total += $item->total;
            }
            $bonificaTableFooter = '</table>';
            $bonificaTableCont = implode($bonificaTableCont);
            $bonificacoesTable = $bonificaDescricao.$bonificaTableHead.$bonificaTableCont.$bonificaTableFooter;
        }


        $string = $cabecalhoLoja.$tableHeader.$tableCont.$tableFooter.$valorTotal. $bonificacoesTable.$msgAmistosa;


        return view('admin.sells.cupom', compact('string'));
////
//        $pdf = PDF::loadView( 'admin.sells.cupom', compact( 'string' ) );
//        return $pdf->download( 'Cupom_Venda_'.$order->id.'.pdf' );
    }

    public function imprimirFichas(Request $request)
    {
        $order_id = $request->order_id;
        $order = Order::find($order_id);
        $itens = DB::table('itens')->select('*')->where('order_id','=',$order_id)->orderByDesc('updated_at')->get()->toArray();
        $itens = Item::hydrate($itens);
        $fichas = [];
        $company = Company::find(1);
        $cabecalhoLoja = ' <h4 style="line-height: 10%; text-align: center;">'.$company->name.'</h4>
                            <h5 style="line-height: 80%; text-align: center;"> Pedido: '.$order->id.'</h5>';

        foreach ($itens as $item){

            $product = Product::find($item->product_id);

            $produto = '<h1 style="text-align: center;">'.$product->name.'</h1>';
            $msgAmistosa = '<p style="text-align: center; font-size: 10px">'.$company->msg.'</p>';


            array_push($fichas, $cabecalhoLoja.$produto.$msgAmistosa);
        }

        //$string = strtr($string, array('\\' => '\\\\', "'" => "\\'", '"' => '\\"',
        //"\r" => '\\r', "\n" => '\\n' ));

        return response()->json(['status_code' => '200', 'data' => $fichas]);
    }

}
