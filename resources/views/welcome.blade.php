<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>MUT Soft</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nova+Flat" rel="stylesheet">

    <!-- Styles -->
    <style>
        html, body {
            background-image: url({{asset('storage/images/fundo.jpg')}});
            background-color: #000000;
            background-position: center; /* Center the image */
            background-repeat: no-repeat; /* Do not repeat the image */
            background-size: cover; /* Resize the background image to cover the entire container */
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 200;
            height: 100vh;
            margin: 0;
            width: 100%;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .links > a {
            color: #8f979b;
            padding: 0 25px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }

        #fade div {
            margin-top: 25px;
            font-size: 21px;
            text-align: center;
            animation: fadein 2s;
            -moz-animation: fadein 2s; /* Firefox */
            -webkit-animation: fadein 2s; /* Safari and Chrome */
            -o-animation: fadein 2s; /* Opera */
        }
        @keyframes fadein {
            from {
                opacity:0;
            }
            to {
                opacity:1;
            }
        }
        @-moz-keyframes fadein { /* Firefox */
            from {
                opacity:0;
            }
            to {
                opacity:1;
            }
        }
        @-webkit-keyframes fadein { /* Safari and Chrome */
            from {
                opacity:0;
            }
            to {
                opacity:1;
            }
        }
        @-o-keyframes fadein { /* Opera */
            from {
                opacity:0;
            }
            to {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
<div class="flex-center position-ref" style=" height: 97%">
    <div  id="fade" style="background: rgba(59,63,66,0.9); width: 100%; margin-bottom: -50px;">
        @if (Route::has('login'))
            <div class="top-right links">
                @if (Auth::check())
                    <a href="{{ url('/home') }}">Página Inicial</a>
                @else
                    <a href="{{ route('login') }}">Login</a> |
                @endif
            </div>
        @endif
        <div class="content">
            <div>
                <img width="700px" src="{{asset('storage/images/mutPrincipalPreto.png')}}" style="margin-bottom: -70px; margin-left: -50px">
                <br>
                <span style="font-size:80px; text-align:center; color:black; font-family: 'Nova Flat', cursive;">Soft</span>
            </div>
        </div>
    </div>
</div>
</body>
<footer style="height: 3%; text-align: center">
    MUT Soft ©
</footer>
</html>
