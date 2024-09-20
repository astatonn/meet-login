
<!-- PHP -->
<!-- @extends('layouts.app')
  @include('layouts.old-nav') 

@section('content') -->

<!-- CSS -->
<!-- <style>
    *{
        margin:0;
        padding:0;
        box-sizing:border-box;
    }
    :root{
        --colorPenso:#000CD9;
        --colorBlack: #000000; 
    }
    #form{
    }
    body { 
        width: 100%; 
        background-image:url('{{asset('assets/images/logo_bg_penso.svg')}}'); 
        background-size: 17%;
        background-repeat: no-repeat;
        background-position-x: -2rem;
        background-position-y: -3rem;
    }  
    .containerAccess{ 
        display:flex;
        width:90%; 
        align-items:center;
        margin:0 auto; 
        border-radius:14px;
        height: 70%;
    }
    .containerAccess__logo{
        width: 50%; 
        display: flex;
        justify-content: center;
        align-items: center;
    } 
    .carousel-img{
        width: 550px;
        height: 400px;
        object-fit: contain;
    }
    .carousel-item{
        min-height:380px;
    }
    .carousel__text{
        text-align: center;
        margin:0;
    } 

    .containerAccess__form{ 
        width: 50%;
        display:flex;
        flex-direction:column;
        padding-right:8rem;
        margin:0 auto;
        height: fit-content;
        border-radius: 14px; 
        max-width:700px; 
    }
    .containerAccess__buttons{
        display:flex;  
        padding-top: 1rem;
        width: 100%;
        align-items: center;
        justify-content: left;
        gap: 1rem;
    } 
    .containerAccess__conteudo{ 
        display: flex; 
        align-items: center; 
        width:600px;
    }
    .containerAccess__buttons--ou{
        text-align:center;
        margin:0;
        padding: .2rem 0;  
    }
    .containerAccess__form--inputs{
        padding:1.5rem 0;
    }  
    .containerAccess__form p{
        font-size:1.2rem;
    }
    .containerAccess__form .penso{
        font-weight:bold;
    }
    .containerAccess__form .meet{
        color:var(--colorPenso);
    }
    /* MEDIAS CSS */
    @media screen and (max-width:1020px) {
        body{
            background:none;
        }
        .containerAccess{
            height: unset ;
        }
        .containerAccess__logo{
            display:none;
        } 
        .containerAccess__form{
            width:90%;
            padding: 1rem 0; 
        } 
        .containerAccess__form h3, .containerAccess__form p{
            text-align:center;
        } 
    } 
    @media screen and (max-width:512px) {
        .containerAccess__form{
            padding:1.5rem 0;
        }
        .containerAccess__buttons{
            display:flex; 
            flex-direction:column; 
            padding-top: 1rem;
            width: 100%; 
        } 
        .containerAccess__buttons button, .containerAccess__buttons a{
            width:100%;
        }
    }
</style> -->
 
 
<!-- HTML -->
<!-- <div class="containerAccess">  
        <div class="containerAccess__logo "> 
            <div class="containerAccess__conteudo ">
              <div id="carouselExampleIndicator" class="carousel slide" data-bs-interval="5000" data-bs-ride="carousel" data-bs-pause="false"> 
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="{{asset('assets/images/call_penso.png')}}" class='carousel-img'> 
                        <p class='carousel__text'>Interaja com os participantes utilizando o chat e o sistema de votação</p>
                    </div>
                    <div class="carousel-item">
                        <img src="{{asset('assets/images/video_penso.png')}}" class='carousel-img'>
                        <p class='carousel__text'>Compartilhe tela, transmita vídeos e áudios para todos os participantes</p>
                    </div>
                    <div class="carousel-item">
                        <img src="{{asset('assets/images/break_penso.svg')}}" class='carousel-img'>
                        <p class='carousel__text'>Crie breakout rooms durante sua reunião</p>
                    </div>
                    <div class="carousel-item">
                        <img src="{{asset('assets/images/time_penso.png')}}" class='carousel-img'>
                        <p class='carousel__text'>Reuniões sem limite de tempo</p>
                    </div>
                    <div class="carousel-item">
                        <img src="{{asset('assets/images/meet_penso.png')}}"  class='carousel-img'>
                        <p class='carousel__text'>Faça videochamadas instantâneas ou programadas do seu navegador, sem instalar aplicativos</p>
                    </div>
                    <div class="carousel-item">
                        <img src="{{asset('assets/images/safe_penso.png')}}" class='carousel-img'>
                        <p class='carousel__text'>Todas as reuniões são criptografadas, o que torna a plataforma mais segura</p>
                    </div>
                    <div class="carousel-item"> 
                        <img src="{{asset('assets/images/grave_penso.png')}}" class='carousel-img'>
                        <p class='carousel__text'>Grave suas reuniões e salve onde quiser</p>
                    </div>
                </div> 
            </div>
        </div>
    </div> 

    <div class="containerAccess__form ">   
                    <p>Conecte-se com pessoas sempre que quiser, pelo tempo que precisar, com <span class='penso'>Penso </span><span class='meet'>Meet</span>.</p>

    <form action="{{route('login')}}" method="POST" >
            @csrf
        <div class="containerAccess__form--inputs">
                <div class="col-sm-12">
                        {{renderInput('email','E-mail',['value' => old('email')],['form-control'],$errors->get('email'))}}
                </div>
                <div class="col-sm-12 mt-3"> 
                    {{renderInput('password','Senha',['type' => 'password'],['form-control'],$errors->get('password'))}}
                </div>
                <div class="containerAccess__buttons">
                    <button type="submit" class="btn  btn-primary">Entrar</button> 
                    <p class="containerAccess__buttons--ou">ou</p>
                    <a onclick="document.getElementById('guest-form').submit()" class="btn  btn-success">Continuar como visitante</a> 
                </div>  
            </div>
        </div>
    </form>
</div>   
</div>  -->

<!-- 
<form method="POST" id="guest-form" action="{{route('login')}}">
    <input type="hidden" name="isGuest" value="true" />
    @csrf
</form> -->
 

<!-- JAVACRIPT -->
<!-- <script> 
function loginAsGuest()
{

    document.getElementById('guest-form').submit();
    fetch(window.location.origin.toString()+ '/login',{
        method:'POST',
        headers:{
            'Content-Type': 'application/json',
            body: {'_token':document.getElementsByName('_token')[0].value,'is_guest':true}
        }
    })
}

</script>
@endsection -->