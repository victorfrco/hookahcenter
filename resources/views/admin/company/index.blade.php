@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <h2>Informações da Empresa</h2>
        </div>
        <form class="form" action="{{route('dadosEmpresa')}}" method="POST">
            Nome Fantasia:
            <input type="text" name="name" value="{{$company->name}}">
            <br>
            CNPJ:
            <input maxlength="18" minlength="18" type="text" name="cnpj" id="cnpj" value="{{$company->cnpj}}">
            <br>
            Endereço:
            <input type="text" name="address" value="{{$company->address}}">
            <br>
            Telefone:
            <input maxlength="14" minlength="13" type="text" name="phone" id="phone" value="{{$company->phone}}">
            <br>
            Mensagem:
            <input type="text" name="msg" width="500" value="{{$company->msg}}">
            <br>
            <br>
            <button class=" btn btn-success" type="submit">Atualizar</button>
        </form>
        <br>
    </div>
    <script>
        $('#cnpj').keyup(function(){
            var v = $(this).val();
            v=v.replace(/\D/g,"")
            v=v.replace(/^(\d{2})(\d)/,"$1.$2")
            v=v.replace(/^(\d{2})\.(\d{3})(\d)/,"$1.$2.$3")
            v=v.replace(/\.(\d{3})(\d)/,".$1/$2")
            v=v.replace(/(\d{4})(\d)/,"$1-$2")
            $(this).val(v);
        });
        $('#phone').keyup(function(){
            var tel = $(this).val();
            tel=tel.replace(/\D/g,"")
            tel=tel.replace(/^(\d)/,"($1")
            tel=tel.replace(/(.{3})(\d)/,"$1)$2")
            if(tel.length == 9) {
                tel=tel.replace(/(.{1})$/,"-$1")
            } else if (tel.length == 10) {
                tel=tel.replace(/(.{2})$/,"-$1")
            } else if (tel.length == 11) {
                tel=tel.replace(/(.{3})$/,"-$1")
            } else if (tel.length == 12) {
                tel=tel.replace(/(.{4})$/,"-$1")
            } else if (tel.length > 12) {
                tel=tel.replace(/(.{4})$/,"-$1")
            }
            $(this).val(tel);
        });

    </script>
@endsection
