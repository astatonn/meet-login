@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12" id="jitsi-room"></div>
    </div>
</div>
@csrf
<script>
    const TOKEN_URL = `${window.location.origin.toString()}${window.location.pathname.toString()}/generate-token`;
    const REDIRECT_URL = `https://jitsipool01.penso.com.br${window.location.pathname.toString()}`;
    console.log(REDIRECT_URL,TOKEN_URL);
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
    return window.location.href = REDIRECT_URL + '?jwt=' + json.jwt
  })


    @elseif(session()->get('guest.auth'))
        window.location.href = REDIRECT_URL

    @endif
    </script>
@endsection