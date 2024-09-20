@extends('layouts.app')
@include('layouts.old-nav')
@section('content')
<style>
    body{
        background-color: #f0f8ff
    }

</style>
<div class="container mt-5 pt-5">
    <div class="row">
    <div class="alert alert-info text-center shadow col-sm-12">
        <h2 class="display-5">Não foi possivel localizar sua reunião.</h2>
    </div>
</div>
    <div class="row">
        <div class="col-sm-12 text-center border rounded p-5 mb-5 mt-2 shadow bg-light">
        <p class="" style="font-size: 1.3rem">ID. <b><span id="room"></span></b> não encontrado, verifique se o ID. da reunião está correto, também é possivel que o moderador não tenha criado esta reunião.</p>
        </div>

        <div class="col-sm-6 text-center">
            <a class="btn btn-lg btn-warning" href="{{url('/')}}">Voltar</a>
        </div>
        <div class="col-sm-6 text-center">
            <a class="btn btn-lg btn-primary" style="background-color: #0000FF" href="{{url()->current()}}">Tentar novamente</a>

        </div>
    </div>
</div>
<script defer>
    let room = window.location.pathname;
    document.addEventListener('DOMContentLoaded',()=>{
        document.getElementById('room').innerHTML = room.substring(1,room.length)
    })
</script>
@endsection