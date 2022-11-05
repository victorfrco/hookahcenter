@extends('layouts.app')

@section('content')
    @if (session('inexistente'))
        @php
            if(session('inexistente')->exists)
                $order = session('inexistente');
        @endphp
        <div class="alert alert-danger" style="position:fixed; width: 40%; margin-left: 30%; z-index:9999;">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Ops!</strong> Produto inexistente, <a href="{{route('admin.products.create')}}" class="alert-link">clique aqui </a>caso queira adicioná-lo.
        </div>
    @endif
    @if (session('semEstoque'))
        @php
            if(session('semEstoque')->exists)
                $order = session('semEstoque');
        @endphp
        <div class="alert alert-warning" style="position:fixed; width: 60%; margin-left: 20%; z-index:9999;">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Ops!</strong> Produto com estoque negativo, <a href="{{route('estoque')}}" class="alert-link">clique aqui </a>caso queira aumentar seu estoque.
        </div>
    @endif
    @if (session('vendaRealizada'))
        <div class="alert alert-success" style="position:fixed; width: 40%; margin-left: 30%; z-index:9999;">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Ok!</strong> Venda realizada com sucesso!
        </div>
    @endif


    <div class="container">
        <div class="col-xs-7 col-sm-6 col-lg-7"  style="margin-left:-90px; margin-right: 130px; margin-bottom: 10px;">
            @if(App\Http\Controllers\CashController::buscaCaixaPorUsuario(\Illuminate\Support\Facades\Auth::id()) != null)
                {!! \Bootstrapper\Facades\Button::primary('Nova Venda')->withAttributes(['class'=>'botao', 'data-toggle' => 'modal', 'data-target' => '#novaMesaModal']) !!}
            @else
                {!! \Bootstrapper\Facades\Button::primary('Nova Venda')->withAttributes(['class'=>'botao', 'data-toggle' => 'modal', 'data-target' => '#novaMesaModal', 'disabled' => 'true']) !!}
                {!! \Bootstrapper\Facades\Button::primary('Abrir Caixa!')->asLinkTo(route('admin.cashes.index')) !!}
            @endif
        </div>
        <div class="row" style="text-align: right">
            {!! Form::open(array('action' => 'SellController@codBarra', 'method' => 'post', 'style' => 'display:inline')) !!}
            {{Form::text('qtd',null,['placeholder' => 'Qtd', 'class' => 'btn', 'style' => 'text-align:left; width:50px; color: #ffffff; background-color:#000000; border-color:#ffcc00', 'id' => 'codBarQtd'])}}
            {!! Form::search('product_barcode',null,['placeholder' => 'Código do produto...', 'class' => 'btn', 'style' => 'text-align:left; width:200px; color: #ffffff; background-color:#000000; border-color:#ffcc00', 'id' => 'codBar']) !!}
            @if(isset($order))
                {!! Form::button(Icon::barcode(), ['type'=>'submit', 'class' => 'btn btn-primary', 'rel'=>'tooltip', 'title'=>'Buscar produto por código']) !!}
            @else
                {!! Form::button(Icon::barcode(), ['type'=>'submit', 'class' => 'btn btn-primary', 'disabled' => 'true']) !!}
            @endif

            @isset($order)
                   {!! Form::hidden('order_id', $order->id) !!}
            @endisset
            {!! Form::close() !!}
            @if(isset($order))
                @if(!$order->associated)
                    @if($order->pay_method != '3')
                        {!! Button::success(Icon::create('link'))->addAttributes(['style' => 'display: inline;margin-left:30px; margin-right:-35px; height:40px;', 'data-toggle' => 'modal', 'data-target' => '#confirmarAssociadoModal', 'rel'=>'tooltip', 'title'=>'Aplicar desconto conforme tabela'])  !!}
                        {!! Button::success(Icon::create('credit-card'))->addAttributes(['style' => 'display: inline;margin-left:30px; margin-right:-35px; height:40px;', 'data-toggle' => 'modal', 'data-target' => '#confirmarCartaoModal', 'rel'=>'tooltip', 'title'=>'Aplicar taxa do cartão conforme tabela'])  !!}
                    @elseif($order->pay_method == '3')
                        {!! Button::primary(Icon::create('link'))->addAttributes(['style' => 'display: inline;margin-left:30px; margin-right:-35px; height:40px;', 'disabled' => 'true', 'rel'=>'tooltip', 'title'=>'Aplicar desconto conforme tabela'])  !!}
                        {!! Button::danger(Icon::create('credit-card'))->addAttributes(['style' => 'display: inline;margin-left:30px; margin-right:-35px; height:40px;', 'data-toggle' => 'modal', 'data-target' => '#removerCartaoModal', 'rel'=>'tooltip', 'title'=>'Aplicar taxa do cartão conforme tabela'])  !!}
                    @endif
                @elseif($order->associated)
                    {!! Button::danger(Icon::create('link'))->addAttributes(['style' => 'display: inline;margin-left:30px; margin-right:-35px; height:40px;', 'data-toggle' => 'modal', 'data-target' => '#removerAssociadoModal', 'rel'=>'tooltip', 'title'=>'Aplicar desconto conforme tabela'])  !!}
                    {!! Button::primary(Icon::create('credit-card'))->addAttributes(['style' => 'display: inline;margin-left:30px; margin-right:-35px; height:40px;', 'disabled' => 'true', 'rel'=>'tooltip', 'title'=>'Aplicar taxa do cartão conforme tabela'])  !!}
                @endif
                    {!! Form::open(array('action' => 'SellController@imprimirCupom', 'method' => 'post', 'style' => 'display:inline', 'target'=>'_blank')) !!}
                    {!! Form::hidden('order_id',$order->id) !!}
                    {!! Form::button(Icon::create('list-alt'), ['type' => 'submit', 'class' => 'btn btn-primary btn-sm', 'style' => 'width:40px;display: inline;margin-left:30px; margin-right:-35px; height:40px;', 'rel'=>'tooltip', 'title'=>'Imprimir cupom da venda'] )  !!}
                    {!! Form::close() !!}
            @else
                {!! Button::primary(Icon::create('link'))->addAttributes(['style' => 'display: inline;margin-left:30px; margin-right:-35px; height:40px;', 'disabled' => 'true', 'rel'=>'tooltip', 'title'=>'Aplicar desconto conforme tabela'])  !!}
                {!! Button::primary(Icon::create('credit-card'))->addAttributes(['style' => 'display: inline;margin-left:30px; margin-right:-35px; height:40px;', 'disabled' => 'true', 'rel'=>'tooltip', 'title'=>'Aplicar taxa do cartão conforme tabela'])  !!}
                {!! Button::primary(Icon::create('list-alt'))->addAttributes(['style' => 'display: inline;margin-left:30px; margin-right:-35px; height:40px;', 'disabled' => 'true', 'rel'=>'tooltip', 'title'=>'Imprimir cupom da venda'])  !!}
            @endif
        </div>
        <div class="row">
            <div class="col-xs-7 col-sm-6 col-lg-8" style="background-color:#000000; background-image:url({{asset('storage/images/brands/listaEsquerda.jpg')}});  margin-left:-61px; border: solid; border-width: 1px; height: 450px;" id="tabsCategorias" data-url="<?= route('admin.categories.create') ?>">
                @php
                    foreach($categories as $category){
                        $brands = App\Models\Brand::all()->where('category_id', '=', $category->id)->where('status','=', 1);
                        $listadivs = [];
                        foreach ($brands as $brand){
                            $exibe = App\Models\Brand::criaLista($brand->id);

                            array_push($listadivs, $exibe);
                        }

                        $string = implode($listadivs);

                       $names[] = [
                                'title' => '<p style="text-align:center; font-size:12px" rel="tooltip" title="'.$category->name.' : '.$category->description.'" >'.substr($category->name,0,9).'</p>',
                                'content' => '<div class="chico" style="overflow-y: scroll">'.$string.'</div>'
                            ];
                            unset($listadivs);
                     }
                      $names[] = [
                         'title' => '<p style="text-align:center; vertical-align: top; font-size:20px" rel="tooltip" title="Nova Categoria">'.Icon::create('plus').'</p>',
                         'content' => ''
                     ];
                @endphp
                @if(isset($order))
                    {!! Tabbable::withContents($names) !!}
                @else
                    <h4 style="text-align: center; margin-top: 25%">Para iniciar clique em "Nova Venda" ou selecione uma mesa disponível!</h4>
                @endif
            </div>
            <div class="col-xs-5 col-sm-6 col-lg-5" style="background-color:#000000; background-image:url({{asset('storage/images/brands/listaEsquerda.jpg')}}); margin-right:-40px; border: solid; border-width: 1px; height: 450px; overflow: auto">
                @if(isset($order))
                        <div align="center" style="border-bottom: solid; border-width: 1px; border-color: #2F3133"> Produtos de
                            @php
                            if($order->type == 2)
                                echo \App\Desk::all()->where('order_id','=', $order->id)->where('status','=',1)->first()->name.' ('.$order->id.')';
                            else
                                echo $order->client->name.'('.$order->id.')';
                            @endphp
                        </div>
                        {!! $tabela = App\Models\Sell::atualizaTabelaDeItens($order->id)!!}
                @else
                        <div align="center" style="border-bottom: solid; border-width: 1px; border-color: #2F3133"> Lista de Produtos </div>
                @endif

            </div>
        </div>
            <div style="margin-left:-75px">Vendas:</div>
        <div class="col-xs-7 col-sm-6 col-lg-7" style="max-height: 70px; min-width:770px; margin-left:-90px; overflow-x: auto;white-space: nowrap;">
            @php
                if(App\Http\Controllers\CashController::buscaCaixaPorUsuario(\Illuminate\Support\Facades\Auth::id()) != null){
                    $orderController = new App\Http\Controllers\OrderController();
                    echo $orderController->carregaPedidosAbertos();
                }
            @endphp
        </div>
        <div class="col-xs-5 col-sm-6 col-lg-5" style="margin-top:-20px; margin-right: -60px; text-align:left;  display: inline;">
                {{--@php--}}
                    {{--if(isset($order))--}}
                        {{--if(\App\Http\Controllers\OrderController::possuiPagamento($order))--}}
                            {{--echo '<p style="margin-left: 27px; margin-top: -5px">Valor a pagar: <span style="font-size: 22px; text-shadow: 1px 1px #ffcc00; color: #ffffff; display: inline;">R$'.number_format($order->total, 2, ',', '.').' (valor pago R$'.(\App\Http\Controllers\OrderController::valorPago($order)).')</span>';--}}
                        {{--else--}}
                            {{--echo '<p style="margin-left: 27px; margin-top: -5px">Valor a pagar: <span style="font-size: 22px; text-shadow: 1px 1px #ffcc00; color: #ffffff; display: inline;">R$'.number_format($order->total, 2, ',', '.').'</span>';--}}
                    {{--else--}}
                        {{--echo '<p style="margin-left: 27px; margin-top: -5px">Valor a pagar: <span style="font-size: 22px; text-shadow: 1px 1px #ffcc00; color: #ffffff; display: inline;">R$0,00</span>';--}}
                    {{--@endphp--}}
            <p style="margin-left: 27px; margin-top: -5px">Valor a pagar: <span style="font-size: 22px; text-shadow: 1px 1px #ffcc00; color: #ffffff; display: inline;">R$@if(isset($order)){{number_format($order->total, 2, ',', '.')}} @else 0,00 @endif </span>
                @php
                    if(isset($order))
                        if(\App\Http\Controllers\OrderController::possuiPagamento($order))
                            echo '(Pago R$'. \App\Http\Controllers\OrderController::valorPago($order).')';
                @endphp
            </p>
            @php
                 if(isset($order)){
                    $itens = App\Models\Item::all()->where('order_id', '=', $order->id);
                    if($itens->count() > 0)
                        echo Bootstrapper\Facades\ButtonGroup::withContents([
                             Button::success('Concluir Venda')->addAttributes(['style' => 'margin-top:-18px; width:150px ', 'data-toggle' => 'modal', 'data-target' => '#concluirVendaModal']),
                             Button::primary('Parcial')->addAttributes(['style' => 'background-color :yellow; margin-top:-18px; width:130px', 'data-toggle' => 'modal', 'data-target' => '#vendaParcial']),
                             Button::danger('Cancelar Venda')->addAttributes(['style' => 'margin-top:-18px;  width:150px; ', 'data-toggle' => 'modal', 'data-target' => '#cancelarVendaModal']),
                        ])->withAttributes(['style' => 'margin-right: -20px; margin-left:25px']);
                    else
                        echo Bootstrapper\Facades\ButtonGroup::withContents([
                             Button::success('Concluir Venda')->addAttributes(['style' => 'margin-top:-18px; width:150px ', 'data-toggle' => 'modal', 'data-target' => '#concluirVendaModal', 'disabled' => 'true']),
                             Button::primary('Parcial')->addAttributes(['style' => 'background-color :yellow; margin-top:-18px; width:130px', 'data-toggle' => 'modal', 'data-target' => '#vendaParcial', 'disabled' => 'true']),
                             Button::danger('Cancelar Venda')->addAttributes(['style' => 'margin-top:-18px;  width:150px; ', 'data-toggle' => 'modal', 'data-target' => '#cancelarVendaModal']),
                        ])->withAttributes(['style' => 'margin-right: -20px; margin-left:25px']);

                }else{
                   echo Bootstrapper\Facades\ButtonGroup::withContents([
                         Button::success('Concluir Venda')->addAttributes(['style' => 'margin-top:-18px; width:150px ', 'data-toggle' => 'modal', 'data-target' => '#concluirVendaModal', 'disabled' => 'true']),
                         Button::primary('Parcial')->addAttributes(['style' => 'background-color :yellow; margin-top:-18px; width:130px', 'data-toggle' => 'modal', 'data-target' => '#vendaParcial', 'disabled' => 'true']),
                         Button::danger('Cancelar Venda')->addAttributes(['style' => 'margin-top:-18px;  width:150px; ', 'data-toggle' => 'modal', 'data-target' => '#cancelarVendaModal', 'disabled' => 'true']),
                    ])->withAttributes(['style' => 'margin-right: -20px; margin-left:25px']);
                }
            @endphp
        </div>
        @if(\App\Desk::all()->where('status', '=', 1)->where('user_id','=',Auth::id())->isNotEmpty())
            <div style="margin-left:-75px">Mesas:</div>
        @endif
        <div class="col-xs-7 col-sm-6 col-lg-7" style="max-height: 70px; min-width:1280px; margin-left:-90px;overflow-x: auto;white-space: nowrap;">
            @php
                if(App\Http\Controllers\CashController::buscaCaixaPorUsuario(\Illuminate\Support\Facades\Auth::id()) != null){
                    echo '<a href="'.action("DeskController@createDesk").'">'.\Bootstrapper\Facades\Button::success('+')->addAttributes(['style' => 'height:39px ;width:50px;margin-right:9px']).'</a>';
                    $deskController = new App\Http\Controllers\DeskController();
                    echo $deskController->carregaMesas();
                }
            @endphp
        </div>
    </div>

    {{--vincularVendaMesa--}}
    <div data-keyboard="false" data-backdrop="static" class="modal fade" id="vincularVendaMesa" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel" style="color: #2F3133">Nova Mesa</h4>
                </div>
                {!! Form::open(array('action' => 'DeskController@vincularMesa', 'method' => 'post')) !!}
                <div class="modal-body">
                    {!! Form::Label('venda', 'Selecione uma Venda:') !!}
                    <select style="max-height: 50px; overflow: auto" class="selectpicker" data-live-search="true" name="order_id">
                        {!! $vendas = App\Models\Order::all()->whereIn('status', [4,5])->where('type', '=', 1) !!}
                        @foreach($vendas as $venda)
                            <option value="{{$venda->id}}">{{$venda->client->nickname}}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="desk_id" />
                    {!! Form::token() !!}
                </div>
                <div class="modal-footer">
                    @if(sizeof($vendas) > 0)
                    {!! Form::submit('Vincular!', array('class' => 'btn btn-success')) !!}
                    @else
                    {!! Form::submit('Vincular!', array('class' => 'btn btn-success', 'disabled'=>'true')) !!}
                    @endif
                    {!! Form::close() !!}
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>

                    {!! Form::open(array('action' => 'DeskController@criarMesaVenda', 'method' => 'post')) !!}
                    <input type="hidden" name="desk_id" />
                    {!! Form::token() !!}
                    {!! Form::submit('Nova Venda!', array('class' => 'btn btn-primary mr-auto','style' => 'float: left;margin-top:-35px')) !!}
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>

    <div data-keyboard="false" data-backdrop="static" class="modal fade" id="excluirMesa" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel" style="color: #2F3133">Excluir Mesa</h4>
                </div>
                {!! Form::open(array('action' => 'DeskController@excluirMesa', 'method' => 'post')) !!}
                <div class="modal-body">
                    <p style="text-align: center; font-weight: bold">Deseja realmente excluir essa mesa?</p>
                    <input type="hidden" name="desk_id" />
                    {!! Form::token() !!}
                </div>
                <div class="modal-footer">
                    {!! Form::submit('Excluir!', array('class' => 'btn btn-danger')) !!}
                    {!! Form::close() !!}
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @include('modal/confirmarAssociadoModal')
    @include('modal/removerAssociadoModal')
    @include('modal/confirmarCartaoModal')
    @include('modal/removerCartaoModal')
    @include('modal/productModal')
    @include('modal/novaMesaModal')
    @include('modal/concluirVendaModal')
    @include('modal/cancelarVendaModal')
    @include('modal/vendaParcial')

    <meta name="_token" content="{!! csrf_token() !!}" />
    <script src="{{asset('js/ajax-crud.js')}}"></script>
@endsection
@section('scripts')
    <script>
        setTimeout(function() {
            document.getElementById( "codBarQtd" ).focus();
        }, 0 );

        $(document).ready(function(){
            var y = document.getElementById("tabsCategorias").style.height;
            var z = y.replace(/\D/g,'');
            var tabCount = $('#tabsCategorias > ul> li').length;
            if(tabCount > 0 && tabCount < 8) {
                z = z - 45  ;
                $(".chico").css({"height": z + "px"}) ;
            }
            if(tabCount > 7 && tabCount < 15) {
                z = z - 90  ;
                $(".chico").css({"height": z + "px"}) ;
            }
            if(tabCount > 14 && tabCount < 22) {
                z = z - 135  ;
                $(".chico").css({"height": z + "px"}) ;
            }
            if(tabCount > 21 && tabCount < 29) {
                z = z - 180  ;
                $(".chico").css({"height": z + "px"}) ;
            }
        });

        function incrementaProduto($id) {
            document.getElementById($id).stepUp(1);
        }

        function decrementaProduto($id) {
            document.getElementById($id).stepDown(1);
        }

        function incrementavenda($id) {
            document.getElementById($id).stepUp(1);
        }

        function decrementavenda($id) {
            document.getElementById($id).stepDown(1);
        }

        $('#tabsCategorias > ul> li:last').click(function (e) {
            e.preventDefault();
            window.location = $('#tabsCategorias').attr('data-url');
        });

        function total() {
            if (document.getElementById('formaPagamentoTotal').value === '4') {
                document.getElementById('obsTotal').style.display = 'block';
            } else {
                document.getElementById('obsTotal').style.display = 'none';
            }
        }

        function troco() {
            if (document.getElementById('formaPagamentoTotal').value === '1') {
                document.getElementById('troco').style.display = 'block';
            } else {
                document.getElementById('troco').style.display = 'none';
            }
        }

        function parcial() {
            if (document.getElementById('formaPagamentoParcial').value === '4') {
                document.getElementById('obsParcial').style.display = 'block';
                document.getElementById('produtosParciais').style.display = 'none';
            } else {
                document.getElementById('obsParcial').style.display = 'none';
                document.getElementById('produtosParciais').style.display = 'block';
            }
        }

        function calcular() {
            var num1 = document.getElementById("num1T").value;
            var num2 = document.getElementById("num2").value;
            var num3 = document.getElementById("num3").value;

            if (num2 !== null && num2 !== '')
            {
                num2=num2.replace(/\D/g,'');
                if(num2 > 0)
                    num2 = num2/100;
            }else
                num2 = 0;

            if (num1 !== null && num1 !== '')
            {
                num1=num1.replace(/\D/g,'');
                if(num1 > 0)
                    num1 = num1/100;
            }else
                num1 = 0;

            if (num3 !== null && num3 !== '')
            {
                num3=num3.replace(/\D/g,'');
                if(num3 > 0)
                    num3 = num3/100;
            }else
                num3 = 0;

            var sub = num2 - (num1 - num3);
            sub=sub.toFixed(2);
            sub=sub.toString();
            sub=sub.replace(/\D/g,'');
            sub=sub.replace(/(\d{1,2})$/, ',$1');
            sub=sub.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            sub = sub != ''?'R$ '+sub:'';
            sub=sub.replace(/^0+/, '');

            if(num2 < (num1 - num3))
                document.getElementById('resultadoT').value = "-"+sub;
            else
                document.getElementById('resultadoT').value = sub;
        }
        function mostraDesconto(){
            document.getElementById('valorDesconto').style.display = 'block';
        }

        $(document).ready(function(){
            $("[rel=tooltip]").tooltip({ placement: 'bottom'});
        });

        $(document).ready(function() {
            $('button.darken-2').click(function(event) {
                var sDeskId = $(this).attr('data_desk_id');
                var oModalEdit = $('#vincularVendaMesa');
                oModalEdit.find('input[name="desk_id"]').val(sDeskId);
            });

        });

        $(document).ready(function() {
            $('button.darken-2').click(function(event) {
                var sDeskId = $(this).attr('data_desk_id');
                var oModalEdit = $('#excluirMesa');
                oModalEdit.find('input[name="desk_id"]').val(sDeskId);
            });

        });
    </script>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css">
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js"></script>

@endsection