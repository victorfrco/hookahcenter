<div data-keyboard="false" data-backdrop="static" class="modal fade" id="vendaParcial" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" style="width: 800px" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Venda Parcial</h4>
            </div>
            {!! Form::open(array('action' => 'SellController@vendaParcial', 'method' => 'post', 'onsubmit' => 'return enviardadosP();')) !!}
            <div class="modal-body" style="overflow-y: auto;max-height: 450px">
                @php
                    if(isset($order))
                        if(\App\Http\Controllers\OrderController::possuiPagamento($order)){
                        echo '<input type="hidden" id="num1P" value="R$ '.number_format($order->total, 2,',', '.').'"/>';
                    echo '<br><p style="display:inline; vertical-align: middle;font-weight: bold">Informe o valor a ser pago: </p>
                    <select class="" id="formaPagamentoParcial" name="formaPagamento" style="width: 212px;" disabled="true">
                        <option value="4">Múltiplo</option>
                    </select>
                    <div id="obsParcial" style="display: block; width:500px">';
                        if(isset($order))
                            echo '<table class="table">
                            <tr>
                                <td>Valor Dinheiro: </td>
                                <td><input style="width:120px" id="dinheiroP" name="dinheiroP" type="text" max="'.$order->total.'"  ></td>
                            </tr>
                            <tr>
                                <td>Valor Débito: </td>
                                <td><input style="width:120px" id="debitoP" name="debitoP" type="text" max="'.$order->total.'"  ></td>
                            </tr>
                            <tr>
                                <td>Valor Crédito: </td>
                                <td><input style="width:120px" id="creditoP" name="creditoP" type="text" max="'.$order->total.'"  ></td>
                            </tr>
                            </table>

                    <div id="produtosParciais">';
                    if(isset($order)){
                            echo Form::hidden('order_id', $order->id);
                            echo Form::hidden('formaPagamento', 4);
                        }else
                            echo 'Não existe pedido em aberto!';
                       }
                        else{

                        echo '<input type="hidden" id="num1P" value="'.$order->total.'"/>';
                        echo '<br><p style="display:inline; vertical-align: middle;font-weight: bold">Selecione a forma de pagamento: </p>
                    <select class="" id="formaPagamentoParcial" required name="formaPagamento" style="width: 212px;" onclick="parcial()">
                        <option value="">Selecione...</option>
                        <option value="1">Dinheiro</option>
                        <option value="2">Cartão de Débito</option>
                        <option value="3">Cartão de Crédito</option>
                        <option value="4">Múltiplo</option>
                    </select>
                    <div id="obsParcial" style="display: none; width:400px">';
                        if(isset($order))
                            echo '
                            <table class="table">
                            <tr>
                                <td>Valor Dinheiro: </td>
                                <td><input style="width:120px" id="dinheiroP" name="dinheiroP" type="text" max="'.$order->total.'"  ></td>
                            </tr>
                            <tr>
                                <td>Valor Débito: </td>
                                <td><input style="width:120px" id="debitoP" name="debitoP" type="text" max="'.$order->total.'"  ></td>
                            </tr>
                            <tr>
                                <td>Valor Crédito: </td>
                                <td><input style="width:120px" id="creditoP" name="creditoP" type="text" max="'.$order->total.'"  ></td>
                            </tr>
                            </table>
                    </div>
                    <div id="produtosParciais">
                    <br><p style="display:inline; vertical-align: middle;font-weight: bold">Selecione os produtos a pagar: </p>';
                    if(isset($order)){
                            $products = App\Http\Controllers\SellController::buscaProdutosPorVenda($order);
                            echo $products;
                            echo Form::hidden('order_id', $order->id);
                        }else
                        echo 'Não existe pedido em aberto!';
                        }
                @endphp
            </div>
        </div>
        <div class="modal-footer">
            {!! Form::submit('Concluir!', array('class' => 'btn btn-success')) !!}
            {!! Form::close() !!}
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        </div>
    </div>
</div>
<script>
    $('#debitoP, #creditoP, #dinheiroP').keyup(function(){
        var v = $(this).val();
        v=v.replace(/\D/g,'');
        v=v.replace(/(\d{1,2})$/, ',$1');
        v=v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        v = v != ''?'R$ '+v:'';
        v=v.replace(/^0+/, '');
        $(this).val(v);
    });

    function enviardadosP() {
        if (document.getElementById('formaPagamentoParcial').value === '4') {
            var dinheiroP = document.getElementById('dinheiroP').value;
            if (dinheiroP !== null && dinheiroP !== '') {
                dinheiroP = dinheiroP.replace(/\D/g, '');
                if (dinheiroP > 0)
                    dinheiroP = dinheiroP / 100;
            } else
                dinheiroP = 0;

            var debitoP = document.getElementById('debitoP').value;
            if (debitoP !== null && debitoP !== '') {
                debitoP = debitoP.replace(/\D/g, '');
                if (debitoP > 0)
                    debitoP = debitoP / 100;
            } else
                debitoP = 0;

            var creditoP = document.getElementById('creditoP').value;
            if (creditoP !== null && creditoP !== '') {
                creditoP = creditoP.replace(/\D/g, '');
                if (creditoP > 0)
                    creditoP = creditoP / 100;
            } else
                creditoP = 0;

            var soma = parseFloat(dinheiroP) + parseFloat(debitoP) + parseFloat(creditoP);

            var venda = document.getElementById('num1P').value;
            venda = venda.replace(/\D/g, '');
            venda = venda / 100;

            final = parseFloat(venda).toFixed(2) - parseFloat(soma).toFixed(2);

            if (final < 0.00)
                window.alert('Valor informado é superior ao valor total da venda');

            if (final >= 0)
                return true;

            return false;
        }
        return true;
    }

</script>