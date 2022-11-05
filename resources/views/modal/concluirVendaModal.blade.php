<div data-keyboard="false" data-backdrop="static" class="modal fade" id="concluirVendaModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Finalizar Venda</h4>
            </div>
            {!! Form::open(array('action' => 'SellController@concluirVenda', 'method' => 'post', 'onsubmit' => 'return enviardadosT();')) !!}
            <div class="modal-body">
                {{--<br><p style="display:inline; vertical-align: middle;font-weight: bold">Informe o vendedor: </p>
                <select style="max-height: 50px; overflow: auto" class="selectpicker" data-live-search="true" name="user_id">
                    {!! $users = App\User::all() !!}
                    @foreach($users as $user)
                        <option value="{{$user->id}}">{{$user->name}}</option>
                    @endforeach
                </select>--}}
                <br>
                <table class="table ">
                    <tr>
                        <td width="250px"><p style="display:inline; vertical-align: middle;font-weight: bold">Forma de pagamento: </p></td>
                        <td width="250px">
                            <select class="select" id="formaPagamentoTotal" required name="formaPagamento" style="width: 212px; text-decoration-color: #0f0f0f" onclick='troco();total();'>
                                <option value="">Selecione...</option>
                                <option value="1">Dinheiro</option>
                                <option value="2">Cartão de Débito</option>
                                <option value="3">Cartão de Crédito</option>
                                <option value="4">Múltiplo</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <div id="troco" style="display: none;">
                    @if(isset($order))
                        <table class="table">
                            <tr> <td width="250px">Valor da venda (R$): </td> <td width="250px"><input style="width: 90px" type="text" id="num1T" value="R$ {{number_format($order->total, 2,',', '.')}}" disabled="true" /></td></tr>
                            <tr> <td width="250px">Valor Recebido (R$): </td> <td width="250px"><input style="width: 90px" type="text" id="num2" onblur="calcular()" /></td></tr>
                            <tr> <td width="250px">Troco (R$): </td> <td width="250px"><input style="width: 90px" type="text" id="resultadoT" disabled="true"/></td></tr>
                        </table>
                    @endif
                </div>
                <div id="obsTotal" style="display: none; width:500px">
                    @if(isset($order))
                        <table class="table">
                            <tr>
                                <td>Valor Dinheiro: </td>
                                <td><input style="width:120px" id="dinheiroT" name="dinheiroT" type="text" max="'.$order->total.'"></td>
                            </tr>
                            <tr>
                                <td>Valor Débito: </td>
                                <td><input style="width:120px" id="debitoT" name="debitoT" type="text" max="'.$order->total.'"></td>
                            </tr>
                            <tr>
                                <td>Valor Crédito: </td>
                                <td><input style="width:120px" id="creditoT" name="creditoT" type="text" max="'.$order->total.' "></td>
                            </tr>
                        </table>
                    @endif
                </div>
                <div id="valorDesconto" style="display: none;">
                    <table class="table">
                        <tr><td width="250px">Desconto: </td> <td width="250px"><input style="width: 90px" id="num3" name="valorDesconto" type="text" onblur="calcular()"></td></tr>
                    </table>
                </div>
                @php
                    if(isset($order)){
                        echo Form::hidden('order_id', $order->id);
                    }
                @endphp
            </div>
            <div class="modal-footer">
                <p style="display: inline; margin-right: 70px">Clique <a onclick='mostraDesconto()'>AQUI </a> para aplicar desconto!</p>
                {!! Form::submit('Concluir!', array('class' => 'btn btn-success')) !!}
                {!! Form::close() !!}
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
<script>
    $('#num1T, #num2, #num3, #debitoT, #creditoT, #dinheiroT').keyup(function(){
        var v = $(this).val();
        v=v.replace(/\D/g,'');
        v=v.replace(/(\d{1,2})$/, ',$1');
        v=v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        v = v != ''?'R$ '+v:'';
        v=v.replace(/^0+/, '');
        $(this).val(v);
    });

    function enviardadosT() {
        if (document.getElementById('formaPagamentoTotal').value === '4') {
            var dinheiroT = document.getElementById('dinheiroT').value;
            if (dinheiroT !== null && dinheiroT !== '') {
                dinheiroT = dinheiroT.replace(/\D/g, '');
                if (dinheiroT > 0)
                    dinheiroT = dinheiroT / 100;
            } else
                dinheiroT = 0;

            var debitoT = document.getElementById('debitoT').value;
            if (debitoT !== null && debitoT !== '') {
                debitoT = debitoT.replace(/\D/g, '');
                if (debitoT > 0)
                    debitoT = debitoT / 100;
            } else
                debitoT = 0;

            var creditoT = document.getElementById('creditoT').value;
            if (creditoT !== null && creditoT !== '') {
                creditoT = creditoT.replace(/\D/g, '');
                if (creditoT > 0)
                    creditoT = creditoT / 100;
            } else
                creditoT = 0;

            var soma = parseFloat(dinheiroT) + parseFloat(debitoT) + parseFloat(creditoT);

            var venda = document.getElementById('num1T').value;
            venda = venda.replace(/\D/g, '');
            venda = venda/100;

            final = parseFloat(venda).toFixed(2) - parseFloat(soma).toFixed(2);

            if (final > 0.00)
                window.alert('Valor informado é inferior ao valor total da venda');
            if (final < 0.00)
                window.alert('Valor informado é superior ao valor total da venda');

            if (final === 0)
                return true;

            return false;
        }
        return true;
    }
</script>