@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <h2>Editar Produto</h2>
            {!! form($form->add('edit', 'submit', [
            'attr' => ['class' => 'btn btn-primary btn-block'],
            'label' => 'Editar'
            ]))
            !!}
        </div>
    </div>
    <script>
        $('#price_discount, #price_card, #price_cost, #price_resale').keyup(function(){
            var v = $(this).val();
            v=v.replace(/\D/g,'');
            v=v.replace(/(\d{1,2})$/, ',$1');
            v=v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            v = v != ''?'R$ '+v:'';
            v=v.replace(/^0+/, '');
            $(this).val(v);
        });
        $(window).on('load', function() {
            var v = $('#price_discount').val();
            v=v.replace(/\D/g,'');
            v=v.replace(/(\d{1,2})$/, ',$1');
            v=v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            v = v != ''?'R$ '+v:'';
            v=v.replace(/^0+/, '');
            $('#price_discount').val(v);
        });
        $(window).on('load', function() {
            var v = $('#price_card').val();
            v=v.replace(/\D/g,'');
            v=v.replace(/(\d{1,2})$/, ',$1');
            v=v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            v = v != ''?'R$ '+v:'';
            v=v.replace(/^0+/, '');
            $('#price_card').val(v);
        });
        $(window).on('load', function() {
            var v = $('#price_cost').val();
            v=v.replace(/\D/g,'');
            v=v.replace(/(\d{1,2})$/, ',$1');
            v=v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            v = v != ''?'R$ '+v:'';
            v=v.replace(/^0+/, '');
            $('#price_cost').val(v);
        });
        $(window).on('load', function() {
            var v = $('#price_resale').val();
            v=v.replace(/\D/g,'');
            v=v.replace(/(\d{1,2})$/, ',$1');
            v=v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            v = v != ''?'R$ '+v:'';
            v=v.replace(/^0+/, '');
            $('#price_resale').val(v);
        });
    </script>
@endsection
