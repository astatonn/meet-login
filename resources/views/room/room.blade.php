@extends('layouts.app')
@section('content')
<style>
  body {
    background-color: #474747;
  }
	html, body {
  height: 100%;
}
	
	#jitsi-room{
height: 100%;
	overflow-y: hidden;
}

  </style>
      <div class="btn-group" style="position: absolute">
  <button type="button" onclick="showMenu()" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <i class="fas fa-bars"></i>
  </button>

</div>
<div class="dropdown-menu mt-5">
  @if(auth()->check())
  <a class="dropdown-item disabled" href="#"><span style="font-weight: bold;">{{auth()->user()->name}}</span></a>
  @else
  <a class="dropdown-item disabled" href="#"><span style="font-weight: bold;">Logado como convidado</span></a>
  @endif
  <a class="dropdown-item" onclick="copyLink(this)" href="#"><span style="font-weight: bold;">Copiar link da reunião</span></a>

  <div class="dropdown-divider"></div>
  @if(!auth()->check())
    <a href="{{route('login')}}" class="dropdown-item  btn-danger"><i class="fas fa-sign-out-alt"></i> Entrar</a>
  @else
  <form method="POST" action="{{route('logout')}}">
    @csrf
    <button action="submit"class="dropdown-item  btn-danger"><i class="fas fa-sign-out-alt"></i>  Sair</button>
    </form>  
  @endif
</div>
      
<div class="fill" id="jitsi-room"></div>

<script src='https://jitsipool01.penso.com.br/external_api.js'></script>
@csrf
<script>
   function showMenu()
 {
  document.querySelector('.dropdown-menu').classList.toggle('show')
 }

 function copyLink(mainObj)
 {
    let url = window.location.origin.toString() + window.location.pathname;
    let obj = document.createElement('input')
    obj.setAttribute('type','text')
    obj.value = url;
    document.body.appendChild(obj)
    obj.select();
    document.execCommand('copy')
    obj.remove();
    let child = mainObj.firstChild
    child.innerHTML = 'Link copiado com sucesso!'
    mainObj.classList.add('disabled')
    setTimeout(() => {
      child.innerHTML = 'Copiar link da reunião'
      mainObj.classList.remove('disabled')
    },1000);
 }

  const TOKEN_URL = `${window.location.origin.toString()}${window.location.pathname.toString()}/generate-token`;
  var width = window.innerWidth;
	var height = window.innerHeight;
	var jitsiWidth = width - (Math.floor(0.5 / 100 * width));
	var jitsiHeight = height - (Math.floor(0.5 / 100 * height))

  @if(auth()->check())
     fetch(TOKEN_URL, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }, body: JSON.stringify({'_token': document.getElementsByName('_token')[0].value})
  }).then((res)=>{
    return res.json();
  }).then((json) => {
    var options = {
      configOverwrite: {
        startWithAudioMuted:true
      },
      roomName: '{{$id}}',
      jwt: json.jwt,
      parentNode: document.getElementById('jitsi-room')

    }
    console.log(options, '<_------------------------');
    const api = new JitsiMeetExternalAPI('jitsipool01.penso.com.br', options);
    api.executeCommand('toggleLobby', true);
    api.executeCommand('displayName','{{auth()->user()->name}}' );
	  api.executeCommand('displayEmail','{{auth()->user()->email}}');
    
      });




    @elseif(session()->get('guest.auth'))
    var options = {
      
      configOverwrite: {
        startWithAudioMuted:false,
        inviteDomain:"custom-company.com",
       brandingRoomAlias: 'anInterestingMeeting'
      },
      roomName: '{{$id}}',
     // width: '100%',
      //height: screen.height,
      //width: '100%',
      height: jitsiHeight,  
      parentNode: document.getElementById('jitsi-room'),
      configOverwrite: {},
      interfaceConfigOverwrite: {
        filmStripOnly: false,
      },
    }
    const api = new JitsiMeetExternalAPI('jitsipool01.penso.com.br', options);

    @endif


    </script>
@endsection