@extends('layouts.app')

@section('content')
    <div class="container">
        <form action="/searchOrderHistory" method="POST" role="search">
            {{ csrf_field() }}
            <div class="input-group">
                <input type="text" class="form-control" name="q"
                       placeholder="Busque a venda por id..." autofocus>
                <span class="input-group-btn">
                        <button type="submit" class="btn btn-default">
                            <span class="glyphicon glyphicon-search"></span>
                        </button>
                    </span>
            </div>
        </form>
    </div>
    <div class="container">
        <div class="row">
            @if(isset($orders))
                {!! Table::withContents($orders->items())
                 ->callback('Ações', function($campo, $model){
                    $linkDetail = route('historyDetail', ['order' => $model->id]);
                     return Button::link('Ver Detalhes &nbsp'.Icon::create('eye-open'))->asLinkTo($linkDetail).' | '
                 .Form::open(array('action' => 'SellController@imprimirCupom', 'method' => 'post', 'style' => 'display:inline', 'target'=>'_blank'))
                .Form::hidden('order_id',$model->id)
                .Form::button('Imprimir '.Icon::create('list-alt'), ['type' => 'submit', 'class' => 'btn btn-link', 'style' => '', 'rel'=>'tooltip', 'title'=>'Imprimir cupom da venda'] )
                .Form::close();
                 })->withAttributes([
                    'style' => 'font-size: 13px'
                 ]);
                 !!}
        </div>
        {!! $orders->links(); !!}
        @else
            <h4>Nenhuma venda realizada!</h4>
        @endif
    </div>
@endsection