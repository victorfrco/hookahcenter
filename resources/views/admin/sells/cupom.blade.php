<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
</head>
<body>
    {!! $string !!}
</body>
</html>

<script>
    window.onload = function() { window.print(); window.close();}
</script>
